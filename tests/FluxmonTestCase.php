<?php

namespace Tests;

use App\Models\Router;
use App\Models\RouterAccount;
use App\Models\User;
use App\Services\Config\ConfigExporter;
use App\Services\Mikrotik\MikrotikFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeExporter;
use Tests\Support\FakeFactory;
use Tests\Support\FakeGateway;

/**
 * Base for tests that need a logged-in admin, a router record and a fake MikroTik.
 */
abstract class FluxmonTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeGateway $mikrotik;

    protected FakeExporter $exporter;

    protected Router $router;

    protected User $admin;

    protected ?User $staff = null;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new FakeFactory();
        $this->mikrotik = $factory->gateway;
        $this->app->instance(MikrotikFactory::class, $factory);

        $this->exporter = new FakeExporter();
        $this->app->instance(ConfigExporter::class, $this->exporter);

        $this->admin = User::factory()->create();
        $this->router = Router::create([
            'name' => 'Office', 'host' => '192.168.88.1', 'port' => 8728, 'username' => 'admin',
            'password' => 'secret', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5,
        ]);
    }

    /** The superadmin: everything, no device login. */
    protected function asAdmin(): static
    {
        return $this->actingAs($this->admin);
    }

    /** A Fluxmon account with the given role, optionally limited to some devices. */
    protected function makeUser(string $role, array $routerIds = []): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'all_routers' => $routerIds === []])->save();
        $user->routers()->sync($routerIds);

        return $user;
    }

    protected function makeRouter(string $name = 'Branch'): Router
    {
        return Router::create(['name' => $name, 'host' => '10.0.0.1', 'port' => 8728, 'username' => 'a', 'password' => 'b', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]);
    }

    /** A management account of one device. */
    protected function makeDeviceAccount(Router $router, string $role = 'technician', string $username = 'tech', string $password = 'device-pass-1'): RouterAccount
    {
        return RouterAccount::create(['router_id' => $router->id, 'name' => ucfirst($username), 'username' => $username, 'password' => $password, 'role' => $role, 'active' => true]);
    }

    /** Staff sign in to Fluxmon, then to the device with a device account. */
    protected function staffOnDevice(Router $router, string $deviceRole = 'technician', array $assigned = []): static
    {
        $this->staff = $this->makeUser('staff', $assigned);
        $this->makeDeviceAccount($router, $deviceRole, 'acct-'.$deviceRole);

        $this->actingAs($this->staff)
            ->post("/r/{$router->id}/login", ['username' => 'acct-'.$deviceRole, 'password' => 'device-pass-1'])
            ->assertSessionHasNoErrors();

        return $this; // still acting as the staff member, with the device session in place
    }
}

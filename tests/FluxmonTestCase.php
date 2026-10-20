<?php

namespace Tests;

use App\Models\Router;
use App\Models\User;
use App\Services\Mikrotik\MikrotikFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakeFactory;
use Tests\Support\FakeGateway;

/**
 * Base for tests that need a logged-in admin, a router record and a fake MikroTik.
 */
abstract class FluxmonTestCase extends TestCase
{
    use RefreshDatabase;

    protected FakeGateway $mikrotik;

    protected Router $router;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = new FakeFactory();
        $this->mikrotik = $factory->gateway;
        $this->app->instance(MikrotikFactory::class, $factory);

        $this->admin = User::factory()->create();
        $this->router = Router::create([
            'name' => 'Office', 'host' => '192.168.88.1', 'port' => 8728, 'username' => 'admin',
            'password' => 'secret', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5,
        ]);
    }

    protected function asAdmin(): static
    {
        return $this->actingAs($this->admin);
    }
}

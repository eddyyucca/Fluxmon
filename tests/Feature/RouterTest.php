<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RouterTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'name' => 'Office',
        'host' => '192.168.88.1',
        'port' => 8728,
        'username' => 'admin',
        'password' => 'secret',
        'currency' => 'Rp',
        'traffic_interface' => 'ether1',
        'timeout' => 5,
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/routers')->assertRedirect('/login');
    }

    public function test_router_is_created_with_encrypted_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/routers', $this->payload)
            ->assertRedirect('/routers');

        $raw = DB::table('routers')->where('name', 'Office')->value('password');
        $this->assertNotSame('secret', $raw);
        $this->assertSame('secret', Router::first()->password);
    }

    public function test_blank_password_on_update_keeps_existing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/routers', $this->payload);
        $router = Router::first();

        $this->actingAs($user)
            ->put("/routers/{$router->id}", array_merge($this->payload, ['host' => '10.0.0.1', 'password' => '']))
            ->assertRedirect('/routers');

        $router->refresh();
        $this->assertSame('10.0.0.1', $router->host);
        $this->assertSame('secret', $router->password);
    }

    public function test_dashboard_renders(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()->assertSee('Fluxmon');
    }
}

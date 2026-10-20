<?php

namespace Tests\Feature;

use App\Models\Router;
use Illuminate\Support\Facades\DB;
use Tests\FluxmonTestCase;

class RouterTest extends FluxmonTestCase
{
    private array $payload = [
        'name' => 'Branch',
        'host' => '10.0.0.1',
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
        $this->asAdmin()->post('/routers', $this->payload)->assertRedirect();

        $raw = DB::table('routers')->where('name', 'Branch')->value('password');
        $this->assertNotSame('secret', $raw);
        $this->assertSame('secret', Router::where('name', 'Branch')->first()->password);
    }

    public function test_blank_password_on_update_keeps_existing(): void
    {
        $this->asAdmin()
            ->put("/routers/{$this->router->id}", array_merge($this->payload, ['name' => 'Office', 'host' => '10.9.9.9', 'password' => '']))
            ->assertRedirect('/routers');

        $this->router->refresh();
        $this->assertSame('10.9.9.9', $this->router->host);
        $this->assertSame('secret', $this->router->password);
    }

    public function test_connection_test_reports_success_and_failure(): void
    {
        $this->asAdmin()->postJson("/routers/{$this->router->id}/test")
            ->assertJson(['ok' => true, 'board' => 'hAP']);
        $this->assertTrue($this->router->fresh()->is_online);

        $this->mikrotik->offline = true;
        $this->asAdmin()->postJson("/routers/{$this->router->id}/test")
            ->assertJson(['ok' => false, 'message' => 'Connection refused']);
        $this->assertFalse($this->router->fresh()->is_online);
    }

    public function test_actions_are_written_to_the_audit_log(): void
    {
        $this->asAdmin()->post('/routers', $this->payload);

        $this->assertDatabaseHas('audit_logs', ['action' => 'router.created', 'details' => 'Branch']);
    }
}

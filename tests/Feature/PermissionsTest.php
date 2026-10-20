<?php

namespace Tests\Feature;

use App\Models\Router;
use App\Models\User;
use Tests\FluxmonTestCase;

class PermissionsTest extends FluxmonTestCase
{
    private function userWithRole(string $role, array $routerIds = []): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'all_routers' => $routerIds === []])->save();
        $user->routers()->sync($routerIds);

        return $user;
    }

    private function secondRouter(): Router
    {
        return Router::create(['name' => 'Branch', 'host' => '10.0.0.1', 'port' => 8728, 'username' => 'a', 'password' => 'b', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]);
    }

    public function test_viewer_can_read_but_not_change_anything(): void
    {
        $viewer = $this->userWithRole('viewer');
        $id = $this->router->id;

        foreach (['/dashboard', "/r/{$id}", "/r/{$id}/users", "/r/{$id}/profiles", '/reports', '/alerts'] as $url) {
            $this->actingAs($viewer)->get($url)->assertOk();
        }

        $this->actingAs($viewer)->post("/r/{$id}/users", ['name' => 'x', 'password' => 'y', 'profile' => 'default'])->assertForbidden();
        $this->actingAs($viewer)->post("/r/{$id}/profiles", [])->assertForbidden();
        $this->actingAs($viewer)->get("/r/{$id}/vouchers")->assertForbidden();
        $this->actingAs($viewer)->get("/r/{$id}/backups")->assertForbidden();
        $this->actingAs($viewer)->get('/settings')->assertForbidden();
        $this->actingAs($viewer)->get('/routers')->assertForbidden();
        $this->actingAs($viewer)->get('/audit')->assertForbidden();
        $this->actingAs($viewer)->post('/alerts/defaults')->assertForbidden();
    }

    public function test_cashier_sells_vouchers_but_cannot_edit_profiles_or_back_up(): void
    {
        $cashier = $this->userWithRole('cashier');
        $id = $this->router->id;

        $this->actingAs($cashier)->get("/r/{$id}/vouchers")->assertOk();
        $this->actingAs($cashier)->post("/r/{$id}/vouchers", [
            'profile' => 'default', 'quantity' => 2, 'length' => 6, 'charset' => 'mixed', 'mode' => 'same',
        ])->assertSessionHasNoErrors();
        $this->assertCount(2, $this->mikrotik->users);

        $this->actingAs($cashier)->post("/r/{$id}/profiles", [])->assertForbidden();
        $this->actingAs($cashier)->get("/r/{$id}/backups")->assertForbidden();
        $this->actingAs($cashier)->get("/r/{$id}/logs")->assertOk(); // monitor.view
    }

    public function test_technician_manages_backups_and_alerts_but_not_routers_or_accounts(): void
    {
        $tech = $this->userWithRole('technician');
        $id = $this->router->id;

        $this->actingAs($tech)->get("/r/{$id}/backups")->assertOk();
        $this->actingAs($tech)->post('/alerts/defaults')->assertSessionHas('status');
        $this->actingAs($tech)->get('/routers')->assertForbidden();
        $this->actingAs($tech)->get('/settings')->assertForbidden();
        $this->actingAs($tech)->post("/r/{$id}/backups/reset-fingerprint")->assertForbidden();
    }

    public function test_router_limited_account_only_sees_its_routers(): void
    {
        $other = $this->secondRouter();
        $limited = $this->userWithRole('technician', [$this->router->id]);

        $this->actingAs($limited)->get("/r/{$this->router->id}")->assertOk();
        $this->actingAs($limited)->get("/r/{$other->id}")->assertNotFound();
        $this->actingAs($limited)->get("/r/{$other->id}/users")->assertNotFound();
        $this->actingAs($limited)->getJson("/dashboard/{$other->id}/stats")->assertNotFound();

        $this->actingAs($limited)->get('/dashboard')->assertSee('Office')->assertDontSee('Branch');
        $this->actingAs($limited)->get("/reports?router={$other->id}")->assertNotFound();
    }

    public function test_reports_for_a_limited_account_exclude_other_routers_sales(): void
    {
        $other = $this->secondRouter();
        foreach ([$this->router, $other] as $router) {
            $batch = \App\Models\VoucherBatch::create(['router_id' => $router->id, 'profile' => 'default', 'quantity' => 1, 'price' => 1000]);
            \App\Models\Voucher::create(['router_id' => $router->id, 'voucher_batch_id' => $batch->id, 'username' => 'u'.$router->id, 'password' => 'p', 'profile' => 'default', 'price' => 1000, 'status' => 'used', 'first_used_at' => now()]);
        }

        $limited = $this->userWithRole('viewer', [$this->router->id]);
        $csv = $this->actingAs($limited)->get('/reports?export=csv')->streamedContent();

        $this->assertStringContainsString('Office', $csv);
        $this->assertStringNotContainsString('Branch', $csv);

        $this->actingAs($limited)->get('/reports?range=today')->assertSee('1.000')->assertDontSee('2.000');
    }

    public function test_limited_account_cannot_manage_global_alert_rules(): void
    {
        $limited = $this->userWithRole('technician', [$this->router->id]);

        $this->actingAs($limited)->post('/alerts/defaults')->assertForbidden();
        $this->actingAs($limited)->post('/alerts', ['name' => 'x', 'metric' => 'cpu', 'operator' => '>', 'threshold' => 90, 'duration_minutes' => 5])->assertForbidden();
        $this->actingAs($limited)->post('/alerts', ['name' => 'x', 'router_id' => $this->router->id, 'metric' => 'cpu', 'operator' => '>', 'threshold' => 90, 'duration_minutes' => 5])->assertSessionHasNoErrors();
    }

    public function test_sidebar_hides_what_the_role_cannot_open(): void
    {
        $viewer = $this->userWithRole('viewer');

        $this->actingAs($viewer)->get("/r/{$this->router->id}")
            ->assertSee('Monitoring')->assertDontSee('Backups')->assertDontSee('Vouchers')->assertDontSee('Settings');
    }

    public function test_role_and_router_assignment_are_managed_from_settings(): void
    {
        $user = $this->userWithRole('viewer');

        $this->asAdmin()->put("/settings/users/{$user->id}", ['role' => 'technician', 'router_ids' => [$this->router->id]])->assertSessionHas('status');
        $user->refresh();
        $this->assertSame('technician', $user->role);
        $this->assertFalse($user->all_routers);
        $this->assertSame([$this->router->id], $user->routers->pluck('id')->all());

        $this->asAdmin()->put("/settings/users/{$user->id}", ['role' => 'viewer'])->assertSessionHas('status');
        $this->assertTrue($user->fresh()->all_routers);

        $this->asAdmin()->put("/settings/users/{$this->admin->id}", ['role' => 'viewer'])->assertSessionHas('error');
        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_mass_assignment_cannot_set_role(): void
    {
        $user = new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret123', 'role' => 'admin']);
        $user->save();

        $this->assertSame('viewer', $user->fresh()->role);
    }

    public function test_new_accounts_added_in_settings_get_the_chosen_role(): void
    {
        $this->asAdmin()->post('/settings/users', [
            'name' => 'Tina', 'email' => 'tina@example.com', 'password' => 'a-long-password-1', 'role' => 'cashier', 'router_ids' => [$this->router->id],
        ])->assertSessionHas('status');

        $tina = User::where('email', 'tina@example.com')->first();
        $this->assertSame('cashier', $tina->role);
        $this->assertFalse($tina->all_routers);
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use Tests\FluxmonTestCase;

/**
 * Three kinds of Fluxmon account:
 *  - superadmin: everything, every device, one combined report;
 *  - administrator: every device without a second login, one device at a time;
 *  - staff: must sign in to each device with that device's own account.
 */
class PermissionsTest extends FluxmonTestCase
{
    private function sale(\App\Models\Router $router, float $price): void
    {
        $batch = VoucherBatch::create(['router_id' => $router->id, 'profile' => 'default', 'quantity' => 1, 'price' => $price]);
        Voucher::create(['router_id' => $router->id, 'voucher_batch_id' => $batch->id, 'username' => 'u'.$router->id.$price, 'password' => 'p', 'profile' => 'default', 'price' => $price, 'status' => 'used', 'first_used_at' => now()]);
    }

    // ---------------------------------------------------------------- staff and the device login

    public function test_staff_are_sent_to_the_device_login_before_opening_a_device(): void
    {
        $staff = $this->makeUser('staff');
        $id = $this->router->id;

        $this->actingAs($staff)->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
        $this->actingAs($staff)->get("/r/{$id}/users")->assertRedirect("/r/{$id}/login");
        $this->actingAs($staff)->get("/r/{$id}/vouchers")->assertRedirect("/r/{$id}/login");
        $this->actingAs($staff)->get("/r/{$id}/login")->assertOk()->assertSee('Sign in to this device');
    }

    public function test_background_json_calls_get_403_with_a_login_url_instead_of_a_redirect(): void
    {
        $staff = $this->makeUser('staff');
        $id = $this->router->id;

        $this->actingAs($staff)->getJson("/dashboard/{$id}/stats")->assertForbidden()->assertJson(['ok' => false, 'login_url' => url("/r/{$id}/login")]);
        $this->actingAs($staff)->getJson("/r/{$id}/traffic?interface=ether1")->assertForbidden();
        $this->actingAs($staff)->getJson("/r/{$id}/active/data")->assertForbidden();
    }

    public function test_staff_have_no_global_pages_until_they_sign_in_to_a_device(): void
    {
        $staff = $this->makeUser('staff');

        $this->actingAs($staff)->get('/dashboard')->assertOk();
        $this->actingAs($staff)->get('/docs')->assertRedirect();
        foreach (['/settings', '/routers', '/audit', '/reports', '/alerts'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_a_technician_device_account_unlocks_that_device_and_its_report(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');

        $this->get("/r/{$id}")->assertOk();
        $this->get("/r/{$id}/backups")->assertOk();
        $this->get("/r/{$id}/profiles")->assertOk();
        $this->get('/reports')->assertOk();
        $this->get('/alerts')->assertOk();

        // Still no Fluxmon-wide powers.
        $this->get('/settings')->assertForbidden();
        $this->get('/routers')->assertForbidden();
        $this->get("/r/{$id}/accounts")->assertForbidden();
        $this->post("/r/{$id}/backups/reset-fingerprint")->assertForbidden();
    }

    public function test_the_device_account_role_decides_what_staff_can_do_there(): void
    {
        $id = $this->router->id;

        $this->staffOnDevice($this->router, 'cashier');
        $this->get("/r/{$id}/vouchers")->assertOk();
        $this->post("/r/{$id}/vouchers", ['profile' => 'default', 'quantity' => 2, 'length' => 6, 'charset' => 'mixed', 'mode' => 'same'])->assertSessionHasNoErrors();
        $this->assertCount(2, $this->mikrotik->users);
        $this->post("/r/{$id}/profiles", [])->assertForbidden();
        $this->get("/r/{$id}/backups")->assertForbidden();
    }

    public function test_a_viewer_device_account_is_read_only(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'viewer');

        foreach (["/r/{$id}", "/r/{$id}/users", "/r/{$id}/profiles", "/r/{$id}/active", '/reports'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->post("/r/{$id}/users", ['name' => 'x', 'password' => 'y', 'profile' => 'default'])->assertForbidden();
        $this->post("/r/{$id}/users/bulk", ['action' => 'delete', 'ids' => ['*1']])->assertForbidden();
        $this->get("/r/{$id}/vouchers")->assertForbidden();
        $this->get("/r/{$id}/backups")->assertForbidden();
        $this->postJson("/r/{$id}/active/kick", ['id' => '*1'])->assertForbidden();
    }

    public function test_signing_in_to_one_device_does_not_unlock_another(): void
    {
        $other = $this->makeRouter('Branch');
        $this->staffOnDevice($this->router, 'technician');

        $this->get("/r/{$this->router->id}")->assertOk();
        $this->get("/r/{$other->id}")->assertRedirect("/r/{$other->id}/login");
        $this->getJson("/dashboard/{$other->id}/stats")->assertForbidden();
    }

    public function test_staff_limited_to_some_devices_cannot_even_see_the_others(): void
    {
        $other = $this->makeRouter('Branch');
        $this->makeDeviceAccount($other, 'technician', 'branch-tech');
        $this->staffOnDevice($this->router, 'technician', [$this->router->id]);

        $this->get("/r/{$other->id}")->assertNotFound();
        $this->get("/r/{$other->id}/login")->assertNotFound();
        $this->post("/r/{$other->id}/login", ['username' => 'branch-tech', 'password' => 'device-pass-1'])->assertNotFound();
        $this->get('/dashboard')->assertSee('Office')->assertDontSee('Branch');
    }

    public function test_reports_for_staff_only_cover_the_device_they_are_signed_in_to(): void
    {
        $other = $this->makeRouter('Branch');
        $this->sale($this->router, 1000);
        $this->sale($other, 2000);

        $this->staffOnDevice($this->router, 'technician');

        $page = $this->get('/reports?range=today')->assertOk();
        $page->assertSee('1,000')->assertDontSee('2,000')->assertDontSee('All routers');

        $this->get("/reports?router={$other->id}")->assertNotFound();
        $csv = $this->get('/reports?export=csv')->streamedContent();
        $this->assertStringContainsString('Office', $csv);
        $this->assertStringNotContainsString('Branch', $csv);
    }

    public function test_dashboard_shows_a_lock_for_unsigned_devices_and_hides_company_totals_from_staff(): void
    {
        $this->sale($this->router, 5000);
        $staff = $this->makeUser('staff');

        $page = $this->actingAs($staff)->get('/dashboard')->assertOk();
        $page->assertSee('Sign in to this device')->assertDontSee('Revenue today')->assertDontSee('/dashboard/'.$this->router->id.'/stats');
    }

    public function test_device_session_expires_after_inactivity_but_polling_does_not_keep_it_alive(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');
        $this->get("/r/{$id}")->assertOk();

        // Seven hours of only background polling...
        $this->travel(7)->hours();
        $this->getJson("/r/{$id}/traffic?interface=ether1")->assertOk();

        // ...so two more hours pass the 8 hour idle limit counted from the last real click.
        $this->travel(2)->hours();
        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
    }

    public function test_real_activity_extends_the_device_session(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');

        foreach ([5, 5, 5] as $hours) {
            $this->travel($hours)->hours();
            $this->get("/r/{$id}/users")->assertOk(); // each click resets the idle clock
        }
    }

    public function test_disabling_a_device_account_signs_the_person_out_at_once(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');
        $this->get("/r/{$id}")->assertOk();

        \App\Models\RouterAccount::query()->update(['active' => false]);

        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
    }

    public function test_changing_a_device_account_role_applies_immediately(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');
        $this->get("/r/{$id}/backups")->assertOk();

        \App\Models\RouterAccount::query()->update(['role' => 'viewer']);

        $this->get("/r/{$id}/backups")->assertForbidden();
    }

    public function test_sidebar_for_staff_only_lists_what_the_device_role_allows(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'viewer');

        $this->get("/r/{$id}")->assertSee('Monitoring')->assertDontSee('Backups')->assertDontSee('Vouchers')->assertDontSee('Settings');
    }

    // ---------------------------------------------------------------- administrator

    public function test_administrator_works_on_every_device_without_a_second_login(): void
    {
        $other = $this->makeRouter('Branch');
        $admin = $this->makeUser('administrator');

        foreach ([$this->router, $other] as $r) {
            foreach (['', '/users', '/profiles', '/vouchers', '/backups', '/monitoring', '/accounts'] as $path) {
                $this->actingAs($admin)->get("/r/{$r->id}{$path}")->assertOk();
            }
        }
        $this->actingAs($admin)->get("/r/{$this->router->id}/login")->assertRedirect("/r/{$this->router->id}");
        $this->actingAs($admin)->get('/audit')->assertOk();
    }

    public function test_administrator_cannot_touch_fluxmon_wide_settings_or_add_routers(): void
    {
        $admin = $this->makeUser('administrator');

        foreach (['/settings', '/routers', '/routers/create'] as $url) {
            $this->actingAs($admin)->get($url)->assertForbidden();
        }
        $this->actingAs($admin)->post('/settings/users', ['name' => 'x', 'email' => 'x@example.com', 'password' => 'long-password-1', 'role' => 'superadmin'])->assertForbidden();
    }

    public function test_only_superadmin_gets_the_combined_report_of_all_devices(): void
    {
        $other = $this->makeRouter('Branch');
        $this->sale($this->router, 1000);
        $this->sale($other, 2000);

        // Superadmin: everything in one.
        $this->asAdmin()->get('/reports?range=today')->assertOk()->assertSee('All routers')->assertSee('3,000');

        // Administrator: one device at a time.
        $admin = $this->makeUser('administrator');
        $page = $this->actingAs($admin)->get('/reports?range=today')->assertOk();
        $page->assertDontSee('All routers')->assertDontSee('3,000');
        $this->actingAs($admin)->get("/reports?router={$other->id}&range=today")->assertSee('2,000')->assertDontSee('1,000');
    }

    public function test_dashboard_company_totals_are_for_the_superadmin_only(): void
    {
        $this->sale($this->router, 5000);

        $this->asAdmin()->get('/dashboard')->assertSee('Revenue today');
        $this->actingAs($this->makeUser('administrator'))->get('/dashboard')->assertOk()->assertDontSee('Revenue today');
    }

    // ---------------------------------------------------------------- superadmin and account management

    public function test_superadmin_never_needs_a_device_login(): void
    {
        $id = $this->router->id;

        foreach (['', '/users', '/vouchers', '/backups', '/accounts'] as $path) {
            $this->asAdmin()->get("/r/{$id}{$path}")->assertOk();
        }
        $this->asAdmin()->get("/r/{$id}/login")->assertRedirect("/r/{$id}");
        $this->asAdmin()->getJson("/dashboard/{$id}/stats")->assertOk()->assertJson(['ok' => true]);
    }

    public function test_roles_and_device_access_are_managed_from_settings(): void
    {
        $user = $this->makeUser('staff');

        $this->asAdmin()->put("/settings/users/{$user->id}", ['role' => 'staff', 'router_ids' => [$this->router->id]])->assertSessionHas('status');
        $user->refresh();
        $this->assertFalse($user->all_routers);
        $this->assertSame([$this->router->id], $user->routers->pluck('id')->all());

        $this->asAdmin()->put("/settings/users/{$user->id}", ['role' => 'administrator', 'router_ids' => [$this->router->id]])->assertSessionHas('status');
        $user->refresh();
        $this->assertSame('administrator', $user->role);
        $this->assertTrue($user->all_routers, 'administrators always see every device');
        $this->assertSame([], $user->routers->pluck('id')->all());

        $this->asAdmin()->put("/settings/users/{$this->admin->id}", ['role' => 'staff'])->assertSessionHas('error');
        $this->assertSame('superadmin', $this->admin->fresh()->role);
    }

    public function test_only_the_three_roles_exist(): void
    {
        $this->asAdmin()->post('/settings/users', ['name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'a-long-password-1', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->asAdmin()->post('/settings/users', ['name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'a-long-password-1', 'role' => 'technician'])->assertSessionHasErrors('role');

        foreach (['superadmin', 'administrator', 'staff'] as $role) {
            $this->asAdmin()->post('/settings/users', ['name' => 'N'.$role, 'email' => $role.'@example.com', 'password' => 'a-long-password-1', 'role' => $role])->assertSessionHasNoErrors();
            $this->assertSame($role, User::where('email', $role.'@example.com')->value('role'));
        }
    }

    public function test_mass_assignment_cannot_set_role(): void
    {
        $user = new User(['name' => 'x', 'email' => 'x@example.com', 'password' => 'secret123', 'role' => 'superadmin']);
        $user->save();

        $this->assertSame('viewer', $user->fresh()->role);
    }

    public function test_alert_rules_are_limited_to_the_devices_staff_may_manage(): void
    {
        $other = $this->makeRouter('Branch');
        $this->staffOnDevice($this->router, 'technician');

        $rule = ['name' => 'x', 'metric' => 'cpu', 'operator' => '>', 'threshold' => 90, 'duration_minutes' => 5];
        $this->post('/alerts', $rule)->assertForbidden(); // a company-wide rule
        $this->post('/alerts', $rule + ['router_id' => $other->id])->assertForbidden();
        $this->post('/alerts', $rule + ['router_id' => $this->router->id])->assertSessionHasNoErrors();
        $this->post('/alerts/defaults')->assertForbidden();
    }
}

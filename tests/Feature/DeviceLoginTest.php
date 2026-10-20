<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\RouterAccount;
use Illuminate\Support\Facades\Hash;
use Tests\FluxmonTestCase;

class DeviceLoginTest extends FluxmonTestCase
{
    public function test_signing_in_to_a_device_sends_staff_where_they_were_going(): void
    {
        $id = $this->router->id;
        $staff = $this->makeUser('staff');
        $account = $this->makeDeviceAccount($this->router, 'technician', 'tech');

        $this->actingAs($staff)->get("/r/{$id}/backups")->assertRedirect("/r/{$id}/login");
        $this->post("/r/{$id}/login", ['username' => 'tech', 'password' => 'device-pass-1'])->assertRedirect("/r/{$id}/backups");

        $this->get("/r/{$id}/backups")->assertOk();
        $this->assertNotNull($account->fresh()->last_login_at);
    }

    public function test_successful_and_failed_device_logins_are_audited_with_the_account(): void
    {
        $id = $this->router->id;
        $this->actingAs($this->makeUser('staff'));
        $this->makeDeviceAccount($this->router, 'cashier', 'kasir');

        $this->post("/r/{$id}/login", ['username' => 'kasir', 'password' => 'wrong-password'])->assertSessionHasErrors('username');
        $this->post("/r/{$id}/login", ['username' => 'kasir', 'password' => 'device-pass-1'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => 'device.login_failed', 'details' => 'kasir', 'router_id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'device.login', 'router_id' => $id]);
    }

    public function test_wrong_password_unknown_user_disabled_account_and_other_devices_account_are_all_rejected(): void
    {
        $other = $this->makeRouter('Branch');
        $this->makeDeviceAccount($other, 'technician', 'branch-only');
        $this->makeDeviceAccount($this->router, 'technician', 'disabled-one')->update(['active' => false]);
        $this->makeDeviceAccount($this->router, 'technician', 'tech');
        $id = $this->router->id;
        $this->actingAs($this->makeUser('staff'));

        foreach ([['tech', 'nope-nope'], ['nobody', 'device-pass-1'], ['disabled-one', 'device-pass-1'], ['branch-only', 'device-pass-1']] as [$u, $p]) {
            $this->post("/r/{$id}/login", ['username' => $u, 'password' => $p])
                ->assertSessionHasErrors(['username' => 'These device credentials do not match.']);
        }
        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
    }

    public function test_repeated_failures_lock_that_device_login_for_that_person_only(): void
    {
        $other = $this->makeRouter('Branch');
        $this->makeDeviceAccount($this->router, 'technician', 'tech');
        $this->makeDeviceAccount($other, 'technician', 'tech');
        $id = $this->router->id;
        $this->actingAs($this->makeUser('staff'));

        foreach (range(1, 5) as $i) {
            $this->post("/r/{$id}/login", ['username' => 'tech', 'password' => 'wrong'.$i])->assertSessionHasErrors('username');
        }

        // Even the right password is refused while locked...
        $this->post("/r/{$id}/login", ['username' => 'tech', 'password' => 'device-pass-1'])->assertSessionHasErrors('username');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('username'));
        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");

        // ...but another device is not affected.
        $this->post("/r/{$other->id}/login", ['username' => 'tech', 'password' => 'device-pass-1'])->assertSessionHasNoErrors();
    }

    public function test_device_passwords_are_stored_hashed(): void
    {
        $account = $this->makeDeviceAccount($this->router, 'technician', 'tech', 'device-pass-1');

        $this->assertNotSame('device-pass-1', $account->password);
        $this->assertTrue(Hash::check('device-pass-1', $account->password));
        $this->assertArrayNotHasKey('password', $account->toArray());
    }

    public function test_device_logout_locks_the_device_again(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');
        $this->get("/r/{$id}")->assertOk();

        $this->post("/r/{$id}/logout")->assertRedirect('/dashboard');

        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
    }

    public function test_staff_actions_are_audited_with_the_device_account_that_did_them(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'cashier');

        $this->post("/r/{$id}/users", ['name' => 'carol', 'password' => 'x', 'profile' => 'default'])->assertSessionHas('status');

        $log = AuditLog::where('action', 'user.created')->first();
        $this->assertStringContainsString('carol', $log->details);
        $this->assertStringContainsString('device account: Acct-cashier', $log->details);
        $this->assertSame($this->staff->id, $log->user_id);
    }

    public function test_the_signed_in_device_account_is_shown_with_a_way_to_sign_out(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');

        $this->get("/r/{$id}")->assertSee('Acct-technician')->assertSee("/r/{$id}/logout");
    }

    // ---------------------------------------------------------------- managing device accounts

    public function test_superadmin_and_administrator_create_update_and_remove_device_accounts(): void
    {
        $id = $this->router->id;
        $payload = ['name' => 'Rina', 'username' => 'rina', 'password' => 'long-password-1', 'role' => 'cashier'];

        $this->asAdmin()->post("/r/{$id}/accounts", $payload)->assertSessionHas('status');
        $account = RouterAccount::where('username', 'rina')->first();
        $this->assertSame('cashier', $account->role);
        $this->assertTrue(Hash::check('long-password-1', $account->password));

        // An administrator can do the same.
        $admin = $this->makeUser('administrator');
        $this->actingAs($admin)->put("/r/{$id}/accounts/{$account->id}", ['name' => 'Rina K', 'role' => 'viewer', 'active' => 1, 'password' => ''])->assertSessionHas('status');
        $account->refresh();
        $this->assertSame('viewer', $account->role);
        $this->assertTrue(Hash::check('long-password-1', $account->password), 'blank password keeps the old one');

        $this->actingAs($admin)->put("/r/{$id}/accounts/{$account->id}", ['name' => 'Rina K', 'role' => 'viewer', 'password' => 'another-password-2'])->assertSessionHas('status');
        $account->refresh();
        $this->assertTrue(Hash::check('another-password-2', $account->password));
        $this->assertFalse($account->active, 'an unticked box means disabled');

        $this->actingAs($admin)->delete("/r/{$id}/accounts/{$account->id}")->assertSessionHas('status');
        $this->assertDatabaseMissing('router_accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'device_account.deleted']);
    }

    public function test_device_account_input_is_validated(): void
    {
        $id = $this->router->id;
        $other = $this->makeRouter('Branch');
        $this->makeDeviceAccount($this->router, 'technician', 'tech');

        $ok = ['name' => 'A', 'username' => 'fresh', 'password' => 'long-password-1', 'role' => 'viewer'];
        $this->asAdmin()->post("/r/{$id}/accounts", array_merge($ok, ['password' => 'short']))->assertSessionHasErrors('password');
        $this->asAdmin()->post("/r/{$id}/accounts", array_merge($ok, ['username' => 'tech']))->assertSessionHasErrors('username');
        $this->asAdmin()->post("/r/{$id}/accounts", array_merge($ok, ['username' => 'has space']))->assertSessionHasErrors('username');
        $this->asAdmin()->post("/r/{$id}/accounts", array_merge($ok, ['role' => 'superadmin']))->assertSessionHasErrors('role');

        // The same username is fine on a different device.
        $this->asAdmin()->post("/r/{$other->id}/accounts", array_merge($ok, ['username' => 'tech']))->assertSessionHasNoErrors();
    }

    public function test_an_account_of_another_device_cannot_be_changed_through_this_one(): void
    {
        $other = $this->makeRouter('Branch');
        $account = $this->makeDeviceAccount($other, 'technician', 'tech');

        $this->asAdmin()->put("/r/{$this->router->id}/accounts/{$account->id}", ['name' => 'x', 'role' => 'viewer'])->assertNotFound();
        $this->asAdmin()->delete("/r/{$this->router->id}/accounts/{$account->id}")->assertNotFound();
        $this->assertDatabaseHas('router_accounts', ['id' => $account->id, 'role' => 'technician']);
    }

    public function test_staff_cannot_manage_device_accounts_even_signed_in_as_a_technician(): void
    {
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');

        $this->get("/r/{$id}/accounts")->assertForbidden();
        $this->post("/r/{$id}/accounts", ['name' => 'x', 'username' => 'x', 'password' => 'long-password-1', 'role' => 'viewer'])->assertForbidden();
        $this->assertSame(1, RouterAccount::count());
    }

    public function test_deleting_a_router_deletes_its_device_accounts(): void
    {
        $this->makeDeviceAccount($this->router);

        $this->asAdmin()->delete("/routers/{$this->router->id}");

        $this->assertSame(0, RouterAccount::count());
    }

    public function test_the_accounts_page_lists_accounts_and_the_menu_links_to_it(): void
    {
        $id = $this->router->id;
        $this->makeDeviceAccount($this->router, 'cashier', 'kasir');

        $this->asAdmin()->get("/r/{$id}/accounts")->assertOk()->assertSee('kasir')->assertSee('Cashier')->assertSee('Never signed in');
        $this->asAdmin()->get("/r/{$id}")->assertSee("/r/{$id}/accounts");
    }

    public function test_session_length_is_configurable(): void
    {
        config(['fluxmon.device_session_minutes' => 30]);
        $id = $this->router->id;
        $this->staffOnDevice($this->router, 'technician');

        $this->travel(20)->minutes();
        $this->get("/r/{$id}")->assertOk();

        $this->travel(31)->minutes();
        $this->get("/r/{$id}")->assertRedirect("/r/{$id}/login");
    }
}

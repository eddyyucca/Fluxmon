<?php

namespace Tests\Feature;

use App\Models\ProfileMeta;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\FluxmonTestCase;

class PagesTest extends FluxmonTestCase
{
    public function test_every_page_renders(): void
    {
        $id = $this->router->id;
        $this->mikrotik->addHotspotUser(['name' => 'alice', 'password' => 'pw', 'profile' => 'default']);

        foreach (['/dashboard', '/routers', '/routers/create', "/routers/{$id}/edit", "/r/{$id}", "/r/{$id}/users", "/r/{$id}/profiles",
            "/r/{$id}/vouchers", "/r/{$id}/active", "/r/{$id}/monitoring", "/r/{$id}/logs", "/r/{$id}/checks", "/r/{$id}/backups",
            '/alerts', '/reports', '/settings', '/audit', '/profile'] as $url) {
            $this->asAdmin()->get($url)->assertOk();
        }
    }

    public function test_json_endpoints(): void
    {
        $id = $this->router->id;

        $this->asAdmin()->getJson("/dashboard/{$id}/stats")->assertJson(['ok' => true, 'cpu' => 12, 'hotspot_users' => 0]);
        $this->asAdmin()->getJson("/r/{$id}/interfaces")->assertJson(['ok' => true, 'interfaces' => ['ether1', 'wlan1']]);
        $this->asAdmin()->getJson("/r/{$id}/traffic?interface=ether1")->assertJson(['ok' => true, 'rx' => 2000000]);
        $this->asAdmin()->getJson("/r/{$id}/active/data")->assertJson(['ok' => true, 'sessions' => []]);

        $this->mikrotik->offline = true;
        $this->asAdmin()->getJson("/dashboard/{$id}/stats")->assertJson(['ok' => false]);
    }

    public function test_pages_degrade_gracefully_when_router_is_offline(): void
    {
        $this->mikrotik->offline = true;
        $id = $this->router->id;

        foreach (["/r/{$id}/users", "/r/{$id}/profiles", "/r/{$id}/vouchers"] as $url) {
            $this->asAdmin()->get($url)->assertOk()->assertSee('Connection refused');
        }
    }

    public function test_users_page_filters_and_bulk_actions(): void
    {
        $id = $this->router->id;
        $this->mikrotik->addHotspotUser(['name' => 'alice', 'password' => 'pw', 'profile' => 'default']);
        $this->mikrotik->addHotspotUser(['name' => 'bob', 'password' => 'pw', 'profile' => 'default']);

        $this->asAdmin()->get("/r/{$id}/users?q=ali")->assertSee('alice')->assertDontSee('bob');

        $aliceId = array_key_first($this->mikrotik->users);
        $this->asAdmin()->post("/r/{$id}/users/bulk", ['action' => 'disable', 'ids' => [$aliceId]])->assertSessionHas('status');
        $this->assertTrue($this->mikrotik->userByName('alice')['disabled']);

        $this->asAdmin()->post("/r/{$id}/users/bulk", ['action' => 'delete', 'ids' => [$aliceId]]);
        $this->assertNull($this->mikrotik->userByName('alice'));
    }

    public function test_user_can_be_created_with_limits(): void
    {
        $this->asAdmin()->post("/r/{$this->router->id}/users", [
            'name' => 'carol', 'password' => 'x', 'profile' => 'default', 'time_limit' => 1, 'time_limit_unit' => 'd', 'data_limit_mb' => 50,
        ])->assertSessionHas('status');

        $u = $this->mikrotik->userByName('carol');
        $this->assertSame('1d', $u['limit_uptime']);
        $this->assertSame(50 * 1048576, $u['limit_bytes']);
    }

    public function test_profile_crud_keeps_price_and_validity_in_the_database(): void
    {
        $id = $this->router->id;
        $this->asAdmin()->post("/r/{$id}/profiles", [
            'name' => 'gold', 'rate_limit' => '2M/5M', 'shared_users' => 2, 'price' => 7500,
            'validity_value' => 1, 'validity_unit' => 'd', 'expire_action' => 'disable',
        ])->assertSessionHas('status');

        $meta = ProfileMeta::where('name', 'gold')->first();
        $this->assertSame(1440, $meta->validity_minutes);
        $this->assertSame(7500.0, $meta->price);

        $profileId = collect($this->mikrotik->profiles())->firstWhere('name', 'gold')['id'];
        $this->asAdmin()->put("/r/{$id}/profiles/{$profileId}", [
            'name' => 'platinum', 'old_name' => 'gold', 'rate_limit' => '5M/10M', 'shared_users' => 1, 'price' => 9000,
            'validity_value' => 2, 'validity_unit' => 'h', 'expire_action' => 'remove',
        ])->assertSessionHas('status');

        $this->assertDatabaseMissing('profile_metas', ['name' => 'gold']);
        $this->assertSame(120, ProfileMeta::where('name', 'platinum')->value('validity_minutes'));

        $this->asAdmin()->delete("/r/{$id}/profiles/{$profileId}", ['name' => 'platinum']);
        $this->assertDatabaseMissing('profile_metas', ['name' => 'platinum']);
    }

    public function test_kicking_an_active_session(): void
    {
        $this->mikrotik->addHotspotUser(['name' => 'dave', 'password' => 'x']);
        $this->mikrotik->simulateUse('dave');
        $sessionId = array_key_first($this->mikrotik->active);

        $this->asAdmin()->postJson("/r/{$this->router->id}/active/kick", ['id' => $sessionId, 'user' => 'dave'])->assertJson(['ok' => true]);

        $this->assertSame([], $this->mikrotik->active);
    }

    public function test_reports_validate_and_export(): void
    {
        $id = $this->router->id;
        $this->asAdmin()->get('/reports?range=custom&from=2026-02-10&to=2026-02-01')->assertSessionHasErrors('to');
        $this->asAdmin()->get("/reports?range=30d&router={$id}")->assertOk();

        $csv = $this->asAdmin()->get('/reports?export=csv');
        $this->assertStringContainsString('sold_at,router,username,profile,price', $csv->streamedContent());
    }

    public function test_registration_closes_after_the_first_admin(): void
    {
        auth()->logout();
        $this->assertGreaterThan(0, User::count());

        $this->get('/register')->assertRedirect('/login');
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])
            ->assertRedirect('/login');
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }

    public function test_fresh_install_sends_login_visitors_to_register(): void
    {
        User::query()->delete();

        $this->get('/login')->assertRedirect('/register');
        $this->get('/register')->assertOk()->assertSee('Create the admin account');
    }

    public function test_admins_can_be_added_but_not_self_deleted(): void
    {
        $this->asAdmin()->post('/settings/users', ['name' => 'Eve', 'email' => 'eve@example.com', 'password' => 'a-long-password-1', 'role' => 'viewer'])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['email' => 'eve@example.com']);

        $this->asAdmin()->delete("/settings/users/{$this->admin->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_telegram_settings_store_token_encrypted_and_send(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        $this->asAdmin()->put('/settings/telegram', ['token' => '123:ABC', 'chat_id' => '999', 'daily_report' => 1])->assertSessionHas('status');

        $this->assertNotSame('123:ABC', Setting::read('telegram_token'));
        $this->assertSame('123:ABC', Setting::readSecret('telegram_token'));

        $this->asAdmin()->post('/settings/telegram/test')->assertSessionHas('status');
        Http::assertSent(fn ($r) => str_contains($r->url(), 'bot123:ABC/sendMessage') && $r['chat_id'] === '999');
    }

    public function test_daily_report_command_sends_summary(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Setting::writeSecret('telegram_token', 't');
        Setting::write('telegram_chat_id', '1');
        Setting::write('telegram_daily_report', '1');

        $this->artisan('fluxmon:daily-report')->assertSuccessful();

        Http::assertSent(fn ($r) => str_contains($r['text'], 'Fluxmon daily report'));
    }

    public function test_language_switch_translates_the_interface(): void
    {
        $this->asAdmin()->put('/settings/locale', ['locale' => 'id'])->assertSessionHas('status');

        $this->asAdmin()->get('/dashboard')->assertSee('Pendapatan hari ini');
    }
}

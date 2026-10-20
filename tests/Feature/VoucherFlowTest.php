<?php

namespace Tests\Feature;

use App\Models\ProfileMeta;
use App\Models\Voucher;
use App\Services\ReportService;
use App\Services\SyncService;
use Tests\FluxmonTestCase;

class VoucherFlowTest extends FluxmonTestCase
{
    private function generate(array $overrides = []): void
    {
        $this->asAdmin()->post("/r/{$this->router->id}/vouchers", array_merge([
            'profile' => 'default', 'quantity' => 5, 'length' => 6, 'charset' => 'mixed', 'mode' => 'pair',
        ], $overrides))->assertSessionHasNoErrors();
    }

    private function profileWithPrice(float $price = 5000, ?int $validityMinutes = 60): void
    {
        ProfileMeta::create(['router_id' => $this->router->id, 'name' => 'default', 'price' => $price, 'validity_minutes' => $validityMinutes, 'expire_action' => 'remove']);
    }

    public function test_generation_creates_unique_vouchers_on_router_and_in_db(): void
    {
        $this->profileWithPrice();
        $this->generate(['quantity' => 20, 'prefix' => 'WF-', 'time_limit' => 2, 'time_limit_unit' => 'h', 'data_limit_mb' => 100]);

        $this->assertCount(20, $this->mikrotik->users);
        $this->assertSame(20, Voucher::count());
        $this->assertSame(20, Voucher::distinct('username')->count());

        $user = array_values($this->mikrotik->users)[0];
        $this->assertStringStartsWith('WF-', $user['name']);
        $this->assertNotSame($user['name'], $user['password']);
        $this->assertSame('2h', $user['limit_uptime']);
        $this->assertSame(100 * 1048576, $user['limit_bytes']);
        $this->assertSame(5000.0, Voucher::first()->price);
    }

    public function test_code_only_mode_uses_the_code_as_password(): void
    {
        $this->generate(['mode' => 'same']);

        foreach ($this->mikrotik->users as $user) {
            $this->assertSame($user['name'], $user['password']);
        }
    }

    public function test_first_login_marks_voucher_used_and_counts_as_sale(): void
    {
        $this->profileWithPrice(5000, 60);
        $this->generate();
        $code = Voucher::first()->username;

        $this->mikrotik->simulateUse($code);
        $result = app(SyncService::class)->syncRouter($this->router);

        $this->assertSame(1, $result['used']);
        $voucher = Voucher::where('username', $code)->first();
        $this->assertSame('used', $voucher->status);
        $this->assertNotNull($voucher->first_used_at);
        $this->assertEqualsWithDelta(now()->addMinutes(60)->timestamp, $voucher->expires_at->timestamp, 5);

        $summary = app(ReportService::class)->summary(now()->startOfDay(), now()->endOfDay());
        $this->assertSame(5000.0, $summary['revenue']);
        $this->assertSame(1, $summary['count']);
    }

    public function test_unused_vouchers_are_not_revenue(): void
    {
        $this->profileWithPrice();
        $this->generate();
        app(SyncService::class)->syncRouter($this->router);

        $this->assertSame(0.0, app(ReportService::class)->summary(now()->startOfDay(), now()->endOfDay())['revenue']);
    }

    public function test_expired_voucher_is_kicked_and_removed_but_revenue_stays(): void
    {
        $this->profileWithPrice(5000, 60);
        $this->generate(['quantity' => 1]);
        $code = Voucher::first()->username;
        $this->mikrotik->simulateUse($code);
        $sync = app(SyncService::class);
        $sync->syncRouter($this->router);

        $this->travel(61)->minutes();
        $result = $sync->syncRouter($this->router);

        $this->assertSame(1, $result['expired']);
        $this->assertNull($this->mikrotik->userByName($code));
        $this->assertSame([], $this->mikrotik->active);
        $this->assertSame('expired', Voucher::first()->status);

        $this->travelBack();
        $this->assertSame(5000.0, app(ReportService::class)->summary(now()->startOfDay(), now()->endOfDay())['revenue']);
    }

    public function test_disable_action_keeps_the_user_but_disables_it(): void
    {
        $this->profileWithPrice(5000, 30);
        ProfileMeta::first()->update(['expire_action' => 'disable']);
        $this->generate(['quantity' => 1]);
        $code = Voucher::first()->username;
        $this->mikrotik->simulateUse($code);
        $sync = app(SyncService::class);
        $sync->syncRouter($this->router);

        $this->travel(31)->minutes();
        $sync->syncRouter($this->router);

        $this->assertTrue($this->mikrotik->userByName($code)['disabled']);
    }

    public function test_voucher_deleted_on_router_is_reconciled(): void
    {
        $this->generate(['quantity' => 2]);
        $unused = Voucher::first()->username;
        $this->mikrotik->removeHotspotUser(array_key_first($this->mikrotik->users));

        app(SyncService::class)->syncRouter($this->router);

        $this->assertDatabaseMissing('vouchers', ['username' => $unused]);
        $this->assertSame(1, Voucher::count());
    }

    public function test_offline_router_is_flagged_and_reports_change(): void
    {
        $sync = app(SyncService::class);
        $sync->syncRouter($this->router);
        $this->assertTrue($this->router->fresh()->is_online);

        $this->mikrotik->offline = true;
        $result = $sync->syncRouter($this->router->fresh());

        $this->assertFalse($result['ok']);
        $this->assertTrue($result['changed']);
        $this->assertFalse($this->router->fresh()->is_online);
    }

    public function test_batch_delete_removes_only_unused_vouchers(): void
    {
        $this->generate(['quantity' => 3]);
        $used = Voucher::first();
        $this->mikrotik->simulateUse($used->username);
        app(SyncService::class)->syncRouter($this->router);

        $batch = $used->batch;
        $this->asAdmin()->delete("/r/{$this->router->id}/vouchers/{$batch->id}")->assertSessionHas('status');

        $this->assertSame(1, Voucher::count());
        $this->assertCount(1, $this->mikrotik->users);
    }

    public function test_csv_and_print_views_work(): void
    {
        $this->generate(['quantity' => 3]);
        $batch = Voucher::first()->batch;

        $csv = $this->asAdmin()->get("/r/{$this->router->id}/vouchers/{$batch->id}/csv");
        $csv->assertOk();
        $this->assertStringContainsString('username,password,profile,price,status', $csv->streamedContent());

        $this->asAdmin()->get("/r/{$this->router->id}/vouchers/{$batch->id}/print")->assertOk()->assertSee('data-qr', false);
    }

    public function test_cannot_open_another_routers_batch(): void
    {
        $this->generate(['quantity' => 1]);
        $batch = Voucher::first()->batch;
        $other = \App\Models\Router::create(['name' => 'Other', 'host' => '1.1.1.1', 'port' => 8728, 'username' => 'a', 'password' => 'b', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]);

        $this->asAdmin()->get("/r/{$other->id}/vouchers/{$batch->id}")->assertNotFound();
    }
}

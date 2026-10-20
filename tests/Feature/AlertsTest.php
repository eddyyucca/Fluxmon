<?php

namespace Tests\Feature;

use App\Mail\AlertMail;
use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\Check;
use App\Models\Metric;
use App\Models\Setting;
use App\Services\Alerting\AlertService;
use App\Services\Monitoring\CheckRunner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\FluxmonTestCase;

class AlertsTest extends FluxmonTestCase
{
    private const NOW = 1_800_000_000;

    private function samples(string $metric, array $values): void
    {
        // One sample per minute, the last one at NOW.
        $start = self::NOW - (count($values) - 1) * 60;
        foreach ($values as $i => $v) {
            Metric::insert(['router_id' => $this->router->id, 'metric' => $metric, 'ref' => '', 'value' => $v, 'resolution' => 60, 'recorded_at' => $start + $i * 60]);
        }
    }

    private function cpuRule(array $overrides = []): AlertRule
    {
        return AlertRule::create($overrides + ['name' => 'High CPU', 'metric' => 'cpu', 'operator' => '>', 'threshold' => 90, 'duration_minutes' => 5, 'enabled' => true]);
    }

    private function evaluate(): void
    {
        app(AlertService::class)->evaluateRules($this->router, self::NOW);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Http::fake(['*' => Http::response(['ok' => true])]);
        Setting::write('webhook_url', 'https://hooks.example.com/fluxmon');
    }

    public function test_rule_fires_only_when_the_whole_window_breaches(): void
    {
        $this->cpuRule();

        $this->samples('cpu', [95, 96, 40, 97, 98, 99]); // one good sample inside the window
        $this->evaluate();
        $this->assertSame(0, AlertEvent::count());

        Metric::truncate();
        $this->samples('cpu', [95, 96, 97, 98, 99, 99]);
        $this->evaluate();
        $this->assertSame(1, AlertEvent::firing()->count());
    }

    public function test_a_single_spike_does_not_trip_a_long_rule(): void
    {
        $this->cpuRule(['duration_minutes' => 10]);
        $this->samples('cpu', [99]); // only one sample so far

        $this->evaluate();

        $this->assertSame(0, AlertEvent::count());
    }

    public function test_firing_is_not_duplicated_and_resolves_when_back_to_normal(): void
    {
        $this->cpuRule();
        $this->samples('cpu', [95, 95, 95, 95, 95, 95]);

        $this->evaluate();
        $this->evaluate();
        $this->assertSame(1, AlertEvent::count());
        Http::assertSentCount(1);

        Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 10, 'resolution' => 60, 'recorded_at' => self::NOW + 60]);
        app(AlertService::class)->evaluateRules($this->router, self::NOW + 60);

        $event = AlertEvent::first();
        $this->assertSame('resolved', $event->state);
        $this->assertNotNull($event->resolved_at);
        Http::assertSentCount(2); // firing + resolved
    }

    public function test_less_than_operator_and_router_scope(): void
    {
        $other = \App\Models\Router::create(['name' => 'Other', 'host' => '1.1.1.1', 'port' => 8728, 'username' => 'a', 'password' => 'b', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]);
        $this->cpuRule(['name' => 'Low voltage', 'metric' => 'voltage', 'operator' => '<', 'threshold' => 20, 'router_id' => $other->id]);
        $this->samples('voltage', [10, 10, 10, 10, 10, 10]);

        $this->evaluate(); // this router is not the rule's router
        $this->assertSame(0, AlertEvent::count());

        app(AlertService::class)->evaluateRules($other, self::NOW);
        $this->assertSame(0, AlertEvent::count(), 'the samples belong to the first router');
    }

    public function test_disabled_rules_are_ignored(): void
    {
        $this->cpuRule(['enabled' => false]);
        $this->samples('cpu', [99, 99, 99, 99, 99, 99]);

        $this->evaluate();

        $this->assertSame(0, AlertEvent::count());
    }

    public function test_router_offline_and_back_online(): void
    {
        $alerts = app(AlertService::class);

        $alerts->router($this->router, false, 'Connection refused');
        $alerts->router($this->router, false, 'Connection refused');
        $this->assertSame(1, AlertEvent::firing()->where('kind', 'router_offline')->count());
        Http::assertSent(fn ($r) => str_contains($r['title'], 'unreachable') && $r['router'] === 'Office' && $r['state'] === 'firing');

        $alerts->router($this->router, true);
        $this->assertSame(0, AlertEvent::firing()->count());
        Http::assertSent(fn ($r) => str_contains($r['title'], 'back online') && $r['state'] === 'resolved');
    }

    public function test_online_router_with_no_open_event_sends_nothing(): void
    {
        app(AlertService::class)->router($this->router, true);

        Http::assertNothingSent();
    }

    public function test_telegram_and_email_channels_are_used_when_configured(): void
    {
        Mail::fake();
        Setting::writeSecret('telegram_token', 'tok');
        Setting::write('telegram_chat_id', '42');
        Setting::write('telegram_offline_alert', '1');
        Setting::write('alert_email', 'noc@example.com, boss@example.com');

        app(AlertService::class)->router($this->router, false, 'timeout');

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org/bottok/sendMessage') && $r['chat_id'] === '42');
        Mail::assertSent(AlertMail::class, fn (AlertMail $m) => $m->hasTo('noc@example.com') && $m->hasTo('boss@example.com') && str_contains($m->title, 'unreachable'));
    }

    public function test_a_failing_channel_does_not_block_the_others(): void
    {
        Http::fake(['hooks.example.com/*' => Http::response('boom', 500), 'api.telegram.org/*' => Http::response(['ok' => true])]);
        Setting::writeSecret('telegram_token', 'tok');
        Setting::write('telegram_chat_id', '42');
        Setting::write('telegram_offline_alert', '1');

        app(AlertService::class)->router($this->router, false);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.telegram.org'));
        $this->assertSame(1, AlertEvent::count());
    }

    public function test_check_runner_records_state_changes_and_alerts_once(): void
    {
        $check = Check::create(['router_id' => $this->router->id, 'name' => 'ISP gateway', 'type' => 'router_ping', 'target' => '8.8.8.8']);
        $runner = app(CheckRunner::class);

        $runner->runAll($this->router, $this->mikrotik, self::NOW);
        $this->assertTrue($check->fresh()->last_status);
        $this->assertSame(4.5, $check->fresh()->last_rtt);
        $this->assertSame(0, AlertEvent::count());

        $this->mikrotik->pingResults['8.8.8.8'] = ['sent' => 2, 'received' => 0, 'avg_ms' => null];
        $runner->runAll($this->router, $this->mikrotik, self::NOW + 60);
        $runner->runAll($this->router, $this->mikrotik, self::NOW + 120);

        $this->assertFalse($check->fresh()->last_status);
        $this->assertSame(1, AlertEvent::firing()->where('kind', 'check_down')->count());
        $this->assertSame(0.0, Metric::where('metric', 'check_up')->where('recorded_at', self::NOW + 60)->value('value'));

        unset($this->mikrotik->pingResults['8.8.8.8']);
        $runner->runAll($this->router, $this->mikrotik, self::NOW + 180);
        $this->assertSame(0, AlertEvent::firing()->count());
    }

    public function test_paused_checks_do_not_run(): void
    {
        $check = Check::create(['router_id' => $this->router->id, 'name' => 'x', 'type' => 'router_ping', 'target' => '1.1.1.1', 'enabled' => false]);

        app(CheckRunner::class)->runAll($this->router, $this->mikrotik, self::NOW);

        $this->assertNull($check->fresh()->last_status);
    }

    public function test_sync_command_runs_the_whole_pipeline_and_flags_offline_routers(): void
    {
        Check::create(['router_id' => $this->router->id, 'name' => 'gw', 'type' => 'router_ping', 'target' => '1.1.1.1']);

        $this->artisan('fluxmon:sync')->assertSuccessful();

        $this->assertTrue($this->router->fresh()->is_online);
        $this->assertNotNull($this->router->fresh()->ros_version, 'capabilities detected on first sync');
        $this->assertGreaterThan(0, Metric::where('metric', 'cpu')->count());
        $this->assertTrue(Check::first()->last_status);

        $this->mikrotik->offline = true;
        $this->artisan('fluxmon:sync')->assertSuccessful();

        $this->assertFalse($this->router->fresh()->is_online);
        $this->assertSame(1, AlertEvent::firing()->where('kind', 'router_offline')->count());
    }

    public function test_alert_pages_and_rule_management(): void
    {
        $this->asAdmin()->post('/alerts/defaults')->assertSessionHas('status');
        $this->assertSame(3, AlertRule::count());
        $this->asAdmin()->post('/alerts/defaults');
        $this->assertSame(3, AlertRule::count(), 'defaults are not duplicated');

        $this->asAdmin()->post('/alerts', ['name' => 'Hot', 'metric' => 'temperature', 'operator' => '>', 'threshold' => 80, 'duration_minutes' => 3, 'router_id' => $this->router->id])
            ->assertSessionHasNoErrors();
        $this->asAdmin()->post('/alerts', ['name' => 'Bad', 'metric' => 'nonsense', 'operator' => '=', 'threshold' => 'x', 'duration_minutes' => 0])
            ->assertSessionHasErrors(['metric', 'operator', 'threshold', 'duration_minutes']);

        $rule = AlertRule::where('name', 'Hot')->first();
        $this->asAdmin()->post("/alerts/{$rule->id}/toggle");
        $this->assertFalse($rule->fresh()->enabled);

        AlertEvent::create(['router_id' => $this->router->id, 'kind' => 'router_offline', 'subject' => 'router', 'title' => 'Office is unreachable', 'state' => 'firing', 'started_at' => now()]);
        $this->asAdmin()->get('/alerts')->assertOk()->assertSee('Office is unreachable')->assertSee('Hot');
        $this->asAdmin()->get('/dashboard')->assertSee('Office is unreachable');

        $this->asAdmin()->delete("/alerts/{$rule->id}");
        $this->assertDatabaseMissing('alert_rules', ['id' => $rule->id]);
    }

    public function test_check_management_validates_input(): void
    {
        $id = $this->router->id;

        $this->asAdmin()->post("/r/{$id}/checks", ['name' => 'Web', 'type' => 'tcp', 'target' => 'example.com'])->assertSessionHasErrors('port');
        $this->asAdmin()->post("/r/{$id}/checks", ['name' => 'Evil', 'type' => 'router_ping', 'target' => '8.8.8.8; /system reset'])->assertSessionHasErrors('target');
        $this->asAdmin()->post("/r/{$id}/checks", ['name' => 'GW', 'type' => 'router_ping', 'target' => '10.0.0.1'])->assertSessionHasNoErrors();

        $check = Check::first();
        $this->asAdmin()->post("/r/{$id}/checks/{$check->id}/toggle");
        $this->assertFalse($check->fresh()->enabled);
        $this->asAdmin()->delete("/r/{$id}/checks/{$check->id}");
        $this->assertSame(0, Check::count());
    }

    public function test_notification_settings_validate_email_and_test_alert_goes_out(): void
    {
        $this->asAdmin()->put('/settings/notifications', ['webhook_url' => 'https://hooks.example.com/x', 'alert_email' => 'ok@example.com, not-an-email'])
            ->assertSessionHas('error');

        $this->asAdmin()->put('/settings/notifications', ['webhook_url' => 'https://hooks.example.com/x', 'alert_email' => 'ok@example.com'])
            ->assertSessionHas('status');
        $this->assertSame('ok@example.com', Setting::read('alert_email'));

        Mail::fake();
        $this->asAdmin()->post('/settings/notifications/test')->assertSessionHas('status');
        Http::assertSent(fn ($r) => str_contains($r['title'], 'test alert'));
    }
}

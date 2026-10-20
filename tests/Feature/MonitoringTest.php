<?php

namespace Tests\Feature;

use App\Models\Metric;
use App\Services\CapabilityDetector;
use App\Services\Monitoring\MetricSeries;
use App\Services\Monitoring\MetricsCollector;
use App\Services\Monitoring\RollupService;
use Illuminate\Support\Facades\Cache;
use Tests\FluxmonTestCase;

class MonitoringTest extends FluxmonTestCase
{
    private const T0 = 1_800_000_000; // a minute boundary

    private function collect(int $at): int
    {
        return app(MetricsCollector::class)->collect($this->router, $this->mikrotik, $at);
    }

    private function value(string $metric, string $ref = '', int $at = self::T0): ?float
    {
        return Metric::where('router_id', $this->router->id)->where('metric', $metric)->where('ref', $ref)->where('recorded_at', $at)->value('value');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->assertSame(0, self::T0 % 60);
    }

    public function test_first_sample_has_no_rates_second_derives_bits_per_second_from_counters(): void
    {
        $this->collect(self::T0);
        $this->assertNull($this->value('rx_bps', 'ether1'), 'no baseline yet');
        $this->assertSame(12.0, $this->value('cpu'));
        $this->assertSame(40.0, $this->value('memory'));
        $this->assertSame(45.0, $this->value('temperature'));

        $this->mikrotik->counters['ether1'] = ['rx' => 7_500_000, 'tx' => 1_500_000];
        $this->collect(self::T0 + 60);

        // 7.5 MB in 60 s = 1,000,000 bit/s ; 1.5 MB in 60 s = 200,000 bit/s
        $this->assertEqualsWithDelta(1_000_000, $this->value('rx_bps', 'ether1', self::T0 + 60), 0.1);
        $this->assertEqualsWithDelta(200_000, $this->value('tx_bps', 'ether1', self::T0 + 60), 0.1);
    }

    public function test_counter_reset_after_reboot_is_skipped_not_negative(): void
    {
        $this->mikrotik->counters['ether1'] = ['rx' => 9_000_000, 'tx' => 9_000_000];
        $this->collect(self::T0);

        $this->mikrotik->counters['ether1'] = ['rx' => 100, 'tx' => 100]; // router rebooted
        $this->collect(self::T0 + 60);

        $this->assertNull($this->value('rx_bps', 'ether1', self::T0 + 60));
        $this->assertSame(0, Metric::where('value', '<', 0)->count());
    }

    public function test_missing_sensors_are_not_stored(): void
    {
        $this->mikrotik->temperature = null;
        $this->collect(self::T0);

        $this->assertNull($this->value('temperature'));
        $this->assertSame(0, Metric::where('metric', 'temperature')->count());
    }

    public function test_series_returns_averaged_points_in_time_order(): void
    {
        foreach ([10, 20, 30, 40] as $i => $cpu) {
            Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => $cpu, 'resolution' => 60, 'recorded_at' => self::T0 - 3000 + $i * 60]);
        }

        $series = app(MetricSeries::class)->get($this->router->id, 'cpu', '', 3600, 240, self::T0);

        $this->assertSame([10.0, 20.0, 30.0, 40.0], $series['values']);
        $this->assertSame($series['times'], collect($series['times'])->sort()->values()->all());
    }

    public function test_series_buckets_long_ranges_down_to_the_point_limit(): void
    {
        foreach (range(0, 599) as $i) {
            Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 50, 'resolution' => 60, 'recorded_at' => self::T0 - 36000 + $i * 60]);
        }

        $series = app(MetricSeries::class)->get($this->router->id, 'cpu', '', 36000, 100, self::T0);

        $this->assertLessThanOrEqual(101, count($series['values']));
        $this->assertSame([50.0], array_values(array_unique($series['values'])));
    }

    public function test_rollup_builds_hourly_averages_and_is_idempotent(): void
    {
        $hour = intdiv(self::T0, 3600) * 3600 - 5 * 3600; // an hour that is well in the past
        foreach ([10, 30] as $i => $v) {
            Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => $v, 'resolution' => 60, 'recorded_at' => $hour + $i * 60]);
        }

        $service = app(RollupService::class);
        $service->run(self::T0);
        $service->run(self::T0);

        $hourly = Metric::where('resolution', 3600)->get();
        $this->assertCount(1, $hourly);
        $this->assertSame($hour, (int) $hourly[0]->recorded_at);
        $this->assertSame(20.0, $hourly[0]->value);
    }

    public function test_rollup_prunes_old_raw_rows_only_after_they_are_old_enough(): void
    {
        $old = self::T0 - 10 * 86400;
        $recent = self::T0 - 86400;
        foreach ([$old, $recent] as $t) {
            Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 1, 'resolution' => 60, 'recorded_at' => $t]);
        }
        Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 1, 'resolution' => 3600, 'recorded_at' => self::T0 - 200 * 86400]);

        $result = app(RollupService::class)->run(self::T0);

        $this->assertSame(1, $result['pruned_raw']);
        $this->assertSame(1, $result['pruned_hourly']);
        $this->assertSame(1, Metric::where('resolution', 60)->count());
    }

    public function test_long_range_series_combines_hourly_history_with_recent_raw(): void
    {
        $oldHour = intdiv(self::T0, 3600) * 3600 - 3 * 86400;
        Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 80, 'resolution' => 3600, 'recorded_at' => $oldHour]);
        // Raw rows inside the covered hour must not be double counted.
        Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 10, 'resolution' => 60, 'recorded_at' => $oldHour + 60]);
        Metric::insert(['router_id' => $this->router->id, 'metric' => 'cpu', 'ref' => '', 'value' => 20, 'resolution' => 60, 'recorded_at' => self::T0 - 60]);

        $series = app(MetricSeries::class)->get($this->router->id, 'cpu', '', 7 * 86400, 1000, self::T0);

        $this->assertContains(80.0, $series['values']);
        $this->assertContains(20.0, $series['values']);
        $this->assertNotContains(10.0, $series['values']);
    }

    public function test_capability_detection_on_routeros_7(): void
    {
        $this->mikrotik->packageNames = ['routeros', 'wifi-qcom', 'container'];
        app(CapabilityDetector::class)->detect($this->router, $this->mikrotik);

        $r = $this->router->fresh();
        $this->assertSame(7, $r->ros_major);
        $this->assertSame('7.14.3', $r->ros_version);
        $this->assertSame('MikroTik-Office', $r->identity);
        $this->assertTrue($r->supports('wireguard'));
        $this->assertTrue($r->supports('wifi_new'));
        $this->assertTrue($r->supports('container'));
        $this->assertFalse($r->supports('wireless_legacy'));
    }

    public function test_capability_detection_on_routeros_6(): void
    {
        $this->mikrotik->version = '6.49.13 (long-term)';
        $this->mikrotik->packageNames = ['system', 'wireless', 'ipv6'];
        app(CapabilityDetector::class)->detect($this->router, $this->mikrotik);

        $r = $this->router->fresh();
        $this->assertSame(6, $r->ros_major);
        $this->assertFalse($r->supports('wireguard'));
        $this->assertTrue($r->supports('wireless_legacy'));
        $this->assertFalse($r->supports('wifi_new'));
        $this->assertTrue($r->supports('ipv6'));
    }

    public function test_charts_data_endpoint(): void
    {
        $this->collect(intdiv(time(), 60) * 60);

        $this->asAdmin()->getJson("/r/{$this->router->id}/monitoring/data?range=1h&interface=ether1")
            ->assertOk()->assertJsonStructure(['cpu' => ['times', 'values'], 'memory', 'temperature', 'hotspot_active', 'rx', 'tx'])
            ->assertJsonPath('cpu.values.0', 12);

        $this->asAdmin()->getJson("/r/{$this->router->id}/monitoring/data?range=bogus")->assertStatus(422);
    }

    public function test_log_viewer_filters_by_topic_and_text(): void
    {
        $this->mikrotik->logLines = [
            ['time' => '10:00:01', 'topics' => 'system,info', 'message' => 'router rebooted'],
            ['time' => '10:00:02', 'topics' => 'wireless,info', 'message' => 'client connected'],
            ['time' => '10:00:03', 'topics' => 'system,error', 'message' => 'login failure for user admin'],
        ];
        $id = $this->router->id;

        $all = $this->asAdmin()->getJson("/r/{$id}/logs/data")->assertOk()->json();
        $this->assertSame('login failure for user admin', $all['lines'][0]['message'], 'newest first');
        $this->assertSame(['error', 'info', 'system', 'wireless'], $all['topics']);

        $this->assertCount(2, $this->asAdmin()->getJson("/r/{$id}/logs/data?topic=system")->json('lines'));
        $this->assertCount(1, $this->asAdmin()->getJson("/r/{$id}/logs/data?q=client")->json('lines'));

        $this->mikrotik->offline = true;
        $this->asAdmin()->getJson("/r/{$id}/logs/data")->assertJson(['ok' => false]);
    }
}

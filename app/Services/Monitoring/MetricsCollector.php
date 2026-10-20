<?php

namespace App\Services\Monitoring;

use App\Models\Metric;
use App\Models\Router;
use App\Services\Mikrotik\Gateway;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Samples one router and stores one minute-resolution row per metric.
 *
 * Metrics: cpu, memory, temperature, voltage, hotspot_active, and rx_bps / tx_bps per interface
 * (derived from the change in the byte counters since the previous sample).
 */
class MetricsCollector
{
    public function collect(Router $router, Gateway $gateway, ?int $now = null): int
    {
        $now = intdiv($now ?? time(), 60) * 60;
        $rows = [];
        $add = function (string $metric, float|int $value, string $ref = '') use (&$rows, $router, $now) {
            $rows[] = [
                'router_id' => $router->id, 'metric' => $metric, 'ref' => $ref, 'value' => $value,
                'resolution' => Metric::RAW, 'recorded_at' => $now,
            ];
        };

        $resource = $gateway->resource();
        $add('cpu', $resource['cpu']);
        $add('memory', $resource['memory']);

        $health = $gateway->health();
        if ($health['temperature'] !== null) {
            $add('temperature', $health['temperature']);
        }
        if ($health['voltage'] !== null) {
            $add('voltage', $health['voltage']);
        }

        try {
            $add('hotspot_active', count($gateway->hotspotActive()));
        } catch (Throwable) {
            // No hotspot configured on this router.
        }

        foreach ($gateway->interfaceCounters() as $if) {
            // Dynamic client interfaces (pppoe-in, l2tp-in, ...) come and go by the thousand.
            if ($if['disabled'] || str_ends_with($if['type'], '-in')) {
                continue;
            }

            $key = "fluxmon.counters.{$router->id}.{$if['name']}";
            $previous = Cache::get($key);
            Cache::put($key, [$now, $if['rx_byte'], $if['tx_byte']], 3600);

            if (! $previous || $now <= $previous[0] || $if['rx_byte'] < $previous[1] || $if['tx_byte'] < $previous[2]) {
                continue; // first sample, or the counters were reset by a reboot
            }

            $seconds = $now - $previous[0];
            $add('rx_bps', ($if['rx_byte'] - $previous[1]) * 8 / $seconds, $if['name']);
            $add('tx_bps', ($if['tx_byte'] - $previous[2]) * 8 / $seconds, $if['name']);
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            Metric::insert($chunk);
        }

        return count($rows);
    }
}

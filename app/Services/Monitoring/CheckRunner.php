<?php

namespace App\Services\Monitoring;

use App\Models\Check;
use App\Models\Metric;
use App\Models\Router;
use App\Services\Alerting\AlertService;
use App\Services\Mikrotik\Gateway;
use Throwable;

class CheckRunner
{
    public function __construct(private readonly AlertService $alerts)
    {
    }

    /** Run every enabled check of a router; records results and raises/clears alerts on changes. */
    public function runAll(Router $router, Gateway $gateway, ?int $now = null): void
    {
        $now = intdiv($now ?? time(), 60) * 60;

        foreach (Check::where('router_id', $router->id)->where('enabled', true)->get() as $check) {
            $this->run($router, $check, $gateway, $now);
        }
    }

    /** @return array{up: bool, rtt: ?float} */
    public function probe(Check $check, Gateway $gateway): array
    {
        try {
            if ($check->type === 'tcp') {
                $started = microtime(true);
                $socket = @fsockopen($check->target, (int) $check->port, $errno, $error, 3);
                if (! $socket) {
                    return ['up' => false, 'rtt' => null];
                }
                fclose($socket);

                return ['up' => true, 'rtt' => round((microtime(true) - $started) * 1000, 2)];
            }

            $result = $gateway->ping($check->target, 2);

            return ['up' => $result['received'] > 0, 'rtt' => $result['avg_ms']];
        } catch (Throwable) {
            return ['up' => false, 'rtt' => null];
        }
    }

    private function run(Router $router, Check $check, Gateway $gateway, int $now): void
    {
        $result = $this->probe($check, $gateway);
        $changed = $check->last_status !== $result['up'];

        $check->forceFill([
            'last_status' => $result['up'],
            'last_rtt' => $result['rtt'],
            'last_checked_at' => now(),
            'last_change_at' => $changed ? now() : $check->last_change_at,
        ])->save();

        $rows = [['router_id' => $router->id, 'metric' => 'check_up', 'ref' => (string) $check->id, 'value' => $result['up'] ? 1 : 0, 'resolution' => Metric::RAW, 'recorded_at' => $now]];
        if ($result['rtt'] !== null) {
            $rows[] = ['router_id' => $router->id, 'metric' => 'check_rtt', 'ref' => (string) $check->id, 'value' => $result['rtt'], 'resolution' => Metric::RAW, 'recorded_at' => $now];
        }
        Metric::insert($rows);

        $this->alerts->check($router, $check, $result['up']);
    }
}

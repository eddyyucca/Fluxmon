<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Alerting\AlertService;
use App\Services\CapabilityDetector;
use App\Services\Mikrotik\MikrotikFactory;
use App\Services\Monitoring\CheckRunner;
use App\Services\Monitoring\MetricsCollector;
use App\Services\SyncService;
use App\Support\DefaultLocale;
use Illuminate\Console\Command;
use Throwable;

class SyncCommand extends Command
{
    protected $signature = 'fluxmon:sync';

    protected $description = 'Per router: sync vouchers, collect metrics, run checks and evaluate alerts';

    public function handle(
        SyncService $sync,
        MikrotikFactory $factory,
        CapabilityDetector $detector,
        MetricsCollector $collector,
        CheckRunner $checks,
        AlertService $alerts,
    ): int {
        DefaultLocale::apply(); // alert texts are written in the admin's default language

        foreach (Router::orderBy('name')->get() as $router) {
            $r = $sync->syncRouter($router);
            $alerts->router($router, $r['ok'], $r['error']);

            if (! $r['ok']) {
                $this->line("{$router->name}: OFFLINE - {$r['error']}");

                continue;
            }

            try {
                $gateway = $factory->make($router);
                if (! $router->ros_version) {
                    $detector->detect($router, $gateway);
                }
                $samples = $collector->collect($router, $gateway);
                $checks->runAll($router, $gateway);
                $alerts->evaluateRules($router);

                $this->line(sprintf('%s: online (vouchers used %d, expired %d, %d samples)', $router->name, $r['used'], $r['expired'], $samples));
            } catch (Throwable $e) {
                $this->warn("{$router->name}: monitoring failed - {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}

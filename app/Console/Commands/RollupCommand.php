<?php

namespace App\Console\Commands;

use App\Services\Monitoring\RollupService;
use Illuminate\Console\Command;

class RollupCommand extends Command
{
    protected $signature = 'fluxmon:rollup';

    protected $description = 'Fold minute samples into hourly averages and prune old monitoring data';

    public function handle(RollupService $rollup): int
    {
        $r = $rollup->run();
        $this->info("Rolled up {$r['rolled']} hourly points; pruned {$r['pruned_raw']} raw and {$r['pruned_hourly']} hourly rows.");

        return self::SUCCESS;
    }
}

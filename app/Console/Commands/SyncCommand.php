<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\SyncService;
use App\Services\Telegram;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    protected $signature = 'fluxmon:sync';

    protected $description = 'Sync vouchers with every router and alert on online/offline changes';

    public function handle(SyncService $sync, Telegram $telegram): int
    {
        foreach (Router::orderBy('name')->get() as $router) {
            $r = $sync->syncRouter($router);

            $this->line(sprintf(
                '%s: %s (used %d, expired %d)',
                $router->name,
                $r['ok'] ? 'online' : 'OFFLINE - '.$r['error'],
                $r['used'],
                $r['expired'],
            ));

            if ($r['changed'] && $telegram->enabled('offline_alert')) {
                $telegram->send($r['ok']
                    ? "✅ Router {$router->name} is back online."
                    : "🔴 Router {$router->name} is unreachable: {$r['error']}");
            }
        }

        return self::SUCCESS;
    }
}

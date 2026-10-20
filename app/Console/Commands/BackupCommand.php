<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\Config\BackupService;
use Illuminate\Console\Command;
use Throwable;

class BackupCommand extends Command
{
    protected $signature = 'fluxmon:backup';

    protected $description = 'Save the configuration of every router (skips routers whose config has not changed)';

    public function handle(BackupService $backups): int
    {
        $failed = 0;

        foreach (Router::orderBy('name')->get() as $router) {
            try {
                $result = $backups->create($router, 'scheduled');
                $this->line("{$router->name}: ".($result['changed'] ? 'saved' : 'unchanged'));
            } catch (Throwable $e) {
                $failed++;
                $this->error("{$router->name}: {$e->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

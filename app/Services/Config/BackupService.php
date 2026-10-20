<?php

namespace App\Services\Config;

use App\Models\ConfigBackup;
use App\Models\Router;

class BackupService
{
    public function __construct(private readonly ConfigExporter $exporter)
    {
    }

    /**
     * Take a backup. When the config is identical to the newest stored one, nothing new is
     * saved and that backup is returned with `changed = false`.
     *
     * @return array{backup: ConfigBackup, changed: bool}
     */
    public function create(Router $router, string $reason, ?int $userId = null): array
    {
        $content = $this->exporter->export($router);
        $hash = hash('sha256', $content);

        $latest = ConfigBackup::where('router_id', $router->id)->latest('id')->first();
        if ($latest && $latest->hash === $hash) {
            return ['backup' => $latest, 'changed' => false];
        }

        $backup = ConfigBackup::create([
            'router_id' => $router->id,
            'user_id' => $userId,
            'reason' => $reason,
            'hash' => $hash,
            'size' => strlen($content),
            'content' => $content,
        ]);

        $this->prune($router);

        return ['backup' => $backup, 'changed' => true];
    }

    /** Keep only the newest N backups per router. */
    public function prune(Router $router): int
    {
        $keep = max(1, (int) config('fluxmon.backups.keep'));
        // Slice in PHP: SQLite rejects OFFSET without LIMIT.
        $ids = ConfigBackup::where('router_id', $router->id)->latest('id')->pluck('id')->slice($keep);

        return $ids->isEmpty() ? 0 : ConfigBackup::whereIn('id', $ids)->delete();
    }
}

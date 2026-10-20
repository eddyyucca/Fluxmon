<?php

namespace App\Services\Mikrotik;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Throwable;

/**
 * Thin wrapper around the RouterOS API for a single Router record.
 */
class MikrotikClient
{
    private ?Client $client = null;

    public function __construct(private readonly Router $router)
    {
    }

    public function connect(): Client
    {
        return $this->client ??= new Client([
            'host' => $this->router->host,
            'port' => $this->router->port,
            'user' => $this->router->username,
            'pass' => $this->router->password,
            'ssl' => $this->router->use_ssl,
            'timeout' => $this->router->timeout,
            'attempts' => 1,
        ]);
    }

    /**
     * Run a query and return the rows as arrays.
     *
     * @param  array<string,string>  $where
     */
    public function get(string $path, array $where = []): array
    {
        $query = new Query($path);
        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }

        return $this->connect()->query($query)->read();
    }

    public function systemResource(): array
    {
        return $this->get('/system/resource/print')[0] ?? [];
    }

    public function hotspotActiveCount(): int
    {
        return count($this->get('/ip/hotspot/active/print'));
    }

    public function hotspotUserCount(): int
    {
        return count($this->get('/ip/hotspot/user/print'));
    }

    /**
     * @return array{ok: bool, message: string, board?: string, version?: string, uptime?: string}
     */
    public function test(): array
    {
        try {
            $res = $this->systemResource();
            $this->router->forceFill(['last_connected_at' => now()])->save();

            return [
                'ok' => true,
                'message' => 'Connected',
                'board' => $res['board-name'] ?? null,
                'version' => $res['version'] ?? null,
                'uptime' => $res['uptime'] ?? null,
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }
}

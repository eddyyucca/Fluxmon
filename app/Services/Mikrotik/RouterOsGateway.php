<?php

namespace App\Services\Mikrotik;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;

/**
 * Gateway backed by the RouterOS API (port 8728 / 8729).
 */
class RouterOsGateway implements Gateway
{
    private ?Client $client = null;

    public function __construct(private readonly Router $router)
    {
    }

    private function client(): Client
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
     * @param  array<string,string|int|null>  $attributes  sent as =key=value words
     * @param  array<string,string>  $where  sent as ?key=value filters
     */
    private function run(string $path, array $attributes = [], array $where = []): array
    {
        $query = new Query($path);
        foreach ($attributes as $key => $value) {
            if ($value !== null) {
                $query->equal($key, (string) $value);
            }
        }
        foreach ($where as $key => $value) {
            $query->where($key, $value);
        }

        return $this->client()->query($query)->read();
    }

    public function resource(): array
    {
        $r = $this->run('/system/resource/print')[0] ?? [];
        $total = (int) ($r['total-memory'] ?? 0);
        $free = (int) ($r['free-memory'] ?? 0);

        return [
            'board' => $r['board-name'] ?? '-',
            'version' => $r['version'] ?? '-',
            'uptime' => $r['uptime'] ?? '-',
            'cpu' => (int) ($r['cpu-load'] ?? 0),
            'memory' => $total ? (int) round(($total - $free) / $total * 100) : 0,
            'total_memory' => $total,
            'free_memory' => $free,
        ];
    }

    public function interfaces(): array
    {
        return array_values(array_filter(array_column($this->run('/interface/print'), 'name')));
    }

    public function traffic(string $interface): array
    {
        $r = $this->client()->query(
            (new Query('/interface/monitor-traffic'))->equal('interface', $interface)->equal('once')
        )->read()[0] ?? [];

        return [
            'rx' => (int) ($r['rx-bits-per-second'] ?? 0),
            'tx' => (int) ($r['tx-bits-per-second'] ?? 0),
        ];
    }

    public function hotspotUsers(): array
    {
        return array_map(fn (array $u) => [
            'id' => $u['.id'],
            'name' => $u['name'] ?? '',
            'password' => $u['password'] ?? '',
            'profile' => $u['profile'] ?? 'default',
            'comment' => $u['comment'] ?? '',
            'disabled' => ($u['disabled'] ?? 'false') === 'true',
            'uptime' => $u['uptime'] ?? '0s',
            'limit_uptime' => $u['limit-uptime'] ?? '',
            'limit_bytes' => (int) ($u['limit-bytes-total'] ?? 0),
            'bytes_in' => (int) ($u['bytes-in'] ?? 0),
            'bytes_out' => (int) ($u['bytes-out'] ?? 0),
        ], $this->run('/ip/hotspot/user/print'));
    }

    public function addHotspotUser(array $attributes): void
    {
        $this->run('/ip/hotspot/user/add', $attributes);
    }

    public function setHotspotUser(string $id, array $attributes): void
    {
        $this->run('/ip/hotspot/user/set', ['.id' => $id] + $attributes);
    }

    public function removeHotspotUser(string $id): void
    {
        $this->run('/ip/hotspot/user/remove', ['.id' => $id]);
    }

    public function resetHotspotUser(string $id): void
    {
        $this->run('/ip/hotspot/user/reset-counters', ['.id' => $id]);
    }

    public function hotspotActive(): array
    {
        return array_map(fn (array $a) => [
            'id' => $a['.id'],
            'user' => $a['user'] ?? '',
            'address' => $a['address'] ?? '',
            'mac' => $a['mac-address'] ?? '',
            'uptime' => $a['uptime'] ?? '',
            'bytes_in' => (int) ($a['bytes-in'] ?? 0),
            'bytes_out' => (int) ($a['bytes-out'] ?? 0),
            'login_by' => $a['login-by'] ?? '',
            'server' => $a['server'] ?? '',
        ], $this->run('/ip/hotspot/active/print'));
    }

    public function kickActive(string $id): void
    {
        $this->run('/ip/hotspot/active/remove', ['.id' => $id]);
    }

    public function profiles(): array
    {
        return array_map(fn (array $p) => [
            'id' => $p['.id'],
            'name' => $p['name'] ?? '',
            'rate_limit' => $p['rate-limit'] ?? '',
            'shared_users' => (int) ($p['shared-users'] ?? 1),
        ], $this->run('/ip/hotspot/user/profile/print'));
    }

    public function addProfile(array $attributes): void
    {
        $this->run('/ip/hotspot/user/profile/add', $attributes);
    }

    public function setProfile(string $id, array $attributes): void
    {
        $this->run('/ip/hotspot/user/profile/set', ['.id' => $id] + $attributes);
    }

    public function removeProfile(string $id): void
    {
        $this->run('/ip/hotspot/user/profile/remove', ['.id' => $id]);
    }
}

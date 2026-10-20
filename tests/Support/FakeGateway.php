<?php

namespace Tests\Support;

use App\Services\Mikrotik\Gateway;

/**
 * In-memory router. Mimics just enough RouterOS behaviour for the app's logic.
 */
class FakeGateway implements Gateway
{
    /** @var array<string,array> */
    public array $users = [];

    /** @var array<string,array> */
    public array $active = [];

    /** @var array<string,array> */
    public array $profiles = [];

    public bool $offline = false;

    private int $seq = 0;

    public function __construct()
    {
        $this->profiles['*0'] = ['id' => '*0', 'name' => 'default', 'rate_limit' => '', 'shared_users' => 1];
    }

    private function guard(): void
    {
        if ($this->offline) {
            throw new \RuntimeException('Connection refused');
        }
    }

    private function nextId(): string
    {
        return '*'.dechex(++$this->seq);
    }

    /** Simulate a customer logging in and using the connection. */
    public function simulateUse(string $username, string $uptime = '5m', int $bytes = 1000): void
    {
        foreach ($this->users as $id => $u) {
            if ($u['name'] === $username) {
                $this->users[$id]['uptime'] = $uptime;
                $this->users[$id]['bytes_in'] = $bytes;
                $aid = $this->nextId();
                $this->active[$aid] = ['id' => $aid, 'user' => $username, 'address' => '10.0.0.5', 'mac' => 'AA:BB:CC:DD:EE:FF', 'uptime' => $uptime, 'bytes_in' => $bytes, 'bytes_out' => 0, 'login_by' => 'http', 'server' => 'hotspot1'];
            }
        }
    }

    public function userByName(string $name): ?array
    {
        foreach ($this->users as $u) {
            if ($u['name'] === $name) {
                return $u;
            }
        }

        return null;
    }

    public function resource(): array
    {
        $this->guard();

        return ['board' => 'hAP', 'version' => '7.14', 'uptime' => '1d', 'cpu' => 12, 'memory' => 40, 'total_memory' => 1000, 'free_memory' => 600];
    }

    public function interfaces(): array
    {
        $this->guard();

        return ['ether1', 'wlan1'];
    }

    public function traffic(string $interface): array
    {
        $this->guard();

        return ['rx' => 2_000_000, 'tx' => 500_000];
    }

    public function hotspotUsers(): array
    {
        $this->guard();

        return array_values($this->users);
    }

    public function addHotspotUser(array $attributes): void
    {
        $this->guard();
        $id = $this->nextId();
        $this->users[$id] = [
            'id' => $id,
            'name' => $attributes['name'],
            'password' => $attributes['password'] ?? '',
            'profile' => $attributes['profile'] ?? 'default',
            'comment' => $attributes['comment'] ?? '',
            'disabled' => false,
            'uptime' => '0s',
            'limit_uptime' => $attributes['limit-uptime'] ?? '',
            'limit_bytes' => (int) ($attributes['limit-bytes-total'] ?? 0),
            'bytes_in' => 0,
            'bytes_out' => 0,
        ];
    }

    public function setHotspotUser(string $id, array $attributes): void
    {
        $this->guard();
        if (isset($attributes['disabled'])) {
            $this->users[$id]['disabled'] = $attributes['disabled'] === 'yes';
        }
    }

    public function removeHotspotUser(string $id): void
    {
        $this->guard();
        unset($this->users[$id]);
    }

    public function resetHotspotUser(string $id): void
    {
        $this->guard();
        $this->users[$id]['uptime'] = '0s';
        $this->users[$id]['bytes_in'] = $this->users[$id]['bytes_out'] = 0;
    }

    public function hotspotActive(): array
    {
        $this->guard();

        return array_values($this->active);
    }

    public function kickActive(string $id): void
    {
        $this->guard();
        unset($this->active[$id]);
    }

    public function profiles(): array
    {
        $this->guard();

        return array_values($this->profiles);
    }

    public function addProfile(array $attributes): void
    {
        $this->guard();
        $id = $this->nextId();
        $this->profiles[$id] = ['id' => $id, 'name' => $attributes['name'], 'rate_limit' => $attributes['rate-limit'] ?? '', 'shared_users' => (int) ($attributes['shared-users'] ?? 1)];
    }

    public function setProfile(string $id, array $attributes): void
    {
        $this->guard();
        $this->profiles[$id] = ['id' => $id, 'name' => $attributes['name'], 'rate_limit' => $attributes['rate-limit'] ?? '', 'shared_users' => (int) ($attributes['shared-users'] ?? 1)];
    }

    public function removeProfile(string $id): void
    {
        $this->guard();
        unset($this->profiles[$id]);
    }
}

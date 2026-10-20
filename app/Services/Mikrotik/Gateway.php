<?php

namespace App\Services\Mikrotik;

/**
 * Everything Fluxmon needs from a MikroTik router. Rows are plain arrays with
 * normalised keys so controllers never touch raw RouterOS attribute names.
 *
 * Implementations throw on connection or command failure.
 */
interface Gateway
{
    /** @return array{board:string,version:string,architecture:string,uptime:string,cpu:int,memory:int,total_memory:int,free_memory:int} */
    public function resource(): array;

    public function identity(): string;

    /** @return list<array{name:string,version:string,disabled:bool}> */
    public function packages(): array;

    /**
     * Interfaces with their byte counters (cumulative since boot).
     *
     * @return list<array{name:string,type:string,running:bool,disabled:bool,comment:string,rx_byte:int,tx_byte:int}>
     */
    public function interfaceCounters(): array;

    /** Sensor readings; null when the board has no such sensor. @return array{temperature:?float,voltage:?float} */
    public function health(): array;

    /** Newest entries last. @return list<array{time:string,topics:string,message:string}> */
    public function logs(int $limit = 500): array;

    /** Ping run by the router itself. @return array{sent:int,received:int,avg_ms:?float} */
    public function ping(string $address, int $count = 2): array;

    /** @return list<string> interface names */
    public function interfaces(): array;

    /** @return array{rx:int,tx:int} bits per second */
    public function traffic(string $interface): array;

    /**
     * @return list<array{id:string,name:string,password:string,profile:string,comment:string,disabled:bool,uptime:string,limit_uptime:string,limit_bytes:int,bytes_in:int,bytes_out:int}>
     */
    public function hotspotUsers(): array;

    /** @param array<string,string|int> $attributes RouterOS attribute names */
    public function addHotspotUser(array $attributes): void;

    /** @param array<string,string|int> $attributes */
    public function setHotspotUser(string $id, array $attributes): void;

    public function removeHotspotUser(string $id): void;

    public function resetHotspotUser(string $id): void;

    /**
     * @return list<array{id:string,user:string,address:string,mac:string,uptime:string,bytes_in:int,bytes_out:int,login_by:string,server:string}>
     */
    public function hotspotActive(): array;

    public function kickActive(string $id): void;

    /** @return list<array{id:string,name:string,rate_limit:string,shared_users:int}> */
    public function profiles(): array;

    /** @param array<string,string|int> $attributes */
    public function addProfile(array $attributes): void;

    /** @param array<string,string|int> $attributes */
    public function setProfile(string $id, array $attributes): void;

    public function removeProfile(string $id): void;
}

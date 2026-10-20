<?php

namespace App\Services;

use App\Models\ProfileMeta;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\Mikrotik\MikrotikFactory;
use App\Support\RouterOs;
use Throwable;

class VoucherGenerator
{
    /** No look-alike characters (0/o, 1/l/i) so printed codes are easy to read. */
    public const CHARSETS = [
        'digits' => '0123456789',
        'lower' => 'abcdefghjkmnpqrstuvwxyz',
        'upper' => 'ABCDEFGHJKMNPQRSTUVWXYZ',
        'mixed' => 'abcdefghjkmnpqrstuvwxyz23456789',
    ];

    public function __construct(private readonly MikrotikFactory $factory)
    {
    }

    /**
     * Create a batch of vouchers on the router and record them locally.
     *
     * @param  array{profile:string,quantity:int,length:int,prefix?:?string,charset:string,mode:string,time_limit_minutes?:?int,data_limit_mb?:?int,price?:?float}  $data
     * @return array{batch: ?VoucherBatch, created: int, error: ?string}
     */
    public function generate(Router $router, array $data, ?int $userId = null): array
    {
        $gateway = $this->factory->make($router);
        $meta = ProfileMeta::where('router_id', $router->id)->where('name', $data['profile'])->first();

        $taken = array_flip(array_column($gateway->hotspotUsers(), 'name'));
        foreach (Voucher::where('router_id', $router->id)->pluck('username') as $name) {
            $taken[$name] = true;
        }

        $batch = VoucherBatch::create([
            'router_id' => $router->id,
            'user_id' => $userId,
            'profile' => $data['profile'],
            'quantity' => 0,
            'price' => $data['price'] ?? $meta?->price ?? 0,
            'validity_minutes' => $meta?->validity_minutes,
            'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            'data_limit_mb' => $data['data_limit_mb'] ?? null,
        ]);

        $created = 0;
        $error = null;

        try {
            for ($i = 0; $i < $data['quantity']; $i++) {
                $username = $this->uniqueCode($data, $taken);
                $password = $data['mode'] === 'pair' ? $this->code($data['length'], $data['charset']) : $username;

                $attributes = [
                    'name' => $username,
                    'password' => $password,
                    'profile' => $data['profile'],
                    'comment' => 'fluxmon:'.$batch->id,
                ];
                if (! empty($data['time_limit_minutes'])) {
                    $attributes['limit-uptime'] = RouterOs::duration((int) $data['time_limit_minutes']);
                }
                if (! empty($data['data_limit_mb'])) {
                    $attributes['limit-bytes-total'] = (int) $data['data_limit_mb'] * 1048576;
                }

                $gateway->addHotspotUser($attributes);

                Voucher::create([
                    'router_id' => $router->id,
                    'voucher_batch_id' => $batch->id,
                    'username' => $username,
                    'password' => $password,
                    'profile' => $data['profile'],
                    'price' => $batch->price,
                ]);
                $created++;
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        if ($created === 0) {
            $batch->delete();

            return ['batch' => null, 'created' => 0, 'error' => $error];
        }

        $batch->update(['quantity' => $created]);

        return ['batch' => $batch, 'created' => $created, 'error' => $error];
    }

    private function code(int $length, string $charset): string
    {
        $chars = self::CHARSETS[$charset] ?? self::CHARSETS['mixed'];
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, $max)];
        }

        return $out;
    }

    /** @param array<string,true> $taken usernames already in use (updated in place) */
    private function uniqueCode(array $data, array &$taken): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $code = ($data['prefix'] ?? '').$this->code($data['length'], $data['charset']);
            if (! isset($taken[$code])) {
                return $taken[$code] = $code;
            }
        }

        throw new \RuntimeException('Could not generate unique codes; increase the code length.');
    }
}

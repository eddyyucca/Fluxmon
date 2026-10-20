<?php

namespace App\Services;

use App\Models\ProfileMeta;
use App\Models\Router;
use App\Models\Voucher;
use App\Services\Mikrotik\MikrotikFactory;
use App\Support\RouterOs;
use Throwable;

/**
 * Reconciles local voucher records with the router: marks vouchers as used on
 * first login (that moment is the sale) and removes/disables them when their
 * validity runs out.
 */
class SyncService
{
    public function __construct(private readonly MikrotikFactory $factory)
    {
    }

    /**
     * @return array{ok: bool, error: ?string, used: int, expired: int, changed: bool}
     *                                                                                  `changed` is true when the router flipped between online and offline.
     */
    public function syncRouter(Router $router): array
    {
        $wasOnline = $router->is_online;
        $result = ['ok' => true, 'error' => null, 'used' => 0, 'expired' => 0, 'changed' => false];

        try {
            $gateway = $this->factory->make($router);
            $users = collect($gateway->hotspotUsers())->keyBy('name');
            $active = null; // fetched lazily, only if something expires

            $metas = ProfileMeta::where('router_id', $router->id)->get()->keyBy('name');
            $vouchers = Voucher::with('batch')
                ->where('router_id', $router->id)
                ->whereIn('status', [Voucher::UNUSED, Voucher::USED])
                ->get();

            foreach ($vouchers as $voucher) {
                $user = $users->get($voucher->username);

                if (! $user) {
                    // Deleted on the router. Never-used codes were never sold; used ones keep their revenue.
                    $voucher->status === Voucher::UNUSED
                        ? $voucher->delete()
                        : $voucher->update(['status' => Voucher::EXPIRED]);

                    continue;
                }

                if ($voucher->status === Voucher::UNUSED
                    && (RouterOs::hasElapsed($user['uptime']) || $user['bytes_in'] > 0 || $user['bytes_out'] > 0)) {
                    $validity = $voucher->batch?->validity_minutes;
                    $voucher->update([
                        'status' => Voucher::USED,
                        'first_used_at' => now(),
                        'expires_at' => $validity ? now()->addMinutes($validity) : null,
                    ]);
                    $result['used']++;
                }

                if ($voucher->status === Voucher::USED && $voucher->expires_at && $voucher->expires_at->isPast()) {
                    $action = $metas->get($voucher->profile)?->expire_action ?? 'remove';

                    $active ??= $gateway->hotspotActive();
                    foreach ($active as $session) {
                        if ($session['user'] === $voucher->username) {
                            $gateway->kickActive($session['id']);
                        }
                    }

                    $action === 'disable'
                        ? $gateway->setHotspotUser($user['id'], ['disabled' => 'yes'])
                        : $gateway->removeHotspotUser($user['id']);

                    $voucher->update(['status' => Voucher::EXPIRED]);
                    $result['expired']++;
                }
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['error'] = $e->getMessage();
        }

        $router->forceFill([
            'is_online' => $result['ok'],
            'last_checked_at' => now(),
            'last_connected_at' => $result['ok'] ? now() : $router->last_connected_at,
        ])->save();

        // First check (null) is not a "change"; only a real flip is worth an alert.
        $result['changed'] = $wasOnline !== null && $wasOnline !== $result['ok'];

        return $result;
    }
}

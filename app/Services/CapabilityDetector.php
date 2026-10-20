<?php

namespace App\Services;

use App\Models\Router;
use App\Services\Mikrotik\Gateway;

/**
 * Reads version, board and installed packages so the UI only offers what the router can do
 * (RouterOS 6 and 7 differ: WireGuard, the new wifi package, ...).
 */
class CapabilityDetector
{
    private const NEW_WIFI = ['wifi', 'wifi-qcom', 'wifi-qcom-ac', 'wifiwave2'];

    public function detect(Router $router, Gateway $gateway): Router
    {
        $resource = $gateway->resource();
        $enabled = array_map(
            fn (array $p) => $p['name'],
            array_filter($gateway->packages(), fn (array $p) => ! $p['disabled']),
        );

        $major = (int) $resource['version'];

        $router->forceFill([
            'identity' => $gateway->identity() ?: null,
            'ros_version' => preg_replace('/\s*\(.*\)$/', '', $resource['version']),
            'ros_major' => $major ?: null,
            'board' => $resource['board'],
            'architecture' => $resource['architecture'],
            'capabilities' => [
                'wireguard' => $major >= 7,
                'wifi_new' => (bool) array_intersect(self::NEW_WIFI, $enabled),
                'wireless_legacy' => in_array('wireless', $enabled, true),
                'container' => in_array('container', $enabled, true),
                'lte' => in_array('lte', $enabled, true),
                'ipv6' => $major >= 7 || in_array('ipv6', $enabled, true),
            ],
        ])->save();

        return $router;
    }
}

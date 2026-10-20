<?php

namespace App\Support;

/**
 * Small helpers for RouterOS value formats.
 */
class RouterOs
{
    /** 90 => "1h30m" (RouterOS duration syntax, also accepts weeks). */
    public static function duration(int $minutes): string
    {
        $d = intdiv($minutes, 1440);
        $h = intdiv($minutes % 1440, 60);
        $m = $minutes % 60;

        return ($d ? "{$d}d" : '').($h ? "{$h}h" : '').($m ? "{$m}m" : '') ?: '0s';
    }

    /** (2, 'h') => 120. Unit is m, h or d; empty/zero value means "no limit" (null). */
    public static function toMinutes(?int $value, ?string $unit): ?int
    {
        if (! $value) {
            return null;
        }

        return $value * match ($unit) {
            'd' => 1440,
            'h' => 60,
            default => 1,
        };
    }

    /** 120 => [2, 'h']; picks the largest unit that divides evenly, for pre-filling forms. */
    public static function splitMinutes(?int $minutes): array
    {
        if (! $minutes) {
            return [null, 'h'];
        }

        return match (true) {
            $minutes % 1440 === 0 => [$minutes / 1440, 'd'],
            $minutes % 60 === 0 => [$minutes / 60, 'h'],
            default => [$minutes, 'm'],
        };
    }

    /** True when a RouterOS uptime string shows any elapsed time ("0s" or empty means none). */
    public static function hasElapsed(?string $uptime): bool
    {
        return $uptime !== null && $uptime !== '' && $uptime !== '0s';
    }

    /** 1500 => "1d 1h", 90 => "1h 30m" — for people, not for RouterOS. */
    public static function humanMinutes(?int $minutes): string
    {
        if (! $minutes) {
            return '-';
        }

        $parts = [];
        foreach ([['d', 1440], ['h', 60], ['m', 1]] as [$unit, $size]) {
            if ($minutes >= $size) {
                $parts[] = intdiv($minutes, $size).$unit;
                $minutes %= $size;
            }
        }

        return implode(' ', $parts);
    }

    public static function bytes(float|int|string|null $bytes): string
    {
        $bytes = (float) $bytes;
        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $i => $unit) {
            if ($bytes < 1024 || $unit === 'TB') {
                return ($i === 0 ? (string) (int) $bytes : number_format($bytes, 2)).' '.$unit;
            }
            $bytes /= 1024;
        }

        return '0 B';
    }
}

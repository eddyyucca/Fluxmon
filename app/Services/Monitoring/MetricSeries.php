<?php

namespace App\Services\Monitoring;

use App\Models\Metric;
use Illuminate\Support\Facades\DB;

/**
 * Reads a metric back as chart-ready points, averaged into at most ~$maxPoints buckets.
 * Ranges over a day combine hourly rollups with the raw samples newer than the last rollup.
 */
class MetricSeries
{
    public const RANGES = ['1h' => 3600, '6h' => 21600, '24h' => 86400, '7d' => 604800, '30d' => 2592000];

    /** @return array{times: list<int>, values: list<float>} */
    public function get(int $routerId, string $metric, string $ref, int $rangeSeconds, int $maxPoints = 240, ?int $now = null): array
    {
        $now ??= time();
        $from = $now - $rangeSeconds;
        $cutoff = intdiv($now - 3600, 3600) * 3600;
        $bucket = max(60, (int) (ceil($rangeSeconds / $maxPoints / 60) * 60));

        $query = DB::table('metrics')
            ->where('router_id', $routerId)->where('metric', $metric)->where('ref', $ref)
            ->where('recorded_at', '>=', $from);

        if ($rangeSeconds > 86400) {
            $query->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('resolution', Metric::HOURLY)->where('recorded_at', '<', $cutoff))
                ->orWhere(fn ($q) => $q->where('resolution', Metric::RAW)->where('recorded_at', '>=', $cutoff)));
        } else {
            $query->where('resolution', Metric::RAW);
        }

        $rows = $query
            ->selectRaw("(recorded_at - recorded_at % {$bucket}) as t, avg(value) as v")
            ->groupBy('t')->orderBy('t')->get();

        return ['times' => $rows->pluck('t')->map(fn ($t) => (int) $t)->all(), 'values' => $rows->pluck('v')->map(fn ($v) => round((float) $v, 2))->all()];
    }

    /** Most recent raw value of each series of a metric, keyed by ref. @return array<string,float> */
    public function latestByRef(int $routerId, string $metric, int $withinSeconds = 300): array
    {
        $rows = DB::table('metrics')
            ->where('router_id', $routerId)->where('metric', $metric)->where('resolution', Metric::RAW)
            ->where('recorded_at', '>=', time() - $withinSeconds)
            ->orderBy('recorded_at')->get(['ref', 'value']);

        $out = [];
        foreach ($rows as $row) {
            $out[$row->ref] = (float) $row->value; // later rows overwrite earlier ones
        }

        return $out;
    }

    /** @return list<string> interface names that have traffic data */
    public function interfaces(int $routerId): array
    {
        return DB::table('metrics')->where('router_id', $routerId)->where('metric', 'rx_bps')
            ->where('resolution', Metric::RAW)->where('recorded_at', '>=', time() - 86400)
            ->distinct()->orderBy('ref')->pluck('ref')->all();
    }
}

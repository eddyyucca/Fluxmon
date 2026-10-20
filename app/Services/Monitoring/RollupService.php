<?php

namespace App\Services\Monitoring;

use App\Models\Metric;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the database small: folds minute samples into hourly averages, then drops old data.
 * Safe to run repeatedly; it rebuilds the last 48 complete hours each time.
 */
class RollupService
{
    /** @return array{rolled: int, pruned_raw: int, pruned_hourly: int} */
    public function run(?int $now = null): array
    {
        $now ??= time();
        $cutoff = intdiv($now - 3600, 3600) * 3600; // end of the last complete hour
        $from = $cutoff - 48 * 3600;

        $buckets = DB::table('metrics')
            ->where('resolution', Metric::RAW)
            ->where('recorded_at', '>=', $from)
            ->where('recorded_at', '<', $cutoff)
            ->selectRaw('router_id, metric, ref, (recorded_at - recorded_at % 3600) as bucket, avg(value) as value')
            ->groupBy('router_id', 'metric', 'ref', 'bucket')
            ->get();

        DB::transaction(function () use ($buckets, $from, $cutoff) {
            DB::table('metrics')->where('resolution', Metric::HOURLY)
                ->where('recorded_at', '>=', $from)->where('recorded_at', '<', $cutoff)->delete();

            foreach ($buckets->chunk(300) as $chunk) {
                DB::table('metrics')->insert($chunk->map(fn ($b) => [
                    'router_id' => $b->router_id, 'metric' => $b->metric, 'ref' => $b->ref,
                    'value' => $b->value, 'resolution' => Metric::HOURLY, 'recorded_at' => $b->bucket,
                ])->all());
            }
        });

        $rawKeep = $now - config('fluxmon.metrics.raw_days') * 86400;
        $hourlyKeep = $now - config('fluxmon.metrics.rollup_days') * 86400;

        return [
            'rolled' => $buckets->count(),
            // Raw rows are only dropped once they are old enough to have been rolled up already.
            'pruned_raw' => DB::table('metrics')->where('resolution', Metric::RAW)->where('recorded_at', '<', min($rawKeep, $from))->delete(),
            'pruned_hourly' => DB::table('metrics')->where('resolution', Metric::HOURLY)->where('recorded_at', '<', $hourlyKeep)->delete(),
        ];
    }
}

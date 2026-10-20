<?php

namespace App\Services;

use App\Models\Voucher;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Sales figures. A voucher counts as sold on the moment of its first login.
 *
 * $routers: null = every router, an int = one router, an array = only those routers
 * (an empty array therefore matches nothing).
 */
class ReportService
{
    private function base(CarbonInterface $from, CarbonInterface $to, int|array|null $routers): Builder
    {
        return Voucher::sold()
            ->whereBetween('first_used_at', [$from, $to])
            ->when($routers !== null, fn (Builder $q) => $q->whereIn('vouchers.router_id', (array) $routers));
    }

    /**
     * @return array{revenue: float, count: int, daily: array{labels: list<string>, revenue: list<float>, count: list<int>}, by_profile: list<object>, by_router: list<object>}
     */
    public function summary(CarbonInterface $from, CarbonInterface $to, int|array|null $routers = null): array
    {
        $totals = $this->base($from, $to, $routers)
            ->selectRaw('coalesce(sum(price), 0) as revenue, count(*) as n')->first();

        $perDay = $this->base($from, $to, $routers)
            ->selectRaw('date(first_used_at) as d, sum(price) as revenue, count(*) as n')
            ->groupBy('d')->get()->keyBy('d');

        $daily = ['labels' => [], 'revenue' => [], 'count' => []];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()) as $day) {
            $row = $perDay->get($day->toDateString());
            $daily['labels'][] = $day->format('d M');
            $daily['revenue'][] = (float) ($row->revenue ?? 0);
            $daily['count'][] = (int) ($row->n ?? 0);
        }

        $byProfile = $this->base($from, $to, $routers)
            ->selectRaw('profile, sum(price) as revenue, count(*) as n')
            ->groupBy('profile')->orderByDesc('revenue')->get()->all();

        $byRouter = $this->base($from, $to, $routers)
            ->join('routers', 'routers.id', '=', 'vouchers.router_id')
            ->select('routers.name', DB::raw('sum(vouchers.price) as revenue'), DB::raw('count(*) as n'))
            ->groupBy('routers.id', 'routers.name')->orderByDesc('revenue')->get()->all();

        return [
            'revenue' => (float) $totals->revenue,
            'count' => (int) $totals->n,
            'daily' => $daily,
            'by_profile' => $byProfile,
            'by_router' => $byRouter,
        ];
    }

    /** Raw sold vouchers for CSV export. */
    public function rows(CarbonInterface $from, CarbonInterface $to, int|array|null $routers = null)
    {
        return $this->base($from, $to, $routers)->with('router:id,name')->orderBy('first_used_at')->cursor();
    }
}

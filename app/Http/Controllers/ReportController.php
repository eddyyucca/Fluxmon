<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const PRESETS = ['today', '7d', '30d', 'month', 'last_month', 'custom'];

    public function index(Request $request, ReportService $reports): View|StreamedResponse
    {
        [$from, $to, $filters] = $this->range($request);

        // Only routers this account may see; asking for another one is refused.
        $routers = Router::visibleTo($request->user())->orderBy('name')->get(['id', 'name', 'currency']);
        abort_if($filters['router'] && ! $routers->contains('id', $filters['router']), 404);
        $scope = $filters['router'] ?? $request->user()->limitedRouterIds();

        if ($request->query('export') === 'csv') {
            return $this->export($reports, $from, $to, $scope);
        }

        $currency = $routers->firstWhere('id', $filters['router'])?->currency ?? $routers->first()?->currency ?? 'Rp';

        return view('reports.index', [
            'summary' => $reports->summary($from, $to, $scope),
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'routers' => $routers,
            'currency' => $currency,
            'presets' => self::PRESETS,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: array{range: string, router: ?int, from: ?string, to: ?string}} */
    private function range(Request $request): array
    {
        $f = $request->validate([
            'range' => ['nullable', Rule::in(self::PRESETS)],
            'router' => ['nullable', 'integer', 'exists:routers,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $range = $f['range'] ?? '7d';
        [$from, $to] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            '30d' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'month' => [now()->startOfMonth(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [
                Carbon::parse($f['from'] ?? now()->subDays(6))->startOfDay(),
                Carbon::parse($f['to'] ?? now())->endOfDay(),
            ],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };

        return [$from, $to, [
            'range' => $range,
            'router' => isset($f['router']) ? (int) $f['router'] : null,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]];
    }

    private function export(ReportService $reports, Carbon $from, Carbon $to, int|array|null $scope): StreamedResponse
    {
        return response()->streamDownload(function () use ($reports, $from, $to, $scope) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sold_at', 'router', 'username', 'profile', 'price']);
            foreach ($reports->rows($from, $to, $scope) as $v) {
                fputcsv($out, [$v->first_used_at->format('Y-m-d H:i:s'), $v->router->name, $v->username, $v->profile, $v->price]);
            }
            fclose($out);
        }, 'fluxmon-sales-'.$from->toDateString().'_'.$to->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }
}

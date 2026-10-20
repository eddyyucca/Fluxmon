<?php

namespace App\Http\Controllers;

use App\Models\AlertEvent;
use App\Models\Router;
use App\Services\Monitoring\MetricSeries;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class MonitorController extends Controller
{
    public function show(Router $router, ReportService $reports): View
    {
        return view('monitor.show', [
            'router' => $router,
            'today' => $reports->summary(now()->startOfDay(), now()->endOfDay(), $router->id),
            'month' => $reports->summary(now()->startOfMonth(), now()->endOfDay(), $router->id),
            'alerts' => AlertEvent::firing()->where('router_id', $router->id)->latest('started_at')->get(),
        ]);
    }

    /** Historical charts page. */
    public function charts(Router $router, MetricSeries $series): View
    {
        $rates = $series->latestByRef($router->id, 'rx_bps');
        $tx = $series->latestByRef($router->id, 'tx_bps');

        return view('monitor.charts', [
            'router' => $router,
            'ranges' => array_keys(MetricSeries::RANGES),
            'interfaces' => $series->interfaces($router->id),
            'rates' => array_map(fn ($rx, $name) => ['name' => $name, 'rx' => $rx, 'tx' => $tx[$name] ?? 0.0], $rates, array_keys($rates)),
        ]);
    }

    /** All series for the charts page in one response. */
    public function metrics(Request $request, Router $router, MetricSeries $series): JsonResponse
    {
        $data = $request->validate([
            'range' => ['required', Rule::in(array_keys(MetricSeries::RANGES))],
            'interface' => ['nullable', 'string', 'max:60'],
        ]);
        $range = MetricSeries::RANGES[$data['range']];
        $interface = $data['interface'] ?? '';

        return response()->json([
            'cpu' => $series->get($router->id, 'cpu', '', $range),
            'memory' => $series->get($router->id, 'memory', '', $range),
            'temperature' => $series->get($router->id, 'temperature', '', $range),
            'hotspot_active' => $series->get($router->id, 'hotspot_active', '', $range),
            'rx' => $interface ? $series->get($router->id, 'rx_bps', $interface, $range) : ['times' => [], 'values' => []],
            'tx' => $interface ? $series->get($router->id, 'tx_bps', $interface, $range) : ['times' => [], 'values' => []],
        ]);
    }

    public function interfaces(Router $router): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'interfaces' => $this->gateway($router)->interfaces()]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function traffic(Request $request, Router $router): JsonResponse
    {
        $interface = $request->validate(['interface' => ['required', 'string', 'max:60']])['interface'];

        try {
            return response()->json(['ok' => true] + $this->gateway($router)->traffic($interface));
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}

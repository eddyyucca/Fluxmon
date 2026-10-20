<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

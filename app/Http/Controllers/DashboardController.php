<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Router;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(ReportService $reports): View
    {
        $today = $reports->summary(now()->startOfDay(), now()->endOfDay());
        $month = $reports->summary(now()->startOfMonth(), now()->endOfDay());
        $trend = $reports->summary(now()->subDays(13)->startOfDay(), now()->endOfDay());

        return view('dashboard', [
            'routers' => Router::orderBy('name')->get(),
            'today' => $today,
            'month' => $month,
            'trend' => $trend['daily'],
            'recent' => AuditLog::with(['user', 'router'])->latest('created_at')->limit(8)->get(),
            'currency' => Router::value('currency') ?? 'Rp',
        ]);
    }

    /** Live stats for one router, polled by the dashboard and overview cards. */
    public function stats(Router $router): JsonResponse
    {
        try {
            $gw = $this->gateway($router);

            return response()->json([
                'ok' => true,
                ...$gw->resource(),
                'hotspot_active' => count($gw->hotspotActive()),
                'hotspot_users' => count($gw->hotspotUsers()),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}

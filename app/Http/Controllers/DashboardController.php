<?php

namespace App\Http\Controllers;

use App\Models\AlertEvent;
use App\Models\AuditLog;
use App\Models\Router;
use App\Services\DeviceAccess;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $user = $request->user();
        $routers = Router::visibleTo($user)->orderBy('name')->get();

        // Devices whose live stats this person may load: all of them for superadmins and
        // administrators, only the ones staff have signed in to for everyone else.
        $unlocked = $user->seesAllDevices() ? $routers->pluck('id')->all() : array_values(array_intersect(DeviceAccess::unlockedIds(), $routers->pluck('id')->all()));

        // The company-wide numbers are the superadmin's single combined report.
        $combined = $user->isSuperadmin();
        $summary = $combined ? [
            'today' => $reports->summary(now()->startOfDay(), now()->endOfDay()),
            'month' => $reports->summary(now()->startOfMonth(), now()->endOfDay()),
            'trend' => $reports->summary(now()->subDays(13)->startOfDay(), now()->endOfDay())['daily'],
        ] : null;

        $alertScope = $user->permittedRouterIds('monitor.view');

        return view('dashboard', [
            'routers' => $routers,
            'unlocked' => $unlocked,
            'combined' => $combined,
            'today' => $summary['today'] ?? null,
            'month' => $summary['month'] ?? null,
            'trend' => $summary['trend'] ?? null,
            'alerts' => AlertEvent::firing()->with('router:id,name')
                ->whereIn('router_id', $alertScope ?? $routers->pluck('id'))->latest('started_at')->limit(5)->get(),
            'recent' => $user->hasPermission('audit.view')
                ? AuditLog::with(['user', 'router'])->latest('created_at')->limit(8)->get()
                : collect(),
            'currency' => $routers->first()?->currency ?? 'Rp',
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

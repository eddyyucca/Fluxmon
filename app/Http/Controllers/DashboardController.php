<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\Mikrotik\MikrotikClient;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', ['routers' => Router::orderBy('name')->get()]);
    }

    /** Live stats for one router, polled by the dashboard cards. */
    public function stats(Router $router): JsonResponse
    {
        try {
            $mt = new MikrotikClient($router);
            $res = $mt->systemResource();

            $total = (int) ($res['total-memory'] ?? 0);
            $free = (int) ($res['free-memory'] ?? 0);

            return response()->json([
                'ok' => true,
                'board' => $res['board-name'] ?? '-',
                'version' => $res['version'] ?? '-',
                'uptime' => $res['uptime'] ?? '-',
                'cpu' => (int) ($res['cpu-load'] ?? 0),
                'memory' => $total ? (int) round(($total - $free) / $total * 100) : 0,
                'hotspot_active' => $mt->hotspotActiveCount(),
                'hotspot_users' => $mt->hotspotUserCount(),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\RouterRequest;
use App\Models\AuditLog;
use App\Models\Router;
use App\Services\CapabilityDetector;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class RouterController extends Controller
{
    public function index(): View
    {
        return view('routers.index', ['routers' => Router::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('routers.form', [
            'router' => new Router(['port' => 8728, 'ssh_port' => 22, 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]),
        ]);
    }

    public function store(RouterRequest $request): RedirectResponse
    {
        $router = Router::create($request->validated());
        AuditLog::record('router.created', $router->name, $router);

        return redirect()->route('r.overview', $router)->with('status', __('Router added.'));
    }

    public function edit(Router $router): View
    {
        return view('routers.form', compact('router'));
    }

    public function update(RouterRequest $request, Router $router): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $router->update($data);
        AuditLog::record('router.updated', $router->name, $router);

        return redirect()->route('routers.index')->with('status', __('Router updated.'));
    }

    public function destroy(Router $router): RedirectResponse
    {
        AuditLog::record('router.deleted', $router->name);
        $router->delete();

        return redirect()->route('routers.index')->with('status', __('Router removed.'));
    }

    public function test(Router $router, CapabilityDetector $detector): JsonResponse
    {
        try {
            $gateway = $this->gateway($router);
            $res = $gateway->resource();
            $router->forceFill(['is_online' => true, 'last_checked_at' => now(), 'last_connected_at' => now()])->save();

            try {
                $detector->detect($router, $gateway); // refresh version and capabilities while connected
            } catch (Throwable) {
                // A read-only API user may not be allowed to list packages; the connection itself works.
            }

            return response()->json(['ok' => true, 'board' => $res['board'], 'version' => $res['version'], 'uptime' => $res['uptime']]);
        } catch (Throwable $e) {
            $router->forceFill(['is_online' => false, 'last_checked_at' => now()])->save();

            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    /** Manual "sync now" from a router page. */
    public function sync(Router $router, SyncService $sync): RedirectResponse
    {
        $r = $sync->syncRouter($router);

        return $r['ok']
            ? back()->with('status', __('Synced: :used newly used, :expired expired.', ['used' => $r['used'], 'expired' => $r['expired']]))
            : back()->with('error', $r['error']);
    }
}

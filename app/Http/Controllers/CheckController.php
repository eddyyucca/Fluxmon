<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Check;
use App\Models\Router;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckController extends Controller
{
    public function index(Router $router): View
    {
        return view('checks.index', [
            'router' => $router,
            'checks' => Check::where('router_id', $router->id)->orderBy('name')->get(),
            'types' => Check::TYPES,
        ]);
    }

    public function store(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(array_keys(Check::TYPES))],
            'target' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9.\-:_]+$/'],
            'port' => ['required_if:type,tcp', 'nullable', 'integer', 'between:1,65535'],
        ]);

        $check = $router->checks()->create($data + ['enabled' => true]);
        AuditLog::record('check.created', $check->name, $router);

        return back()->with('status', __('Check added. It runs on the next sync.'));
    }

    public function toggle(Router $router, Check $check): RedirectResponse
    {
        abort_unless($check->router_id === $router->id, 404);
        $check->update(['enabled' => ! $check->enabled]);

        return back();
    }

    public function destroy(Router $router, Check $check): RedirectResponse
    {
        abort_unless($check->router_id === $router->id, 404);
        AuditLog::record('check.deleted', $check->name, $router);
        $check->delete();

        return back()->with('status', __('Check removed.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AlertEvent;
use App\Models\AlertRule;
use App\Models\AuditLog;
use App\Models\Router;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function index(Request $request): View
    {
        $routers = Router::visibleTo($request->user())->orderBy('name')->get(['id', 'name']);
        $ids = $routers->pluck('id');

        return view('alerts.index', [
            'routers' => $routers,
            'metrics' => AlertRule::METRICS,
            'rules' => AlertRule::with('router:id,name')
                ->where(fn ($q) => $q->whereNull('router_id')->orWhereIn('router_id', $ids))->orderBy('name')->get(),
            'events' => AlertEvent::with('router:id,name')->whereIn('router_id', $ids)
                ->orderByRaw("case state when 'firing' then 0 else 1 end")->latest('started_at')->limit(50)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'router_id' => ['nullable', 'integer', 'exists:routers,id'],
            'metric' => ['required', Rule::in(array_keys(AlertRule::METRICS))],
            'operator' => ['required', Rule::in(['>', '<'])],
            'threshold' => ['required', 'numeric'],
            'duration_minutes' => ['required', 'integer', 'between:1,120'],
        ]);

        $this->assertRouterAllowed($request, $data['router_id'] ?? null);
        $rule = AlertRule::create($data + ['enabled' => true]);
        AuditLog::record('alert_rule.created', $rule->name);

        return back()->with('status', __('Alert rule created.'));
    }

    /** One click to get sensible defaults. */
    public function defaults(Request $request): RedirectResponse
    {
        // The defaults apply to every router, so a router-limited account may not create them.
        abort_unless($request->user()->limitedRouterIds() === null, 403);

        $made = 0;
        foreach ([
            ['High CPU', 'cpu', '>', 90, 5],
            ['High memory', 'memory', '>', 90, 5],
            ['High temperature', 'temperature', '>', 75, 5],
        ] as [$name, $metric, $op, $threshold, $minutes]) {
            $made += AlertRule::firstOrCreate(
                ['router_id' => null, 'metric' => $metric, 'operator' => $op],
                ['name' => $name, 'threshold' => $threshold, 'duration_minutes' => $minutes, 'enabled' => true],
            )->wasRecentlyCreated ? 1 : 0;
        }

        return back()->with('status', __(':count rule(s) added.', ['count' => $made]));
    }

    public function toggle(Request $request, AlertRule $rule): RedirectResponse
    {
        $this->assertRouterAllowed($request, $rule->router_id);
        $rule->update(['enabled' => ! $rule->enabled]);

        return back();
    }

    public function destroy(Request $request, AlertRule $rule): RedirectResponse
    {
        $this->assertRouterAllowed($request, $rule->router_id);
        AuditLog::record('alert_rule.deleted', $rule->name);
        $rule->delete();

        return back()->with('status', __('Alert rule removed.'));
    }

    /** Accounts limited to some routers may only manage rules of those routers. */
    private function assertRouterAllowed(Request $request, ?int $routerId): void
    {
        $limited = $request->user()->limitedRouterIds();
        if ($limited !== null) {
            abort_unless($routerId !== null && in_array($routerId, $limited, true), 403);
        }
    }
}

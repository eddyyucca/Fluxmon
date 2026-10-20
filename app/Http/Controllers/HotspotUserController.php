<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Router;
use App\Support\RouterOs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class HotspotUserController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request, Router $router): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'profile' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['online', 'disabled', 'unused'])],
        ]);

        $error = null;
        $profiles = [];
        $users = collect();

        try {
            $gw = $this->gateway($router);
            $profiles = array_column($gw->profiles(), 'name');
            $online = array_flip(array_column($gw->hotspotActive(), 'user'));

            $users = collect($gw->hotspotUsers())
                ->reject(fn ($u) => $u['name'] === 'default-trial')
                ->map(fn ($u) => $u + ['online' => isset($online[$u['name']])])
                ->when($filters['q'] ?? null, fn ($c, $q) => $c->filter(
                    fn ($u) => stripos($u['name'], $q) !== false || stripos($u['comment'], $q) !== false
                ))
                ->when($filters['profile'] ?? null, fn ($c, $p) => $c->where('profile', $p))
                ->when($filters['status'] ?? null, fn ($c, $s) => match ($s) {
                    'online' => $c->where('online', true),
                    'disabled' => $c->where('disabled', true),
                    'unused' => $c->filter(fn ($u) => ! RouterOs::hasElapsed($u['uptime']) && $u['bytes_in'] + $u['bytes_out'] === 0),
                })
                ->values();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $users->forPage($page, self::PER_PAGE)->values(),
            $users->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('hotspot.users', compact('router', 'paginator', 'profiles', 'filters', 'error'));
    }

    public function store(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^\S+$/'],
            'password' => ['required', 'string', 'max:64'],
            'profile' => ['required', 'string', 'max:100'],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'time_limit_unit' => ['nullable', Rule::in(['m', 'h', 'd'])],
            'data_limit_mb' => ['nullable', 'integer', 'min:1'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $attributes = ['name' => $data['name'], 'password' => $data['password'], 'profile' => $data['profile'], 'comment' => $data['comment'] ?? null];
        if ($minutes = RouterOs::toMinutes($data['time_limit'] ?? null, $data['time_limit_unit'] ?? null)) {
            $attributes['limit-uptime'] = RouterOs::duration($minutes);
        }
        if (! empty($data['data_limit_mb'])) {
            $attributes['limit-bytes-total'] = $data['data_limit_mb'] * 1048576;
        }

        return $this->attempt(function () use ($router, $attributes, $data) {
            $this->gateway($router)->addHotspotUser($attributes);
            AuditLog::record('user.created', $data['name'], $router);
        }, __('User :name created.', ['name' => $data['name']]));
    }

    public function bulk(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['enable', 'disable', 'reset', 'delete'])],
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['string', 'max:32'],
        ]);

        return $this->attempt(function () use ($router, $data) {
            $gw = $this->gateway($router);
            foreach ($data['ids'] as $id) {
                match ($data['action']) {
                    'enable' => $gw->setHotspotUser($id, ['disabled' => 'no']),
                    'disable' => $gw->setHotspotUser($id, ['disabled' => 'yes']),
                    'reset' => $gw->resetHotspotUser($id),
                    'delete' => $gw->removeHotspotUser($id),
                };
            }
            AuditLog::record('user.'.$data['action'], count($data['ids']).' user(s)', $router);
        }, __(':count user(s) updated.', ['count' => count($data['ids'])]));
    }
}

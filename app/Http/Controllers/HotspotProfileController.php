<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProfileMeta;
use App\Models\Router;
use App\Support\RouterOs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class HotspotProfileController extends Controller
{
    public function index(Router $router): View
    {
        $error = null;
        $profiles = [];

        try {
            $metas = ProfileMeta::where('router_id', $router->id)->get()->keyBy('name');
            $profiles = array_map(function (array $p) use ($metas) {
                $meta = $metas->get($p['name']);
                [$value, $unit] = RouterOs::splitMinutes($meta?->validity_minutes);

                return $p + [
                    'price' => $meta?->price ?? 0,
                    'validity_minutes' => $meta?->validity_minutes,
                    'validity_value' => $value,
                    'validity_unit' => $unit,
                    'expire_action' => $meta?->expire_action ?? 'remove',
                ];
            }, $this->gateway($router)->profiles());
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return view('hotspot.profiles', compact('router', 'profiles', 'error'));
    }

    public function store(Request $request, Router $router): RedirectResponse
    {
        $data = $this->validated($request);

        return $this->attempt(function () use ($router, $data) {
            $this->gateway($router)->addProfile($this->routerAttributes($data));
            $this->saveMeta($router, $data['name'], $data);
            AuditLog::record('profile.created', $data['name'], $router);
        }, __('Profile :name created.', ['name' => $data['name']]));
    }

    public function update(Request $request, Router $router, string $id): RedirectResponse
    {
        $data = $this->validated($request) + ['old_name' => $request->input('old_name')];

        return $this->attempt(function () use ($router, $id, $data) {
            $this->gateway($router)->setProfile($id, $this->routerAttributes($data));

            if ($data['old_name'] && $data['old_name'] !== $data['name']) {
                ProfileMeta::where('router_id', $router->id)->where('name', $data['old_name'])->delete();
            }
            $this->saveMeta($router, $data['name'], $data);
            AuditLog::record('profile.updated', $data['name'], $router);
        }, __('Profile :name saved.', ['name' => $data['name']]));
    }

    public function destroy(Request $request, Router $router, string $id): RedirectResponse
    {
        $name = (string) $request->input('name');

        return $this->attempt(function () use ($router, $id, $name) {
            $this->gateway($router)->removeProfile($id);
            ProfileMeta::where('router_id', $router->id)->where('name', $name)->delete();
            AuditLog::record('profile.deleted', $name, $router);
        }, __('Profile :name removed.', ['name' => $name]));
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'regex:/^\S+$/'],
            'rate_limit' => ['nullable', 'string', 'max:100'],
            'shared_users' => ['required', 'integer', 'between:1,999'],
            'price' => ['required', 'numeric', 'min:0'],
            'validity_value' => ['nullable', 'integer', 'min:1'],
            'validity_unit' => ['nullable', Rule::in(['m', 'h', 'd'])],
            'expire_action' => ['required', Rule::in(['remove', 'disable'])],
        ]);

        $data['validity_minutes'] = RouterOs::toMinutes($data['validity_value'] ?? null, $data['validity_unit'] ?? null);

        return $data;
    }

    private function routerAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'rate-limit' => $data['rate_limit'] ?? '',
            'shared-users' => $data['shared_users'],
        ];
    }

    private function saveMeta(Router $router, string $name, array $data): void
    {
        ProfileMeta::updateOrCreate(
            ['router_id' => $router->id, 'name' => $name],
            ['price' => $data['price'], 'validity_minutes' => $data['validity_minutes'], 'expire_action' => $data['expire_action']],
        );
    }
}

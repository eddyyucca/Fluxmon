<?php

namespace App\Http\Controllers;

use App\Http\Requests\RouterRequest;
use App\Models\Router;
use App\Services\Mikrotik\MikrotikClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RouterController extends Controller
{
    public function index(): View
    {
        return view('routers.index', ['routers' => Router::orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('routers.form', [
            'router' => new Router(['port' => 8728, 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]),
        ]);
    }

    public function store(RouterRequest $request): RedirectResponse
    {
        Router::create($request->validated());

        return redirect()->route('routers.index')->with('status', 'Router added.');
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

        return redirect()->route('routers.index')->with('status', 'Router updated.');
    }

    public function destroy(Router $router): RedirectResponse
    {
        $router->delete();

        return redirect()->route('routers.index')->with('status', 'Router removed.');
    }

    public function test(Router $router): JsonResponse
    {
        return response()->json((new MikrotikClient($router))->test());
    }
}

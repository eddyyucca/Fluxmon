<?php

namespace App\Http\Controllers;

use App\Models\Router;
use App\Services\Mikrotik\Gateway;
use App\Services\Mikrotik\MikrotikFactory;
use Illuminate\Http\RedirectResponse;
use Throwable;

abstract class Controller
{
    protected function gateway(Router $router): Gateway
    {
        return app(MikrotikFactory::class)->make($router);
    }

    /**
     * Run a router action; flash a success message, or the router's error.
     */
    protected function attempt(callable $action, string $success): RedirectResponse
    {
        try {
            $action();

            return back()->with('status', $success);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}

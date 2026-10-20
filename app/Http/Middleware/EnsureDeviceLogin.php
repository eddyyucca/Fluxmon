<?php

namespace App\Http\Middleware;

use App\Models\Router;
use App\Services\DeviceAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff must sign in to a device before opening it. Superadmins and administrators work on every
 * device without a second login.
 */
class EnsureDeviceLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $router = $request->route('router');
        $user = $request->user();

        if (! $router instanceof Router || $user?->seesAllDevices()) {
            return $next($request);
        }

        // Pages the browser polls in the background (JSON GETs) do not count as activity.
        $polling = $request->isMethod('GET') && $request->expectsJson();

        if (DeviceAccess::refresh($router, touch: ! $polling)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => false,
                'message' => __('Sign in to this device first.'),
                'login_url' => route('r.login', $router),
            ], 403);
        }

        // Remember where they were going so the device login can send them there.
        return redirect()->guest(route('r.login', $router));
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Router;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $router = $request->route('router');

        // On a device page the right must hold on that device; elsewhere on at least one device
        // (staff reach company-wide pages such as reports only through a device they have signed in to).
        $allowed = $router instanceof Router
            ? $user?->hasPermission($permission, $router)
            : ($user?->hasPermission($permission) || $user?->canAnywhere($permission));

        abort_unless($allowed, 403, __('Your role does not allow this.'));

        return $next($request);
    }
}

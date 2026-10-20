<?php

namespace App\Http\Middleware;

use App\Models\Router;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks accounts that are limited to specific routers from opening any other router. */
class EnsureRouterAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $router = $request->route('router');

        if ($router instanceof Router) {
            // 404 rather than 403: do not reveal that the router exists.
            abort_unless($request->user()?->canAccessRouter($router), 404);
        }

        return $next($request);
    }
}

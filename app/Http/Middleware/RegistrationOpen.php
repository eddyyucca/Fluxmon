<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public registration exists only to create the first admin. After that,
 * admins add teammates from Settings.
 */
class RegistrationOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (User::exists()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}

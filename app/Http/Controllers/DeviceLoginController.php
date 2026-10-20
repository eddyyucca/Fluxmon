<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Router;
use App\Models\RouterAccount;
use App\Services\DeviceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/** The second login: a device account for one specific router. */
class DeviceLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function show(Request $request, Router $router): View|RedirectResponse
    {
        if ($request->user()->seesAllDevices() || DeviceAccess::entry($router)) {
            return redirect()->route('r.overview', $router);
        }

        return view('devices.login', compact('router'));
    }

    public function store(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        // Per person and device, so guessing one device's password does not lock out the others.
        $key = 'device-login:'.$request->user()->id.':'.$router->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors(['username' => __('Too many attempts. Try again in :seconds seconds.', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        $account = RouterAccount::where('router_id', $router->id)->where('username', $data['username'])->where('active', true)->first();

        // Always run a hash check so a wrong username takes as long as a wrong password.
        $valid = Hash::check($data['password'], $account?->password ?? Hash::make(str()->random(16)));

        if (! $account || ! $valid) {
            RateLimiter::hit($key, 60);
            AuditLog::record('device.login_failed', $data['username'], $router);

            return back()->withInput($request->only('username'))->withErrors(['username' => __('These device credentials do not match.')]);
        }

        RateLimiter::clear($key);
        $account->forceFill(['last_login_at' => now()])->save();
        DeviceAccess::login($router, $account);
        AuditLog::record('device.login', $account->name, $router);

        return redirect()->intended(route('r.overview', $router));
    }

    public function destroy(Router $router): RedirectResponse
    {
        AuditLog::record('device.logout', DeviceAccess::accountName($router), $router);
        DeviceAccess::logout($router);

        return redirect()->route('dashboard');
    }
}

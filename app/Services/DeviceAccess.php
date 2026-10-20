<?php

namespace App\Services;

use App\Models\Router;
use App\Models\RouterAccount;

/**
 * The second login. Signing in to a device stores which device account was used in the session;
 * that account's role decides what staff may do there, until it expires after a period of inactivity.
 */
class DeviceAccess
{
    private const KEY = 'device_access';

    public static function login(Router $router, RouterAccount $account): void
    {
        session()->put(self::KEY.'.'.$router->id, [
            'account_id' => $account->id,
            'name' => $account->name,
            'role' => $account->role,
            'at' => now()->timestamp,
        ]);
    }

    /** @return array{account_id:int,name:string,role:string,at:int}|null null when not signed in or expired */
    public static function entry(Router|int $router): ?array
    {
        $id = $router instanceof Router ? $router->id : $router;
        $entry = session()->get(self::KEY.'.'.$id);

        if (! $entry) {
            return null;
        }

        if (now()->timestamp - $entry['at'] > config('fluxmon.device_session_minutes') * 60) {
            self::logout($id);

            return null;
        }

        return $entry;
    }

    /**
     * Re-read the account on every request: a disabled or deleted account is signed out at once,
     * and a changed role applies immediately instead of at the next sign-in.
     * `$touch` says whether this counts as the person being active.
     */
    public static function refresh(Router|int $router, bool $touch = true): ?array
    {
        $entry = self::entry($router);
        if (! $entry) {
            return null;
        }

        $account = RouterAccount::where('id', $entry['account_id'])->where('active', true)->first();
        if (! $account) {
            self::logout($router);

            return null;
        }

        $id = $router instanceof Router ? $router->id : $router;
        session()->put(self::KEY.'.'.$id, [
            'account_id' => $account->id, 'name' => $account->name, 'role' => $account->role,
            // Background polling must not keep a forgotten browser tab signed in forever.
            'at' => $touch ? now()->timestamp : $entry['at'],
        ]);

        return session()->get(self::KEY.'.'.$id);
    }

    public static function role(Router|int $router): ?string
    {
        return self::entry($router)['role'] ?? null;
    }

    public static function accountName(Router|int $router): ?string
    {
        return self::entry($router)['name'] ?? null;
    }

    public static function logout(Router|int $router): void
    {
        $id = $router instanceof Router ? $router->id : $router;
        session()->forget(self::KEY.'.'.$id);
    }

    /** @return list<int> ids of devices currently signed in to */
    public static function unlockedIds(): array
    {
        return collect(array_keys((array) session()->get(self::KEY, [])))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => self::refresh($id, touch: false) !== null)
            ->values()->all();
    }
}

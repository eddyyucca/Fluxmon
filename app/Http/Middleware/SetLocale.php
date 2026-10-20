<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language for the request.
 *
 * Order: the signed-in user's saved choice, the choice made on this browser (session, then cookie
 * so the sign-in page remembers it), the default language set by an admin, then English.
 */
class SetLocale
{
    public const SUPPORTED = ['en' => 'English', 'id' => 'Bahasa Indonesia'];

    public const COOKIE = 'fluxmon_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        App::setLocale($locale);
        Carbon::setLocale($locale); // month names and "2 hours ago" follow the language

        return $next($request);
    }

    public static function valid(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::SUPPORTED);
    }

    private function resolve(Request $request): string
    {
        $candidates = [
            $request->user()?->locale,
            $request->hasSession() ? $request->session()->get('locale') : null,
            $request->cookie(self::COOKIE),
            Schema::hasTable('settings') ? Setting::read('locale') : null,
        ];

        foreach ($candidates as $candidate) {
            if (self::valid($candidate)) {
                return $candidate;
            }
        }

        return 'en';
    }
}

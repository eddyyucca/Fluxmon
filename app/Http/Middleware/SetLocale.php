<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED = ['en' => 'English', 'id' => 'Bahasa Indonesia'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = Schema::hasTable('settings') ? Setting::read('locale', 'en') : 'en';
        App::setLocale(array_key_exists($locale, self::SUPPORTED) ? $locale : 'en');

        return $next($request);
    }
}

<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

/**
 * Background jobs (sync, daily report) have no browser to ask, so they speak the admin's default language.
 */
class DefaultLocale
{
    public static function apply(): string
    {
        $locale = Schema::hasTable('settings') ? Setting::read('locale') : null;
        $locale = SetLocale::valid($locale) ? $locale : 'en';

        App::setLocale($locale);
        Carbon::setLocale($locale);

        return $locale;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

/** Lets anyone, signed in or not, switch the interface language for themselves. */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->validate([
            'locale' => ['required', Rule::in(array_keys(SetLocale::SUPPORTED))],
        ])['locale'];

        $request->session()->put('locale', $locale);
        Cookie::queue(SetLocale::COOKIE, $locale, 60 * 24 * 365);

        // A signed-in person keeps the choice on every device.
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back();
    }
}

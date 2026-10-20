<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'locale' => Setting::read('locale', 'en'),
            'locales' => SetLocale::SUPPORTED,
            'telegram' => [
                'configured' => (bool) Setting::readSecret('telegram_token'),
                'chat_id' => Setting::read('telegram_chat_id'),
                'daily_report' => Setting::read('telegram_daily_report') === '1',
                'offline_alert' => Setting::read('telegram_offline_alert') === '1',
            ],
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function updateLocale(Request $request): RedirectResponse
    {
        $data = $request->validate(['locale' => ['required', Rule::in(array_keys(SetLocale::SUPPORTED))]]);
        Setting::write('locale', $data['locale']);

        return back()->with('status', __('Language saved.'));
    }

    public function updateTelegram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:200'],
            'chat_id' => ['nullable', 'string', 'max:100'],
            'daily_report' => ['boolean'],
            'offline_alert' => ['boolean'],
        ]);

        // A blank token field keeps the stored one.
        if (! empty($data['token'])) {
            Setting::writeSecret('telegram_token', $data['token']);
        }
        Setting::write('telegram_chat_id', $data['chat_id'] ?? null);
        Setting::write('telegram_daily_report', $request->boolean('daily_report') ? '1' : '0');
        Setting::write('telegram_offline_alert', $request->boolean('offline_alert') ? '1' : '0');
        AuditLog::record('settings.telegram');

        return back()->with('status', __('Telegram settings saved.'));
    }

    public function testTelegram(Telegram $telegram): RedirectResponse
    {
        $error = $telegram->send('✅ Fluxmon test message');

        return $error ? back()->with('error', $error) : back()->with('status', __('Test message sent.'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);

        $user = User::create([...$data, 'password' => Hash::make($data['password'])]);
        AuditLog::record('admin.created', $user->email);

        return back()->with('status', __('Admin :name added.', ['name' => $user->name]));
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        AuditLog::record('admin.deleted', $user->email);
        $user->delete();

        return back()->with('status', __('Admin removed.'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\AuditLog;
use App\Models\Router;
use App\Models\Setting;
use App\Models\User;
use App\Services\Alerting\Notifier;
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
            'webhook_url' => Setting::read('webhook_url'),
            'alert_email' => Setting::read('alert_email'),
            'users' => User::with('routers:id,name')->orderBy('name')->get(),
            'routers' => Router::orderBy('name')->get(['id', 'name']),
            'roles' => User::ROLES,
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

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'webhook_url' => ['nullable', 'url', 'max:500'],
            'alert_email' => ['nullable', 'string', 'max:500'],
        ]);

        $emails = array_filter(array_map('trim', explode(',', (string) ($data['alert_email'] ?? ''))));
        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return back()->withInput()->with('error', __(':email is not a valid email address.', ['email' => $email]));
            }
        }

        Setting::write('webhook_url', $data['webhook_url'] ?? null);
        Setting::write('alert_email', implode(', ', $emails) ?: null);
        AuditLog::record('settings.notifications');

        return back()->with('status', __('Notification settings saved.'));
    }

    public function testNotifications(Notifier $notifier): RedirectResponse
    {
        $notifier->send('✅ Fluxmon test alert', 'If you can read this, alerts reach this channel.');

        return back()->with('status', __('Test alert sent to every configured channel.'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
            'router_ids' => ['nullable', 'array'],
            'router_ids.*' => ['integer', 'exists:routers,id'],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $user->forceFill(['role' => $data['role'], 'all_routers' => empty($data['router_ids'])])->save();
        $user->routers()->sync($data['router_ids'] ?? []);
        AuditLog::record('admin.created', "{$user->email} ({$user->role})");

        return back()->with('status', __('Account :name added.', ['name' => $user->name]));
    }

    /** Change a teammate's role or which routers they can see. */
    public function updateUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('You cannot change your own role.'));
        }

        $data = $request->validate([
            'role' => ['required', Rule::in(User::ROLES)],
            'router_ids' => ['nullable', 'array'],
            'router_ids.*' => ['integer', 'exists:routers,id'],
        ]);

        $user->forceFill(['role' => $data['role'], 'all_routers' => empty($data['router_ids'])])->save();
        $user->routers()->sync($data['router_ids'] ?? []);
        AuditLog::record('admin.updated', "{$user->email} ({$user->role})");

        return back()->with('status', __('Account updated.'));
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }

        AuditLog::record('admin.deleted', $user->email);
        $user->delete();

        return back()->with('status', __('Account removed.'));
    }
}

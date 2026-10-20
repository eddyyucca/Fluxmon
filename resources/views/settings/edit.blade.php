<x-app-layout>
    <x-slot name="header">{{ __('Settings') }}</x-slot>

    @php
        $roleHelp = [
            'superadmin' => __('Everything: every device without a second login, one combined report, routers, settings and accounts.'),
            'administrator' => __('Every device without a second login, one device at a time, and its device accounts. No settings, no adding routers, no combined report.'),
            'staff' => __('Signs in to Fluxmon, then to each device with that device\'s own account. What they can do there depends on that account.'),
        ];
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        {{-- About --}}
        <div class="card card-pad flex flex-col gap-4 sm:flex-row sm:items-center">
            <x-application-logo class="h-12 w-12 shrink-0" />
            <div class="min-w-0 flex-1">
                <h3 class="card-title">Fluxmon</h3>
                <p class="muted mt-0.5">
                    {{ __('Developed by') }}
                    <a href="{{ config('fluxmon.developer.url') }}" target="_blank" rel="noopener" class="font-medium text-indigo-600 hover:underline dark:text-cyan-400">{{ config('fluxmon.developer.name') }}</a>
                    · {{ config('fluxmon.developer.tagline') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                <a href="{{ config('fluxmon.developer.url') }}" target="_blank" rel="noopener" class="shrink-0"><x-fluxa-badge class="!h-8" /></a>
                <a href="mailto:{{ config('fluxmon.developer.email') }}" class="btn-secondary btn-sm break-all">{{ config('fluxmon.developer.email') }}</a>
            </div>
        </div>

        {{-- Language --}}
        <form method="POST" action="{{ route('settings.locale') }}" class="card card-pad flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf @method('PUT')
            <div class="min-w-0 flex-1">
                <h3 class="card-title">{{ __('Default language') }}</h3>
                <p class="muted mt-1">{{ __('Used for anyone who has not chosen their own with the EN / ID switch at the top of the page.') }}</p>
            </div>
            <select name="locale" class="input !w-52">
                @foreach ($locales as $code => $name) <option value="{{ $code }}" @selected($locale === $code)>{{ $name }}</option> @endforeach
            </select>
            <button class="btn-primary">{{ __('Save') }}</button>
        </form>

        {{-- Telegram --}}
        <div class="card card-pad">
            <h3 class="card-title">Telegram</h3>
            <p class="muted mt-1">{{ __('Get a daily sales report and alerts. Create a bot with @BotFather, then message it once and use your chat ID.') }}</p>

            <form method="POST" action="{{ route('settings.telegram') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <div>
                    <label class="label">{{ __('Bot token') }}</label>
                    <input type="password" name="token" class="input" autocomplete="off" placeholder="{{ $telegram['configured'] ? '••••••••  ('.__('saved').')' : '123456:ABC…' }}">
                </div>
                <div>
                    <label class="label">{{ __('Chat ID') }}</label>
                    <input name="chat_id" value="{{ old('chat_id', $telegram['chat_id']) }}" class="input" placeholder="123456789">
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="daily_report" value="1" @checked($telegram['daily_report']) class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Daily report at 23:55') }}</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="offline_alert" value="1" @checked($telegram['offline_alert']) class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Send alerts to Telegram') }}</label>
                <div class="flex gap-2 sm:col-span-2"><button class="btn-primary">{{ __('Save') }}</button></div>
            </form>

            @if ($telegram['configured'])
                <form method="POST" action="{{ route('settings.telegram.test') }}" class="mt-2">@csrf
                    <button class="btn-secondary btn-sm">{{ __('Send test message') }}</button>
                </form>
            @endif
        </div>

        {{-- Other alert channels --}}
        <div class="card card-pad">
            <h3 class="card-title">{{ __('Other alert channels') }}</h3>
            <p class="muted mt-1">{{ __('Alerts (router offline, failed checks, threshold rules) are sent to every channel configured here and to Telegram.') }}</p>
            <form method="POST" action="{{ route('settings.notifications') }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                @csrf @method('PUT')
                <div>
                    <label class="label">{{ __('Webhook URL') }}</label>
                    <input type="url" name="webhook_url" value="{{ old('webhook_url', $webhook_url) }}" placeholder="https://example.com/hook" class="input">
                    <p class="mt-1 text-xs text-slate-500">{{ __('Receives a JSON POST: title, message, router, state.') }}</p>
                </div>
                <div>
                    <label class="label">{{ __('Email recipients') }}</label>
                    <input name="alert_email" value="{{ old('alert_email', $alert_email) }}" placeholder="noc@example.com, boss@example.com" class="input">
                    <p class="mt-1 text-xs text-slate-500">{{ __('Needs mail settings (MAIL_* in .env).') }}</p>
                </div>
                <div class="flex gap-2 sm:col-span-2"><button class="btn-primary">{{ __('Save') }}</button></div>
            </form>
            <form method="POST" action="{{ route('settings.notifications.test') }}" class="mt-2">@csrf
                <button class="btn-secondary btn-sm">{{ __('Send test alert') }}</button>
            </form>
        </div>

        {{-- Accounts --}}
        <div class="card overflow-hidden">
            <div class="px-5 pt-5 sm:px-6">
                <h3 class="card-title">{{ __('Accounts') }}</h3>
                <p class="muted mt-1">{{ __('Roles decide how far an account reaches. Staff sign in to each device with that device\'s own account, and can be limited to some devices.') }}</p>
            </div>

            <div class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($users as $u)
                    @php $self = $u->is(auth()->user()); @endphp
                    <form method="POST" action="{{ route('settings.users.update', $u) }}" class="grid items-start gap-3 px-5 py-4 sm:grid-cols-12 sm:px-6"
                          x-data="{ limited: {{ $u->role === 'staff' && ! $u->all_routers ? 'true' : 'false' }}, role: @js($u->role) }">
                        @csrf @method('PUT')
                        <div class="min-w-0 sm:col-span-4">
                            <div class="break-words font-medium text-slate-900 dark:text-white">{{ $u->name }} @if ($self) <span class="badge-indigo ml-1">{{ __('you') }}</span> @endif</div>
                            <div class="break-all text-sm text-slate-500">{{ $u->email }}</div>
                        </div>
                        <div class="min-w-0 sm:col-span-3">
                            <select name="role" x-model="role" class="input" @disabled($self)>
                                @foreach ($roles as $r) <option value="{{ $r }}">{{ __(ucfirst($r)) }}</option> @endforeach
                            </select>
                            <p class="mt-1 text-xs text-slate-500" x-text="@js($roleHelp)[role]"></p>
                        </div>
                        <div class="min-w-0 sm:col-span-3" x-show="role === 'staff'">
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="limited" @disabled($self) class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Only some routers') }}</label>
                            <div x-show="limited" x-cloak class="mt-2 space-y-1">
                                @foreach ($routers as $r)
                                    <label class="flex items-start gap-2 text-sm">
                                        <input type="checkbox" name="router_ids[]" value="{{ $r->id }}" @checked($u->routers->contains($r->id)) :disabled="!limited" class="mt-0.5 shrink-0 rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> <span class="min-w-0 break-words">{{ $r->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex gap-1 sm:col-span-2 sm:justify-end">
                            @unless ($self)
                                <button class="btn-secondary btn-sm">{{ __('Save') }}</button>
                                <button type="submit" form="del-{{ $u->id }}" class="btn-ghost btn-sm !text-red-700 dark:!text-red-400">{{ __('Remove') }}</button>
                            @endunless
                        </div>
                    </form>
                    @unless ($self)
                        <form id="del-{{ $u->id }}" method="POST" action="{{ route('settings.users.destroy', $u) }}" onsubmit="return confirm(@js(__('Remove this account?')))">@csrf @method('DELETE')</form>
                    @endunless
                @endforeach
            </div>

            <form method="POST" action="{{ route('settings.users.store') }}" class="grid gap-3 border-t border-slate-100 p-5 dark:border-slate-800 sm:grid-cols-5 sm:p-6"
                  x-data="{ role: 'staff', limited: false }">
                @csrf
                <input name="name" placeholder="{{ __('Name') }}" value="{{ old('name') }}" class="input" required>
                <input type="email" name="email" placeholder="{{ __('Email') }}" value="{{ old('email') }}" class="input" required>
                <input type="password" name="password" placeholder="{{ __('Password') }}" class="input" required autocomplete="new-password">
                <select name="role" x-model="role" class="input">
                    @foreach ($roles as $r) <option value="{{ $r }}">{{ __(ucfirst($r)) }}</option> @endforeach
                </select>
                <button class="btn-primary">{{ __('Add account') }}</button>

                <div class="sm:col-span-5" x-show="role === 'staff'">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="limited" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Only some routers') }}</label>
                    <div x-show="limited" x-cloak class="mt-2 flex flex-wrap gap-x-5 gap-y-1">
                        @foreach ($routers as $r)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="router_ids[]" value="{{ $r->id }}" :disabled="!limited" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ $r->name }}</label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

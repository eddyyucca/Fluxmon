<x-app-layout>
    <x-slot name="header">{{ __('Settings') }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-6">
        {{-- Language --}}
        <form method="POST" action="{{ route('settings.locale') }}" class="card card-pad flex flex-wrap items-end gap-3">
            @csrf @method('PUT')
            <div class="flex-1">
                <h3 class="card-title">{{ __('Language') }}</h3>
                <p class="muted mt-1">{{ __('Applies to everyone using this Fluxmon.') }}</p>
            </div>
            <select name="locale" class="input !w-52">
                @foreach ($locales as $code => $name) <option value="{{ $code }}" @selected($locale === $code)>{{ $name }}</option> @endforeach
            </select>
            <button class="btn-primary">{{ __('Save') }}</button>
        </form>

        {{-- Telegram --}}
        <div class="card card-pad">
            <h3 class="card-title">Telegram</h3>
            <p class="muted mt-1">{{ __('Get a daily sales report and an alert when a router goes offline. Create a bot with @BotFather, then message it once and use your chat ID.') }}</p>

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
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="offline_alert" value="1" @checked($telegram['offline_alert']) class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Router offline / online alerts') }}</label>
                <div class="flex gap-2 sm:col-span-2">
                    <button class="btn-primary">{{ __('Save') }}</button>
                </div>
            </form>

            @if ($telegram['configured'])
                <form method="POST" action="{{ route('settings.telegram.test') }}" class="mt-2">@csrf
                    <button class="btn-secondary btn-sm">{{ __('Send test message') }}</button>
                </form>
            @endif
        </div>

        {{-- Admins --}}
        <div class="card overflow-hidden">
            <div class="px-5 pt-5 sm:px-6">
                <h3 class="card-title">{{ __('Admins') }}</h3>
                <p class="muted mt-1">{{ __('Everyone listed here has full access.') }}</p>
            </div>
            <table class="table mt-3">
                <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Email') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td class="font-medium text-slate-900 dark:text-white">{{ $u->name }} @if ($u->is(auth()->user())) <span class="badge-indigo ml-1">{{ __('you') }}</span> @endif</td>
                            <td class="text-slate-600 dark:text-slate-400">{{ $u->email }}</td>
                            <td class="text-right">
                                @unless ($u->is(auth()->user()))
                                    <form method="POST" action="{{ route('settings.users.destroy', $u) }}" class="inline" onsubmit="return confirm(@js(__('Remove this admin?')))">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost btn-sm !text-red-600">{{ __('Remove') }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <form method="POST" action="{{ route('settings.users.store') }}" class="grid gap-3 border-t border-slate-100 p-5 dark:border-slate-800 sm:grid-cols-4 sm:p-6">
                @csrf
                <input name="name" placeholder="{{ __('Name') }}" value="{{ old('name') }}" class="input" required>
                <input type="email" name="email" placeholder="{{ __('Email') }}" value="{{ old('email') }}" class="input" required>
                <input type="password" name="password" placeholder="{{ __('Password') }}" class="input" required autocomplete="new-password">
                <button class="btn-primary">{{ __('Add admin') }}</button>
            </form>
        </div>
    </div>
</x-app-layout>

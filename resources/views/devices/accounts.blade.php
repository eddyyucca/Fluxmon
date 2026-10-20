<x-app-layout>
    <x-slot name="header">{{ __('Device accounts') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    @php
        $roleHelp = [
            'technician' => __('Monitoring, hotspot, vouchers, backups, alerts. No router or account management.'),
            'cashier' => __('Sell vouchers and manage hotspot users. No profiles, backups or settings.'),
            'viewer' => __('Read-only: dashboards, users and reports.'),
        ];
    @endphp

    <p class="muted mb-4 max-w-3xl">{{ __('These accounts sign in to this device only. Staff log in to Fluxmon first and then to the device with one of these accounts; the role decides what they can do here. Superadmins and administrators do not need one.') }}</p>

    <form method="POST" action="{{ route('r.accounts.store', $router) }}" class="card card-pad mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5" x-data="{ role: @js(old('role', 'technician')), help: @js($roleHelp) }">
        @csrf
        <div class="min-w-0"><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name') }}" class="input" required></div>
        <div class="min-w-0"><label class="label">{{ __('Device username') }}</label><input name="username" value="{{ old('username') }}" class="input" required autocomplete="off"></div>
        <div class="min-w-0"><label class="label">{{ __('Device password') }}</label><input type="password" name="password" class="input" required autocomplete="new-password"></div>
        <div class="min-w-0">
            <label class="label">{{ __('Role') }}</label>
            <select name="role" x-model="role" class="input">
                @foreach ($roles as $r) <option value="{{ $r }}">{{ __(ucfirst($r)) }}</option> @endforeach
            </select>
        </div>
        <div class="flex items-end"><button class="btn-primary w-full whitespace-nowrap">{{ __('Add account') }}</button></div>
        <p class="muted sm:col-span-2 lg:col-span-5" x-text="help[role]"></p>
    </form>

    <div class="card divide-y divide-slate-100 dark:divide-slate-800">
        @forelse ($accounts as $a)
            <div x-data="{ edit: false }" class="px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="break-words font-medium text-slate-900 dark:text-white">{{ $a->name }}</div>
                        <div class="break-all font-mono text-xs text-slate-500">{{ $a->username }}</div>
                    </div>
                    <span class="badge-indigo">{{ __(ucfirst($a->role)) }}</span>
                    @if ($a->active) <span class="badge-green">{{ __('Active') }}</span> @else <span class="badge-gray">{{ __('Disabled') }}</span> @endif
                    <span class="hidden text-xs text-slate-500 sm:inline">{{ $a->last_login_at ? __('Last sign-in').' '.$a->last_login_at->diffForHumans() : __('Never signed in') }}</span>
                    <button type="button" class="btn-ghost btn-sm" @click="edit = !edit">{{ __('Edit') }}</button>
                    <form method="POST" action="{{ route('r.accounts.destroy', [$router, $a]) }}" onsubmit="return confirm(@js(__('Remove this device account?')))">
                        @csrf @method('DELETE')
                        <button class="btn-ghost btn-sm !text-red-700 dark:!text-red-400">{{ __('Remove') }}</button>
                    </form>
                </div>

                <form x-cloak x-show="edit" method="POST" action="{{ route('r.accounts.update', [$router, $a]) }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    @csrf @method('PUT')
                    <div class="min-w-0"><label class="label">{{ __('Name') }}</label><input name="name" value="{{ $a->name }}" class="input" required></div>
                    <div class="min-w-0">
                        <label class="label">{{ __('Role') }}</label>
                        <select name="role" class="input">
                            @foreach ($roles as $r) <option value="{{ $r }}" @selected($a->role === $r)>{{ __(ucfirst($r)) }}</option> @endforeach
                        </select>
                    </div>
                    <div class="min-w-0"><label class="label">{{ __('New password (optional)') }}</label><input type="password" name="password" class="input" autocomplete="new-password"></div>
                    <label class="flex items-end gap-2 pb-2 text-sm"><input type="checkbox" name="active" value="1" @checked($a->active) class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Can sign in') }}</label>
                    <div class="flex items-end"><button class="btn-primary w-full">{{ __('Save') }}</button></div>
                </form>
            </div>
        @empty
            <div class="px-5 py-10 text-center text-slate-500">{{ __('No device accounts yet.') }}</div>
        @endforelse
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">{{ $router->exists ? __('Edit router') : __('Add router') }}</x-slot>

    @php
        $fields = [
            ['name', __('Name'), 'text', __('Office hotspot')],
            ['host', __('Host / IP'), 'text', '192.168.88.1'],
            ['port', __('API port'), 'number', '8728'],
            ['username', __('Username'), 'text', 'admin'],
            ['hotspot_name', __('Hotspot name (shown on vouchers)'), 'text', ''],
            ['dns_name', __('DNS name / login address'), 'text', 'hotspot.local'],
            ['currency', __('Currency'), 'text', 'Rp'],
            ['traffic_interface', __('Default traffic interface'), 'text', 'ether1'],
            ['timeout', __('Timeout (seconds)'), 'number', '5'],
        ];
    @endphp

    <form method="POST" action="{{ $router->exists ? route('routers.update', $router) : route('routers.store') }}"
          class="card card-pad mx-auto grid max-w-3xl grid-cols-1 gap-5 sm:grid-cols-2">
        @csrf
        @if ($router->exists) @method('PUT') @endif

        @foreach ($fields as [$field, $label, $type, $placeholder])
            <div>
                <label class="label" for="{{ $field }}">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="input" placeholder="{{ $placeholder }}" value="{{ old($field, $router->$field) }}">
                <x-input-error :messages="$errors->get($field)" class="input-error" />
            </div>
        @endforeach

        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" class="input" autocomplete="new-password">
            @if ($router->exists) <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to keep the current password.') }}</p> @endif
            <x-input-error :messages="$errors->get('password')" class="input-error" />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 sm:col-span-2">
            <input type="checkbox" name="use_ssl" value="1" @checked(old('use_ssl', $router->use_ssl)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
            {{ __('Use API-SSL (port 8729)') }}
        </label>

        <div class="flex items-center gap-3 sm:col-span-2">
            <button class="btn-primary">{{ __('Save') }}</button>
            <a href="{{ route('routers.index') }}" class="btn-ghost">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-app-layout>

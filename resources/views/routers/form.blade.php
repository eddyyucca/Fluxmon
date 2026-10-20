<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $router->exists ? __('Edit router') : __('Add router') }}
        </h2>
    </x-slot>

    @php
        $fields = [
            ['name', 'Name', 'text'],
            ['host', 'Host / IP', 'text'],
            ['port', 'API port', 'number'],
            ['username', 'Username', 'text'],
            ['hotspot_name', 'Hotspot name', 'text'],
            ['dns_name', 'DNS name', 'text'],
            ['currency', 'Currency', 'text'],
            ['traffic_interface', 'Traffic interface', 'text'],
            ['timeout', 'Timeout (s)', 'number'],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ $router->exists ? route('routers.update', $router) : route('routers.store') }}"
                  class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                @if ($router->exists) @method('PUT') @endif

                @foreach ($fields as [$field, $label, $type])
                    <div>
                        <x-input-label :for="$field" :value="__($label)" />
                        <x-text-input :id="$field" :name="$field" :type="$type" class="mt-1 block w-full"
                                      :value="old($field, $router->$field)" />
                        <x-input-error :messages="$errors->get($field)" class="mt-2" />
                    </div>
                @endforeach

                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    @if ($router->exists)
                        <p class="mt-1 text-xs text-gray-500">{{ __('Leave blank to keep the current password.') }}</p>
                    @endif
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                    <input type="checkbox" name="use_ssl" value="1" @checked(old('use_ssl', $router->use_ssl)) class="rounded border-gray-300">
                    {{ __('Use API-SSL (port 8729)') }}
                </label>

                <div class="sm:col-span-2 flex items-center gap-4">
                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                    <a href="{{ route('routers.index') }}" class="text-sm text-gray-600 hover:underline">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

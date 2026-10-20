<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Routers') }}</h2>
            <a href="{{ route('routers.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Add router') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('Name') }}</th>
                            <th class="px-4 py-3">{{ __('Host') }}</th>
                            <th class="px-4 py-3">{{ __('Hotspot') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($routers as $router)
                            <tr x-data="routerTest('{{ route('routers.test', $router) }}')">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $router->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $router->host }}:{{ $router->port }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $router->hotspot_name ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <span x-text="label" :class="color">
                                        {{ $router->last_connected_at ? __('Last seen').' '.$router->last_connected_at->diffForHumans() : __('Not tested') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                    <button type="button" @click="run()" :disabled="busy" class="text-indigo-600 hover:underline">{{ __('Test') }}</button>
                                    <a href="{{ route('routers.edit', $router) }}" class="text-gray-600 hover:underline">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('routers.destroy', $router) }}" class="inline" onsubmit="return confirm('{{ __('Remove this router?') }}')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:underline">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No routers yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('routerTest', (url) => ({
                busy: false, label: null, color: 'text-gray-500',
                async run() {
                    this.busy = true; this.label = 'Testing...'; this.color = 'text-gray-500';
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json'},
                        });
                        const d = await res.json();
                        this.label = d.ok ? `Online · ${d.board} · v${d.version}` : d.message;
                        this.color = d.ok ? 'text-green-600' : 'text-red-600';
                    } catch (e) {
                        this.label = 'Request failed'; this.color = 'text-red-600';
                    }
                    this.busy = false;
                },
            }));
        });
    </script>
</x-app-layout>

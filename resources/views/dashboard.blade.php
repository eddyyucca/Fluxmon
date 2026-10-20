<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Dashboard') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($routers->isEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-600">
                    {{ __('No routers yet.') }}
                    <a href="{{ route('routers.create') }}" class="text-indigo-600 hover:underline">{{ __('Add your first router') }}</a>
                </div>
            @else
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @foreach ($routers as $router)
                        <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="routerStats('{{ route('dashboard.stats', $router) }}')" x-init="load(); setInterval(() => load(), 10000)">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900">{{ $router->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $router->host }}</p>
                                </div>
                                <span class="text-xs font-medium px-2 py-1 rounded-full"
                                      :class="s === null ? 'bg-gray-100 text-gray-500' : (s.ok ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')"
                                      x-text="s === null ? 'Loading' : (s.ok ? 'Online' : 'Offline')"></span>
                            </div>

                            <template x-if="s && !s.ok">
                                <p class="mt-4 text-sm text-red-600" x-text="s.message"></p>
                            </template>

                            <template x-if="s && s.ok">
                                <div class="mt-4 space-y-4">
                                    <p class="text-sm text-gray-600">
                                        <span x-text="s.board"></span> · RouterOS <span x-text="s.version"></span> · up <span x-text="s.uptime"></span>
                                    </p>
                                    <div class="grid grid-cols-2 gap-3 text-center">
                                        <div class="rounded-md bg-gray-50 p-3">
                                            <div class="text-2xl font-semibold text-gray-900" x-text="s.hotspot_active"></div>
                                            <div class="text-xs text-gray-500">{{ __('Active') }}</div>
                                        </div>
                                        <div class="rounded-md bg-gray-50 p-3">
                                            <div class="text-2xl font-semibold text-gray-900" x-text="s.hotspot_users"></div>
                                            <div class="text-xs text-gray-500">{{ __('Hotspot users') }}</div>
                                        </div>
                                    </div>
                                    @foreach (['cpu' => 'CPU', 'memory' => 'Memory'] as $key => $label)
                                        <div>
                                            <div class="flex justify-between text-xs text-gray-600 mb-1">
                                                <span>{{ $label }}</span><span x-text="s.{{ $key }} + '%'"></span>
                                            </div>
                                            <div class="h-2 rounded bg-gray-100">
                                                <div class="h-2 rounded bg-indigo-500" :style="`width:${s.{{ $key }}}%`"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </template>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('routerStats', (url) => ({
                s: null,
                async load() {
                    try {
                        const res = await fetch(url, {headers: {'Accept': 'application/json'}});
                        this.s = await res.json();
                    } catch (e) {
                        this.s = {ok: false, message: 'Request failed'};
                    }
                },
            }));
        });
    </script>
</x-app-layout>

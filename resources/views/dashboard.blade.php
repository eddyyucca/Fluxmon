<x-app-layout>
    <x-slot name="header">{{ __('Dashboard') }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('routers.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add router') }}</a>
    </x-slot>

    @if ($routers->isEmpty())
        <div class="card card-pad mx-auto max-w-lg text-center">
            <x-application-logo class="mx-auto h-14 w-14" />
            <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">{{ __('Welcome to Fluxmon') }}</h2>
            <p class="muted mt-1">{{ __('Connect your first MikroTik router to start monitoring and selling vouchers.') }}</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <a href="{{ route('routers.create') }}" class="btn-primary">{{ __('Add your first router') }}</a>
                <a href="{{ route('docs', 'getting-started') }}" class="btn-secondary">{{ __('Read the guide') }}</a>
            </div>
        </div>
    @else
        {{-- Firing alerts --}}
        @foreach ($alerts as $a)
            <a href="{{ route('alerts') }}" class="alert-err flex items-center gap-2"><x-icon name="bolt" class="h-4 w-4 shrink-0" /> {{ $a->title }} <span class="ml-auto text-xs opacity-70">{{ $a->started_at->diffForHumans() }}</span></a>
        @endforeach

        {{-- Revenue --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                [__('Revenue today'), \App\Support\Fmt::money($today['revenue'], $currency)],
                [__('Vouchers sold today'), $today['count']],
                [__('Revenue this month'), \App\Support\Fmt::money($month['revenue'], $currency)],
                [__('Vouchers sold this month'), $month['count']],
            ] as [$label, $value])
                <div class="card card-pad">
                    <div class="muted">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        {{-- Trend --}}
        <div class="card card-pad mt-6">
            <div class="flex items-center justify-between">
                <h3 class="card-title">{{ __('Revenue, last 14 days') }}</h3>
                <a href="{{ route('reports') }}" class="text-sm text-indigo-600 hover:underline dark:text-cyan-400">{{ __('Full report') }}</a>
            </div>
            <div class="mt-4 h-56" x-data="revenueChart(@js($trend['labels']), @js($trend['revenue']), @js($currency))" x-init="draw()">
                <canvas x-ref="c"></canvas>
            </div>
        </div>

        {{-- Routers --}}
        <h3 class="mb-3 mt-8 text-sm font-semibold text-slate-500 uppercase tracking-wide">{{ __('Routers') }}</h3>
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @foreach ($routers as $router)
                <div class="card card-pad" x-data="routerStats('{{ route('dashboard.stats', $router) }}')" x-init="load(); setInterval(() => load(), 10000)">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('r.overview', $router) }}" class="block truncate font-semibold text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-cyan-400">{{ $router->name }}</a>
                            <div class="text-xs text-slate-500">{{ $router->host }}</div>
                        </div>
                        <span :class="s === null ? 'badge-gray' : (s.ok ? 'badge-green' : 'badge-red')" x-text="s === null ? '{{ __('Checking') }}' : (s.ok ? '{{ __('Online') }}' : '{{ __('Offline') }}')"></span>
                    </div>

                    <p x-show="s && !s.ok" x-cloak class="mt-4 text-sm text-red-600 dark:text-red-400" x-text="s && s.message"></p>

                    <div x-show="s && s.ok" x-cloak class="mt-4 space-y-4">
                        <p class="muted"><span x-text="s && s.board"></span> · RouterOS <span x-text="s && s.version"></span> · <span x-text="s && s.uptime"></span></p>
                        <div class="grid grid-cols-2 gap-3 text-center">
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                                <div class="text-2xl font-semibold text-slate-900 dark:text-white" x-text="s && s.hotspot_active"></div>
                                <div class="text-xs text-slate-500">{{ __('Online now') }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/60">
                                <div class="text-2xl font-semibold text-slate-900 dark:text-white" x-text="s && s.hotspot_users"></div>
                                <div class="text-xs text-slate-500">{{ __('Hotspot users') }}</div>
                            </div>
                        </div>
                        @foreach (['cpu' => 'CPU', 'memory' => __('Memory')] as $key => $label)
                            <div>
                                <div class="mb-1 flex justify-between text-xs text-slate-500"><span>{{ $label }}</span><span x-text="s && (s.{{ $key }} + '%')"></span></div>
                                <div class="bar"><div :style="`width:${s ? s.{{ $key }} : 0}%`"></div></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent activity --}}
        @if ($recent->isNotEmpty())
            <div class="card mt-8 overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-5 sm:px-6">
                    <h3 class="card-title">{{ __('Recent activity') }}</h3>
                    <a href="{{ route('audit') }}" class="text-sm text-indigo-600 hover:underline dark:text-cyan-400">{{ __('View all') }}</a>
                </div>
                <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($recent as $log)
                        <li class="flex items-center justify-between gap-4 px-5 py-2.5 text-sm sm:px-6">
                            <span class="min-w-0 truncate"><span class="font-medium">{{ $log->user?->name ?? '—' }}</span> <span class="text-slate-500">{{ $log->action }}</span> {{ $log->details }}</span>
                            <span class="shrink-0 text-xs text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('routerStats', (url) => ({
                s: null,
                async load() {
                    try {
                        this.s = await (await fetch(url, {headers: {Accept: 'application/json'}})).json();
                    } catch (e) {
                        this.s = {ok: false, message: 'Request failed'};
                    }
                },
            }));

            Alpine.data('revenueChart', (labels, data, currency) => ({
                draw() {
                    new Chart(this.$refs.c, {
                        type: 'bar',
                        data: {labels, datasets: [{data, backgroundColor: '#0284c7', borderRadius: 6, maxBarThickness: 28}]},
                        options: {
                            plugins: {tooltip: {callbacks: {label: (c) => fmtMoney(c.parsed.y, currency)}}},
                            scales: {y: {beginAtZero: true, ticks: {callback: (v) => v.toLocaleString(document.documentElement.lang === 'id' ? 'id-ID' : 'en-US')}}, x: {grid: {display: false}}},
                        },
                    });
                },
            }));
        });
    </script>
</x-app-layout>

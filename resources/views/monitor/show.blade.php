<x-app-layout>
    <x-slot name="header">{{ $router->name }}</x-slot>
    <x-slot name="actions">
        <form method="POST" action="{{ route('r.sync', $router) }}">
            @csrf
            <button class="btn-secondary btn-sm" title="{{ __('Check for newly used and expired vouchers now') }}">
                <x-icon name="refresh" class="h-4 w-4" /> {{ __('Sync') }}
            </button>
        </form>
    </x-slot>

    <div x-data="overview(@js(route('dashboard.stats', $router)), @js(route('r.interfaces', $router)), @js(route('r.traffic', $router)), @js($router->traffic_interface))"
         x-init="init()">

        @foreach ($alerts as $a)
            <div class="alert-err flex items-center gap-2"><x-icon name="bolt" class="h-4 w-4 shrink-0" /> {{ $a->title }} <span class="ml-auto text-xs opacity-70">{{ $a->started_at->diffForHumans() }}</span></div>
        @endforeach

        @if ($router->ros_version)
            <div class="mb-3 flex flex-wrap items-center gap-1.5">
                @if ($router->identity) <span class="badge-gray">{{ $router->identity }}</span> @endif
                <span class="badge-indigo">RouterOS {{ $router->ros_version }}</span>
                @if ($router->architecture) <span class="badge-gray">{{ $router->architecture }}</span> @endif
                @foreach (['wireguard' => 'WireGuard', 'wifi_new' => 'WiFi (new)', 'wireless_legacy' => 'Wireless (legacy)', 'container' => 'Container', 'lte' => 'LTE', 'ipv6' => 'IPv6'] as $cap => $label)
                    @if ($router->supports($cap)) <span class="badge-green">{{ $label }}</span> @endif
                @endforeach
            </div>
        @endif

        <div class="mb-5 flex items-center gap-2">
            <span :class="s === null ? 'badge-gray' : (s.ok ? 'badge-green' : 'badge-red')" x-text="s === null ? '{{ __('Checking') }}' : (s.ok ? '{{ __('Online') }}' : '{{ __('Offline') }}')"></span>
            <span class="muted" x-show="s && s.ok" x-cloak><span x-text="s && s.board"></span> · RouterOS <span x-text="s && s.version"></span> · {{ __('up') }} <span x-text="s && s.uptime"></span></span>
            <span class="text-sm text-red-600 dark:text-red-400" x-show="s && !s.ok" x-cloak x-text="s && s.message"></span>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card card-pad">
                <div class="muted">{{ __('Online now') }}</div>
                <div class="mt-1 text-3xl font-bold text-slate-900 dark:text-white" x-text="s && s.ok ? s.hotspot_active : '–'"></div>
            </div>
            <div class="card card-pad">
                <div class="muted">{{ __('Hotspot users') }}</div>
                <div class="mt-1 text-3xl font-bold text-slate-900 dark:text-white" x-text="s && s.ok ? s.hotspot_users : '–'"></div>
            </div>
            <div class="card card-pad">
                <div class="muted">{{ __('Revenue today') }}</div>
                <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ \App\Support\Fmt::money($today['revenue'], $router->currency) }}</div>
                <div class="text-xs text-slate-500">{{ $today['count'] }} {{ __('vouchers') }}</div>
            </div>
            <div class="card card-pad">
                <div class="muted">{{ __('Revenue this month') }}</div>
                <div class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">{{ \App\Support\Fmt::money($month['revenue'], $router->currency) }}</div>
                <div class="text-xs text-slate-500">{{ $month['count'] }} {{ __('vouchers') }}</div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <div class="card card-pad lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="card-title">{{ __('Live traffic') }}</h3>
                    <div class="flex items-center gap-4 text-xs">
                        <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-cyan-500"></i> {{ __('Download') }} <b x-text="fmtBits(rx)"></b></span>
                        <span class="flex items-center gap-1.5"><i class="h-2 w-2 rounded-full bg-indigo-500"></i> {{ __('Upload') }} <b x-text="fmtBits(tx)"></b></span>
                        <select x-model="iface" @change="reset()" class="input !w-auto !py-1 text-xs">
                            <template x-for="i in interfaces" :key="i"><option :value="i" x-text="i"></option></template>
                        </select>
                    </div>
                </div>
                <div class="mt-4 h-64"><canvas x-ref="chart"></canvas></div>
            </div>

            <div class="card card-pad space-y-5">
                <h3 class="card-title">{{ __('System') }}</h3>
                @foreach (['cpu' => 'CPU', 'memory' => __('Memory')] as $key => $label)
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-slate-500"><span>{{ $label }}</span><span x-text="s && s.ok ? s.{{ $key }} + '%' : '–'"></span></div>
                        <div class="bar"><div :style="`width:${s && s.ok ? s.{{ $key }} : 0}%`"></div></div>
                    </div>
                @endforeach
                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                    <div class="grid grid-cols-2 gap-2">
                        <a href="{{ route('r.vouchers', $router) }}" class="btn-primary btn-sm"><x-icon name="ticket" class="h-4 w-4" /> {{ __('Generate vouchers') }}</a>
                        <a href="{{ route('r.users', $router) }}" class="btn-secondary btn-sm"><x-icon name="users" class="h-4 w-4" /> {{ __('Users') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('overview', (statsUrl, ifacesUrl, trafficUrl, defaultIface) => ({
                s: null, interfaces: [], iface: defaultIface, rx: 0, tx: 0, chart: null, timer: null,

                async init() {
                    this.drawChart();
                    this.loadStats();
                    setInterval(() => this.loadStats(), 10000);

                    try {
                        const d = await (await fetch(ifacesUrl, {headers: {Accept: 'application/json'}})).json();
                        if (d.ok) {
                            this.interfaces = d.interfaces;
                            if (!this.interfaces.includes(this.iface)) this.iface = this.interfaces[0] || this.iface;
                        }
                    } catch (e) {}
                    this.poll();
                },

                async loadStats() {
                    try {
                        this.s = await (await fetch(statsUrl, {headers: {Accept: 'application/json'}})).json();
                    } catch (e) {
                        this.s = {ok: false, message: 'Request failed'};
                    }
                },

                drawChart() {
                    const line = (label, color) => ({label, data: [], borderColor: color, backgroundColor: color + '22', fill: true, tension: .35, pointRadius: 0, borderWidth: 2});
                    this.chart = new Chart(this.$refs.chart, {
                        type: 'line',
                        data: {labels: [], datasets: [line('Download', '#06b6d4'), line('Upload', '#6366f1')]},
                        options: {
                            animation: false,
                            interaction: {mode: 'index', intersect: false},
                            plugins: {tooltip: {callbacks: {label: (c) => `${c.dataset.label}: ${fmtBits(c.parsed.y)}`}}},
                            scales: {x: {display: false}, y: {beginAtZero: true, ticks: {callback: (v) => fmtBits(v)}}},
                        },
                    });
                },

                reset() {
                    this.chart.data.labels = [];
                    this.chart.data.datasets.forEach((d) => (d.data = []));
                    this.chart.update();
                },

                async poll() {
                    try {
                        const d = await (await fetch(`${trafficUrl}?interface=${encodeURIComponent(this.iface)}`, {headers: {Accept: 'application/json'}})).json();
                        if (d.ok) {
                            this.rx = d.rx; this.tx = d.tx;
                            const c = this.chart;
                            c.data.labels.push('');
                            c.data.datasets[0].data.push(d.rx);
                            c.data.datasets[1].data.push(d.tx);
                            if (c.data.labels.length > 40) {
                                c.data.labels.shift();
                                c.data.datasets.forEach((x) => x.data.shift());
                            }
                            c.update();
                        }
                    } catch (e) {}
                    setTimeout(() => this.poll(), 2000);
                },
            }));
        });
    </script>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">{{ __('Monitoring') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    <div x-data="charts(@js(route('r.monitoring.data', $router)), @js($interfaces[0] ?? ''))" x-init="init()">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 dark:border-slate-700 dark:bg-slate-900">
                @foreach ($ranges as $r)
                    <button type="button" @click="range = '{{ $r }}'; load()"
                            :class="range === '{{ $r }}' ? 'bg-indigo-600 text-white' : 'text-slate-600 dark:text-slate-300'"
                            class="rounded-md px-3 py-1 text-sm font-medium">{{ $r }}</button>
                @endforeach
            </div>
            <p class="muted" x-show="empty" x-cloak>{{ __('No data yet. Samples are collected every minute while the scheduler runs (php artisan schedule:work).') }}</p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="card card-pad">
                <h3 class="card-title">CPU / {{ __('Memory') }} (%)</h3>
                <div class="mt-3 h-56"><canvas x-ref="load"></canvas></div>
            </div>
            <div class="card card-pad">
                <h3 class="card-title">{{ __('Online users') }}</h3>
                <div class="mt-3 h-56"><canvas x-ref="users"></canvas></div>
            </div>
            <div class="card card-pad lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="card-title">{{ __('Traffic') }}</h3>
                    @if ($interfaces)
                        <select x-model="iface" @change="load()" class="input !w-auto !py-1 text-xs">
                            @foreach ($interfaces as $i) <option value="{{ $i }}">{{ $i }}</option> @endforeach
                        </select>
                    @endif
                </div>
                <div class="mt-3 h-64"><canvas x-ref="traffic"></canvas></div>
            </div>
            <div class="card card-pad lg:col-span-2" x-show="hasTemp" x-cloak>
                <h3 class="card-title">{{ __('Temperature') }} (°C)</h3>
                <div class="mt-3 h-48"><canvas x-ref="temp"></canvas></div>
            </div>
        </div>

        @if ($rates)
            <div class="card mt-6 overflow-x-auto">
                <h3 class="card-title px-5 pt-5">{{ __('Interfaces, last sample') }}</h3>
                <table class="table mt-3">
                    <thead><tr><th>{{ __('Interface') }}</th><th class="text-right">{{ __('Download') }}</th><th class="text-right">{{ __('Upload') }}</th></tr></thead>
                    <tbody>
                        @foreach ($rates as $r)
                            <tr>
                                <td class="font-medium text-slate-900 dark:text-white">{{ $r['name'] }}</td>
                                <td class="text-right" x-text="fmtBits({{ $r['rx'] }})"></td>
                                <td class="text-right" x-text="fmtBits({{ $r['tx'] }})"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('charts', (url, defaultIface) => ({
                range: '24h', iface: defaultIface, empty: false, hasTemp: false, c: {},

                init() {
                    const line = (label, color, fill = false) => ({label, data: [], borderColor: color, backgroundColor: color + '22', fill, tension: .3, pointRadius: 0, borderWidth: 2});
                    const make = (el, datasets, fmt) => new Chart(el, {
                        type: 'line',
                        data: {labels: [], datasets},
                        options: {
                            animation: false,
                            interaction: {mode: 'index', intersect: false},
                            plugins: {legend: {display: datasets.length > 1, position: 'bottom', labels: {boxWidth: 10}}, tooltip: {callbacks: {label: (c) => `${c.dataset.label}: ${fmt(c.parsed.y)}`}}},
                            scales: {x: {ticks: {maxTicksLimit: 8, maxRotation: 0}, grid: {display: false}}, y: {beginAtZero: true, ticks: {callback: fmt}}},
                        },
                    });
                    const pct = (v) => `${Math.round(v)}%`;
                    this.c.load = make(this.$refs.load, [line('CPU', '#0284c7'), line('Memory', '#14b8a6')], pct);
                    this.c.users = make(this.$refs.users, [line('Online', '#10b981', true)], (v) => Math.round(v));
                    this.c.traffic = make(this.$refs.traffic, [line('Download', '#14b8a6', true), line('Upload', '#0284c7', true)], fmtBits);
                    this.c.temp = make(this.$refs.temp, [line('°C', '#f59e0b')], (v) => `${v.toFixed(0)}°`);
                    this.load();
                    setInterval(() => this.load(), 60000);
                },

                label(t) {
                    const d = new Date(t * 1000);
                    const time = d.toLocaleTimeString(document.documentElement.lang, {hour: '2-digit', minute: '2-digit'});
                    return ['1h', '6h', '24h'].includes(this.range) ? time : `${d.toLocaleDateString(document.documentElement.lang, {day: '2-digit', month: 'short'})} ${time}`;
                },

                fill(chart, series) {
                    // Series can differ in length (a metric may start later), so use the longest as the x axis.
                    const longest = series.reduce((a, s) => (s.times.length > a.times.length ? s : a), {times: []});
                    chart.data.labels = longest.times.map((t) => this.label(t));
                    series.forEach((s, i) => {
                        const byTime = new Map(s.times.map((t, k) => [t, s.values[k]]));
                        chart.data.datasets[i].data = longest.times.map((t) => byTime.get(t) ?? null);
                    });
                    chart.update();
                },

                async load() {
                    try {
                        const q = new URLSearchParams({range: this.range, interface: this.iface});
                        const d = await (await fetch(`${url}?${q}`, {headers: {Accept: 'application/json'}})).json();
                        this.empty = d.cpu.times.length === 0;
                        this.hasTemp = d.temperature.times.length > 0;
                        this.fill(this.c.load, [d.cpu, d.memory]);
                        this.fill(this.c.users, [d.hotspot_active]);
                        this.fill(this.c.traffic, [d.rx, d.tx]);
                        this.fill(this.c.temp, [d.temperature]);
                    } catch (e) {}
                },
            }));
        });
    </script>
</x-app-layout>

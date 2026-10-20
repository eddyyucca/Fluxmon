<x-app-layout>
    <x-slot name="header">{{ __('Reports') }}</x-slot>
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" /> CSV</a>
    </x-slot>

    <form method="GET" class="card card-pad mb-6 flex flex-wrap items-end gap-3" x-data="{ range: @js($filters['range']) }">
        <div>
            <label class="label">{{ __('Period') }}</label>
            <select name="range" x-model="range" class="input !w-44">
                @foreach (['today' => __('Today'), '7d' => __('Last 7 days'), '30d' => __('Last 30 days'), 'month' => __('This month'), 'last_month' => __('Last month'), 'custom' => __('Custom range')] as $k => $label)
                    <option value="{{ $k }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <template x-if="range === 'custom'">
            <div class="flex gap-3">
                <div><label class="label">{{ __('From') }}</label><input type="date" name="from" value="{{ $filters['from'] }}" class="input"></div>
                <div><label class="label">{{ __('To') }}</label><input type="date" name="to" value="{{ $filters['to'] }}" class="input"></div>
            </div>
        </template>
        <div>
            <label class="label">{{ __('Router') }}</label>
            <select name="router" class="input !w-48">
                <option value="">{{ __('All routers') }}</option>
                @foreach ($routers as $r) <option value="{{ $r->id }}" @selected($filters['router'] === $r->id)>{{ $r->name }}</option> @endforeach
            </select>
        </div>
        <button class="btn-primary">{{ __('Apply') }}</button>
        <div class="muted ml-auto">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</div>
    </form>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card card-pad"><div class="muted">{{ __('Revenue') }}</div><div class="mt-1 text-2xl font-bold">{{ \App\Support\Fmt::money($summary['revenue'], $currency) }}</div></div>
        <div class="card card-pad"><div class="muted">{{ __('Vouchers sold') }}</div><div class="mt-1 text-2xl font-bold">{{ number_format($summary['count']) }}</div></div>
        <div class="card card-pad"><div class="muted">{{ __('Average price') }}</div><div class="mt-1 text-2xl font-bold">{{ \App\Support\Fmt::money($summary['count'] ? $summary['revenue'] / $summary['count'] : 0, $currency) }}</div></div>
        @php $days = max(count($summary['daily']['labels']), 1); @endphp
        <div class="card card-pad"><div class="muted">{{ __('Average per day') }}</div><div class="mt-1 text-2xl font-bold">{{ \App\Support\Fmt::money($summary['revenue'] / $days, $currency) }}</div></div>
    </div>

    <div class="card card-pad mt-6">
        <h3 class="card-title">{{ __('Revenue per day') }}</h3>
        <div class="mt-4 h-72" x-data="reportChart(@js($summary['daily']['labels']), @js($summary['daily']['revenue']), @js($summary['daily']['count']), @js($currency))" x-init="draw()">
            <canvas x-ref="c"></canvas>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <h3 class="card-title px-5 pt-5">{{ __('By profile') }}</h3>
            <table class="table mt-3">
                <thead><tr><th>{{ __('Profile') }}</th><th class="text-right">{{ __('Sold') }}</th><th class="text-right">{{ __('Revenue') }}</th></tr></thead>
                <tbody>
                    @forelse ($summary['by_profile'] as $row)
                        <tr><td class="font-medium">{{ $row->profile }}</td><td class="text-right">{{ $row->n }}</td><td class="text-right">{{ \App\Support\Fmt::money($row->revenue, $currency) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-500">{{ __('No sales in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card overflow-hidden">
            <h3 class="card-title px-5 pt-5">{{ __('By router') }}</h3>
            <table class="table mt-3">
                <thead><tr><th>{{ __('Router') }}</th><th class="text-right">{{ __('Sold') }}</th><th class="text-right">{{ __('Revenue') }}</th></tr></thead>
                <tbody>
                    @forelse ($summary['by_router'] as $row)
                        <tr><td class="font-medium">{{ $row->name }}</td><td class="text-right">{{ $row->n }}</td><td class="text-right">{{ \App\Support\Fmt::money($row->revenue, $currency) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="py-8 text-center text-slate-500">{{ __('No sales in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="muted mt-6">{{ __('A voucher counts as sold at its first login. Figures update when Fluxmon syncs with the router (every minute while the scheduler runs, or with the Sync button).') }}</p>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('reportChart', (labels, revenue, counts, currency) => ({
                draw() {
                    new Chart(this.$refs.c, {
                        type: 'bar',
                        data: {labels, datasets: [{data: revenue, backgroundColor: '#6366f1', borderRadius: 6, maxBarThickness: 36}]},
                        options: {
                            plugins: {tooltip: {callbacks: {label: (c) => `${fmtMoney(c.parsed.y, currency)} · ${counts[c.dataIndex]} vouchers`}}},
                            scales: {y: {beginAtZero: true, ticks: {callback: (v) => v.toLocaleString('id-ID')}}, x: {grid: {display: false}}},
                        },
                    });
                },
            }));
        });
    </script>
</x-app-layout>

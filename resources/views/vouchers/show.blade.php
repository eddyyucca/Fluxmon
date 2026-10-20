<x-app-layout>
    <x-slot name="header">{{ __('Batch') }} #{{ $batch->id }} <span class="text-sm font-normal text-slate-500">· {{ $batch->profile }}</span></x-slot>
    <x-slot name="actions">
        <a href="{{ route('r.vouchers.csv', [$router, $batch]) }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" /> CSV</a>
        <a href="{{ route('r.vouchers.print', [$router, $batch]) }}" target="_blank" class="btn-primary btn-sm"><x-icon name="printer" class="h-4 w-4" /> {{ __('Print') }}</a>
    </x-slot>

    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card card-pad"><div class="muted">{{ __('Vouchers') }}</div><div class="mt-1 text-2xl font-bold">{{ $batch->quantity }}</div></div>
        <div class="card card-pad"><div class="muted">{{ __('Unused') }}</div><div class="mt-1 text-2xl font-bold">{{ $counts['unused'] ?? 0 }}</div></div>
        <div class="card card-pad"><div class="muted">{{ __('Used') }}</div><div class="mt-1 text-2xl font-bold">{{ ($counts['used'] ?? 0) + ($counts['expired'] ?? 0) }}</div></div>
        <div class="card card-pad">
            <div class="muted">{{ __('Validity') }} / {{ __('Price') }}</div>
            <div class="mt-1 text-lg font-bold">{{ \App\Support\RouterOs::humanMinutes($batch->validity_minutes) }} · {{ \App\Support\Fmt::money($batch->price, $router->currency) }}</div>
        </div>
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Username') }}</th><th>{{ __('Password') }}</th><th>{{ __('Status') }}</th><th>{{ __('First used') }}</th><th>{{ __('Expires') }}</th></tr></thead>
            <tbody>
                @foreach ($vouchers as $v)
                    <tr>
                        <td class="font-mono font-medium text-slate-900 dark:text-white">{{ $v->username }}</td>
                        <td class="font-mono text-slate-500">{{ $v->password === $v->username ? '—' : $v->password }}</td>
                        <td>
                            @if ($v->status === 'unused') <span class="badge-gray">{{ __('Unused') }}</span>
                            @elseif ($v->status === 'used') <span class="badge-green">{{ __('Used') }}</span>
                            @else <span class="badge-amber">{{ __('Expired') }}</span> @endif
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $v->first_used_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $v->expires_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $vouchers->links() }}</div>
</x-app-layout>

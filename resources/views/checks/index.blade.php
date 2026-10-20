<x-app-layout>
    <x-slot name="header">{{ __('Checks') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    @php $manage = auth()->user()->hasPermission('alerts.manage', $router); @endphp

    <p class="muted mb-4">{{ __('A check tests whether something is reachable every minute and raises an alert when it stops answering. "Ping from router" asks the router itself (for example: can it reach its ISP gateway?); "TCP port" is tested from the Fluxmon server.') }}</p>

    @if ($manage)
        <form method="POST" action="{{ route('r.checks.store', $router) }}" class="card card-pad mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5"
              x-data="{ type: @js(old('type', 'router_ping')) }">
            @csrf
            <div><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name') }}" placeholder="{{ __('ISP gateway') }}" class="input" required></div>
            <div>
                <label class="label">{{ __('Type') }}</label>
                <select name="type" x-model="type" class="input">
                    @foreach ($types as $k => $label) <option value="{{ $k }}">{{ __($label) }}</option> @endforeach
                </select>
            </div>
            <div><label class="label">{{ __('Host / IP') }}</label><input name="target" value="{{ old('target') }}" placeholder="8.8.8.8" class="input" required></div>
            <div x-show="type === 'tcp'" x-cloak><label class="label">{{ __('Port') }}</label><input type="number" name="port" value="{{ old('port') }}" placeholder="443" class="input"></div>
            <div class="flex items-end"><button class="btn-primary w-full">{{ __('Add check') }}</button></div>
        </form>
    @endif

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Target') }}</th><th>{{ __('Status') }}</th><th>{{ __('Latency') }}</th><th>{{ __('Last checked') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($checks as $c)
                    <tr class="{{ $c->enabled ? '' : 'opacity-50' }}">
                        <td class="font-medium text-slate-900 dark:text-white">{{ $c->name }}</td>
                        <td class="text-slate-600 dark:text-slate-400">{{ __($types[$c->type] ?? $c->type) }}</td>
                        <td class="font-mono text-xs">{{ $c->target }}@if ($c->port):{{ $c->port }}@endif</td>
                        <td>
                            @if ($c->last_status === null) <span class="badge-gray">{{ __('Waiting') }}</span>
                            @elseif ($c->last_status) <span class="badge-green">{{ __('Up') }}</span>
                            @else <span class="badge-red">{{ __('Down') }}</span> @endif
                        </td>
                        <td>{{ $c->last_rtt !== null ? number_format($c->last_rtt, 1).' ms' : '—' }}</td>
                        <td class="text-slate-500">{{ $c->last_checked_at?->diffForHumans() ?? '—' }}</td>
                        <td class="whitespace-nowrap text-right">
                            @if ($manage)
                                <form method="POST" action="{{ route('r.checks.toggle', [$router, $c]) }}" class="inline">@csrf
                                    <button class="btn-ghost btn-sm">{{ $c->enabled ? __('Pause') : __('Resume') }}</button>
                                </form>
                                <form method="POST" action="{{ route('r.checks.destroy', [$router, $c]) }}" class="inline" onsubmit="return confirm(@js(__('Remove this check?')))">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm !text-red-700 dark:!text-red-400"><x-icon name="trash" class="h-4 w-4" /></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">{{ __('No checks yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">{{ __('Alerts') }}</x-slot>
    @php $manage = auth()->user()->hasPermission('alerts.manage'); @endphp

    <div class="grid gap-6 xl:grid-cols-5">
        {{-- Events --}}
        <div class="card overflow-hidden xl:col-span-3">
            <h3 class="card-title px-5 pt-5">{{ __('Recent events') }}</h3>
            <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($events as $e)
                    <li class="flex items-start gap-3 px-5 py-3 text-sm">
                        <span class="mt-0.5 {{ $e->state === 'firing' ? 'badge-red' : 'badge-green' }}">{{ $e->state === 'firing' ? __('Firing') : __('Resolved') }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-slate-900 dark:text-slate-100">{{ $e->title }}</div>
                            <div class="text-xs text-slate-500">
                                {{ $e->started_at->format('d M H:i') }}
                                @if ($e->resolved_at) → {{ $e->resolved_at->format('d M H:i') }} ({{ $e->started_at->diffForHumans($e->resolved_at, true) }}) @endif
                                @if ($e->value !== null) · {{ __('value') }} {{ round($e->value, 1) }} @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-slate-500">{{ __('Nothing has fired. Router offline alerts are always on; add rules for CPU, memory and temperature.') }}</li>
                @endforelse
            </ul>
        </div>

        {{-- Rules --}}
        <div class="space-y-6 xl:col-span-2">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between px-5 pt-5">
                    <h3 class="card-title">{{ __('Rules') }}</h3>
                    @if ($manage && auth()->user()->limitedRouterIds() === null)
                        <form method="POST" action="{{ route('alerts.defaults') }}">@csrf
                            <button class="btn-ghost btn-sm">{{ __('Add recommended') }}</button>
                        </form>
                    @endif
                </div>
                <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($rules as $r)
                        <li class="flex items-center gap-3 px-5 py-3 text-sm {{ $r->enabled ? '' : 'opacity-50' }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $r->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $metrics[$r->metric] ?? $r->metric }} {{ $r->operator }} {{ $r->threshold + 0 }} · {{ $r->duration_minutes }} min · {{ $r->router?->name ?? __('all routers') }}
                                </div>
                            </div>
                            @if ($manage)
                                <form method="POST" action="{{ route('alerts.toggle', $r) }}">@csrf
                                    <button class="btn-ghost btn-sm">{{ $r->enabled ? __('Pause') : __('Resume') }}</button>
                                </form>
                                <form method="POST" action="{{ route('alerts.destroy', $r) }}" onsubmit="return confirm(@js(__('Remove this rule?')))">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm !text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-slate-500">{{ __('No rules yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            @if ($manage)
                <form method="POST" action="{{ route('alerts.store') }}" class="card card-pad grid grid-cols-2 gap-3">
                    @csrf
                    <h3 class="card-title col-span-2">{{ __('New rule') }}</h3>
                    <div class="col-span-2"><label class="label">{{ __('Name') }}</label><input name="name" value="{{ old('name') }}" class="input" required></div>
                    <div>
                        <label class="label">{{ __('Metric') }}</label>
                        <select name="metric" class="input">
                            @foreach ($metrics as $k => $label) <option value="{{ $k }}" @selected(old('metric') === $k)>{{ __($label) }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Router') }}</label>
                        <select name="router_id" class="input">
                            @if (auth()->user()->limitedRouterIds() === null) <option value="">{{ __('All routers') }}</option> @endif
                            @foreach ($routers as $r) <option value="{{ $r->id }}" @selected((int) old('router_id') === $r->id)>{{ $r->name }}</option> @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('Fires when value is') }}</label>
                        <div class="flex gap-2">
                            <select name="operator" class="input !w-20"><option value=">">&gt;</option><option value="<">&lt;</option></select>
                            <input type="number" step="any" name="threshold" value="{{ old('threshold', 90) }}" class="input" required>
                        </div>
                    </div>
                    <div><label class="label">{{ __('For (minutes)') }}</label><input type="number" min="1" max="120" name="duration_minutes" value="{{ old('duration_minutes', 5) }}" class="input" required></div>
                    <div class="col-span-2"><button class="btn-primary">{{ __('Add rule') }}</button></div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

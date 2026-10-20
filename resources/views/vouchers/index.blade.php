<x-app-layout>
    <x-slot name="header">{{ __('Vouchers') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    @if ($error) <div class="alert-err">{{ $error }}</div> @endif

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Generator --}}
        <form method="POST" action="{{ route('r.vouchers.generate', $router) }}" class="card card-pad space-y-4 xl:col-span-1"
              x-data="{ profile: @js(old('profile', $profiles[0] ?? '')), prices: @js($prices), mode: @js(old('mode', 'pair')) }">
            @csrf
            <h3 class="card-title">{{ __('Generate vouchers') }}</h3>

            <div>
                <label class="label">{{ __('Profile') }}</label>
                <select name="profile" x-model="profile" class="input" required>
                    @foreach ($profiles as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500" x-show="prices[profile] !== undefined" x-cloak>
                    {{ __('Profile price') }}: <b x-text="fmtMoney(prices[profile] ?? 0, @js($router->currency))"></b>
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">{{ __('Quantity') }}</label><input type="number" name="quantity" min="1" max="500" value="{{ old('quantity', 10) }}" class="input" required></div>
                <div><label class="label">{{ __('Code length') }}</label><input type="number" name="length" min="3" max="16" value="{{ old('length', 6) }}" class="input" required></div>
                <div><label class="label">{{ __('Prefix') }}</label><input name="prefix" maxlength="8" value="{{ old('prefix') }}" placeholder="WF-" class="input"></div>
                <div>
                    <label class="label">{{ __('Characters') }}</label>
                    <select name="charset" class="input">
                        @foreach ($charsets as $c)
                            <option value="{{ $c }}" @selected(old('charset', 'mixed') === $c)>{{ ['digits' => __('Digits'), 'lower' => __('Lowercase'), 'upper' => __('Uppercase'), 'mixed' => __('Lowercase + digits')][$c] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="label">{{ __('Login type') }}</label>
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-2.5 dark:border-slate-700" :class="mode === 'same' && 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10'">
                        <input type="radio" name="mode" value="same" x-model="mode" class="text-indigo-600"> {{ __('Code only') }}
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 p-2.5 dark:border-slate-700" :class="mode === 'pair' && 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10'">
                        <input type="radio" name="mode" value="pair" x-model="mode" class="text-indigo-600"> {{ __('User + password') }}
                    </label>
                </div>
            </div>

            <div><label class="label">{{ __('Online time limit (optional)') }}</label><x-duration name="time_limit" /></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="label">{{ __('Data limit, MB') }}</label><input type="number" min="1" name="data_limit_mb" value="{{ old('data_limit_mb') }}" class="input"></div>
                <div><label class="label">{{ __('Price override') }}</label><input type="number" min="0" step="any" name="price" value="{{ old('price') }}" placeholder="{{ __('profile price') }}" class="input"></div>
            </div>

            <button class="btn-primary w-full" @disabled(! $profiles)>{{ __('Generate') }}</button>
        </form>

        {{-- Batches --}}
        <div class="card overflow-x-auto xl:col-span-2">
            <div class="flex items-center justify-between px-5 pt-5">
                <h3 class="card-title">{{ __('Batches') }}</h3>
                <form method="POST" action="{{ route('r.sync', $router) }}">@csrf
                    <button class="btn-ghost btn-sm"><x-icon name="refresh" class="h-4 w-4" /> {{ __('Sync') }}</button>
                </form>
            </div>
            <table class="table mt-3">
                <thead><tr>
                    <th>#</th><th>{{ __('Profile') }}</th><th>{{ __('Created') }}</th><th>{{ __('Unused') }}</th><th>{{ __('Used') }}</th><th>{{ __('Revenue') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse ($batches as $b)
                        <tr>
                            <td class="text-slate-500">{{ $b->id }}</td>
                            <td>
                                <a href="{{ route('r.vouchers.show', [$router, $b]) }}" class="font-medium text-slate-900 hover:text-indigo-600 dark:text-white dark:hover:text-cyan-400">{{ $b->profile }}</a>
                                <div class="text-xs text-slate-500">{{ $b->quantity }} × {{ \App\Support\Fmt::money($b->price, $router->currency) }}</div>
                            </td>
                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-400">{{ $b->created_at->format('d M Y H:i') }}</td>
                            <td><span class="badge-gray">{{ $b->unused_count }}</span></td>
                            <td><span class="badge-green">{{ $b->used_count + $b->expired_count }}</span></td>
                            <td class="whitespace-nowrap">{{ \App\Support\Fmt::money($b->revenue ?? 0, $router->currency) }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('r.vouchers.print', [$router, $b]) }}" target="_blank" class="btn-ghost btn-sm"><x-icon name="printer" class="h-4 w-4" /> {{ __('Print') }}</a>
                                <form method="POST" action="{{ route('r.vouchers.destroy', [$router, $b]) }}" class="inline" onsubmit="return confirm(@js(__('Remove the unused vouchers of this batch from the router?')))">
                                    @csrf @method('DELETE')
                                    <button class="btn-ghost btn-sm !text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-500">{{ __('No vouchers generated yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-5 pb-4">{{ $batches->links() }}</div>
        </div>
    </div>
</x-app-layout>

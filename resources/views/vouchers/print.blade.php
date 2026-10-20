<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Vouchers') }} #{{ $batch->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/print.js'])
    <style>
        @page { size: A4; margin: 8mm; }
        @media print { body { background: #fff !important; } }
        .sheet { display: grid; gap: 3mm; grid-template-columns: repeat({{ $size === 'small' ? 5 : 3 }}, minmax(0, 1fr)); }
        .ticket { break-inside: avoid; border: 1px dashed #94a3b8; border-radius: 6px; overflow: hidden; background: #fff; color: #0f172a; }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-900">
    <form method="GET" class="print:hidden sticky top-0 z-10 flex flex-wrap items-center gap-4 border-b border-slate-200 bg-white px-6 py-3 text-sm">
        <strong>{{ $vouchers->count() }} {{ __('vouchers') }}</strong>
        <label class="flex items-center gap-2">{{ __('Size') }}
            <select name="size" class="input !w-auto !py-1" onchange="this.form.submit()">
                <option value="normal" @selected($size === 'normal')>{{ __('Normal (3 per row)') }}</option>
                <option value="small" @selected($size === 'small')>{{ __('Small (5 per row)') }}</option>
            </select>
        </label>
        <label class="flex items-center gap-2"><input type="hidden" name="qr" value="0"><input type="checkbox" name="qr" value="1" @checked($qr) onchange="this.form.submit()" class="rounded"> {{ __('QR code') }}</label>
        <label class="flex items-center gap-2"><input type="hidden" name="unused_only" value="0"><input type="checkbox" name="unused_only" value="1" @checked(request()->boolean('unused_only', true)) onchange="this.form.submit()" class="rounded"> {{ __('Unused only') }}</label>
        <button type="button" onclick="window.print()" class="btn-primary ml-auto">{{ __('Print') }}</button>
    </form>

    <div class="p-4 print:p-0">
        @if ($vouchers->isEmpty())
            <p class="py-20 text-center text-slate-500">{{ __('Nothing to print.') }}</p>
        @endif

        <div class="sheet">
            @foreach ($vouchers as $v)
                @php
                    $same = $v->username === $v->password;
                    $url = 'http://'.$router->loginHost().'/login?'.http_build_query(['username' => $v->username, 'password' => $v->password]);
                    $small = $size === 'small';
                @endphp
                <div class="ticket">
                    <div class="flex items-center justify-between bg-gradient-to-r from-indigo-600 to-cyan-500 px-2.5 py-1 text-white {{ $small ? 'text-[9px]' : 'text-xs' }}">
                        <span class="truncate font-semibold">{{ $router->hotspot_name ?: $router->name }}</span>
                        <span class="shrink-0 font-medium">{{ \App\Support\Fmt::money($v->price, $router->currency) }}</span>
                    </div>
                    <div class="flex items-center gap-2 {{ $small ? 'p-1.5' : 'p-2.5' }}">
                        <div class="min-w-0 flex-1">
                            @if ($same)
                                <div class="{{ $small ? 'text-[8px]' : 'text-[10px]' }} uppercase tracking-wide text-slate-500">{{ __('Voucher code') }}</div>
                                <div class="truncate font-mono font-bold {{ $small ? 'text-sm' : 'text-lg' }}">{{ $v->username }}</div>
                            @else
                                <div class="{{ $small ? 'text-[8px]' : 'text-[10px]' }} uppercase tracking-wide text-slate-500">{{ __('Username') }}</div>
                                <div class="truncate font-mono font-bold {{ $small ? 'text-xs' : 'text-base' }}">{{ $v->username }}</div>
                                <div class="mt-0.5 {{ $small ? 'text-[8px]' : 'text-[10px]' }} uppercase tracking-wide text-slate-500">{{ __('Password') }}</div>
                                <div class="truncate font-mono font-bold {{ $small ? 'text-xs' : 'text-base' }}">{{ $v->password }}</div>
                            @endif
                            <div class="mt-1 text-slate-500 {{ $small ? 'text-[8px]' : 'text-[10px]' }}">
                                {{ $v->profile }}@if ($batch->validity_minutes) · {{ \App\Support\RouterOs::humanMinutes($batch->validity_minutes) }}@endif
                            </div>
                        </div>
                        @if ($qr)
                            <canvas width="{{ $small ? 44 : 72 }}" height="{{ $small ? 44 : 72 }}" data-qr="{{ $url }}" class="shrink-0"></canvas>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</body>
</html>

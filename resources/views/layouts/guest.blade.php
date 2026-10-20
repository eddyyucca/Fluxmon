<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Fluxmon') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <meta name="theme-color" content="#07111f">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <script>
            try {
                const t = localStorage.getItem('fluxmon-theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) {}
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
        <div class="grid min-h-screen lg:grid-cols-2">
            {{-- Brand panel --}}
            <div class="relative hidden overflow-hidden bg-gradient-to-br from-[#07111f] via-[#0d1b2e] to-[#0e3b4f] p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.12]" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round">
                    @foreach (range(0, 9) as $i)
                        <path d="M-20 {{ 120 + $i * 80 }}c120-70 220-70 320 0s200 70 320 0"/>
                    @endforeach
                </svg>

                <div class="relative flex items-center gap-3">
                    <x-application-logo class="h-11 w-11 shadow-lg ring-1 ring-white/10" />
                    <span class="text-2xl font-extrabold italic tracking-tight">flux<span class="text-cyan-300">mon</span></span>
                </div>

                <div class="relative max-w-md">
                    <h1 class="text-4xl font-bold leading-tight">{{ __('Run your hotspot business from one place.') }}</h1>
                    <p class="mt-4 text-lg text-indigo-100">{{ __('Vouchers, live monitoring and sales reports for MikroTik — open source and built for operators.') }}</p>
                    <ul class="mt-8 space-y-3 text-indigo-50">
                        @foreach ([__('Multi-router dashboard'), __('Voucher printing with QR codes'), __('Sales reports and Telegram alerts')] as $point)
                            <li class="flex items-center gap-3"><x-icon name="bolt" class="h-5 w-5 text-cyan-200" /> {{ $point }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative text-sm text-indigo-100">
                    <a href="{{ config('fluxmon.developer.url') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 hover:opacity-90">
                        <span>{{ __('Developed by') }}</span>
                        <x-fluxa-badge on="dark" class="!h-9" />
                    </a>
                    <div class="mt-3 text-indigo-200">{{ config('fluxmon.developer.tagline') }} · {{ __('Open source · GPL-2.0') }}</div>
                </div>
            </div>

            {{-- Form --}}
            <div class="flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                        <x-application-logo class="h-10 w-10" />
                        <span class="text-xl font-extrabold italic tracking-tight">flux<span class="text-indigo-600 dark:text-cyan-400">mon</span></span>
                    </div>

                    {{ $slot }}

                    <a href="{{ config('fluxmon.developer.url') }}" target="_blank" rel="noopener"
                       class="mt-10 flex w-fit items-center gap-2.5 text-xs text-slate-400 hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300 lg:hidden">
                        <span>{{ __('Developed by') }}</span> <x-fluxa-badge class="!h-5" />
                    </a>
                </div>
            </div>
        </div>
    </body>
</html>

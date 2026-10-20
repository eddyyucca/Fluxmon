<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Fluxmon') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Apply the saved theme before first paint to avoid a flash. --}}
        <script>
            try {
                const t = localStorage.getItem('fluxmon-theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
            } catch (e) {}
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $current = request()->route('router');
        $current = $current instanceof \App\Models\Router ? $current : null;
        $me = auth()->user();
        // [route, icon, label, active-pattern, permission, badge]
        $groups = [
            [null, [
                ['dashboard', 'home', __('Dashboard'), 'dashboard', null],
                ['reports', 'chart', __('Reports'), 'reports', 'reports.view'],
                ['alerts', 'bolt', __('Alerts'), 'alerts*', 'monitor.view', $firingAlerts ?: null],
            ]],
        ];
        if ($current) {
            $groups[] = [$current->name, [
                ['r.overview', 'signal', __('Overview'), 'r.overview', 'monitor.view'],
                ['r.monitoring', 'chart', __('Monitoring'), 'r.monitoring*', 'monitor.view'],
                ['r.checks', 'refresh', __('Checks'), 'r.checks*', 'monitor.view'],
                ['r.logs', 'list', __('Logs'), 'r.logs*', 'monitor.view'],
            ]];
            $groups[] = [__('Hotspot'), [
                ['r.users', 'users', __('Users'), 'r.users*', 'hotspot.view'],
                ['r.profiles', 'tag', __('Profiles'), 'r.profiles*', 'hotspot.view'],
                ['r.vouchers', 'ticket', __('Vouchers'), 'r.vouchers*', 'vouchers.manage'],
                ['r.active', 'wifi', __('Active sessions'), 'r.active*', 'hotspot.view'],
            ]];
            $groups[] = [__('Maintenance'), [
                ['r.backups', 'download', __('Backups'), 'r.backups*', 'backups.manage'],
            ]];
        }
        $groups[] = [__('Manage'), [
            ['routers.index', 'server', __('Routers'), 'routers.*', 'routers.manage'],
            ['settings', 'cog', __('Settings'), 'settings*', 'settings.manage'],
            ['audit', 'list', __('Audit log'), 'audit', 'audit.view'],
        ]];

        // Drop what this role may not open, then any group left empty.
        $groups = collect($groups)
            ->map(fn ($g) => [$g[0], array_map(
                fn ($i) => array_pad($i, 6, null),
                array_values(array_filter($g[1], fn ($i) => $i[4] === null || $me->hasPermission($i[4]))),
            )])
            ->filter(fn ($g) => $g[1])->values()->all();
    @endphp
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200"
          x-data="{ sidebar: false, dark: document.documentElement.classList.contains('dark') }">

        {{-- Mobile overlay --}}
        <div x-cloak x-show="sidebar" x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform dark:border-slate-800 dark:bg-slate-900 lg:translate-x-0"
               :class="{ '!translate-x-0': sidebar }">
            <a href="{{ route('dashboard') }}" class="flex h-16 shrink-0 items-center gap-2.5 px-5">
                <x-application-logo class="h-9 w-9" />
                <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Flux<span class="text-indigo-600 dark:text-cyan-400">mon</span></span>
            </a>

            @if ($navRouters->isNotEmpty())
                <div class="px-4 pb-1">
                    <label class="sr-only" for="router-switch">{{ __('Router') }}</label>
                    <select id="router-switch" class="input" onchange="if (this.value) location.href = this.value">
                        @unless ($current)
                            <option value="">{{ __('Select a router…') }}</option>
                        @endunless
                        @foreach ($navRouters as $r)
                            <option value="{{ route('r.overview', $r) }}" @selected($current?->id === $r->id)>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <nav class="flex-1 overflow-y-auto px-3 pb-4">
                @foreach ($groups as [$heading, $items])
                    @if ($heading) <div class="nav-heading">{{ $heading }}</div> @endif
                    <div class="space-y-0.5 {{ $heading ? '' : 'pt-3' }}">
                        @foreach ($items as [$route, $icon, $label, $match, $perm, $badge])
                            <a href="{{ str_starts_with($route, 'r.') ? route($route, $current) : route($route) }}"
                               class="nav-link {{ request()->routeIs($match) ? 'nav-link-active' : '' }}">
                                <x-icon :name="$icon" class="h-[18px] w-[18px]" /> {{ $label }}
                                @if ($badge) <span class="badge-red ml-auto">{{ $badge }}</span> @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-slate-200 p-3 dark:border-slate-800">
                <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                        {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('profile.edit') }}" class="block truncate text-sm font-medium text-slate-900 hover:underline dark:text-slate-100">{{ Auth::user()->name }}</a>
                        <div class="truncate text-xs text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200" title="{{ __('Log out') }}">
                            <x-icon name="logout" class="h-[18px] w-[18px]" />
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/80 px-4 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80 sm:px-6 lg:px-8">
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden" @click="sidebar = true" aria-label="Menu">
                    <x-icon name="menu" />
                </button>

                <div class="min-w-0 flex-1">
                    @isset($header)
                        <div class="truncate text-lg font-semibold text-slate-900 dark:text-white">{{ $header }}</div>
                    @endisset
                </div>

                @isset($actions) <div class="flex items-center gap-2">{{ $actions }}</div> @endisset

                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                        @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); try { localStorage.setItem('fluxmon-theme', dark ? 'dark' : 'light') } catch (e) {}"
                        title="{{ __('Toggle dark mode') }}">
                    <x-icon name="moon" x-show="!dark" /><x-icon name="sun" x-show="dark" x-cloak />
                </button>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                @if (session('status')) <div class="alert-ok">{{ session('status') }}</div> @endif
                @if (session('error')) <div class="alert-err">{{ session('error') }}</div> @endif
                @if ($errors->any() && ! request()->routeIs('profile.*'))
                    <div class="alert-err">
                        <ul class="list-inside list-disc">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>

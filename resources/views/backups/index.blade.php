<x-app-layout>
    <x-slot name="header">{{ __('Backups') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>
    <x-slot name="actions">
        <form method="POST" action="{{ route('r.backups.store', $router) }}">
            @csrf
            <button class="btn-primary btn-sm"><x-icon name="download" class="h-4 w-4" /> {{ __('Back up now') }}</button>
        </form>
    </x-slot>

    <div class="alert-ok !border-slate-200 !bg-white !text-slate-600 dark:!border-slate-800 dark:!bg-slate-900 dark:!text-slate-400">
        {{ __('Fluxmon saves the router configuration (an /export script) over SSH every night at 03:00 and keeps the newest :n versions. Sensitive values such as passwords are not included by RouterOS 7. A new version is only stored when something changed.', ['n' => config('fluxmon.backups.keep')]) }}
        <span class="block mt-1">{{ __('SSH must be enabled on the router (port :port) and the API user must be allowed to log in over SSH.', ['port' => $router->ssh_port]) }}</span>
    </div>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>#</th><th>{{ __('Saved') }}</th><th>{{ __('Reason') }}</th><th>{{ __('By') }}</th><th class="text-right">{{ __('Size') }}</th><th></th></tr></thead>
            <tbody>
                @forelse ($backups as $b)
                    <tr>
                        <td class="text-slate-500">{{ $b->id }}</td>
                        <td class="whitespace-nowrap font-medium text-slate-900 dark:text-white">
                            <a href="{{ route('r.backups.show', [$router, $b]) }}" class="hover:text-indigo-600 dark:hover:text-cyan-400">{{ $b->created_at->format('d M Y H:i') }}</a>
                        </td>
                        <td><span class="badge-gray">{{ $b->reason }}</span></td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $b->user?->name ?? '—' }}</td>
                        <td class="text-right">{{ \App\Support\RouterOs::bytes($b->size) }}</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('r.backups.show', [$router, $b]) }}" class="btn-ghost btn-sm">{{ __('Changes') }}</a>
                            <a href="{{ route('r.backups.download', [$router, $b]) }}" class="btn-ghost btn-sm">.rsc</a>
                            <form method="POST" action="{{ route('r.backups.destroy', [$router, $b]) }}" class="inline" onsubmit="return confirm(@js(__('Delete this backup?')))">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm !text-red-600"><x-icon name="trash" class="h-4 w-4" /></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">{{ __('No backups yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 pb-4">{{ $backups->links() }}</div>
    </div>

    @if (auth()->user()->hasPermission('routers.manage') && $router->ssh_fingerprint)
        <form method="POST" action="{{ route('r.backups.reset-fingerprint', $router) }}" class="mt-6 text-sm text-slate-500" onsubmit="return confirm(@js(__('Clear the pinned SSH key? Do this only if you reinstalled the router.')))">
            @csrf
            {{ __('SSH host key pinned:') }} <code class="font-mono text-xs">{{ substr($router->ssh_fingerprint, 0, 16) }}…</code>
            <button class="ml-2 text-indigo-600 hover:underline dark:text-cyan-400">{{ __('Clear') }}</button>
        </form>
    @endif
</x-app-layout>

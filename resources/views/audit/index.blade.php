<x-app-layout>
    <x-slot name="header">{{ __('Audit log') }}</x-slot>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>{{ __('When') }}</th><th>{{ __('Admin') }}</th><th>{{ __('Action') }}</th><th>{{ __('Router') }}</th><th>{{ __('Details') }}</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-slate-600 dark:text-slate-400" title="{{ $log->created_at }}">{{ $log->created_at->translatedFormat('d M H:i') }}</td>
                        <td>{{ $log->user?->name ?? '—' }}</td>
                        <td><span class="badge-indigo">{{ $log->action }}</span></td>
                        <td>{{ $log->router?->name ?? '—' }}</td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $log->details }}</td>
                        <td class="font-mono text-xs text-slate-500">{{ $log->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">{{ __('Nothing recorded yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>

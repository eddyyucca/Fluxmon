<x-app-layout>
    <x-slot name="header">{{ __('Backup') }} #{{ $backup->id }} <span class="text-sm font-normal text-slate-500">· {{ $backup->created_at->translatedFormat('d M Y H:i') }}</span></x-slot>
    <x-slot name="actions">
        <a href="{{ route('r.backups.download', [$router, $backup]) }}" class="btn-secondary btn-sm"><x-icon name="download" class="h-4 w-4" /> .rsc</a>
        <a href="{{ route('r.backups', $router) }}" class="btn-ghost btn-sm">{{ __('All backups') }}</a>
    </x-slot>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-3 text-sm">
        <span class="text-slate-500">{{ __('Compare with') }}</span>
        <select name="against" class="input !w-56" onchange="this.form.submit()">
            @unless ($against) <option value="">{{ __('— nothing, this is the first —') }}</option> @endunless
            @foreach ($others as $o)
                <option value="{{ $o->id }}" @selected($against?->id === $o->id)>#{{ $o->id }} · {{ $o->created_at->translatedFormat('d M Y H:i') }}</option>
            @endforeach
        </select>
    </form>

    <div class="card overflow-hidden">
        @if (! $against)
            <p class="px-5 py-4 text-sm text-slate-500">{{ __('This is the oldest backup, so there is nothing to compare with. Full configuration:') }}</p>
            <pre class="max-h-[70vh] overflow-auto border-t border-slate-100 p-4 font-mono text-xs leading-5 dark:border-slate-800">{{ $backup->content }}</pre>
        @elseif (! $changes)
            <p class="px-5 py-8 text-center text-slate-500">{{ __('No differences from backup #:id.', ['id' => $against->id]) }}</p>
        @else
            <p class="border-b border-slate-100 px-5 py-3 text-sm text-slate-500 dark:border-slate-800">
                {{ __('Changes since backup #:id', ['id' => $against->id]) }}
                <span class="ml-2 text-emerald-700 dark:text-emerald-400">+{{ collect($changes)->where('type', '+')->count() }}</span>
                <span class="ml-1 text-red-700 dark:text-red-400">−{{ collect($changes)->where('type', '-')->count() }}</span>
            </p>
            <div class="max-h-[70vh] overflow-auto font-mono text-xs leading-5">
                @foreach ($changes as $line)
                    @if ($line['type'] === '…')
                        <div class="bg-slate-50 px-4 py-0.5 text-slate-500 dark:bg-slate-800/60">⋯</div>
                    @else
                        <div class="whitespace-pre-wrap break-all px-4 py-0.5 {{ $line['type'] === '+' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : ($line['type'] === '-' ? 'bg-red-50 text-red-800 dark:bg-red-500/10 dark:text-red-300' : 'text-slate-500') }}"><span class="mr-2 select-none opacity-60">{{ $line['type'] === ' ' ? ' ' : $line['type'] }}</span>{{ $line['text'] }}</div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>

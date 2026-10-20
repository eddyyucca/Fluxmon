<x-app-layout>
    <x-slot name="header">{{ __('Routers') }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('routers.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add router') }}</a>
    </x-slot>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Host') }}</th>
                    <th>{{ __('Hotspot') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($routers as $router)
                    <tr x-data="routerTest('{{ route('routers.test', $router) }}')">
                        <td class="font-medium text-slate-900 dark:text-white">
                            <a href="{{ route('r.overview', $router) }}" class="hover:text-indigo-600 dark:hover:text-cyan-400">{{ $router->name }}</a>
                        </td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $router->host }}:{{ $router->port }}</td>
                        <td class="text-slate-600 dark:text-slate-400">{{ $router->hotspot_name ?: '-' }}</td>
                        <td>
                            <span x-show="!label" class="{{ $router->is_online === null ? 'badge-gray' : ($router->is_online ? 'badge-green' : 'badge-red') }}">
                                {{ $router->is_online === null ? __('Not tested') : ($router->is_online ? __('Online') : __('Offline')) }}
                            </span>
                            <span x-cloak x-show="label" x-text="label" :class="ok ? 'badge-green' : 'badge-red'"></span>
                        </td>
                        <td class="whitespace-nowrap text-right">
                            <button type="button" @click="run()" :disabled="busy" class="btn-ghost btn-sm">{{ __('Test') }}</button>
                            <a href="{{ route('routers.edit', $router) }}" class="btn-ghost btn-sm">{{ __('Edit') }}</a>
                            <form method="POST" action="{{ route('routers.destroy', $router) }}" class="inline" onsubmit="return confirm(@js(__('Remove this router and its voucher history?')))">
                                @csrf @method('DELETE')
                                <button class="btn-ghost btn-sm !text-red-600">{{ __('Delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-slate-500">{{ __('No routers yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('routerTest', (url) => ({
                busy: false, label: null, ok: false,
                async run() {
                    this.busy = true; this.ok = true; this.label = 'Testing…';
                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json'},
                        });
                        const d = await res.json();
                        this.ok = d.ok;
                        this.label = d.ok ? `Online · ${d.board} · v${d.version}` : d.message;
                    } catch (e) {
                        this.ok = false; this.label = 'Request failed';
                    }
                    this.busy = false;
                },
            }));
        });
    </script>
</x-app-layout>

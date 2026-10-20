<x-app-layout>
    <x-slot name="header">{{ __('Active sessions') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    <div x-data="activeSessions(@js(route('r.active.data', $router)), @js(route('r.active.kick', $router)), @js(__('Disconnect this user?')))" x-init="load(); setInterval(() => load(), 5000)">
        <p x-show="error" x-cloak class="alert-err" x-text="error"></p>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <input x-model="q" placeholder="{{ __('Filter by user, IP or MAC') }}" class="input !w-72">
            <div class="muted"><b x-text="filtered().length"></b> {{ __('online') }} · {{ __('refreshes every 5 s') }}</div>
        </div>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr>
                    <th>{{ __('User') }}</th><th>{{ __('Address') }}</th><th>{{ __('MAC') }}</th><th>{{ __('Uptime') }}</th><th>{{ __('Downloaded') }}</th><th>{{ __('Uploaded') }}</th><th></th>
                </tr></thead>
                <tbody>
                    <template x-for="s in filtered()" :key="s.id">
                        <tr>
                            <td class="font-medium text-slate-900 dark:text-white" x-text="s.user"></td>
                            <td x-text="s.address"></td>
                            <td class="font-mono text-xs text-slate-500" x-text="s.mac"></td>
                            <td x-text="s.uptime"></td>
                            <td x-text="fmtBytes(s.bytes_out)"></td>
                            <td x-text="fmtBytes(s.bytes_in)"></td>
                            <td class="text-right"><button type="button" class="btn-ghost btn-sm !text-red-700 dark:!text-red-400" @click="kick(s)">{{ __('Disconnect') }}</button></td>
                        </tr>
                    </template>
                    <tr x-show="loaded && filtered().length === 0" x-cloak><td colspan="7" class="py-10 text-center text-slate-500">{{ __('Nobody is online right now.') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('activeSessions', (dataUrl, kickUrl, confirmMsg) => ({
                sessions: [], q: '', error: '', loaded: false,
                filtered() {
                    const q = this.q.toLowerCase();
                    return this.sessions.filter((s) => !q || [s.user, s.address, s.mac].some((v) => (v || '').toLowerCase().includes(q)));
                },
                async load() {
                    try {
                        const d = await (await fetch(dataUrl, {headers: {Accept: 'application/json'}})).json();
                        if (d.ok) { this.sessions = d.sessions; this.error = ''; } else { this.error = d.message; }
                    } catch (e) { this.error = 'Request failed'; }
                    this.loaded = true;
                },
                async kick(s) {
                    if (!confirm(confirmMsg)) return;
                    const res = await fetch(kickUrl, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json', 'Content-Type': 'application/json'},
                        body: JSON.stringify({id: s.id, user: s.user}),
                    });
                    const d = await res.json();
                    if (!d.ok) this.error = d.message;
                    this.load();
                },
            }));
        });
    </script>
</x-app-layout>

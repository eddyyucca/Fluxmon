<x-app-layout>
    <x-slot name="header">{{ __('Logs') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    <div x-data="logViewer(@js(route('r.logs.data', $router)))" x-init="load()">
        <p x-show="error" x-cloak class="alert-err" x-text="error"></p>

        <div class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="label">{{ __('Topic') }}</label>
                <select x-model="topic" @change="load()" class="input !w-44">
                    <option value="">{{ __('All') }}</option>
                    <template x-for="t in topics" :key="t"><option :value="t" x-text="t"></option></template>
                </select>
            </div>
            <div>
                <label class="label">{{ __('Search') }}</label>
                <input x-model.debounce.400ms="q" @input.debounce.400ms="load()" placeholder="{{ __('Text in message') }}" class="input !w-64">
            </div>
            <button type="button" class="btn-secondary" @click="load()"><x-icon name="refresh" class="h-4 w-4" /> {{ __('Refresh') }}</button>
            <label class="flex items-center gap-2 pb-2 text-sm"><input type="checkbox" x-model="auto" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"> {{ __('Auto refresh') }}</label>
        </div>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th class="w-40">{{ __('Time') }}</th><th class="w-48">{{ __('Topics') }}</th><th>{{ __('Message') }}</th></tr></thead>
                <tbody>
                    <template x-for="(l, i) in lines" :key="i">
                        <tr>
                            <td class="whitespace-nowrap text-slate-500" x-text="l.time"></td>
                            <td><template x-for="t in l.topics.split(',')"><span class="badge-gray mr-1" :class="t === 'error' || t === 'critical' ? '!bg-red-100 !text-red-700' : (t === 'warning' ? '!bg-amber-100 !text-amber-700' : '')" x-text="t"></span></template></td>
                            <td class="font-mono text-xs" x-text="l.message"></td>
                        </tr>
                    </template>
                    <tr x-show="loaded && lines.length === 0" x-cloak><td colspan="3" class="py-10 text-center text-slate-500">{{ __('No log entries match.') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('logViewer', (url) => ({
                lines: [], topics: [], topic: '', q: '', error: '', loaded: false, auto: false,
                init() { setInterval(() => this.auto && this.load(), 5000); },
                async load() {
                    try {
                        const p = new URLSearchParams({topic: this.topic, q: this.q});
                        const d = await (await fetch(`${url}?${p}`, {headers: {Accept: 'application/json'}})).json();
                        if (d.ok) { this.lines = d.lines; this.topics = d.topics; this.error = ''; } else { this.error = d.message; }
                    } catch (e) { this.error = 'Request failed'; }
                    this.loaded = true;
                },
            }));
        });
    </script>
</x-app-layout>

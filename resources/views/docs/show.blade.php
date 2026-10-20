<x-app-layout>
    <x-slot name="header">{{ __('Documentation') }}</x-slot>

    <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 lg:gap-8 lg:grid-cols-[14rem_minmax(0,1fr)] xl:grid-cols-[14rem_minmax(0,1fr)_13rem]">
        {{-- Pages --}}
        <nav class="min-w-0 lg:sticky lg:top-24 lg:self-start" aria-label="{{ __('Documentation') }}">
            <ul class="flex gap-1 overflow-x-auto pb-2 lg:block lg:space-y-0.5 lg:overflow-visible lg:pb-0">
                @foreach ($pages as $s => $title)
                    <li class="shrink-0">
                        <a href="{{ route('docs', $s) }}"
                           class="nav-link items-start whitespace-nowrap lg:whitespace-normal {{ $s === $slug ? 'nav-link-active' : '' }}">
                            <span class="w-5 shrink-0 text-right text-xs opacity-60">{{ $loop->iteration }}</span> {{ __($title) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Article --}}
        <article class="min-w-0" x-data="docToc()" x-init="build()">
            <div class="card card-pad sm:p-8">
                <div class="prose-docs" x-ref="body">
                    <h1>{{ __($pages[$slug]) }}</h1>
                    @include($content)
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 text-sm">
                @if ($neighbours['prev'])
                    <a href="{{ route('docs', $neighbours['prev']) }}" class="btn-secondary">← {{ __($pages[$neighbours['prev']]) }}</a>
                @else <span></span> @endif
                @if ($neighbours['next'])
                    <a href="{{ route('docs', $neighbours['next']) }}" class="btn-secondary">{{ __($pages[$neighbours['next']]) }} →</a>
                @endif
            </div>

            {{-- On this page (built from the h2 headings; shown on wide screens) --}}
            <template x-teleport="#doc-toc-slot">
                <div x-show="items.length" x-cloak>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('On this page') }}</div>
                    <ul class="mt-2 space-y-1.5 border-l border-slate-200 text-sm dark:border-slate-800">
                        <template x-for="i in items" :key="i.id">
                            <li><a :href="'#' + i.id" class="-ml-px block border-l border-transparent pl-3 text-slate-500 hover:border-indigo-500 hover:text-slate-900 dark:hover:text-white" x-text="i.text"></a></li>
                        </template>
                    </ul>
                </div>
            </template>
        </article>

        <aside class="hidden xl:sticky xl:top-24 xl:block xl:self-start" id="doc-toc-slot"></aside>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('docToc', () => ({
                items: [],
                build() {
                    const slug = (t) => t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                    this.items = [...this.$refs.body.querySelectorAll('h2')].map((h) => {
                        h.id = h.id || slug(h.textContent);
                        return {id: h.id, text: h.textContent};
                    });
                },
            }));
        });
    </script>
</x-app-layout>

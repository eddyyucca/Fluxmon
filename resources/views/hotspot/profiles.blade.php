<x-app-layout>
    <x-slot name="header">{{ __('Profiles') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    @if ($error) <div class="alert-err">{{ $error }}</div> @endif

    <div x-data="profileForm(@js(route('r.profiles.update', [$router, '__ID__'])), @js(route('r.profiles.store', $router)))">
        {{-- Form: adds a profile, or edits the one picked from the table --}}
        <form method="POST" :action="editing ? updateUrl.replace('__ID__', encodeURIComponent(editing)) : storeUrl" class="card card-pad mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="old_name" :value="old_name">

            <div class="sm:col-span-2 lg:col-span-3 flex items-center justify-between">
                <h3 class="card-title" x-text="editing ? @js(__('Edit profile')) : @js(__('New profile'))"></h3>
                <button type="button" x-show="editing" x-cloak @click="reset()" class="btn-ghost btn-sm">{{ __('Cancel edit') }}</button>
            </div>

            <div><label class="label">{{ __('Name') }}</label><input name="name" x-model="f.name" class="input" required></div>
            <div>
                <label class="label">{{ __('Rate limit (upload/download)') }}</label>
                <input name="rate_limit" x-model="f.rate_limit" placeholder="2M/5M" class="input">
            </div>
            <div><label class="label">{{ __('Shared users') }}</label><input type="number" min="1" name="shared_users" x-model="f.shared_users" class="input" required></div>
            <div><label class="label">{{ __('Price') }} ({{ $router->currency }})</label><input type="number" min="0" step="any" name="price" x-model="f.price" class="input" required></div>
            <div>
                <label class="label">{{ __('Validity after first login') }}</label>
                <div class="flex gap-2">
                    <input type="number" min="1" name="validity_value" x-model="f.validity_value" placeholder="{{ __('No expiry') }}" class="input">
                    <select name="validity_unit" x-model="f.validity_unit" class="input !w-28">
                        <option value="m">{{ __('minutes') }}</option><option value="h">{{ __('hours') }}</option><option value="d">{{ __('days') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="label">{{ __('When expired') }}</label>
                <select name="expire_action" x-model="f.expire_action" class="input">
                    <option value="remove">{{ __('Remove the user') }}</option>
                    <option value="disable">{{ __('Disable the user') }}</option>
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3"><button class="btn-primary" x-text="editing ? @js(__('Save changes')) : @js(__('Create profile'))"></button></div>
        </form>

        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr>
                    <th>{{ __('Name') }}</th><th>{{ __('Rate limit') }}</th><th>{{ __('Shared') }}</th><th>{{ __('Price') }}</th><th>{{ __('Validity') }}</th><th>{{ __('On expiry') }}</th><th></th>
                </tr></thead>
                <tbody>
                    @forelse ($profiles as $p)
                        <tr>
                            <td class="font-medium text-slate-900 dark:text-white">{{ $p['name'] }}</td>
                            <td class="text-slate-600 dark:text-slate-400">{{ $p['rate_limit'] ?: __('Unlimited') }}</td>
                            <td>{{ $p['shared_users'] }}</td>
                            <td>{{ \App\Support\Fmt::money($p['price'], $router->currency) }}</td>
                            <td>{{ \App\Support\RouterOs::humanMinutes($p['validity_minutes']) }}</td>
                            <td><span class="badge-gray">{{ $p['expire_action'] === 'disable' ? __('Disable') : __('Remove') }}</span></td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" class="btn-ghost btn-sm" @click="edit(@js($p))">{{ __('Edit') }}</button>
                                <form method="POST" action="{{ route('r.profiles.destroy', [$router, $p['id']]) }}" class="inline" onsubmit="return confirm(@js(__('Delete this profile?')))">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="name" value="{{ $p['name'] }}">
                                    <button class="btn-ghost btn-sm !text-red-600">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-slate-500">{{ __('No profiles found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('profileForm', (updateUrl, storeUrl) => {
                const blank = () => ({name: '', rate_limit: '', shared_users: 1, price: 0, validity_value: '', validity_unit: 'h', expire_action: 'remove'});
                return {
                    updateUrl, storeUrl, editing: null, old_name: '', f: blank(),
                    edit(p) {
                        this.editing = p.id; this.old_name = p.name;
                        this.f = {
                            name: p.name, rate_limit: p.rate_limit, shared_users: p.shared_users, price: p.price,
                            validity_value: p.validity_value ?? '', validity_unit: p.validity_unit, expire_action: p.expire_action,
                        };
                        window.scrollTo({top: 0, behavior: 'smooth'});
                    },
                    reset() { this.editing = null; this.old_name = ''; this.f = blank(); },
                };
            });
        });
    </script>
</x-app-layout>

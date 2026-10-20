<x-app-layout>
    <x-slot name="header">{{ __('Users') }} <span class="text-sm font-normal text-slate-500">· {{ $router->name }}</span></x-slot>

    @if ($error)
        <div class="alert-err">{{ $error }}</div>
    @endif

    <div x-data="{ add: {{ $errors->any() ? 'true' : 'false' }} }">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="label" for="q">{{ __('Search') }}</label>
                    <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Name or comment') }}" class="input !w-52">
                </div>
                <div>
                    <label class="label" for="profile">{{ __('Profile') }}</label>
                    <select id="profile" name="profile" class="input !w-40">
                        <option value="">{{ __('All') }}</option>
                        @foreach ($profiles as $p) <option value="{{ $p }}" @selected(($filters['profile'] ?? '') === $p)>{{ $p }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="status">{{ __('Status') }}</label>
                    <select id="status" name="status" class="input !w-36">
                        <option value="">{{ __('All') }}</option>
                        @foreach (['online' => __('Online'), 'disabled' => __('Disabled'), 'unused' => __('Never used')] as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn-secondary">{{ __('Filter') }}</button>
            </form>

            <button type="button" class="btn-primary" @click="add = !add"><x-icon name="plus" class="h-4 w-4" /> {{ __('Add user') }}</button>
        </div>

        {{-- Add user --}}
        <form x-cloak x-show="add" x-transition method="POST" action="{{ route('r.users.store', $router) }}" class="card card-pad mb-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div><label class="label">{{ __('Username') }}</label><input name="name" value="{{ old('name') }}" class="input" required></div>
            <div><label class="label">{{ __('Password') }}</label><input name="password" value="{{ old('password') }}" class="input" required></div>
            <div>
                <label class="label">{{ __('Profile') }}</label>
                <select name="profile" class="input" required>
                    @foreach ($profiles as $p) <option @selected(old('profile') === $p)>{{ $p }}</option> @endforeach
                </select>
            </div>
            <div><label class="label">{{ __('Time limit (optional)') }}</label><x-duration name="time_limit" /></div>
            <div><label class="label">{{ __('Data limit, MB (optional)') }}</label><input type="number" min="1" name="data_limit_mb" value="{{ old('data_limit_mb') }}" class="input"></div>
            <div><label class="label">{{ __('Comment') }}</label><input name="comment" value="{{ old('comment') }}" class="input"></div>
            <div class="sm:col-span-2 lg:col-span-3"><button class="btn-primary">{{ __('Create user') }}</button></div>
        </form>

        {{-- Table with bulk actions --}}
        <form method="POST" action="{{ route('r.users.bulk', $router) }}" x-ref="bulk"
              x-data="{
                  all: false,
                  toggleAll() { this.$refs.table.querySelectorAll('input[name=\'ids[]\']').forEach(c => c.checked = this.all) },
                  count() { return this.$refs.table.querySelectorAll('input[name=\'ids[]\']:checked').length },
                  one(action, id, msg) {
                      if (action === 'delete' && !confirm(msg)) return;
                      this.$refs.table.querySelectorAll('input[name=\'ids[]\']').forEach(c => c.checked = c.value === id);
                      this.$refs.action.value = action;
                      this.$el.requestSubmit();
                  },
              }"
              @change="$refs.n && ($refs.n.textContent = count())">
            @csrf
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <span class="muted"><b x-ref="n">0</b> {{ __('selected') }}</span>
                    <select name="action" x-ref="action" class="input !w-40 !py-1.5">
                        <option value="enable">{{ __('Enable') }}</option>
                        <option value="disable">{{ __('Disable') }}</option>
                        <option value="reset">{{ __('Reset counters') }}</option>
                        <option value="delete">{{ __('Delete') }}</option>
                    </select>
                    <button class="btn-secondary btn-sm" @click="if (count() === 0) { $event.preventDefault(); } else if ($refs.action.value === 'delete' && !confirm(@js(__('Delete the selected users?')))) { $event.preventDefault(); }">{{ __('Apply') }}</button>
                    <span class="ml-auto muted">{{ $paginator->total() }} {{ __('users') }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table" x-ref="table">
                        <thead>
                            <tr>
                                <th class="w-10"><input type="checkbox" x-model="all" @change="toggleAll()" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"></th>
                                <th>{{ __('User') }}</th>
                                <th>{{ __('Password') }}</th>
                                <th>{{ __('Profile') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Uptime') }}</th>
                                <th>{{ __('Data') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($paginator as $u)
                                <tr>
                                    <td><input type="checkbox" name="ids[]" value="{{ $u['id'] }}" class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800"></td>
                                    <td>
                                        <div class="font-medium text-slate-900 dark:text-white">{{ $u['name'] }}</div>
                                        @if ($u['comment']) <div class="text-xs text-slate-500">{{ $u['comment'] }}</div> @endif
                                    </td>
                                    <td class="font-mono text-xs text-slate-500">{{ $u['password'] }}</td>
                                    <td><span class="badge-indigo">{{ $u['profile'] }}</span></td>
                                    <td>
                                        @if ($u['disabled']) <span class="badge-red">{{ __('Disabled') }}</span>
                                        @elseif ($u['online']) <span class="badge-green">{{ __('Online') }}</span>
                                        @else <span class="badge-gray">{{ __('Idle') }}</span> @endif
                                    </td>
                                    <td class="text-slate-600 dark:text-slate-400">{{ $u['uptime'] }}@if ($u['limit_uptime']) <span class="text-slate-400">/ {{ $u['limit_uptime'] }}</span>@endif</td>
                                    <td class="whitespace-nowrap text-slate-600 dark:text-slate-400">
                                        {{ \App\Support\RouterOs::bytes($u['bytes_in'] + $u['bytes_out']) }}
                                        @if ($u['limit_bytes']) <span class="text-slate-400">/ {{ \App\Support\RouterOs::bytes($u['limit_bytes']) }}</span> @endif
                                    </td>
                                    <td class="whitespace-nowrap text-right">
                                        <button type="button" class="btn-ghost btn-sm" @click="one('{{ $u['disabled'] ? 'enable' : 'disable' }}', @js($u['id']))">{{ $u['disabled'] ? __('Enable') : __('Disable') }}</button>
                                        <button type="button" class="btn-ghost btn-sm" @click="one('reset', @js($u['id']))">{{ __('Reset') }}</button>
                                        <button type="button" class="btn-ghost btn-sm !text-red-600" @click="one('delete', @js($u['id']), @js(__('Delete this user?')))">{{ __('Delete') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="py-10 text-center text-slate-500">{{ __('No users found.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <div class="mt-4">{{ $paginator->links() }}</div>
    </div>
</x-app-layout>

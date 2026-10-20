<x-app-layout>
    <x-slot name="header">{{ $router->name }}</x-slot>

    <div class="mx-auto mt-4 w-full max-w-md sm:mt-10">
        <div class="card card-pad">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"><x-icon name="key" /></span>
                <div class="min-w-0">
                    <h2 class="break-words text-lg font-semibold text-slate-900 dark:text-white">{{ __('Sign in to this device') }}</h2>
                    <p class="muted mt-0.5 break-words">{{ $router->name }}</p>
                </div>
            </div>

            <p class="muted mt-4">{{ __('Each device has its own management accounts. Enter the account of this device. Ask a superadmin or administrator if you do not have one.') }}</p>

            <form method="POST" action="{{ route('r.login.store', $router) }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="username">{{ __('Device username') }}</label>
                    <input id="username" name="username" value="{{ old('username') }}" class="input" required autofocus autocomplete="off">
                    <x-input-error :messages="$errors->get('username')" class="input-error" />
                </div>
                <div>
                    <label class="label" for="password">{{ __('Device password') }}</label>
                    <input id="password" type="password" name="password" class="input" required autocomplete="off">
                    <x-input-error :messages="$errors->get('password')" class="input-error" />
                </div>
                <button class="btn-primary w-full">{{ __('Sign in to device') }}</button>
            </form>
        </div>

        <p class="mt-4 text-center text-sm"><a href="{{ route('dashboard') }}" class="text-indigo-600 hover:underline dark:text-cyan-400">← {{ __('Back to dashboard') }}</a></p>
    </div>
</x-app-layout>

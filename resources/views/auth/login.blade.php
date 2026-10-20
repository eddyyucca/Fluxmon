<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('Welcome back') }}</h2>
    <p class="muted mt-1">{{ __('Sign in to manage your routers.') }}</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label class="label" for="email">{{ __('Email') }}</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="input-error" />
        </div>

        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" class="input" type="password" name="password" required autocomplete="current-password">
            <x-input-error :messages="$errors->get('password')" class="input-error" />
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
            {{ __('Remember me') }}
        </label>

        <button class="btn-primary w-full">{{ __('Sign in') }}</button>
    </form>
</x-guest-layout>

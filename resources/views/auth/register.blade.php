<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('Create the admin account') }}</h2>
    <p class="muted mt-1">{{ __('This is the first run. After this, only admins can add more accounts.') }}</p>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label class="label" for="name">{{ __('Name') }}</label>
            <input id="name" class="input" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            <x-input-error :messages="$errors->get('name')" class="input-error" />
        </div>

        <div>
            <label class="label" for="email">{{ __('Email') }}</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            <x-input-error :messages="$errors->get('email')" class="input-error" />
        </div>

        <div>
            <label class="label" for="password">{{ __('Password') }}</label>
            <input id="password" class="input" type="password" name="password" required autocomplete="new-password">
            <x-input-error :messages="$errors->get('password')" class="input-error" />
        </div>

        <div>
            <label class="label" for="password_confirmation">{{ __('Confirm password') }}</label>
            <input id="password_confirmation" class="input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button class="btn-primary w-full">{{ __('Create account') }}</button>
    </form>
</x-guest-layout>

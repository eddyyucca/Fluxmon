{{-- The Fluxa logo. `on` says what it sits on: light (default, switches with dark mode) or dark (always the white logo). --}}
@props(['on' => 'auto'])

@if ($on === 'dark')
    <img src="{{ asset('brand/fluxa-white.png') }}" alt="{{ config('fluxmon.developer.name') }}" {{ $attributes->merge(['class' => 'h-6 w-auto']) }}>
@else
    <img src="{{ asset('brand/fluxa-black.png') }}" alt="{{ config('fluxmon.developer.name') }}" {{ $attributes->merge(['class' => 'h-6 w-auto dark:hidden']) }}>
    <img src="{{ asset('brand/fluxa-white.png') }}" alt="" aria-hidden="true" {{ $attributes->merge(['class' => 'hidden h-6 w-auto dark:block']) }}>
@endif

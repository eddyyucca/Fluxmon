{{-- EN | ID toggle. Works for guests too; the choice is remembered per browser and per signed-in account. --}}
@php($current = app()->getLocale())

<form method="POST" action="{{ route('locale') }}" {{ $attributes->merge(['class' => 'inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs font-semibold dark:border-slate-700 dark:bg-slate-900']) }}>
    @csrf
    @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $code => $name)
        <button name="locale" value="{{ $code }}" title="{{ $name }}" aria-label="{{ $name }}" @if ($code === $current) aria-current="true" @endif
                class="rounded-md px-2.5 py-1 uppercase transition {{ $code === $current ? 'bg-indigo-600 text-white' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white' }}">{{ $code }}</button>
    @endforeach
</form>

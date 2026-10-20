{{-- Number + unit pair. Posts as {name}, {name}_unit; the controller converts with RouterOs::toMinutes(). --}}
@props(['name', 'minutes' => null, 'placeholder' => ''])

@php
    [$value, $unit] = \App\Support\RouterOs::splitMinutes($minutes ? (int) $minutes : null);
    $value = old($name, $value);
    $unit = old($name.'_unit', $unit);
@endphp

<div class="flex gap-2">
    <input type="number" min="1" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" class="input" x-ref="{{ $name }}">
    <select name="{{ $name }}_unit" class="input !w-28" x-ref="{{ $name }}_unit">
        @foreach (['m' => __('minutes'), 'h' => __('hours'), 'd' => __('days')] as $k => $label)
            <option value="{{ $k }}" @selected($unit === $k)>{{ $label }}</option>
        @endforeach
    </select>
</div>

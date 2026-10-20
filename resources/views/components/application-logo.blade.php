{{-- Fluxmon mark: rounded tile with three flowing signal waves. Colors are fixed; size via class. --}}
<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Fluxmon" {{ $attributes }}>
    <defs>
        <linearGradient id="fluxmon-g" x1="0" y1="0" x2="64" y2="64" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#4f46e5"/>
            <stop offset="1" stop-color="#06b6d4"/>
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="15" fill="url(#fluxmon-g)"/>
    <g fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round">
        <path d="M12 22c6-8 12-8 20 0s14 8 20 0" opacity=".55"/>
        <path d="M12 34c6-8 12-8 20 0s14 8 20 0"/>
        <path d="M12 46c6-8 12-8 20 0s14 8 20 0" opacity=".55"/>
    </g>
</svg>

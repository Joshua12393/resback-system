@props(['name'])
<svg {{ $attributes->merge(['class' => 'ui-icon', 'viewBox' => '0 0 24 24', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>
            @break
        @case('accounts')
            <circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 4v2"/>
            @break
        @case('profile')
            <circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
            @break
        @case('feedback')
            <path d="M5 3h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H9l-6 3V5a2 2 0 0 1 2-2Z"/><path d="M7 8h10M7 12h6"/>
            @break
        @case('logout')
            <path d="M9 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h4M14 7l5 5-5 5M8 12h11"/>
            @break
        @case('history')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
    @endswitch
</svg>

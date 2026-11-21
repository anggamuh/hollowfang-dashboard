@props(['name' => 'overview'])
@php
    $paths = [
        'overview' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'monitoring' => 'M3 12h4l3-8 4 16 3-8h4',
        'gateway' => 'M8 8a6 6 0 0 1 8 0 M5 5a10 10 0 0 1 14 0 M10 11a3 3 0 0 1 4 0 M12 15v6',
        'members' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M17 4a4 4 0 0 1 0 7 M22 21v-2a4 4 0 0 0-3-3.9',
        'moderation' => 'M12 3 3 7v5c0 5 9 9 9 9s9-4 9-9V7z M8 12l3 3 5-6',
        'music' => 'M9 18V5l12-2v13 M9 8l12-2 M9 18a3 3 0 1 1-3-3 M21 16a3 3 0 1 1-3-3',
        'webhooks' => 'M3 11v4h4l10 5V6L7 11z M7 15l2 6 M21 9v8',
        'events' => 'M4 5h16v16H4z M8 3v4 M16 3v4 M4 11h16 M8 15h2 M14 15h2',
        'community' => 'M12 3 4 7v10l8 4 8-4V7z M4 7l8 5 8-5 M12 12v9',
        'logs' => 'M6 3h12v18H6z M9 7h6 M9 11h6 M9 15h4',
        'settings' => 'M4 7h16 M4 17h16 M8 4v6 M16 14v6',
        'search' => 'M21 21l-5-5 M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
        'arrow' => 'M5 12h14 M13 6l6 6-6 6',
        'close' => 'M6 6l12 12 M6 18 18 6',
        'menu' => 'M4 6h16 M4 12h16 M4 18h16',
        'refresh' => 'M20 7v5h-5 M4 17v-5h5 M6 7a7 7 0 0 1 12-2l2 3 M4 16l2 3a7 7 0 0 0 12-2',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'hf-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['overview'] }}" /></svg>

@php
    $classes = $attributes->get('class', '');
    $isWhite = str_contains($classes, 'text-white');
    $logoSrc = $isWhite ? 'images/autobusfblanco.svg' : 'images/parada-de-autobus.svg';
@endphp
<img src="{{ asset($logoSrc) }}" alt="Ubicaditos Logo" {{ $attributes }} />

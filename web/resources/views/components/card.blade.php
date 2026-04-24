@props(['title' => null, 'danger' => false])
@php
    $borderStyle = $danger ? 'border-color: var(--cherry); border-width: 3px;' : '';
    $titleBorder = $danger ? 'var(--cherry)' : 'var(--coffee)';
    $titleColor  = $danger ? ' color: var(--cherry);' : '';
@endphp

<div {{ $attributes->merge(['class' => 'card', 'style' => $borderStyle]) }}>
    @if ($title)
        <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin-bottom: 16px; border-bottom: 3px solid {{ $titleBorder }}; padding-bottom: 10px;{{ $titleColor }}">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>

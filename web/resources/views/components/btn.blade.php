@props(['variant' => 'primary', 'size' => null, 'href' => null, 'type' => 'button'])
@php
    $cls = 'btn-' . $variant . ($size ? ' btn-' . $size : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif

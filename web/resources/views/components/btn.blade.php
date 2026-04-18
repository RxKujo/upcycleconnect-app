@props(['variant' => 'primary', 'size' => null, 'href' => null, 'type' => 'button', 'block' => false])
@php
    $classes = ['btn', 'btn-' . $variant];
    if ($size) $classes[] = 'btn-' . $size;
    if ($block) $classes[] = 'btn-block';
    $cls = implode(' ', $classes);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $cls]) }}>{{ $slot }}</button>
@endif

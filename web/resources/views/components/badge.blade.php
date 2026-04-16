@props(['variant' => 'waiting'])
<span {{ $attributes->merge(['class' => 'badge badge-' . $variant]) }}>{{ $slot }}</span>

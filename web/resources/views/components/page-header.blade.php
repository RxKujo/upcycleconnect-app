@props(['title'])

<div class="page-header">
    <h1 class="page-title">{{ $title }}</h1>
    {{ $slot }}
</div>

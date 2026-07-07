@props(['title', 'i18n' => null])

<div class="page-header">
    <h1 class="page-title" @if($i18n) data-i18n="{{ $i18n }}" @endif>{{ $title }}</h1>
    {{ $slot }}
</div>

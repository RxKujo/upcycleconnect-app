@props(['id', 'title', 'titleStyle' => ''])

<div class="modal-overlay" id="{{ $id }}">
    <div class="modal">
        <h3 @if($titleStyle) style="{{ $titleStyle }}" @endif>{{ $title }}</h3>
        {{ $slot }}
    </div>
</div>

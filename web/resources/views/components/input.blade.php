@props(['label' => null, 'name', 'type' => 'text', 'placeholder' => '', 'value' => null, 'required' => false, 'error' => null])

<div class="form-group">
    @if ($label)
        <label class="form-label" for="{{ $name }}">{{ $label }}</label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        class="form-input{{ $error ? ' error' : '' }}"
        placeholder="{{ $placeholder }}"
        @if ($value !== null) value="{{ old($name, $value) }}" @endif
        @if ($required) required @endif
        {{ $attributes->except(['class', 'type', 'id', 'name', 'placeholder', 'value', 'required']) }}
    >
    @if ($error)
        <p class="error-message" style="display: block;">{{ $error }}</p>
    @endif
</div>

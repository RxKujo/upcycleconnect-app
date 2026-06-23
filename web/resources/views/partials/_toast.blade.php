@php
    $toastMessages = [];
    if (session('toast_success')) $toastMessages[] = ['type' => 'success', 'text' => session('toast_success')];
    if (session('toast_error'))   $toastMessages[] = ['type' => 'error',   'text' => session('toast_error')];
    if (session('toast_warning')) $toastMessages[] = ['type' => 'warning', 'text' => session('toast_warning')];
    if (session('toast_info'))    $toastMessages[] = ['type' => 'info',    'text' => session('toast_info')];
@endphp

@if(count($toastMessages))
<style>
#toast-container {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 380px;
    pointer-events: none;
}
.toast-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px 18px;
    border: 3px solid #120309;
    box-shadow: 5px 5px 0px #120309;
    font-family: 'Outfit', sans-serif;
    font-size: 0.92rem;
    line-height: 1.45;
    color: #120309;
    pointer-events: all;
    animation: toast-in 0.25s ease-out forwards;
    position: relative;
    cursor: default;
}
.toast-item.toast-success { background: #D8C99B; }
.toast-item.toast-error   { background: #A4243B; color: #F5F0E1; }
.toast-item.toast-warning { background: #D8C99B; }
.toast-item.toast-info    { background: #18607D; color: #F5F0E1; }
.toast-icon {
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 1px;
    font-family: sans-serif;
}
.toast-text { flex: 1; }
.toast-close {
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    color: inherit;
    opacity: 0.7;
    padding: 0 0 0 8px;
    font-family: 'DM Mono', monospace;
}
.toast-close:hover { opacity: 1; }
.toast-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: rgba(18,3,9,0.3);
    animation: toast-progress 5s linear forwards;
}
.toast-item.toast-error   .toast-progress { background: rgba(245,240,225,0.4); }
.toast-item.toast-info    .toast-progress { background: rgba(245,240,225,0.4); }
@keyframes toast-in {
    from { opacity: 0; transform: translateX(40px); }
    to   { opacity: 1; transform: translateX(0); }
}
@keyframes toast-out {
    from { opacity: 1; transform: translateX(0); max-height: 120px; margin-bottom: 0; }
    to   { opacity: 0; transform: translateX(40px); max-height: 0; padding: 0; margin: 0; border-width: 0; }
}
@keyframes toast-progress {
    from { width: 100%; }
    to   { width: 0%; }
}
</style>

<div id="toast-container" role="status" aria-live="polite">
    @foreach($toastMessages as $msg)
    <div class="toast-item toast-{{ $msg['type'] }}" data-autohide="5000">
        <span class="toast-icon">
            @if($msg['type'] === 'success') ✓
            @elseif($msg['type'] === 'error') !
            @elseif($msg['type'] === 'warning') ⚠
            @else ℹ
            @endif
        </span>
        <span class="toast-text">{{ $msg['text'] }}</span>
        <button class="toast-close" onclick="this.closest('.toast-item').dispatchEvent(new Event('dismiss'))" aria-label="Fermer">✕</button>
        <div class="toast-progress"></div>
    </div>
    @endforeach
</div>

<script>
(function () {
    document.querySelectorAll('.toast-item').forEach(function (el) {
        var delay = parseInt(el.dataset.autohide) || 5000;
        function dismiss() {
            el.style.animation = 'toast-out 0.3s ease-in forwards';
            el.addEventListener('animationend', function () { el.remove(); }, { once: true });
        }
        el.addEventListener('dismiss', dismiss);
        setTimeout(dismiss, delay);
    });
})();
</script>
@endif

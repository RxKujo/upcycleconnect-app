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

{{-- ─── Confirmation modale globale (remplace window.confirm) ───────────────── --}}
{{-- Usage : <form ... data-confirm="Votre message ?"> ; ou JS : confirmAction(msg, callback) --}}
<div id="confirm-overlay" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:100000; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#F5F0E1; border:3px solid #120309; box-shadow:6px 6px 0 #120309; max-width:420px; width:100%; padding:28px;">
        <p id="confirm-message" style="font-family:'Outfit',sans-serif; font-size:1.05rem; color:#120309; margin:0 0 24px; line-height:1.4;">Confirmer cette action ?</p>
        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" id="confirm-cancel" style="font-family:'Bebas Neue',sans-serif; letter-spacing:0.08em; text-transform:uppercase; cursor:pointer; background:#F5F0E1; color:#120309; border:3px solid #120309; padding:9px 22px; font-size:1.05rem; box-shadow:3px 3px 0 rgba(18,3,9,0.25);">Annuler</button>
            <button type="button" id="confirm-ok" style="font-family:'Bebas Neue',sans-serif; letter-spacing:0.08em; text-transform:uppercase; cursor:pointer; background:#A4243B; color:#F5F0E1; border:3px solid #120309; padding:9px 22px; font-size:1.05rem; box-shadow:3px 3px 0 rgba(18,3,9,0.25);">Confirmer</button>
        </div>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('confirm-overlay');
    if (!overlay) return;
    var msgEl = document.getElementById('confirm-message');
    var okBtn = document.getElementById('confirm-ok');
    var cancelBtn = document.getElementById('confirm-cancel');
    var onOk = null, onCancel = null;

    function open(message, okCb, cancelCb) {
        msgEl.textContent = message || 'Confirmer cette action ?';
        onOk = okCb; onCancel = cancelCb;
        overlay.style.display = 'flex';
    }
    function resolve(confirmed) {
        var ok = onOk, cancel = onCancel;
        overlay.style.display = 'none';
        onOk = null; onCancel = null;
        if (confirmed) { if (ok) ok(); }
        else if (cancel) { cancel(); }
    }

    okBtn.addEventListener('click', function () { resolve(true); });
    cancelBtn.addEventListener('click', function () { resolve(false); });
    overlay.addEventListener('mousedown', function (e) { if (e.target === overlay) resolve(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && overlay.style.display === 'flex') resolve(false); });

    // Intercepte les formulaires marqués data-confirm
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute && form.hasAttribute('data-confirm') && !form.dataset.confirmed) {
            e.preventDefault();
            open(form.getAttribute('data-confirm'), function () {
                form.dataset.confirmed = '1';
                if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
            });
        }
    }, true);

    // Usage programmatique (renvoie une Promise<boolean>) :
    //   if (!await confirmAction('message')) return;
    window.confirmAction = function (message) {
        return new Promise(function (res) {
            open(message, function () { res(true); }, function () { res(false); });
        });
    };
})();
</script>

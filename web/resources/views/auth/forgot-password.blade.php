@extends('layouts.public')
@section('title', 'Mot de passe oublié')

{{-- Vue : mot de passe oublié. Réponse générique (anti-énumération). --}}

{{-- === Contenu === --}}
@section('content')
<div class="page-container" style="max-width:500px;margin:80px auto;text-align:center;">
    <h1 style="font-family:'Bebas Neue',sans-serif;font-size:2.5rem;letter-spacing:0.06em;margin-bottom:16px;">Mot de passe oublié</h1>
    <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.65;margin-bottom:40px;text-transform:uppercase;letter-spacing:0.06em;">
        Saisissez votre email pour recevoir un lien de réinitialisation.
    </p>

    <div id="form-wrapper" style="background:white;border:3px solid var(--coffee);box-shadow:5px 5px 0 var(--coffee);padding:32px;text-align:left;">
        <form id="forgot-form" onsubmit="return sendReset(event)">
            <div style="margin-bottom:20px;">
                <label style="font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;display:block;margin-bottom:8px;">Email</label>
                <input type="email" id="email-input" required autocomplete="email" placeholder="votre@email.com"
                    style="width:100%;padding:12px 16px;border:3px solid var(--coffee);font-family:inherit;font-size:1rem;outline:none;box-sizing:border-box;">
            </div>
            <button type="submit" id="submit-btn" style="width:100%;font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:0.1em;text-transform:uppercase;background:var(--cherry);color:var(--cream);border:3px solid var(--coffee);padding:14px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">
                Envoyer le lien
            </button>
            <p id="msg" style="margin-top:16px;font-family:'DM Mono',monospace;font-size:0.8rem;display:none;padding:12px;"></p>
        </form>
    </div>

    <a href="/login" style="display:inline-block;margin-top:24px;font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;opacity:0.6;">← Retour à la connexion</a>
</div>
@endsection

{{-- === Scripts === --}}
@section('scripts')
<script>
// Envoie la demande et affiche le message.
async function sendReset(e) {
    e.preventDefault();
    const btn = document.getElementById('submit-btn');
    const msg = document.getElementById('msg');
    const email = document.getElementById('email-input').value.trim();
    btn.disabled = true;
    btn.textContent = 'Envoi…';
    msg.style.display = 'none';
    try {
        const resp = await fetch('{{ config("services.api.public_url") }}/api/v1/auth/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        msg.style.display = 'block';
        if (resp.ok) {
            msg.style.background = '#dff5e1';
            msg.style.border = '1px solid #3a7d44';
            msg.style.color = '#1a4d22';
            msg.textContent = 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation dans quelques minutes.';
            document.getElementById('forgot-form').style.display = 'none';
        } else {
            msg.style.background = '#fde2e2';
            msg.style.border = '1px solid #b00';
            msg.style.color = '#b00';
            msg.textContent = 'Impossible d\'envoyer l\'email. Réessayez.';
            btn.disabled = false;
            btn.textContent = 'Envoyer le lien';
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.background = '#fde2e2';
        msg.textContent = 'Erreur de connexion au serveur.';
        btn.disabled = false;
        btn.textContent = 'Envoyer le lien';
    }
    return false;
}
</script>
@endsection

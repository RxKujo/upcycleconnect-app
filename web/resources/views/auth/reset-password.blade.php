@extends('layouts.public')
@section('title', 'Nouveau mot de passe')

@section('content')
<div class="page-container" style="max-width:500px;margin:80px auto;text-align:center;">
    <h1 style="font-family:'Bebas Neue',sans-serif;font-size:2.5rem;letter-spacing:0.06em;margin-bottom:16px;">Nouveau mot de passe</h1>
    <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.65;margin-bottom:40px;text-transform:uppercase;letter-spacing:0.06em;">
        Choisissez un mot de passe sécurisé (8 caractères minimum).
    </p>

    <div id="form-wrapper" style="background:white;border:3px solid var(--coffee);box-shadow:5px 5px 0 var(--coffee);padding:32px;text-align:left;">
        <form id="reset-form" onsubmit="return submitReset(event)">
            <div style="margin-bottom:20px;">
                <label style="font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;display:block;margin-bottom:8px;">Nouveau mot de passe</label>
                <input type="password" id="password-input" required minlength="8" placeholder="••••••••"
                    style="width:100%;padding:12px 16px;border:3px solid var(--coffee);font-family:inherit;font-size:1rem;outline:none;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:24px;">
                <label style="font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;font-weight:700;display:block;margin-bottom:8px;">Confirmer le mot de passe</label>
                <input type="password" id="confirm-input" required minlength="8" placeholder="••••••••"
                    style="width:100%;padding:12px 16px;border:3px solid var(--coffee);font-family:inherit;font-size:1rem;outline:none;box-sizing:border-box;">
            </div>
            <button type="submit" id="submit-btn" style="width:100%;font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:0.1em;text-transform:uppercase;background:var(--cherry);color:var(--cream);border:3px solid var(--coffee);padding:14px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">
                Enregistrer
            </button>
            <p id="msg" style="margin-top:16px;font-family:'DM Mono',monospace;font-size:0.8rem;display:none;padding:12px;"></p>
        </form>
    </div>

    <a href="/login" style="display:inline-block;margin-top:24px;font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.06em;opacity:0.6;">← Retour à la connexion</a>
</div>
@endsection

@section('scripts')
<script>
const token = new URLSearchParams(window.location.search).get('token');
if (!token) {
    document.getElementById('form-wrapper').innerHTML = '<p style="color:var(--cherry);font-family:\'DM Mono\',monospace;font-size:0.85rem;">Lien invalide. <a href="/forgot-password">Demander un nouveau lien</a>.</p>';
}

async function submitReset(e) {
    e.preventDefault();
    const msg = document.getElementById('msg');
    const pw = document.getElementById('password-input').value;
    const confirm = document.getElementById('confirm-input').value;
    msg.style.display = 'none';

    if (pw !== confirm) {
        msg.style.display = 'block';
        msg.style.background = '#fde2e2';
        msg.style.border = '1px solid #b00';
        msg.style.color = '#b00';
        msg.textContent = 'Les mots de passe ne correspondent pas.';
        return false;
    }

    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.textContent = 'Enregistrement…';

    try {
        const resp = await fetch('{{ config("services.api.public_url") }}/api/v1/auth/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token, new_password: pw })
        });
        msg.style.display = 'block';
        if (resp.ok) {
            msg.style.background = '#dff5e1';
            msg.style.border = '1px solid #3a7d44';
            msg.style.color = '#1a4d22';
            msg.textContent = 'Mot de passe mis à jour. Redirection…';
            document.getElementById('reset-form').style.display = 'none';
            setTimeout(() => { window.location.href = '/login'; }, 2000);
        } else {
            const d = await resp.json();
            msg.style.background = '#fde2e2';
            msg.style.border = '1px solid #b00';
            msg.style.color = '#b00';
            msg.textContent = d.erreur || 'Token invalide ou expiré.';
            btn.disabled = false;
            btn.textContent = 'Enregistrer';
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.background = '#fde2e2';
        msg.textContent = 'Erreur de connexion.';
        btn.disabled = false;
        btn.textContent = 'Enregistrer';
    }
    return false;
}
</script>
@endsection

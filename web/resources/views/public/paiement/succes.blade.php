@extends('layouts.public')
@section('title', 'Paiement confirmé')

@section('styles')
.succes-wrapper { max-width: 600px; margin: 80px auto; text-align: center; }
.succes-icon { font-size: 4rem; color: var(--forest); margin-bottom: 24px; }
.succes-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; letter-spacing: 0.08em; color: var(--forest); margin-bottom: 16px; }
.succes-text { font-size: 1.05rem; color: var(--teal); line-height: 1.7; margin-bottom: 40px; }
.succes-details { background: white; border: var(--border); box-shadow: var(--shadow-sm); padding: 28px; margin-bottom: 40px; text-align: left; }
.succes-detail-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(18,3,9,0.08); }
.succes-detail-row:last-child { border-bottom: none; }
.succes-detail-label { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; opacity: 0.55; }
.succes-detail-value { font-weight: 600; font-size: 0.95rem; }
.succes-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.loading-state { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.06em; color: var(--teal); margin-bottom: 40px; }
@endsection

@section('content')
<div class="page-container">
<div class="succes-wrapper">
    <div class="succes-icon">✓</div>
    <h1 class="succes-title">Paiement confirmé !</h1>
    <p class="succes-text" id="succes-text">
        Votre paiement a été accepté. Vous recevrez une confirmation par email.
    </p>

    <div id="loading-state" class="loading-state">Chargement du récapitulatif…</div>

    <div id="succes-details" class="succes-details" style="display:none">
        <div class="succes-detail-row">
            <span class="succes-detail-label">Référence paiement</span>
            <span class="succes-detail-value font-mono" id="detail-pi" style="font-size:0.78rem;"></span>
        </div>
        <div class="succes-detail-row" id="detail-type-row">
            <span class="succes-detail-label">Type</span>
            <span class="succes-detail-value" id="detail-type"></span>
        </div>
        <div class="succes-detail-row" id="detail-montant-row" style="display:none">
            <span class="succes-detail-label">Montant débité</span>
            <span class="succes-detail-value" id="detail-montant" style="color:var(--cherry);font-family:'Bebas Neue',sans-serif;font-size:1.4rem;"></span>
        </div>
    </div>

    <div class="succes-actions">
        <a href="/mes-commandes" id="link-commandes" class="btn btn-primary" style="display:none">Voir mes commandes</a>
        <a href="#" id="link-profil" class="btn btn-primary" style="display:none">Mon espace</a>
        <a href="/" class="btn btn-secondary">Retour à l'accueil</a>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const piId = params.get('payment_intent');
    const status = params.get('redirect_status');
    const type = params.get('type') || 'panier';

    if (status !== 'succeeded') {
        document.getElementById('succes-icon') && (document.getElementById('succes-icon').textContent = '✕');
        document.getElementById('succes-title').textContent = 'Paiement échoué';
        document.getElementById('succes-title').style.color = 'var(--cherry)';
        document.getElementById('succes-text').textContent = 'Le paiement n\'a pas pu être finalisé. Aucun montant n\'a été débité.';
        document.getElementById('loading-state').style.display = 'none';
        return;
    }

    document.getElementById('loading-state').style.display = 'none';
    document.getElementById('succes-details').style.display = 'block';

    if (piId) {
        document.getElementById('detail-pi').textContent = piId;
    }

    if (type === 'panier') {
        document.getElementById('detail-type').textContent = 'Achat d\'objet(s)';
        document.getElementById('link-commandes').style.display = 'inline-flex';
        window.UCPanier && window.UCPanier.clear();
    } else if (type === 'evenement') {
        document.getElementById('detail-type').textContent = 'Inscription événement';
        const lp = document.getElementById('link-profil');
        const token = localStorage.getItem('auth_token');
        let role = 'particulier';
        try { role = JSON.parse(atob(token.split('.')[1])).role || 'particulier'; } catch(e) {}
        lp.href = role === 'professionnel' ? '/professionnel/profile' : '/particulier/profile';
        lp.style.display = 'inline-flex';
    } else if (type === 'commande') {
        document.getElementById('detail-type').textContent = 'Achat direct';
        document.getElementById('link-commandes').style.display = 'inline-flex';
        window.UCPanier && window.UCPanier.clear();
    }

    // Optionnel : récupérer le montant via l'API
    const token = localStorage.getItem('auth_token');
    if (token && piId) {
        apiFetch('/api/v1/commandes/me').then(r => r && r.ok ? r.json() : null).then(data => {
            if (data && data.length > 0) {
                const row = document.getElementById('detail-montant-row');
                row.style.display = 'flex';
            }
        }).catch(() => {});
    }
});
</script>
@endsection

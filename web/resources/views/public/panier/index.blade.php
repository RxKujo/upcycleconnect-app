@extends('layouts.public')
@section('title', 'Mon panier')

@section('styles')
.stripe-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(18,3,9,0.7);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}
.stripe-modal-overlay.open { display: flex; }
.stripe-modal {
    background: var(--cream);
    border: 3px solid var(--coffee);
    box-shadow: 8px 8px 0 var(--coffee);
    padding: 40px;
    max-width: 520px;
    width: calc(100% - 32px);
    max-height: 90vh;
    overflow-y: auto;
}
.stripe-modal-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2rem;
    letter-spacing: 0.08em;
    margin-bottom: 8px;
}
.stripe-modal-amount {
    font-family: 'DM Mono', monospace;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--teal);
    margin-bottom: 28px;
}
.stripe-modal-amount strong {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.6rem;
    color: var(--cherry);
    vertical-align: middle;
    margin-left: 6px;
}
#stripe-payment-element { margin-bottom: 24px; }
#stripe-error {
    color: var(--cherry);
    font-size: 0.9rem;
    margin-bottom: 16px;
    min-height: 20px;
    font-family: 'DM Mono', monospace;
}
.stripe-modal-actions { display: flex; gap: 12px; }
#btn-payer {
    flex: 1;
    padding: 16px;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background: var(--cherry);
    color: var(--cream);
    border: 3px solid var(--coffee);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
}
#btn-payer:active { transform: translate(2px,2px); box-shadow: none; }
#btn-payer:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
#btn-annuler-paiement {
    padding: 16px 20px;
    font-family: 'DM Mono', monospace;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: var(--cream);
    color: var(--coffee);
    border: 3px solid var(--coffee);
    cursor: pointer;
}
.commission-note {
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--teal);
    opacity: 0.7;
    margin-top: 12px;
}
@endsection

@section('content')
<div class="page-container" style="max-width:900px;">
    <p class="section-label">Achats</p>
    <h1 class="page-title">Mon panier</h1>

    <div id="panierEmpty" style="text-align:center; padding:72px 24px; border:var(--border); background:white; box-shadow:var(--shadow-sm);">
        <div style="width:84px; height:84px; margin:0 auto 24px; border:3px solid var(--coffee); display:flex; align-items:center; justify-content:center; background:var(--wheat);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" stroke="var(--coffee)" stroke-width="1.6" viewBox="0 0 24 24">
                <circle cx="9" cy="21" r="1.4"/><circle cx="18" cy="21" r="1.4"/>
                <path d="M1 1h4l2.6 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>
            </svg>
        </div>
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2.2rem; letter-spacing:0.04em; margin-bottom:10px;">Votre panier est vide</h3>
        <p style="opacity:0.6; max-width:420px; margin:0 auto 28px; line-height:1.5;">
            Parcourez le marché et ajoutez des objets à récupérer ou à acheter. Ils apparaîtront ici.
        </p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="{{ route('annonces.index') }}" class="btn btn-primary">Explorer le marché</a>
            <a href="/" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>

    <div id="panierContent" style="display:none;">
        <div id="panierItems" style="display:flex; flex-direction:column; gap:12px; margin-bottom:32px;"></div>

        <div style="border:var(--border); padding:24px; background:white; box-shadow:var(--shadow-sm);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">Sous-total articles</span>
                <span id="panierSousTotal" style="font-family:'Bebas Neue',sans-serif; font-size:1.6rem;">0,00 €</span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span class="font-mono" style="font-size:0.75rem; opacity:0.5;">Commission UpcycleConnect</span>
                <span id="panierCommission" style="font-family:'DM Mono',monospace; font-size:0.85rem; color:var(--teal);">—</span>
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:2px solid var(--coffee); padding-top:16px; margin-bottom:20px;">
                <span class="font-mono" style="font-size:0.9rem; font-weight:700;">Total à payer</span>
                <span id="panierTotal" style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--cherry);">0,00 €</span>
            </div>
            <p id="panierAuthWarn" style="display:none; font-size:0.85rem; color:var(--cherry); margin-bottom:16px;">
                Vous devez être <a href="/login?return=%2Fpanier" style="text-decoration:underline;">connecté</a> pour valider votre commande.
            </p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="button" id="btnCheckout" class="btn btn-primary btn-lg">Payer par carte</button>
                <button type="button" id="btnClear" class="btn btn-secondary">Vider le panier</button>
                <a href="{{ route('annonces.index') }}" class="btn btn-secondary">Continuer mes achats</a>
            </div>
            <p id="checkoutResult" style="display:none; margin-top:16px; padding:14px; font-size:0.9rem;"></p>
        </div>
    </div>
</div>

{{-- Modal Stripe --}}
<div id="stripe-modal-overlay" class="stripe-modal-overlay">
    <div class="stripe-modal">
        <div class="stripe-modal-title">Paiement sécurisé</div>
        <div class="stripe-modal-amount">
            Total à débiter : <strong id="modal-amount-display">0,00 €</strong>
        </div>
        <div id="stripe-payment-element"></div>
        <div id="stripe-error"></div>
        <div class="stripe-modal-actions">
            <button id="btn-payer">Payer maintenant</button>
            <button id="btn-annuler-paiement">Annuler</button>
        </div>
        <p class="commission-note">Paiement sécurisé par Stripe — vos données bancaires ne sont jamais stockées.</p>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
let stripeInstance = null;
let stripeElements = null;
let panierDetails = null;

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function formatPrix(p) {
    return (parseFloat(p) || 0).toFixed(2).replace('.', ',') + ' €';
}

async function getStripeKey() {
    try {
        const res = await fetch(API_BASE + '/api/v1/stripe/config');
        const d = await res.json();
        return d.publishable_key;
    } catch(e) { return null; }
}

function renderTotals(items) {
    const sousTotal = items.reduce((s, i) => s + i.prix, 0);
    const commission = items.reduce((s, i) => s + i.commission, 0);
    const total = items.reduce((s, i) => s + i.total_item, 0);
    document.getElementById('panierSousTotal').textContent = formatPrix(sousTotal);
    document.getElementById('panierCommission').textContent = '+ ' + formatPrix(commission);
    document.getElementById('panierTotal').textContent = formatPrix(total);
}

function render() {
    var cartItems = window.UCPanier.items();
    var empty = document.getElementById('panierEmpty');
    var content = document.getElementById('panierContent');
    if (cartItems.length === 0) {
        empty.style.display = 'block'; content.style.display = 'none'; return;
    }
    empty.style.display = 'none'; content.style.display = 'block';

    var html = cartItems.map(function(i) {
        var prixLabel = i.type_annonce === 'don' ? 'Gratuit' : formatPrix(i.prix);
        return '<div style="display:grid;grid-template-columns:1fr auto auto;gap:16px;align-items:center;border:var(--border);padding:16px 20px;background:white;">' +
            '<div>' +
                '<a href="/annonces/' + i.id_annonce + '" style="font-weight:600;font-size:1rem;text-decoration:none;">' + escapeHtml(i.titre) + '</a>' +
                '<p class="font-mono" style="font-size:0.72rem;opacity:0.55;margin-top:4px;">' +
                    'Vendeur ' + escapeHtml(i.vendeur || '—') +
                    ' · Remise ' + escapeHtml(i.mode_remise === 'conteneur' ? 'via conteneur' : 'main propre') +
                '</p>' +
            '</div>' +
            '<span style="font-family:\'Bebas Neue\',sans-serif;font-size:1.5rem;color:var(--cherry);">' + prixLabel + '</span>' +
            '<button type="button" data-remove="' + i.id_annonce + '" style="background:none;border:none;cursor:pointer;color:#b00;font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;">✕ Retirer</button>' +
        '</div>';
    }).join('');
    document.getElementById('panierItems').innerHTML = html;

    // Totaux provisoires (sans commission exacte — sera précisé après PI)
    const sousTotal = window.UCPanier.total();
    document.getElementById('panierSousTotal').textContent = formatPrix(sousTotal);
    document.getElementById('panierCommission').textContent = '~' + formatPrix(sousTotal * 0.1);
    document.getElementById('panierTotal').textContent = '~' + formatPrix(sousTotal * 1.1);

    document.querySelectorAll('[data-remove]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.UCPanier.remove(btn.getAttribute('data-remove'));
            render();
        });
    });

    document.getElementById('panierAuthWarn').style.display = localStorage.getItem('auth_token') ? 'none' : 'block';
}

async function openStripeModal() {
    const token = localStorage.getItem('auth_token');
    if (!token) { window.location.href = '/login?return=%2Fpanier'; return; }

    const btn = document.getElementById('btnCheckout');
    btn.disabled = true;
    btn.textContent = 'Préparation…';
    document.getElementById('checkoutResult').style.display = 'none';

    // Créer le Payment Intent pour le panier entier
    const cartItems = window.UCPanier.items().map(i => ({
        id_annonce: i.id_annonce,
        mode_remise: i.mode_remise || 'main_propre'
    }));
    try {
        const res = await fetch(API_BASE + '/api/v1/stripe/payment-intent/panier', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ items: cartItems })
        });
        const data = await res.json();
        if (!res.ok) {
            showResult('error', data.erreur || 'Impossible de créer le paiement.');
            btn.disabled = false; btn.textContent = 'Payer par carte';
            return;
        }
        panierDetails = data;
        renderTotals(data.items);
        document.getElementById('modal-amount-display').textContent = formatPrix(data.montant_total);
        await mountStripeElements(data.client_secret);
        document.getElementById('stripe-modal-overlay').classList.add('open');
    } catch(e) {
        showResult('error', 'Erreur réseau. Veuillez réessayer.');
    } finally {
        btn.disabled = false; btn.textContent = 'Payer par carte';
    }
}

async function mountStripeElements(clientSecret) {
    if (!stripeInstance) {
        const pk = await getStripeKey();
        if (!pk) { showResult('error', 'Configuration Stripe indisponible.'); return; }
        stripeInstance = Stripe(pk);
    }
    stripeElements = stripeInstance.elements({ clientSecret, appearance: {
        theme: 'flat',
        variables: { colorPrimary: '#A4243B', colorBackground: '#F5F0E1', fontFamily: 'Outfit, sans-serif', borderRadius: '0px' }
    }});
    const paymentEl = stripeElements.create('payment');
    document.getElementById('stripe-payment-element').innerHTML = '';
    paymentEl.mount('#stripe-payment-element');
}

async function confirmPayment() {
    const btn = document.getElementById('btn-payer');
    btn.disabled = true; btn.textContent = 'Traitement…';
    document.getElementById('stripe-error').textContent = '';

    const { error } = await stripeInstance.confirmPayment({
        elements: stripeElements,
        confirmParams: {
            return_url: window.location.origin + '/paiement/succes?type=panier'
        }
    });

    if (error) {
        document.getElementById('stripe-error').textContent = error.message;
        btn.disabled = false; btn.textContent = 'Payer maintenant';
    }
    // Si succès → Stripe redirige vers return_url automatiquement
}

function showResult(type, msg) {
    const el = document.getElementById('checkoutResult');
    el.style.display = 'block';
    el.style.background = type === 'error' ? '#fde2e2' : '#dff5e1';
    el.style.borderLeft = '3px solid ' + (type === 'error' ? '#b00' : '#3a7d44');
    el.innerHTML = msg;
}

document.addEventListener('DOMContentLoaded', function() {
    render();

    document.getElementById('btnClear').addEventListener('click', function() {
        confirmAction('Vider le panier ?').then(function (ok) { if (ok) { window.UCPanier.clear(); render(); } });
    });

    document.getElementById('btnCheckout').addEventListener('click', openStripeModal);

    document.getElementById('btn-payer').addEventListener('click', confirmPayment);

    document.getElementById('btn-annuler-paiement').addEventListener('click', function() {
        document.getElementById('stripe-modal-overlay').classList.remove('open');
        document.getElementById('stripe-payment-element').innerHTML = '';
        stripeElements = null;
    });

    // Fermer en cliquant l'overlay
    document.getElementById('stripe-modal-overlay').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('open');
            document.getElementById('stripe-payment-element').innerHTML = '';
            stripeElements = null;
        }
    });
});
</script>
@endsection

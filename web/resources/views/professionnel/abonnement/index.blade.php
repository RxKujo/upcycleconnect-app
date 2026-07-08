@extends('layouts.professionnel')
@section('title', 'Choisir mon abonnement')

{{-- Choix d'abonnement Pro : plans chargés via l'API, toggle mensuel/annuel, checkout Stripe --}}

{{-- === Styles === --}}
@section('styles')
<style>
    .page-intro { max-width: 640px; margin-bottom: 48px; }
    .page-intro p { font-size: 1.1rem; color: var(--teal); margin-top: 10px; line-height: 1.6; }

    /* Toggle mensuel / annuel */
    .billing-toggle { display: flex; align-items: center; gap: 16px; margin-bottom: 48px; }
    .toggle-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; cursor: pointer; }
    .toggle-label.active { color: var(--cherry); font-weight: 700; }
    .toggle-switch { position: relative; width: 52px; height: 28px; background: var(--coffee); border: 3px solid var(--coffee); cursor: pointer; flex-shrink: 0; }
    .toggle-knob { position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: var(--wheat); transition: transform 0.2s; }
    .toggle-switch.annual .toggle-knob { transform: translateX(24px); }
    .badge-economy { font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; background: var(--forest); color: var(--cream); padding: 3px 10px; border: 2px solid var(--coffee); letter-spacing: 0.05em; }

    /* Plans grid */
    .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; align-items: start; }
    @media (max-width: 900px) { .plans-grid { grid-template-columns: 1fr; } }

    .plan-card { background: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow); padding: 32px; display: flex; flex-direction: column; position: relative; transition: transform 0.15s; }
    .plan-card:hover { transform: translate(-2px, -2px); }
    .plan-card.featured { background: var(--coffee); color: var(--cream); border-color: var(--cherry); box-shadow: 6px 6px 0px var(--cherry); }
    .plan-card.featured .plan-price-sub,
    .plan-card.featured .plan-feature { color: var(--wheat); }
    .plan-card.featured .feature-icon { color: var(--wheat); }

    .plan-badge-popular { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: var(--cherry); color: var(--cream); font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; padding: 4px 16px; border: 2px solid var(--coffee); white-space: nowrap; }

    .plan-name { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.1em; margin-bottom: 4px; }
    .plan-price { font-family: 'Bebas Neue', sans-serif; font-size: 3.6rem; line-height: 1; margin: 16px 0 4px; }
    .plan-price sup { font-size: 1.8rem; vertical-align: super; }
    .plan-price-sub { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--teal); margin-bottom: 8px; }
    .plan-price-annual { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; color: var(--forest); margin-bottom: 24px; min-height: 20px; }
    .plan-card.featured .plan-price-annual { color: var(--wheat); }

    .plan-divider { border: none; border-top: 2px solid rgba(18,3,9,0.15); margin: 20px 0; }
    .plan-card.featured .plan-divider { border-color: rgba(245,240,225,0.2); }

    .plan-features { list-style: none; display: flex; flex-direction: column; gap: 10px; margin-bottom: 32px; flex: 1; }
    .plan-feature { display: flex; align-items: flex-start; gap: 10px; font-size: 0.95rem; line-height: 1.4; }
    .feature-icon { flex-shrink: 0; font-size: 1rem; margin-top: 1px; }

    .plan-btn { width: 100%; padding: 14px 24px; font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 0.1em; text-transform: uppercase; border: 3px solid var(--coffee); cursor: pointer; box-shadow: var(--shadow-sm); transition: transform 0.1s, box-shadow 0.1s; text-align: center; }
    .plan-btn:active { transform: translate(3px, 3px); box-shadow: none; }
    .plan-btn-primary { background: var(--cherry); color: var(--cream); }
    .plan-btn-secondary { background: var(--cream); color: var(--coffee); }
    .plan-btn-featured { background: var(--cream); color: var(--coffee); border-color: var(--cream); }
    .plan-btn-current { background: var(--wheat); color: var(--coffee); opacity: 0.7; cursor: default; pointer-events: none; }

    .plan-btn.loading { opacity: 0.6; pointer-events: none; }

    /* Abonnement actif banner */
    .current-plan-banner { background: var(--forest); color: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); padding: 16px 24px; margin-bottom: 40px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
    .current-plan-banner strong { font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; }
    .current-plan-banner .btn-portal { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; background: var(--cream); color: var(--coffee); border: 2px solid var(--cream); padding: 8px 20px; cursor: pointer; }
    .current-plan-banner .btn-portal:hover { background: var(--wheat); }

    .loading-plans { text-align: center; padding: 60px 0; font-family: 'DM Mono', monospace; text-transform: uppercase; color: var(--teal); letter-spacing: 0.05em; }
</style>
@endsection

{{-- === Contenu === --}}
@section('content')
<x-page-header title="Votre abonnement Pro" i18n="abo.title" />

<div class="page-intro">
    <p><span data-i18n="abo.subtitle">Choisissez le plan qui correspond à votre activité. Changez ou annulez à tout moment depuis votre espace de gestion.</span></p>
</div>

{{-- Banner abonnement actif --}}
<div id="current-plan-banner" style="display:none" class="current-plan-banner">
    <div>
        <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.05em;opacity:0.8;" data-i18n="abo.current">Plan actuel —</span>
        <strong id="current-plan-name"></strong>
    </div>
    <button class="btn-portal" onclick="openPortail()"><span data-i18n="abo.manage">Gérer mon abonnement →</span></button>
</div>

{{-- Toggle mensuel / annuel --}}
<div class="billing-toggle">
    <span class="toggle-label active" id="label-mensuel" onclick="setBilling('mensuel')"><span data-i18n="abo.monthly">Mensuel</span></span>
    <div class="toggle-switch" id="billing-toggle" onclick="toggleBilling()">
        <div class="toggle-knob"></div>
    </div>
    <span class="toggle-label" id="label-annuel" onclick="setBilling('annuel')"><span data-i18n="abo.yearly">Annuel</span></span>
    <span class="badge-economy">2 mois offerts</span>
</div>

<div id="loading-plans" class="loading-plans"><span data-i18n="abo.loading">Chargement des offres…</span></div>

<div id="plans-grid" class="plans-grid" style="display:none"></div>

{{-- === Script === --}}
<script>
const API_BASE = '{{ config("services.api.public_url") }}';
let billing = 'mensuel';
let plans = [];
let abonnementActif = null;

function getToken() { return localStorage.getItem('auth_token'); }

async function apiFetch(url, options = {}) {
    const token = getToken();
    const headers = { 'Content-Type': 'application/json', ...(token ? { 'Authorization': 'Bearer ' + token } : {}), ...(options.headers || {}) };
    return fetch(API_BASE + url, { ...options, headers });
}

function toggleBilling() { setBilling(billing === 'mensuel' ? 'annuel' : 'mensuel'); }

function setBilling(mode) {
    billing = mode;
    document.getElementById('billing-toggle').classList.toggle('annual', mode === 'annuel');
    document.getElementById('label-mensuel').classList.toggle('active', mode === 'mensuel');
    document.getElementById('label-annuel').classList.toggle('active', mode === 'annuel');
    renderPlans();
}

async function loadAbonnementActif() {
    const token = getToken();
    if (!token) return;
    try {
        const res = await apiFetch('/api/v1/stripe/facturation');
        if (!res.ok) return;
        const data = await res.json();
        if (data.abonnement_actif) {
            abonnementActif = data.abonnement_actif;
            document.getElementById('current-plan-name').textContent = abonnementActif.nom_plan;
            document.getElementById('current-plan-banner').style.display = 'flex';
        }
    } catch(e) {}
}

async function loadPlans() {
    try {
        const res = await fetch(API_BASE + '/api/v1/public/abonnements');
        plans = await res.json();
    } catch(e) {
        document.getElementById('loading-plans').textContent = 'Erreur de chargement des offres.';
        return;
    }
    document.getElementById('loading-plans').style.display = 'none';
    document.getElementById('plans-grid').style.display = 'grid';
    renderPlans();
}

function renderPlans() {
    const grid = document.getElementById('plans-grid');
    grid.innerHTML = '';

    const features = {
        1: [ // Freemium
            { icon: '✓', text: 'Profil entreprise visible' },
            { icon: '✓', text: 'Accès à la marketplace' },
            { icon: '✓', text: 'Forum & conseils' },
            { icon: '✗', text: 'Alertes personnalisées', disabled: true },
            { icon: '✗', text: 'Mise en avant des annonces', disabled: true },
            { icon: '✗', text: 'Dashboard mensuel', disabled: true },
        ],
        2: [ // Essential
            { icon: '✓', text: 'Profil entreprise mis en avant' },
            { icon: '✓', text: 'Jusqu\'à 10 alertes personnalisées' },
            { icon: '✓', text: 'Rayon d\'alerte 50 km' },
            { icon: '✓', text: 'Badges certifiés' },
            { icon: '✓', text: 'Accès prioritaire aux annonces' },
            { icon: '✗', text: 'Dashboard annuel', disabled: true },
        ],
        3: [ // Expert
            { icon: '✓', text: 'Profil en tête de liste' },
            { icon: '✓', text: 'Alertes illimitées' },
            { icon: '✓', text: 'Rayon d\'alerte 200 km' },
            { icon: '✓', text: 'Badges premium' },
            { icon: '✓', text: 'Dashboard mensuel & annuel' },
            { icon: '✓', text: 'Support prioritaire' },
        ],
    };

    plans.forEach((plan, index) => {
        const isFeatured = plan.prix_mensuel > 0 && index === 1;
        const isCurrent = abonnementActif && abonnementActif.nom_plan === plan.nom;
        const isFree = plan.prix_mensuel === 0;

        const prixDisplay = isFree ? 'Gratuit'
            : billing === 'mensuel'
                ? `<sup>€</sup>${plan.prix_mensuel.toFixed(2)}`
                : `<sup>€</sup>${plan.prix_annuel ? plan.prix_annuel.toFixed(2) : (plan.prix_mensuel * 10).toFixed(2)}`;

        const perLabel = isFree ? '' : (billing === 'mensuel' ? '/ mois' : '/ an');
        const economie = (plan.prix_mensuel > 0 && billing === 'annuel')
            ? `Économisez ${(plan.prix_mensuel * 2).toFixed(2)} €/an`
            : '&nbsp;';

        const planFeatures = features[plan.id_abonnement] || [];
        const featuresHtml = planFeatures.map(f =>
            `<li class="plan-feature" style="${f.disabled ? 'opacity:0.4' : ''}">
                <span class="feature-icon">${f.icon}</span>
                <span>${escapeHtml(f.text)}</span>
            </li>`
        ).join('');

        let btnClass, btnLabel, btnAction;
        if (isCurrent) {
            btnClass = 'plan-btn plan-btn-current';
            btnLabel = 'Plan actuel';
            btnAction = '';
        } else if (isFree) {
            btnClass = 'plan-btn plan-btn-secondary';
            btnLabel = 'Continuer gratuitement';
            btnAction = `onclick="window.location='/professionnel/profile'"`;
        } else if (isFeatured) {
            btnClass = 'plan-btn plan-btn-featured';
            btnLabel = 'Choisir Essential Pro';
            btnAction = `onclick="checkout(${plan.id_abonnement}, this)"`;
        } else {
            btnClass = 'plan-btn plan-btn-primary';
            btnLabel = 'Choisir Expert Pro';
            btnAction = `onclick="checkout(${plan.id_abonnement}, this)"`;
        }

        const card = document.createElement('div');
        card.className = 'plan-card' + (isFeatured ? ' featured' : '');
        card.innerHTML = `
            ${isFeatured ? '<div class="plan-badge-popular">Le plus populaire</div>' : ''}
            <div class="plan-name">${escapeHtml(plan.nom)}</div>
            <div class="plan-price">${isFree ? '<span style="font-size:2.2rem">Gratuit</span>' : prixDisplay}</div>
            <div class="plan-price-sub">${perLabel}</div>
            <div class="plan-price-annual">${economie}</div>
            <hr class="plan-divider">
            <ul class="plan-features">${featuresHtml}</ul>
            <button class="${btnClass}" ${btnAction}>${btnLabel}</button>
        `;
        grid.appendChild(card);
    });
}

async function checkout(idAbonnement, btn) {
    if (!getToken()) { window.location.href = '/login'; return; }
    btn.classList.add('loading');
    btn.textContent = 'Redirection…';
    try {
        const res = await apiFetch('/api/v1/stripe/abonnement/checkout', {
            method: 'POST',
            body: JSON.stringify({ id_abonnement: idAbonnement, periodicite: billing })
        });
        const data = await res.json();
        if (data.url) {
            window.location.href = data.url;
        } else {
            alert(data.erreur || 'Une erreur est survenue.');
            btn.classList.remove('loading');
            btn.textContent = 'Choisir ce plan';
        }
    } catch(e) {
        alert('Erreur réseau. Veuillez réessayer.');
        btn.classList.remove('loading');
        btn.textContent = 'Choisir ce plan';
    }
}

async function openPortail() {
    try {
        const res = await apiFetch('/api/v1/stripe/abonnement/portail', { method: 'POST' });
        const data = await res.json();
        if (data.url) window.location.href = data.url;
        else alert(data.erreur || 'Erreur portail.');
    } catch(e) {
        alert('Erreur réseau.');
    }
}

function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

document.addEventListener('DOMContentLoaded', () => {
    loadAbonnementActif();
    loadPlans();
});
</script>
@endsection

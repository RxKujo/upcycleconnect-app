@extends('layouts.professionnel')
@section('title', 'Abonnement activé')

{{-- Paiement Stripe réussi : affiche le plan actif (chargé via l'API) --}}

{{-- === Styles === --}}
@section('styles')
<style>
    .succes-wrapper { max-width: 560px; margin: 80px auto; text-align: center; }
    .succes-icon { font-size: 4rem; margin-bottom: 24px; }
    .succes-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; letter-spacing: 0.1em; color: var(--forest); margin-bottom: 16px; }
    .succes-text { font-size: 1.1rem; color: var(--teal); line-height: 1.7; margin-bottom: 40px; }
    .succes-card { background: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow); padding: 32px; margin-bottom: 40px; text-align: left; }
    .succes-card-title { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.08em; color: var(--cherry); margin-bottom: 16px; }
    .succes-plan-name { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 0.1em; }
    .succes-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
</style>
@endsection

{{-- === Contenu === --}}
@section('content')
<div class="succes-wrapper">
    <div class="succes-icon">✓</div>
    <h1 class="succes-title">Abonnement activé !</h1>
    <p class="succes-text">
        Votre paiement a été accepté. Votre nouvel abonnement est maintenant actif.
        Un email de confirmation avec votre facture vous a été envoyé.
    </p>

    <div class="succes-card" id="plan-details" style="display:none">
        <div class="succes-card-title">Votre plan actif</div>
        <div class="succes-plan-name" id="plan-name-display">—</div>
        <div id="plan-extra" style="font-family:'DM Mono',monospace;font-size:0.85rem;margin-top:8px;color:var(--teal);text-transform:uppercase;letter-spacing:0.04em;"></div>
    </div>

    <div class="succes-actions">
        <a href="/professionnel/profile" class="btn btn-primary">Mon espace pro →</a>
        <a href="/professionnel/abonnement" class="btn btn-secondary">Voir mon abonnement</a>
    </div>
</div>

{{-- === Script === --}}
<script>
const API_BASE = '{{ config("services.api.public_url") }}';
function getToken() { return localStorage.getItem('auth_token'); }


async function loadPlanActif() {
    const token = getToken();
    if (!token) return;
    try {
        const res = await fetch(API_BASE + '/api/v1/stripe/facturation', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (data.abonnement_actif) {
            document.getElementById('plan-name-display').textContent = data.abonnement_actif.nom_plan;
            document.getElementById('plan-extra').textContent =
                data.abonnement_actif.prix_mensuel > 0
                    ? `${data.abonnement_actif.prix_mensuel.toFixed(2)} € / mois`
                    : 'Gratuit';
            document.getElementById('plan-details').style.display = 'block';
        }
    } catch(e) {}
}

document.addEventListener('DOMContentLoaded', loadPlanActif);
</script>
@endsection

@extends('layouts.public')

@section('title', 'Dépôt en conteneur')

@section('styles')
.depot-wrap { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }
.depot-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
@media(max-width: 768px) { .depot-grid { grid-template-columns: 1fr; } }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); margin-bottom: 8px; }
.page-sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; color: #666; text-transform: uppercase; margin-bottom: 48px; }
.section-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; color: var(--coffee); margin-bottom: 24px; border-bottom: 3px solid var(--coffee); padding-bottom: 8px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
.form-input, .form-select, .form-textarea { width: 100%; border: 3px solid var(--coffee); background: white; padding: 12px 16px; font-family: 'Outfit', sans-serif; font-size: 1rem; outline: none; box-sizing: border-box; }
.form-textarea { min-height: 100px; resize: vertical; }
.btn { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; border: 3px solid var(--coffee); padding: 14px 32px; font-size: 1.1rem; box-shadow: var(--shadow-sm); transition: all 0.2s; width: 100%; }
.btn-primary { background: var(--forest); color: var(--cream); }
.btn:hover { transform: translate(2px,2px); box-shadow: var(--shadow-hover); }
#map { height: 420px; border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); }
.alert { padding: 16px 20px; border: 3px solid; font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 20px; display: none; }
.alert-success { background: #d4edda; border-color: var(--forest); color: var(--forest); }
.alert-error { background: #f8d7da; border-color: var(--cherry); color: var(--cherry); }
.mes-demandes { margin-top: 60px; }
.demande-row { border: 3px solid var(--coffee); background: white; padding: 20px 24px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
.badge { display: inline-flex; padding: 4px 10px; font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; font-weight: bold; border: 2px solid var(--coffee); }
.badge-wait { background: var(--wheat); }
.badge-ok { background: var(--forest); color: white; }
.badge-bad { background: var(--cherry); color: white; }
.badge-code { background: var(--teal); color: white; }
.code-barre-box { background: var(--wheat); border: 3px solid var(--coffee); padding: 12px 20px; font-family: 'DM Mono', monospace; font-size: 1.1rem; font-weight: bold; letter-spacing: 0.1em; margin-top: 8px; }
@endsection

@section('content')
<div class="depot-wrap">
    <h1 class="page-title">Dépôt en conteneur</h1>
    <p class="page-sub">Déposez vos objets dans l'un de nos conteneurs partenaires</p>

    <div class="depot-grid">
        <!-- Formulaire -->
        <div>
            <h2 class="section-title">Ma demande de dépôt</h2>
            <div id="alert-success" class="alert alert-success">Demande envoyée ! Notre équipe la traitera sous 48h.</div>
            <div id="alert-error" class="alert alert-error">Erreur lors de l'envoi de votre demande.</div>

            <form id="form-depot" onsubmit="submitDepot(event)">
                <div class="form-group">
                    <label class="form-label">Titre de l'objet *</label>
                    <input type="text" id="depot-titre" class="form-input" placeholder="Ex: Table basse en bois" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Type d'objet *</label>
                    <select id="depot-type" class="form-select" required>
                        <option value="">Sélectionnez...</option>
                        <option value="Mobilier">Mobilier</option>
                        <option value="Électroménager">Électroménager</option>
                        <option value="Vêtements">Vêtements</option>
                        <option value="Électronique">Électronique</option>
                        <option value="Livres / Médias">Livres / Médias</option>
                        <option value="Jouets">Jouets</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Outillage">Outillage</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea id="depot-description" class="form-textarea" placeholder="Décrivez l'état et les caractéristiques de l'objet..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantité</label>
                    <input type="number" id="depot-quantite" class="form-input" value="1" min="1" max="20">
                </div>
                <div class="form-group">
                    <label class="form-label">Adresse de retrait</label>
                    <input type="text" id="depot-adresse" class="form-input" placeholder="Adresse où l'objet peut être récupéré">
                </div>
                <div style="display:grid;grid-template-columns:1fr 2fr;gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Code postal</label>
                        <input type="text" id="depot-cp" class="form-input" placeholder="75001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ville</label>
                        <input type="text" id="depot-ville" class="form-input" placeholder="Paris">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Conteneur sélectionné</label>
                    <input type="text" id="depot-conteneur-display" class="form-input" placeholder="Cliquez sur la carte pour sélectionner" readonly style="cursor:pointer;background:#f9f9f9;">
                    <input type="hidden" id="depot-conteneur-id">
                </div>
                <button type="submit" class="btn btn-primary">Envoyer ma demande</button>
            </form>
        </div>

        <!-- Carte -->
        <div>
            <h2 class="section-title">Conteneurs proches</h2>
            <p style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#666;margin-bottom:16px;">
                Cliquez sur un conteneur pour le sélectionner — ou « Autour de moi » pour les plus proches
            </p>
            <div id="map" data-conteneurs-map data-api="{{ config('services.api.public_url') }}" data-selectable="1"></div>
        </div>
    </div>

    <!-- Mes demandes -->
    <div class="mes-demandes" id="mes-demandes-section" style="display:none;">
        <h2 class="section-title">Mes demandes</h2>
        <div id="mes-demandes-list"></div>
    </div>
</div>
@endsection

@section('scripts')
@vite('resources/js/conteneurs-map.js')
<script>
const API = '{{ config("services.api.public_url") }}';
const token = localStorage.getItem('uc_token');
let selectedConteneur = null;

if (!token) {
    window.location.href = '/login?return=/depot';
}

// La carte (marqueurs, popups, « Autour de moi », itinéraire) est gérée par le
// module réutilisable resources/js/conteneurs-map.js. Ici on écoute seulement la
// sélection d'un conteneur pour remplir le formulaire de dépôt.
document.addEventListener('DOMContentLoaded', function() {
    const mapEl = document.getElementById('map');
    if (mapEl) {
        mapEl.addEventListener('conteneur:selected', function(e) {
            const c = e.detail.conteneur;
            selectedConteneur = c;
            document.getElementById('depot-conteneur-id').value = c.id_conteneur;
            document.getElementById('depot-conteneur-display').value = c.conteneur_ref + ' — ' + c.adresse + ', ' + c.ville;
        });
    }
    loadMesDemandes();
});

async function submitDepot(e) {
    e.preventDefault();
    document.getElementById('alert-success').style.display = 'none';
    document.getElementById('alert-error').style.display = 'none';

    const payload = {
        titre: document.getElementById('depot-titre').value,
        type_objet: document.getElementById('depot-type').value,
        description: document.getElementById('depot-description').value,
        quantite: parseInt(document.getElementById('depot-quantite').value) || 1,
        adresse_retrait: document.getElementById('depot-adresse').value,
        code_postal_retrait: document.getElementById('depot-cp').value,
        ville_retrait: document.getElementById('depot-ville').value,
        id_conteneur: document.getElementById('depot-conteneur-id').value ? parseInt(document.getElementById('depot-conteneur-id').value) : null
    };

    const r = await fetch(API + '/api/v1/depot/demande', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (r.ok) {
        document.getElementById('alert-success').style.display = 'block';
        document.getElementById('form-depot').reset();
        document.getElementById('depot-conteneur-id').value = '';
        document.getElementById('depot-conteneur-display').value = '';
        selectedConteneur = null;
        loadMesDemandes();
    } else {
        document.getElementById('alert-error').style.display = 'block';
    }
}

async function loadMesDemandes() {
    if (!token) return;
    const r = await fetch(API + '/api/v1/depot/demandes/me', {
        headers: { 'Authorization': 'Bearer ' + token }
    });
    if (!r.ok) return;
    const demandes = await r.json();
    if (!demandes || !demandes.length) return;

    document.getElementById('mes-demandes-section').style.display = 'block';
    const statusLabels = { en_attente: 'En attente', validee: 'Validée', refusee: 'Refusée', code_envoye: 'Code envoyé' };
    const statusClass = { en_attente: 'badge-wait', validee: 'badge-ok', refusee: 'badge-bad', code_envoye: 'badge-code' };

    document.getElementById('mes-demandes-list').innerHTML = demandes.map(d => `
        <div class="demande-row">
            <div>
                <strong style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;">${d.titre}</strong>
                <p style="font-size:0.9rem;color:#666;margin:4px 0 0;">${d.type_objet} — ${new Date(d.date_demande).toLocaleDateString('fr-FR')}</p>
                ${d.code_barre ? `<div class="code-barre-box">Code-barre: ${d.code_barre}</div>` : ''}
                ${d.motif_refus ? `<p style="color:var(--cherry);font-size:0.9rem;margin-top:8px;">Motif: ${d.motif_refus}</p>` : ''}
            </div>
            <span class="badge ${statusClass[d.statut] || 'badge-wait'}">${statusLabels[d.statut] || d.statut}</span>
        </div>
    `).join('');
}
</script>
@endsection

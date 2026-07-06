@extends('layouts.salarie')

@section('title', $evenement ? 'Modifier événement' : 'Créer un événement')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $evenement ? 'Modifier' : 'Nouvel' }} événement</h1>
    <a href="{{ route('salarie.evenements.index') }}" class="btn-secondary">← Retour</a>
</div>

@php
    $isEdit = $evenement !== null;
    $action = $isEdit ? route('salarie.evenements.update', $evenement['id_evenement']) : route('salarie.evenements.store');
@endphp

@if($errors->any())
<div class="alert alert-error" style="display:block; text-transform:none; margin-bottom:20px;">
    <ul style="margin:0; padding-left:20px; font-family:'Outfit',sans-serif;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ $action }}" method="POST" class="card" autocomplete="off" id="evForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if(!$isEdit && !empty($templates))
    <div class="form-group">
        <label class="form-label">Template (optionnel)</label>
        <select id="templateSelect" class="form-select">
            <option value="">— Aucun (saisie libre) —</option>
            @foreach($templates as $t)
            <option value="{{ $t['id_template'] }}" data-modele='@json($t['modele'] ?? new \stdClass())'>{{ $t['nom_template'] }}</option>
            @endforeach
        </select>
        <input type="hidden" name="id_template" id="idTemplate" />
    </div>
    @endif

    <div class="form-group">
        <label class="form-label" for="titre">Titre</label>
        <input type="text" name="titre" id="titre" class="form-input" required maxlength="200"
               value="{{ old('titre', $evenement['titre'] ?? '') }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="description">Description</label>
        <textarea name="description" id="description" class="form-textarea" required rows="6">{{ old('description', $evenement['description'] ?? '') }}</textarea>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:24px;">
        <div class="form-group">
            <label class="form-label" for="type_evenement">Type</label>
            <select name="type_evenement" id="type_evenement" class="form-select" required>
                @foreach(['formation' => 'Formation', 'atelier' => 'Atelier', 'conference' => 'Conférence', 'conseil' => 'Conseil'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('type_evenement', $evenement['type_evenement'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="nb_places_total">Places (globales)</label>
            <input type="number" name="nb_places_total" id="nb_places_total" class="form-input" required min="1"
                   value="{{ old('nb_places_total', $evenement['nb_places_total'] ?? 10) }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="prix">Prix (€)</label>
            <input type="number" name="prix" id="prix" class="form-input" required min="0" step="0.01"
                   value="{{ old('prix', $evenement['prix'] ?? 0) }}">
        </div>
    </div>

    <div style="margin-top:8px; padding-top:20px; border-top:2px solid rgba(18,3,9,0.1);">
        @include('partials.seances-editor', ['seances' => $evenement['seances'] ?? null, 'animateurs' => $animateurs ?? []])
    </div>

    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-top:28px; padding-top:24px; border-top:2px solid rgba(18,3,9,0.1);">
        <button type="submit" class="btn-primary" id="submitBtn">{{ $isEdit ? 'Mettre à jour' : 'Soumettre pour validation' }}</button>
        <button type="button" class="btn-secondary" onclick="openSaveTplModal()">Enregistrer comme modèle</button>
        <a href="{{ route('salarie.evenements.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>

{{-- Mini-modale : enregistrer les infos générales comme modèle réutilisable --}}
<div class="modal-overlay" id="saveTplModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeSaveTplModal()">&times;</button>
        <h3 style="margin:0 0 6px;">Enregistrer comme modèle</h3>
        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; color:#888; margin:0 0 18px;">
            Les infos générales (titre, type, description, places, prix) sont sauvegardées. Les séances (dates/lieux) ne le sont pas.
        </p>
        <form action="{{ route('salarie.templates.store') }}" method="POST" id="saveTplForm">
            @csrf
            <div class="form-group">
                <label class="form-label" for="nom_template">Nom du modèle</label>
                <input type="text" name="nom_template" id="nom_template" class="form-input" required maxlength="150" placeholder="ex. Atelier réparation mensuel">
            </div>
            <input type="hidden" name="m_titre" id="tpl_titre">
            <input type="hidden" name="m_description" id="tpl_description">
            <input type="hidden" name="type_evenement" id="tpl_type">
            <input type="hidden" name="nb_places_total" id="tpl_places">
            <input type="hidden" name="prix" id="tpl_prix">
            <button type="submit" class="btn-primary" style="margin-top:6px;">Enregistrer le modèle</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:480px; padding:28px 32px; position:relative; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    #submitBtn:disabled { opacity: 0.4; cursor: not-allowed; filter: grayscale(0.6); }
</style>
<script>
function openSaveTplModal() {
    if (!document.getElementById('titre').value && !document.getElementById('description').value) {
        alert('Renseignez au moins le titre ou la description avant d\'enregistrer un modèle.');
        return;
    }
    document.getElementById('tpl_titre').value       = document.getElementById('titre').value;
    document.getElementById('tpl_description').value  = document.getElementById('description').value;
    document.getElementById('tpl_type').value         = document.getElementById('type_evenement').value;
    document.getElementById('tpl_places').value       = document.getElementById('nb_places_total').value || 10;
    document.getElementById('tpl_prix').value         = document.getElementById('prix').value || 0;
    document.getElementById('saveTplModal').classList.add('active');
}
function closeSaveTplModal() { document.getElementById('saveTplModal').classList.remove('active'); }
document.getElementById('saveTplModal').addEventListener('mousedown', function (e) { if (e.target.id === 'saveTplModal') closeSaveTplModal(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSaveTplModal(); });

// === Garde-fou prix : confirmer la gratuité si le prix est à 0 ===
(function () {
    var form = document.getElementById('evForm');
    if (!form) return;
    var confirmedFree = false;
    form.addEventListener('submit', async function (e) {
        if (confirmedFree) return;
        var prix = parseFloat(document.getElementById('prix').value || '0');
        if (!isNaN(prix) && prix === 0) {
            e.preventDefault();
            var msg = "Aucun prix indiqué : l'événement sera proposé GRATUITEMENT. Confirmez-vous ?";
            var ok = window.confirmAction ? await window.confirmAction(msg) : window.confirm(msg);
            if (ok) { confirmedFree = true; form.submit(); }
        }
    });
})();

// === Auto-remplissage depuis un template (infos générales uniquement) ===
(function () {
    var sel = document.getElementById('templateSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var opt = sel.options[sel.selectedIndex];
        document.getElementById('idTemplate').value = opt.value || '';
        if (!opt.value) return;
        try {
            var m = JSON.parse(opt.getAttribute('data-modele') || '{}');
            document.getElementById('titre').value = m.titre || '';
            document.getElementById('description').value = m.description || '';
            if (m.type_evenement) document.getElementById('type_evenement').value = m.type_evenement;
            document.getElementById('nb_places_total').value = m.nb_places_total || 10;
            document.getElementById('prix').value = (m.prix !== undefined ? m.prix : 0);
        } catch (e) { console.warn('Template invalide', e); }
    });
})();
</script>
@endsection

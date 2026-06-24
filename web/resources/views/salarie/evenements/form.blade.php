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

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <div class="form-group">
            <label class="form-label" for="type_evenement">Type</label>
            <select name="type_evenement" id="type_evenement" class="form-select" required>
                @foreach(['formation' => 'Formation', 'atelier' => 'Atelier', 'conference' => 'Conférence'] as $val => $lbl)
                <option value="{{ $val }}" {{ old('type_evenement', $evenement['type_evenement'] ?? '') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="format">Format</label>
            <select name="format" id="format" class="form-select" required>
                <option value="presentiel" {{ old('format', $evenement['format'] ?? '') === 'presentiel' ? 'selected' : '' }}>Présentiel</option>
                <option value="distanciel" {{ old('format', $evenement['format'] ?? '') === 'distanciel' ? 'selected' : '' }}>Distanciel</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="lieu">Lieu (vide si distanciel)</label>
        <div class="autocomplete-wrapper">
            <input type="text" name="lieu" id="lieu" class="form-input" maxlength="300" autocomplete="off"
                   placeholder="Commencez à taper une adresse…"
                   value="{{ old('lieu', $evenement['lieu'] ?? '') }}">
            <div class="autocomplete-dropdown" id="lieuSuggestions"></div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <div class="form-group">
            <label class="form-label" for="date_debut">Début</label>
            <input type="datetime-local" name="date_debut" id="date_debut" class="form-input" required
                   value="{{ old('date_debut', isset($evenement['date_debut']) ? \Carbon\Carbon::parse($evenement['date_debut'])->format('Y-m-d\TH:i') : '') }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="date_fin">Fin</label>
            <input type="datetime-local" name="date_fin" id="date_fin" class="form-input" required
                   value="{{ old('date_fin', isset($evenement['date_fin']) ? \Carbon\Carbon::parse($evenement['date_fin'])->format('Y-m-d\TH:i') : '') }}">
        </div>
    </div>

    <div id="dateAlert" class="date-alert" style="display:none;"></div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
        <div class="form-group">
            <label class="form-label" for="nb_places_total">Nombre de places</label>
            <input type="number" name="nb_places_total" id="nb_places_total" class="form-input" required min="1"
                   value="{{ old('nb_places_total', $evenement['nb_places_total'] ?? 10) }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="prix">Prix (€)</label>
            <input type="number" name="prix" id="prix" class="form-input" required min="0" step="0.01"
                   value="{{ old('prix', $evenement['prix'] ?? 0) }}">
        </div>
    </div>

    <div style="display:flex; gap:16px; flex-wrap:wrap;">
        <button type="submit" class="btn-primary" id="submitBtn">{{ $isEdit ? 'Mettre à jour' : 'Soumettre pour validation' }}</button>
        <button type="button" class="btn-secondary" onclick="openSaveTplModal()">Enregistrer comme modèle</button>
        <a href="{{ route('salarie.evenements.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>

{{-- Mini-modale : enregistrer les champs courants comme modèle réutilisable --}}
<div class="modal-overlay" id="saveTplModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeSaveTplModal()">&times;</button>
        <h3 style="margin:0 0 6px;">Enregistrer comme modèle</h3>
        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; color:#888; margin:0 0 18px;">
            Les valeurs actuelles du formulaire (type, format, description, places, prix…) seront sauvegardées pour réutilisation. Les dates ne sont pas enregistrées.
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
            <input type="hidden" name="format" id="tpl_format">
            <input type="hidden" name="m_lieu" id="tpl_lieu">
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
    .autocomplete-wrapper { position: relative; }
    .autocomplete-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: var(--cream); border: var(--border); border-top: none; max-height: 240px; overflow-y: auto; box-shadow: var(--shadow-sm); }
    .autocomplete-item { padding: 10px 14px; cursor: pointer; font-family: 'DM Mono', monospace; font-size: 0.85rem; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover { background: var(--wheat); }
    .date-alert { margin: 4px 0 20px; padding: 12px 16px; border: 3px solid var(--cherry); background: #f8d7da; color: var(--cherry); font-family: 'DM Mono', monospace; font-size: 0.82rem; text-transform: uppercase; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow-sm); }
    .date-alert::before { content: '⚠'; font-size: 1.1rem; }
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
    document.getElementById('tpl_format').value       = document.getElementById('format').value;
    document.getElementById('tpl_lieu').value         = document.getElementById('lieu').value;
    document.getElementById('tpl_places').value       = document.getElementById('nb_places_total').value || 10;
    document.getElementById('tpl_prix').value         = document.getElementById('prix').value || 0;
    document.getElementById('saveTplModal').classList.add('active');
}
function closeSaveTplModal() { document.getElementById('saveTplModal').classList.remove('active'); }
document.getElementById('saveTplModal').addEventListener('mousedown', function (e) { if (e.target.id === 'saveTplModal') closeSaveTplModal(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSaveTplModal(); });

// === Garde-fou prix : confirmer la gratuité si le prix est à 0 (oubli probable) ===
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

// === Validation temps réel des dates (durée max 2 semaines) ===
(function () {
    var debut = document.getElementById('date_debut');
    var fin = document.getElementById('date_fin');
    var alertBox = document.getElementById('dateAlert');
    var submitBtn = document.getElementById('submitBtn');
    if (!debut || !fin || !alertBox || !submitBtn) return;
    var MAX_MS = 14 * 24 * 3600 * 1000;

    function check() {
        var d = debut.value ? new Date(debut.value) : null;
        var f = fin.value ? new Date(fin.value) : null;
        var msg = '';
        if (d && f) {
            if (f <= d) {
                msg = 'La date de fin doit être postérieure à la date de début.';
            } else if (f - d > MAX_MS) {
                msg = "La durée d'un événement ne peut pas dépasser 2 semaines (14 jours).";
            }
        }
        if (msg) {
            alertBox.textContent = msg;
            alertBox.style.display = 'flex';
            submitBtn.disabled = true;
        } else {
            alertBox.style.display = 'none';
            submitBtn.disabled = false;
        }
    }
    debut.addEventListener('input', check);
    fin.addEventListener('input', check);
    debut.addEventListener('change', check);
    fin.addEventListener('change', check);
    check();
})();

// === Autocomplétion adresse (Base Adresse Nationale - data.geopf.fr) ===
(function () {
    var lieuInput = document.getElementById('lieu');
    var lieuSuggestions = document.getElementById('lieuSuggestions');
    if (!lieuInput || !lieuSuggestions) return;
    var debounceTimer = null;

    function closeSuggestions() {
        lieuSuggestions.style.display = 'none';
        lieuSuggestions.innerHTML = '';
    }

    lieuInput.addEventListener('input', function () {
        var value = lieuInput.value.trim();
        clearTimeout(debounceTimer);
        if (value.length < 3) { closeSuggestions(); return; }
        debounceTimer = setTimeout(async function () {
            try {
                var res = await fetch('https://data.geopf.fr/geocodage/search/?q=' + encodeURIComponent(value) + '&limit=5');
                if (!res.ok) throw new Error();
                var data = await res.json();
                var features = data.features || [];
                if (features.length === 0) { closeSuggestions(); return; }
                lieuSuggestions.innerHTML = '';
                features.forEach(function (feature) {
                    var item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = feature.properties.label;
                    item.addEventListener('mousedown', function (e) {
                        e.preventDefault();
                        lieuInput.value = feature.properties.label;
                        closeSuggestions();
                    });
                    lieuSuggestions.appendChild(item);
                });
                lieuSuggestions.style.display = 'block';
            } catch {
                closeSuggestions();
            }
        }, 300);
    });
    lieuInput.addEventListener('blur', function () { setTimeout(closeSuggestions, 150); });
})();

(function () {
    var sel = document.getElementById('templateSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var opt = sel.options[sel.selectedIndex];
        document.getElementById('idTemplate').value = opt.value || '';
        if (!opt.value) return;
        try {
            var m = JSON.parse(opt.getAttribute('data-modele') || '{}');
            // À chaque changement de modèle, tous les champs sont réécrits avec ses valeurs
            ['titre','description','lieu'].forEach(function (k) {
                document.getElementById(k).value = m[k] || '';
            });
            ['type_evenement','format'].forEach(function (k) {
                if (m[k]) document.getElementById(k).value = m[k];
            });
            document.getElementById('nb_places_total').value = m.nb_places_total || 10;
            document.getElementById('prix').value = (m.prix !== undefined ? m.prix : 0);
        } catch (e) { console.warn('Template invalide', e); }
    });
})();
</script>
@endsection

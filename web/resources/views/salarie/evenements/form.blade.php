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

<form action="{{ $action }}" method="POST" class="card" autocomplete="off">
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
        <input type="text" name="lieu" id="lieu" class="form-input" maxlength="300"
               value="{{ old('lieu', $evenement['lieu'] ?? '') }}">
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

    <div style="display:flex; gap:16px;">
        <button type="submit" class="btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Soumettre pour validation' }}</button>
        <a href="{{ route('salarie.evenements.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    var sel = document.getElementById('templateSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
        var opt = sel.options[sel.selectedIndex];
        document.getElementById('idTemplate').value = opt.value || '';
        if (!opt.value) return;
        try {
            var m = JSON.parse(opt.getAttribute('data-modele') || '{}');
            ['titre','description','lieu'].forEach(function (k) {
                if (m[k] && !document.getElementById(k).value) document.getElementById(k).value = m[k];
            });
            ['type_evenement','format'].forEach(function (k) {
                if (m[k]) document.getElementById(k).value = m[k];
            });
            if (m.nb_places_total) document.getElementById('nb_places_total').value = m.nb_places_total;
            if (m.prix !== undefined) document.getElementById('prix').value = m.prix;
        } catch (e) { console.warn('Template invalide', e); }
    });
})();
</script>
@endsection

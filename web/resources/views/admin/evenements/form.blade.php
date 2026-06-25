@extends('layouts.admin')
@section('title', isset($evenement) ? 'Modifier événement' : 'Nouvel événement')

@section('content')
<style>
    .autocomplete-wrapper { position: relative; }
    .autocomplete-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: var(--cream); border: var(--border); border-top: none; max-height: 240px; overflow-y: auto; box-shadow: var(--shadow-sm); }
    .autocomplete-item { padding: 10px 14px; cursor: pointer; font-family: 'DM Mono', monospace; font-size: 0.85rem; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover { background: var(--wheat); }
</style>
<div class="page-header">
    <h1 class="page-title">{{ isset($evenement) ? 'Modifier événement' : 'Nouvel événement' }}</h1>
    <a href="{{ route('admin.evenements.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

@if($errors->any())
<div class="alert alert-error" style="display: block; text-transform: none;">
    <p style="font-weight: 700; margin: 0 0 8px;">Erreurs du formulaire :</p>
    <ul style="margin: 0; padding-left: 20px; font-family: 'Outfit', sans-serif;">
        @foreach($errors->all() as $error)
            <li style="font-size: 0.95rem;">{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card">
    <form method="POST" action="{{ isset($evenement) ? route('admin.evenements.update', $evenement['id_evenement']) : route('admin.evenements.store') }}">
        @csrf
        @if(isset($evenement))
            @method('PUT')
        @endif

        <div class="info-grid">
            <div class="form-group">
                <label class="form-label" for="titre">Titre</label>
                <input id="titre" name="titre" class="form-input" value="{{ old('titre', $evenement['titre'] ?? '') }}" required>
                @error('titre')<span style="display: block; margin-top: 8px; color: var(--cherry); font-family: 'DM Mono', monospace; font-size: 0.8rem;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="type_evenement">Type</label>
                <select id="type_evenement" name="type_evenement" class="form-select" required>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type_evenement', $evenement['type_evenement'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="format">Format</label>
                <select id="format" name="format" class="form-select" required>
                    @foreach($formats as $key => $label)
                        <option value="{{ $key }}" {{ old('format', $evenement['format'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="lieu">Lieu (vide si distanciel)</label>
                <div class="autocomplete-wrapper">
                    <input id="lieu" name="lieu" class="form-input" autocomplete="off"
                           placeholder="Commencez à taper une adresse…"
                           value="{{ old('lieu', $evenement['lieu'] ?? '') }}">
                    <div class="autocomplete-dropdown" id="lieuSuggestions"></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="date_debut">Date début</label>
                <div style="display: flex; gap: 12px;">
                    <input id="date_debut_date" type="date" class="form-input" style="flex:1"
                        value="{{ old('date_debut_date', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_debut'])->format('Y-m-d') : '') }}"
                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                    <input id="date_debut_hour" type="number" min="0" max="23" class="form-input" style="flex:0.5" placeholder="HH"
                        value="{{ old('date_debut_hour', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_debut'])->format('H') : '') }}" required>
                    <input id="date_debut_minute" type="number" min="0" max="59" step="5" class="form-input" style="flex:0.5" placeholder="MM"
                        value="{{ old('date_debut_minute', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_debut'])->format('i') : '') }}" required>
                </div>
                <input id="date_debut" name="date_debut" type="hidden">
                @error('date_debut')<span style="display: block; margin-top: 8px; color: var(--cherry); font-family: 'DM Mono', monospace; font-size: 0.8rem;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="date_fin">Date fin</label>
                <div style="display: flex; gap: 12px;">
                    <input id="date_fin_date" type="date" class="form-input" style="flex:1"
                        value="{{ old('date_fin_date', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_fin'])->format('Y-m-d') : '') }}"
                        min="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                    <input id="date_fin_hour" type="number" min="0" max="23" class="form-input" style="flex:0.5" placeholder="HH"
                        value="{{ old('date_fin_hour', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_fin'])->format('H') : '') }}" required>
                    <input id="date_fin_minute" type="number" min="0" max="59" step="5" class="form-input" style="flex:0.5" placeholder="MM"
                        value="{{ old('date_fin_minute', isset($evenement) ? \Carbon\Carbon::parse($evenement['date_fin'])->format('i') : '') }}" required>
                </div>
                <input id="date_fin" name="date_fin" type="hidden">
                @error('date_fin')<span style="display: block; margin-top: 8px; color: var(--cherry); font-family: 'DM Mono', monospace; font-size: 0.8rem;">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="nb_places_total">Capacité</label>
                <input id="nb_places_total" name="nb_places_total" type="number" min="1" class="form-input" value="{{ old('nb_places_total', $evenement['nb_places_total'] ?? '') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="prix">Prix (€)</label>
                <input id="prix" name="prix" type="number" step="0.01" min="0" class="form-input" value="{{ old('prix', $evenement['prix'] ?? '') }}" required>
            </div>

            @if(count($users) > 0)
            <div class="form-group full-width">
                <label class="form-label">Animateurs</label>
                @php
                    $selectedIds = collect($evenement['animateurs'] ?? [])->pluck('id_utilisateur')->toArray();
                @endphp
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    @foreach($users as $user)
                    <label style="display: flex; align-items: center; gap: 8px; padding: 8px 14px; border: 2px solid var(--coffee); background: white; cursor: pointer; font-size: 0.95rem;">
                        <input type="checkbox" name="animateurs[]" value="{{ $user['id_utilisateur'] }}"
                            {{ in_array($user['id_utilisateur'], old('animateurs', $selectedIds)) ? 'checked' : '' }}>
                        {{ $user['prenom'] }} {{ $user['nom'] }}
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="form-group full-width">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-textarea" required>{{ old('description', $evenement['description'] ?? '') }}</textarea>
                @error('description')<span style="display: block; margin-top: 8px; color: var(--cherry); font-family: 'DM Mono', monospace; font-size: 0.8rem;">{{ $message }}</span>@enderror
            </div>
        </div>

        <div style="margin-top: 32px; padding-top: 28px; border-top: 2px solid rgba(18,3,9,0.1);">
            <button type="submit" class="btn-primary">{{ isset($evenement) ? 'Enregistrer' : 'Créer' }}</button>
        </div>
    </form>
</div>

<script>
function buildDate(dateId, hourId, minId, hiddenId) {
    const d = document.getElementById(dateId).value;
    const h = String(document.getElementById(hourId).value || '0').padStart(2, '0');
    const m = String(document.getElementById(minId).value || '0').padStart(2, '0');
    if (d) document.getElementById(hiddenId).value = `${d}T${h}:${m}:00Z`;
}
function syncDates() {
    buildDate('date_debut_date', 'date_debut_hour', 'date_debut_minute', 'date_debut');
    buildDate('date_fin_date', 'date_fin_hour', 'date_fin_minute', 'date_fin');
}
['date_debut_date','date_debut_hour','date_debut_minute','date_fin_date','date_fin_hour','date_fin_minute']
    .forEach(id => document.getElementById(id).addEventListener('change', syncDates));
syncDates();

// === Autocomplétion adresse (Base Adresse Nationale - data.geopf.fr) ===
(function () {
    var lieuInput = document.getElementById('lieu');
    var lieuSuggestions = document.getElementById('lieuSuggestions');
    if (!lieuInput || !lieuSuggestions) return;
    var debounceTimer = null;
    function closeSuggestions() { lieuSuggestions.style.display = 'none'; lieuSuggestions.innerHTML = ''; }
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
            } catch (e) { closeSuggestions(); }
        }, 300);
    });
    lieuInput.addEventListener('blur', function () { setTimeout(closeSuggestions, 150); });
})();
</script>
@endsection

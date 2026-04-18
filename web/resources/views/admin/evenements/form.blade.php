@extends('layouts.admin')
@section('title', isset($evenement) ? 'Modifier événement' : 'Nouvel événement')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($evenement) ? 'Modifier événement' : 'Nouvel événement' }}</h1>
    <a href="{{ route('admin.evenements.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

@if($errors->any())
<div style="background-color: #ffe6e6; border: 1px solid #A4243B; border-radius: 4px; padding: 16px; margin-bottom: 24px;">
    <p style="color: #A4243B; font-weight: 600; margin: 0 0 8px 0;">Erreurs du formulaire :</p>
    <ul style="margin: 0; padding-left: 20px;">
        @foreach($errors->all() as $error)
            <li style="color: #A4243B; font-size: 0.9rem;">{{ $error }}</li>
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
                @error('titre')<span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>@enderror
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
                <label class="form-label" for="lieu">Lieu</label>
                <input id="lieu" name="lieu" class="form-input" value="{{ old('lieu', $evenement['lieu'] ?? '') }}">
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
                @error('date_debut')<span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>@enderror
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
                @error('date_fin')<span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>@enderror
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
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($users as $user)
                    <label style="display: flex; align-items: center; gap: 6px; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 0.9rem;">
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
                @error('description')<span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>@enderror
            </div>
        </div>

        <button type="submit" class="btn-primary">{{ isset($evenement) ? 'Enregistrer' : 'Créer' }}</button>
    </form>
</div>

<script>
function buildDate(dateId, hourId, minId, hiddenId) {
    const d = document.getElementById(dateId).value;
    const h = String(document.getElementById(hourId).value || '0').padStart(2, '0');
    const m = String(document.getElementById(minId).value || '0').padStart(2, '0');
    if (d) document.getElementById(hiddenId).value = `${d} ${h}:${m}:00`;
}
function syncDates() {
    buildDate('date_debut_date', 'date_debut_hour', 'date_debut_minute', 'date_debut');
    buildDate('date_fin_date', 'date_fin_hour', 'date_fin_minute', 'date_fin');
}
['date_debut_date','date_debut_hour','date_debut_minute','date_fin_date','date_fin_hour','date_fin_minute']
    .forEach(id => document.getElementById(id).addEventListener('change', syncDates));
syncDates();
</script>
@endsection

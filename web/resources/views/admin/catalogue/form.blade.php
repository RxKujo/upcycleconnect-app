@extends('layouts.admin')
@section('title', isset($item) ? 'Modifier élément' : 'Nouvel élément')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($item) ? 'Modifier élément' : 'Nouvel élément' }}</h1>
    <a href="{{ route('admin.catalogue.index') }}" class="btn-secondary btn-sm">← Retour</a>
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
    <form method="POST" action="{{ isset($item) ? route('admin.catalogue.update', $item['id_catalogue_item']) : route('admin.catalogue.store') }}">
        @csrf
        @if(isset($item))
            @method('PUT')
        @endif

        <div class="info-grid">
            <div class="form-group">
                <label class="form-label" for="titre">Titre</label>
                <input id="titre" name="titre" class="form-input" value="{{ old('titre', $item['titre'] ?? '') }}" required>
                @error('titre')
                    <span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="categorie">Catégorie</label>
                <select id="categorie" name="categorie" class="form-select" required>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ old('categorie', $item['categorie'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="format">Format</label>
                <select id="format" name="format" class="form-select" required>
                    @foreach($formats as $key => $label)
                        <option value="{{ $key }}" {{ old('format', $item['format'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="lieu">Lieu</label>
                <input id="lieu" name="lieu" class="form-input" value="{{ old('lieu', $item['lieu'] ?? '') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="date_debut">Date début</label>
                <div style="display: flex; gap: 12px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Date</label>
                        <input id="date_debut_date" type="date" class="form-input" value="{{ old('date_debut_date', isset($item) ? \Carbon\Carbon::parse($item['date_debut'])->format('Y-m-d') : '') }}" required>
                    </div>
                    <div style="flex: 0.8;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Heure</label>
                        <input id="date_debut_hour" type="number" min="0" max="23" class="form-input" placeholder="00" value="{{ old('date_debut_hour', isset($item) ? \Carbon\Carbon::parse($item['date_debut'])->format('H') : '') }}" required>
                    </div>
                    <div style="flex: 0.8;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Min</label>
                        <input id="date_debut_minute" type="number" min="0" max="59" step="5" class="form-input" placeholder="00" value="{{ old('date_debut_minute', isset($item) ? \Carbon\Carbon::parse($item['date_debut'])->format('i') : '') }}" required>
                    </div>
                </div>
                <input id="date_debut" name="date_debut" type="hidden" value="{{ old('date_debut', '') }}">
                @error('date_debut')
                    <span style="color: #A4243B; font-size: 0.85rem; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label" for="date_fin">Date fin</label>
                <div style="display: flex; gap: 12px; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Date</label>
                        <input id="date_fin_date" type="date" class="form-input" value="{{ old('date_fin_date', isset($item) ? \Carbon\Carbon::parse($item['date_fin'])->format('Y-m-d') : '') }}" required>
                    </div>
                    <div style="flex: 0.8;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Heure</label>
                        <input id="date_fin_hour" type="number" min="0" max="23" class="form-input" placeholder="00" value="{{ old('date_fin_hour', isset($item) ? \Carbon\Carbon::parse($item['date_fin'])->format('H') : '') }}" required>
                    </div>
                    <div style="flex: 0.8;">
                        <label style="display: block; font-size: 0.8rem; color: #666; margin-bottom: 4px;">Min</label>
                        <input id="date_fin_minute" type="number" min="0" max="59" step="5" class="form-input" placeholder="00" value="{{ old('date_fin_minute', isset($item) ? \Carbon\Carbon::parse($item['date_fin'])->format('i') : '') }}" required>
                    </div>
                </div>
                <input id="date_fin" name="date_fin" type="hidden" value="{{ old('date_fin', '') }}">
                @error('date_fin')
                    <span style="color: #A4243B; font-size: 0.85rem; display: block; margin-top: 4px;">{{ $message }}</span>
                @enderror
            </div>

            <script>
                function formatDate() {
                    const dateDebut = document.getElementById('date_debut_date').value;
                    const hourDebut = String(document.getElementById('date_debut_hour').value || '00').padStart(2, '0');
                    const minDebut = String(document.getElementById('date_debut_minute').value || '00').padStart(2, '0');
                    
                    if (dateDebut) {
                        // Format ISO pour Laravel et Go : Y-m-d H:i:s
                        document.getElementById('date_debut').value = `${dateDebut} ${hourDebut}:${minDebut}:00`;
                    }

                    const dateFin = document.getElementById('date_fin_date').value;
                    const hourFin = String(document.getElementById('date_fin_hour').value || '00').padStart(2, '0');
                    const minFin = String(document.getElementById('date_fin_minute').value || '00').padStart(2, '0');
                    
                    if (dateFin) {
                        // Format ISO pour Laravel et Go : Y-m-d H:i:s
                        document.getElementById('date_fin').value = `${dateFin} ${hourFin}:${minFin}:00`;
                    }
                }

                document.getElementById('date_debut_date').addEventListener('change', formatDate);
                document.getElementById('date_debut_hour').addEventListener('input', formatDate);
                document.getElementById('date_debut_minute').addEventListener('input', formatDate);
                document.getElementById('date_fin_date').addEventListener('change', formatDate);
                document.getElementById('date_fin_hour').addEventListener('input', formatDate);
                document.getElementById('date_fin_minute').addEventListener('input', formatDate);

                window.addEventListener('load', formatDate);
            </script>
            <div class="form-group">
                <label class="form-label" for="nb_places_total">Capacité</label>
                <input id="nb_places_total" name="nb_places_total" type="number" min="1" class="form-input" value="{{ old('nb_places_total', $item['nb_places_total'] ?? '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="prix">Prix (€)</label>
                <input id="prix" name="prix" type="number" step="0.01" min="0" class="form-input" value="{{ old('prix', $item['prix'] ?? '') }}" required>
            </div>
            <div class="form-group full-width">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-textarea" required>{{ old('description', $item['description'] ?? '') }}</textarea>
                @error('description')
                    <span style="color: #A4243B; font-size: 0.85rem;">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn-primary">{{ isset($item) ? 'Enregistrer' : 'Créer' }}</button>
    </form>
</div>
@endsection

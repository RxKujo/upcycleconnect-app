@extends('layouts.admin')
@section('title', isset($item) ? 'Modifier élément' : 'Nouvel élément')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($item) ? 'Modifier élément' : 'Nouvel élément' }}</h1>
    <a href="{{ route('admin.catalogue.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

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
                <input id="date_debut" name="date_debut" type="datetime-local" class="form-input" value="{{ old('date_debut', isset($item['date_debut']) ? \Carbon\Carbon::parse($item['date_debut'])->format('Y-m-d\TH:i') : '') }}" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="date_fin">Date fin</label>
                <input id="date_fin" name="date_fin" type="datetime-local" class="form-input" value="{{ old('date_fin', isset($item['date_fin']) ? \Carbon\Carbon::parse($item['date_fin'])->format('Y-m-d\TH:i') : '') }}" required>
            </div>
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
            </div>
        </div>

        <button type="submit" class="btn-primary">{{ isset($item) ? 'Enregistrer' : 'Créer' }}</button>
    </form>
</div>
@endsection

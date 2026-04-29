@extends('layouts.salarie')

@section('title', $article ? 'Modifier article' : 'Nouvel article')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $article ? 'Modifier' : 'Nouvel' }} article</h1>
    <a href="{{ route('salarie.articles.index') }}" class="btn-secondary">← Retour</a>
</div>

@php
    $isEdit = $article !== null;
    $action = $isEdit ? route('salarie.articles.update', $article['id_article']) : route('salarie.articles.store');
@endphp

<form action="{{ $action }}" method="POST" class="card" autocomplete="off">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-group">
        <label class="form-label" for="titre">Titre</label>
        <input type="text" name="titre" id="titre" class="form-input" required maxlength="300"
               value="{{ old('titre', $article['titre'] ?? '') }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="categorie">Catégorie (optionnel)</label>
        <input type="text" name="categorie" id="categorie" class="form-input" maxlength="100"
               placeholder="conseils, actualites, tutoriel..."
               value="{{ old('categorie', $article['categorie'] ?? '') }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="contenu">Contenu</label>
        <textarea name="contenu" id="contenu" class="form-textarea" required rows="14">{{ old('contenu', $article['contenu'] ?? '') }}</textarea>
    </div>

    <div class="form-group">
        <label class="form-label" for="statut">Statut</label>
        <select name="statut" id="statut" class="form-select" required>
            <option value="brouillon" {{ old('statut', $article['statut'] ?? 'brouillon') === 'brouillon' ? 'selected' : '' }}>Brouillon (non visible)</option>
            <option value="publie" {{ old('statut', $article['statut'] ?? '') === 'publie' ? 'selected' : '' }}>Publié</option>
            <option value="archive" {{ old('statut', $article['statut'] ?? '') === 'archive' ? 'selected' : '' }}>Archivé</option>
        </select>
    </div>

    <div style="display:flex; gap:16px;">
        <button type="submit" class="btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
        <a href="{{ route('salarie.articles.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection

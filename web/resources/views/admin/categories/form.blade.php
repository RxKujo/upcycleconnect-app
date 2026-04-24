@extends('layouts.admin')
@section('title', $categorie ? 'Modifier catégorie' : 'Nouvelle catégorie')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $categorie ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h1>
    <a href="{{ route('admin.categories.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="max-width: 600px; cursor: default; transform: none;">
    <form method="POST"
          action="{{ $categorie ? route('admin.categories.update', $categorie['id_categorie']) : route('admin.categories.store') }}">
        @csrf
        @if($categorie)
            @method('PUT')
        @endif

        <div class="form-group">
            <label class="form-label">Nom</label>
            <input type="text" name="nom" class="form-input" value="{{ old('nom', $categorie['nom'] ?? '') }}" required>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-textarea" required>{{ old('description', $categorie['description'] ?? '') }}</textarea>
        </div>

        <button type="submit" class="btn-primary">
            {{ $categorie ? 'Enregistrer' : 'Créer' }}
        </button>
    </form>
</div>
@endsection

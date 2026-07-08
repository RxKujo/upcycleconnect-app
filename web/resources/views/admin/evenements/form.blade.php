@extends('layouts.admin')
@section('title', isset($evenement) ? 'Modifier événement' : 'Nouvel événement')

{{-- Vue admin : formulaire de création / édition d'un événement (le même gabarit sert
     aux deux cas selon la présence de $evenement). Inclut l'éditeur de séances. --}}

@section('content')
{{-- === En-tête de page === --}}
<div class="page-header">
    <h1 class="page-title">{{ isset($evenement) ? 'Modifier événement' : 'Nouvel événement' }}</h1>
    <a href="{{ route('admin.evenements.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

{{-- === Erreurs de validation === --}}
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

{{-- === Formulaire principal (infos générales + séances) === --}}
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
                <label class="form-label" for="nb_places_total">Capacité (globale)</label>
                <input id="nb_places_total" name="nb_places_total" type="number" min="1" class="form-input" value="{{ old('nb_places_total', $evenement['nb_places_total'] ?? '') }}" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="prix">Prix (€) — pour toute la formation</label>
                <input id="prix" name="prix" type="number" step="0.01" min="0" class="form-input" value="{{ old('prix', $evenement['prix'] ?? '') }}" required>
            </div>

            <div class="form-group full-width">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-textarea" required>{{ old('description', $evenement['description'] ?? '') }}</textarea>
            </div>
        </div>

        {{-- === Éditeur de séances (partial réutilisable) === --}}
        <div style="margin-top: 28px; padding-top: 24px; border-top: 2px solid rgba(18,3,9,0.1);">
            @include('partials.seances-editor', ['seances' => $evenement['seances'] ?? null, 'animateurs' => $users])
        </div>

        <div style="margin-top: 32px; padding-top: 28px; border-top: 2px solid rgba(18,3,9,0.1);">
            <button type="submit" class="btn-primary">{{ isset($evenement) ? 'Enregistrer' : 'Créer' }}</button>
        </div>
    </form>
</div>
@endsection

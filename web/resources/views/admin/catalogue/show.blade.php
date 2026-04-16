@extends('layouts.admin')
@section('title', 'Détails de l\'Annonce')

@section('content')
<div class="page-header" style="align-items: flex-start; flex-direction: column; gap: 16px;">
    <div>
        <a href="{{ route('admin.catalogue.index') }}" style="color: var(--cherry); text-decoration: none; font-family: 'DM Mono', monospace; font-size: 0.9rem; font-weight: bold;">← RETOUR AUX ANNONCES</a>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <h2 class="page-title">{{ $item['titre'] }}</h2>
        <div>
            @if($item['statut'] === 'validee')
                <x-badge variant="valid" style="font-size: 1.1rem; padding: 10px 20px;">VALIDÉE</x-badge>
            @elseif($item['statut'] === 'en_attente')
                <x-badge style="font-size: 1.1rem; padding: 10px 20px;">EN ATTENTE</x-badge>
            @elseif($item['statut'] === 'vendue')
                <x-badge style="font-size: 1.1rem; padding: 10px 20px; background-color: var(--coffee); color: white;">VENDUE</x-badge>
            @else
                <x-badge variant="refused" style="font-size: 1.1rem; padding: 10px 20px;">{{ strtoupper($item['statut']) }}</x-badge>
            @endif
        </div>
    </div>
</div>

<x-card>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Type d'annonce</span>
            <p class="info-value"><strong style="color: {{ $item['type_annonce'] == 'don' ? 'var(--forest)' : 'var(--cherry)' }}; text-transform: uppercase;">{{ $item['type_annonce'] }}</strong></p>
        </div>

        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ $item['type_annonce'] == 'vente' && isset($item['prix']) ? number_format($item['prix'], 2) . ' €' : 'Gratuit' }}</p>
        </div>

        <div class="info-item">
            <span class="info-label">Mode de Remise</span>
            <p class="info-value">{{ $item['mode_remise'] == 'conteneur' ? 'Dépôt en Conteneur' : 'Remise en Main Propre' }}</p>
        </div>

        <div class="info-item">
            <span class="info-label">Date de Création</span>
            <p class="info-value">{{ date('d/m/Y à H:i', strtotime($item['date_creation'])) }}</p>
        </div>

        @if(isset($item['categorie_objet']))
        <div class="info-item">
            <span class="info-label">Catégorie Objet</span>
            <p class="info-value">{{ ucfirst($item['categorie_objet']) }}</p>
        </div>
        @endif

        @if(isset($item['materiau_objet']))
        <div class="info-item">
            <span class="info-label">Matériau Principal</span>
            <p class="info-value">{{ ucfirst($item['materiau_objet']) }}</p>
        </div>
        @endif

        @if(isset($item['etat_objet']))
        <div class="info-item">
            <span class="info-label">État de l'objet</span>
            <p class="info-value">{{ str_replace('_', ' ', ucfirst($item['etat_objet'])) }}</p>
        </div>
        @endif

        <div class="info-item full-width">
            <span class="info-label">Description Complète</span>
            <p class="info-value" style="white-space: pre-line; background: rgba(0,0,0,0.02); padding: 20px; border-left: 4px solid var(--cherry);">{{ $item['description'] }}</p>
        </div>

        @if($item['motif_refus'])
        <div class="info-item full-width">
            <span class="info-label">Motif du Refus</span>
            <p class="info-value" style="color: var(--cherry); font-weight: bold;">{{ $item['motif_refus'] }}</p>
        </div>
        @endif
    </div>
</x-card>

@if($item['statut'] === 'en_attente')
<x-card style="border-top: 5px solid var(--cherry);">
    <h3 class="font-bebas" style="font-size: 2rem; margin-top: 0; margin-bottom: 24px;">Modération</h3>
    <div style="display: flex; gap: 24px; align-items: flex-start;">
        <form action="{{ route('admin.catalogue.valider', $item['id_annonce']) }}" method="POST" style="flex: 1;">
            @csrf
            @method('PUT')
            <x-btn variant="success" type="submit" style="width: 100%; padding: 16px; font-size: 1.4rem; gap: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>
                VALIDER CETTE ANNONCE
            </x-btn>
        </form>

        <form action="{{ route('admin.catalogue.refuser', $item['id_annonce']) }}" method="POST" style="flex: 2; display: flex; flex-direction: column; gap: 12px; background: rgba(255,0,0,0.05); padding: 20px; border: 2px dashed var(--cherry);">
            @csrf
            @method('PUT')
            <label class="form-label" style="margin: 0;">Motif du refus (obligatoire)</label>
            <input type="text" name="motif_refus" class="form-input" placeholder="Pourquoi cette annonce est-elle rejetée ?" required>
            <x-btn variant="danger" type="submit" style="align-self: flex-end; gap: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/></svg>
                REFUSER
            </x-btn>
        </form>
    </div>
</x-card>
@endif
@endsection

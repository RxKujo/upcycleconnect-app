@extends('layouts.admin')
@section('title', 'Événement #' . $evenement['id_evenement'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Événement #{{ $evenement['id_evenement'] }}</h1>
    <a href="{{ route('admin.evenements.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Titre</span>
            <p class="info-value">{{ $evenement['titre'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Type</span>
            <p class="info-value"><x-badge>{{ $evenement['type_evenement'] }}</x-badge></p>
        </div>
        <div class="info-item">
            <span class="info-label">Format</span>
            <p class="info-value">{{ $evenement['format'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Lieu</span>
            <p class="info-value">{{ $evenement['lieu'] ?? '—' }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Date début</span>
            <p class="info-value">{{ \Carbon\Carbon::parse($evenement['date_debut'])->format('d/m/Y H:i') }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Date fin</span>
            <p class="info-value">{{ \Carbon\Carbon::parse($evenement['date_fin'])->format('d/m/Y H:i') }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Places</span>
            <p class="info-value">{{ $evenement['nb_places_dispo'] }} / {{ $evenement['nb_places_total'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ number_format($evenement['prix'], 2, ',', ' ') }} €</p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                @if($evenement['statut'] === 'valide')
                    <x-badge variant="valid">Validé</x-badge>
                @elseif($evenement['statut'] === 'refuse')
                    <x-badge variant="refused">Refusé</x-badge>
                @elseif($evenement['statut'] === 'annule')
                    <x-badge variant="refused">Annulé</x-badge>
                @elseif($evenement['statut'] === 'termine')
                    <x-badge variant="valid">Terminé</x-badge>
                @else
                    <x-badge>En attente</x-badge>
                @endif
            </p>
        </div>
        @if(!empty($evenement['animateurs']))
        <div class="info-item full-width">
            <span class="info-label">Animateurs</span>
            <p class="info-value">
                @foreach($evenement['animateurs'] as $a)
                    <span style="display: inline-block; margin-right: 8px;">{{ $a['prenom'] }} {{ $a['nom'] }}</span>
                @endforeach
            </p>
        </div>
        @endif
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value">{{ $evenement['description'] }}</p>
        </div>
    </div>
</div>

<div class="action-cell" style="margin-top: 24px;">
    <a href="{{ route('admin.evenements.edit', $evenement['id_evenement']) }}" class="btn-secondary">Modifier</a>
    <form action="{{ route('admin.evenements.attente', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-secondary">En attente</button>
    </form>
    <form action="{{ route('admin.evenements.valider', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-success">Valider</button>
    </form>
    <form action="{{ route('admin.evenements.refuser', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-danger">Refuser</button>
    </form>
</div>
@endsection

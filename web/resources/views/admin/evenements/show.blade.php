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
            <p class="info-value"><span class="badge badge-waiting">{{ $evenement['type_evenement'] }}</span></p>
        </div>
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value">{{ $evenement['description'] }}</p>
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
            <p class="info-value">{{ $evenement['nb_places_dispo'] }} <span style="font-size: 0.8rem; color: var(--cherry);">sur</span> {{ $evenement['nb_places_total'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ number_format($evenement['prix'], 2, ',', ' ') }} €</p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                @if($evenement['statut'] === 'valide')
                    <span class="badge badge-valid">Validé</span>
                @elseif($evenement['statut'] === 'refuse')
                    <span class="badge badge-refused">Refusé</span>
                @elseif($evenement['statut'] === 'annule')
                    <span class="badge badge-refused">Annulé</span>
                @elseif($evenement['statut'] === 'termine')
                    <span class="badge badge-valid">Terminé</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </p>
        </div>
    </div>
</div>

@if($evenement['statut'] === 'en_attente')
<div class="action-cell" style="margin-top: 24px;">
    <form action="{{ route('admin.evenements.valider', $evenement['id_evenement']) }}" method="POST">
        @csrf
        <button type="submit" class="btn-success">Valider</button>
    </form>
    <form action="{{ route('admin.evenements.refuser', $evenement['id_evenement']) }}" method="POST">
        @csrf
        <button type="submit" class="btn-danger">Refuser</button>
    </form>
</div>
@endif
@endsection

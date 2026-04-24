@extends('layouts.admin')
@section('title', 'Événement #' . $evenement['id_evenement'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Événement #{{ $evenement['id_evenement'] }}</h1>
    <div class="action-cell">
        <a href="{{ route('admin.evenements.edit', $evenement['id_evenement']) }}" class="btn-secondary btn-sm">Modifier</a>
        <form action="{{ route('admin.evenements.destroy', $evenement['id_evenement']) }}" method="POST" onsubmit="return confirm('Supprimer cet événement ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
        </form>
        <a href="{{ route('admin.evenements.index') }}" class="btn-secondary btn-sm">← Retour</a>
    </div>
</div>

@php
    $nbInscrits = $evenement['nb_inscrits'] ?? 0;
    $nbTotal = $evenement['nb_places_total'] ?? 1;
    $tauxRemplissage = $nbTotal > 0 ? round(($nbInscrits / $nbTotal) * 100) : 0;
@endphp

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px;">
    <div class="card" style="text-align: center; cursor: default; transform: none; padding: 24px;">
        <div style="font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); line-height: 1;">{{ $nbInscrits }}</div>
        <div style="font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; color: var(--cherry); margin-top: 4px;">Inscrits</div>
    </div>
    <div class="card" style="text-align: center; cursor: default; transform: none; padding: 24px;">
        <div style="font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); line-height: 1;">{{ $evenement['nb_places_dispo'] }}</div>
        <div style="font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; color: var(--cherry); margin-top: 4px;">Places restantes</div>
    </div>
    <div class="card" style="text-align: center; cursor: default; transform: none; padding: 24px;">
        <div style="font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: {{ $tauxRemplissage >= 80 ? 'var(--forest)' : 'var(--coffee)' }}; line-height: 1;">{{ $tauxRemplissage }}%</div>
        <div style="font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; color: var(--cherry); margin-top: 4px;">Taux de remplissage</div>
    </div>
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
            <span class="info-label">Places totales</span>
            <p class="info-value">{{ $evenement['nb_places_total'] }}</p>
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
        @if(!empty($evenement['animateurs']))
        <div class="info-item full-width">
            <span class="info-label">Animateurs</span>
            <p class="info-value">
                @foreach($evenement['animateurs'] as $a)
                    <span class="badge badge-waiting" style="margin-right: 8px;">{{ $a['prenom'] }} {{ $a['nom'] }}</span>
                @endforeach
            </p>
        </div>
        @endif
    </div>
</div>

<div class="action-cell" style="margin-top: 24px;">
    @if($evenement['statut'] === 'en_attente')
    <form action="{{ route('admin.evenements.valider', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-success">Valider</button>
    </form>
    <form action="{{ route('admin.evenements.refuser', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-danger">Refuser</button>
    </form>
    @elseif($evenement['statut'] === 'valide' || $evenement['statut'] === 'refuse')
    <form action="{{ route('admin.evenements.attente', $evenement['id_evenement']) }}" method="POST">
        @csrf @method('PUT')
        <button type="submit" class="btn-secondary">Remettre en attente</button>
    </form>
    @endif
</div>
@endsection

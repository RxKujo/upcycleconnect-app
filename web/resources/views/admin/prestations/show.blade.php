@extends('layouts.admin')
@section('title', 'Prestation #' . $prestation['id_prestation'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Prestation #{{ $prestation['id_prestation'] }}</h1>
    <a href="{{ route('admin.prestations.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Titre</span>
            <p class="info-value">{{ $prestation['titre'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ number_format($prestation['prix'], 2, ',', ' ') }} €</p>
        </div>
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value">{{ $prestation['description'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Catégorie ID</span>
            <p class="info-value">{{ $prestation['id_categorie'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                @if($prestation['statut'] === 'validee')
                    <span class="badge badge-valid">Validée</span>
                @elseif($prestation['statut'] === 'refusee')
                    <span class="badge badge-refused">Refusée</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </p>
        </div>
    </div>
</div>

@if($prestation['statut'] === 'en_attente')
<div class="action-cell" style="margin-top: 24px;">
    <form action="{{ route('admin.prestations.valider', $prestation['id_prestation']) }}" method="POST">
        @csrf
        <button type="submit" class="btn-success">Valider</button>
    </form>
    <form action="{{ route('admin.prestations.refuser', $prestation['id_prestation']) }}" method="POST">
        @csrf
        <button type="submit" class="btn-danger">Refuser</button>
    </form>
</div>
@endif
@endsection

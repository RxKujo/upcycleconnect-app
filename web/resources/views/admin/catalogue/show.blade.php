@extends('layouts.admin')
@section('title', 'Catalogue #' . $item['id_catalogue_item'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Catalogue #{{ $item['id_catalogue_item'] }}</h1>
    <a href="{{ route('admin.catalogue.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Titre</span>
            <p class="info-value">{{ $item['titre'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Catégorie</span>
            <p class="info-value">{{ $item['categorie'] }}</p>
        </div>
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value">{{ $item['description'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Format</span>
            <p class="info-value">{{ $item['format'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Lieu</span>
            <p class="info-value">{{ $item['lieu'] ?? '—' }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Date début</span>
            <p class="info-value">{{ \App\Helpers\DateHelper::formatFrenchWithPeriod($item['date_debut']) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Date fin</span>
            <p class="info-value">{{ \App\Helpers\DateHelper::formatFrenchWithPeriod($item['date_fin']) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Places</span>
            <p class="info-value">{{ $item['nb_places_dispo'] }} <span style="font-size: 0.8rem; color: var(--cherry);">sur</span> {{ $item['nb_places_total'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ number_format($item['prix'], 2, ',', ' ') }} €</p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                @if($item['statut'] === 'publie')
                    <span class="badge badge-valid">Publié</span>
                @elseif($item['statut'] === 'en_attente')
                    <span class="badge badge-waiting">En attente</span>
                @elseif($item['statut'] === 'annule')
                    <span class="badge badge-refused">Annulé</span>
                @else
                    <span class="badge badge-waiting">Brouillon</span>
                @endif
            </p>
        </div>
    </div>
</div>

<div class="action-cell" style="margin-top: 24px; gap: 12px;">
    <a href="{{ route('admin.catalogue.edit', $item['id_catalogue_item']) }}" class="btn-secondary">Modifier</a>
    @if($item['statut'] === 'en_attente')
        <form action="{{ route('admin.catalogue.valider', $item['id_catalogue_item']) }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn-success">Publier</button>
        </form>
    @endif
    <form action="{{ route('admin.catalogue.destroy', $item['id_catalogue_item']) }}" method="POST" style="margin: 0;">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn-danger">Annuler</button>
    </form>
</div>

<div class="card" style="margin-top: 32px;">
    <h2 class="font-bebas" style="font-size: 1.3rem; margin-bottom: 18px;">Réservations</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID Réservation</th>
                    <th>Utilisateur</th>
                    <th>Date</th>
                    <th>Statut paiement</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $reservation)
                <tr>
                    <td>{{ $reservation['id_reservation'] }}</td>
                    <td>{{ $reservation['id_utilisateur'] }}</td>
                    <td>{{ \App\Helpers\DateHelper::formatFrench($reservation['date_reservation']) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $reservation['statut_paiement'])) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 24px;">Aucune réservation.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

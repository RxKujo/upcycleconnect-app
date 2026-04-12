@extends('layouts.admin')
@section('title', 'Réservations catalogue')

@section('content')
<div class="page-header">
    <h1 class="page-title">Réservations</h1>
    <a href="{{ route('admin.catalogue.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Utilisateur</th>
                <th>Date de réservation</th>
                <th>Statut paiement</th>
                <th>Prix payé</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $reservation)
            <tr>
                <td>{{ $reservation['id_reservation'] }}</td>
                <td>{{ $reservation['id_utilisateur'] }}</td>
                <td>{{ \Carbon\Carbon::parse($reservation['date_reservation'])->format('d/m/Y H:i') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $reservation['statut_paiement'])) }}</td>
                <td>{{ $reservation['prix_paye'] ? number_format($reservation['prix_paye'], 2, ',', ' ') . ' €' : '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucune réservation.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

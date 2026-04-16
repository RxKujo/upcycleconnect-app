@extends('layouts.admin')
@section('title', 'Annonces')

@section('content')
<x-page-header title="Annonces" />

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Remise</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($annonces as $a)
        <tr>
            <td>{{ $a['id_annonce'] }}</td>
            <td style="font-weight: 600;">{{ $a['titre'] }}</td>
            <td><x-badge>{{ ucfirst($a['type_annonce']) }}</x-badge></td>
            <td>{{ ucfirst(str_replace('_', ' ', $a['mode_remise'])) }}</td>
            <td>{{ isset($a['prix']) && $a['prix'] > 0 ? number_format($a['prix'], 2, ',', ' ') . ' €' : 'Gratuit' }}</td>
            <td>
                @if($a['statut'] === 'validee')
                    <x-badge variant="valid">Validée</x-badge>
                @elseif($a['statut'] === 'refusee')
                    <x-badge variant="refused">Refusée</x-badge>
                @elseif($a['statut'] === 'annulee')
                    <x-badge variant="refused">Annulée</x-badge>
                @elseif($a['statut'] === 'vendue')
                    <x-badge variant="valid">Vendue / Donnée</x-badge>
                @else
                    <x-badge>En attente</x-badge>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.annonces.show', $a['id_annonce']) }}">Voir</x-btn>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 24px;">Aucune annonce.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

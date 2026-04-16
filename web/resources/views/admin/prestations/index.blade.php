@extends('layouts.admin')
@section('title', 'Prestations')

@section('content')
<x-page-header title="Prestations" />

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($prestations as $p)
        <tr>
            <td>{{ $p['id_prestation'] }}</td>
            <td style="font-weight: 600;">{{ $p['titre'] }}</td>
            <td>{{ number_format($p['prix'], 2, ',', ' ') }} €</td>
            <td>
                @if($p['statut'] === 'validee')
                    <x-badge variant="valid">Validée</x-badge>
                @elseif($p['statut'] === 'refusee')
                    <x-badge variant="refused">Refusée</x-badge>
                @else
                    <x-badge>En attente</x-badge>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.prestations.show', $p['id_prestation']) }}">Voir</x-btn>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5" style="text-align: center; padding: 24px;">Aucune prestation.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

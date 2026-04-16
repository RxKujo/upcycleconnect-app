@extends('layouts.admin')
@section('title', 'Événements')

@section('content')
<x-page-header title="Événements">
    <x-btn href="{{ route('admin.evenements.create') }}">Créer un Événement</x-btn>
</x-page-header>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Format</th>
            <th>Date début</th>
            <th>Places</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($evenements as $e)
        <tr>
            <td>{{ $e['id_evenement'] }}</td>
            <td style="font-weight: 600;">{{ $e['titre'] }}</td>
            <td><x-badge>{{ $e['type_evenement'] }}</x-badge></td>
            <td>{{ $e['format'] }}</td>
            <td>{{ \Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y H:i') }}</td>
            <td>{{ $e['nb_places_dispo'] }}/{{ $e['nb_places_total'] }}</td>
            <td>
                @if($e['statut'] === 'valide')
                    <x-badge variant="valid">Validé</x-badge>
                @elseif($e['statut'] === 'refuse')
                    <x-badge variant="refused">Refusé</x-badge>
                @elseif($e['statut'] === 'annule')
                    <x-badge variant="refused">Annulé</x-badge>
                @elseif($e['statut'] === 'termine')
                    <x-badge variant="valid">Terminé</x-badge>
                @else
                    <x-badge>En attente</x-badge>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.evenements.show', $e['id_evenement']) }}">Voir</x-btn>
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.evenements.edit', $e['id_evenement']) }}" style="border-color: var(--cherry); color: var(--cherry);">Modifier</x-btn>
                    <form action="{{ route('admin.evenements.destroy', $e['id_evenement']) }}" method="POST" onsubmit="return confirm('Supprimer cet événement ?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger" size="sm" type="submit">Suppr</x-btn>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align: center; padding: 24px;">Aucun événement.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

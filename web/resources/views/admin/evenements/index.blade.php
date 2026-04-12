@extends('layouts.admin')
@section('title', 'Événements')

@section('content')
<div class="page-header">
    <h1 class="page-title">Événements</h1>
</div>

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
            <td><span class="badge badge-waiting">{{ $e['type_evenement'] }}</span></td>
            <td>{{ $e['format'] }}</td>
            <td>{{ \Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y H:i') }}</td>
            <td>{{ $e['nb_places_dispo'] }}/{{ $e['nb_places_total'] }}</td>
            <td>
                @if($e['statut'] === 'valide')
                    <span class="badge badge-valid">Validé</span>
                @elseif($e['statut'] === 'refuse')
                    <span class="badge badge-refused">Refusé</span>
                @elseif($e['statut'] === 'annule')
                    <span class="badge badge-refused">Annulé</span>
                @elseif($e['statut'] === 'termine')
                    <span class="badge badge-valid">Terminé</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.evenements.show', $e['id_evenement']) }}" class="btn-secondary btn-sm">Voir</a>
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

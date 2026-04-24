@extends('layouts.admin')
@section('title', 'Événements')

@section('content')
<div class="page-header">
    <h1 class="page-title">Événements</h1>
    <a href="{{ route('admin.evenements.create') }}" class="btn-primary">+ Créer</a>
</div>

<div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <select id="filter-statut" class="form-select" style="width: auto; min-width: 160px;" onchange="filterTable()">
        <option value="">Tous les statuts</option>
        <option value="en_attente">En attente</option>
        <option value="valide">Validé</option>
        <option value="refuse">Refusé</option>
        <option value="annule">Annulé</option>
        <option value="termine">Terminé</option>
    </select>
    <select id="filter-type" class="form-select" style="width: auto; min-width: 160px;" onchange="filterTable()">
        <option value="">Tous les types</option>
        <option value="formation">Formation</option>
        <option value="atelier">Atelier</option>
        <option value="conseil">Conseil</option>
    </select>
</div>

<div class="table-container">
<table id="evenements-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Format</th>
            <th>Date début</th>
            <th>Places</th>
            <th>Inscrits</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($evenements as $e)
        <tr data-statut="{{ $e['statut'] }}" data-type="{{ $e['type_evenement'] }}">
            <td>{{ $e['id_evenement'] }}</td>
            <td style="font-weight: 600;">{{ $e['titre'] }}</td>
            <td><span class="badge badge-waiting">{{ $e['type_evenement'] }}</span></td>
            <td>{{ $e['format'] }}</td>
            <td>{{ \Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y H:i') }}</td>
            <td>{{ $e['nb_places_dispo'] }}/{{ $e['nb_places_total'] }}</td>
            <td>{{ $e['nb_inscrits'] ?? 0 }}</td>
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
                    <a href="{{ route('admin.evenements.edit', $e['id_evenement']) }}" class="btn-secondary btn-sm">Modifier</a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align: center; padding: 24px;">Aucun événement.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<script>
function filterTable() {
    const statut = document.getElementById('filter-statut').value;
    const type = document.getElementById('filter-type').value;
    document.querySelectorAll('#evenements-table tbody tr[data-statut]').forEach(row => {
        const matchStatut = !statut || row.dataset.statut === statut;
        const matchType = !type || row.dataset.type === type;
        row.style.display = matchStatut && matchType ? '' : 'none';
    });
}
</script>
@endsection

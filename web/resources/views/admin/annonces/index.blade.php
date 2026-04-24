@extends('layouts.admin')
@section('title', 'Annonces')

@section('content')
<div class="page-header">
    <h1 class="page-title">Annonces</h1>
</div>

@php
    $enAttente = collect($annonces)->where('statut', 'en_attente')->count();
@endphp

@if($enAttente > 0)
<div class="alert alert-error" style="background-color: #fff3cd; border-color: var(--coffee); color: var(--coffee);">
    <span style="font-size: 1.4rem;">⚠</span>
    {{ $enAttente }} annonce{{ $enAttente > 1 ? 's' : '' }} en attente de validation
</div>
@endif

<div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: center;">
    <input type="text" id="search-input" class="form-input" style="flex: 1; min-width: 200px; max-width: 400px;"
           placeholder="Rechercher par titre..." oninput="filterTable()">
    <select id="filter-statut" class="form-select" style="width: auto; min-width: 180px;" onchange="filterTable()">
        <option value="">Tous les statuts</option>
        <option value="en_attente" selected>En attente</option>
        <option value="validee">Validée</option>
        <option value="refusee">Refusée</option>
        <option value="annulee">Annulée</option>
    </select>
    <select id="filter-type" class="form-select" style="width: auto; min-width: 140px;" onchange="filterTable()">
        <option value="">Tous les types</option>
        <option value="don">Don</option>
        <option value="vente">Vente</option>
    </select>
    <button class="btn-secondary btn-sm" onclick="resetFilters()">Réinitialiser</button>
</div>

<div id="no-results" style="display:none; text-align:center; padding: 32px; color: rgba(18,3,9,0.5); font-family: 'DM Mono', monospace; text-transform: uppercase;">
    Aucune annonce ne correspond aux filtres.
</div>

<div class="table-container">
<table id="annonces-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Remise</th>
            <th>Prix</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($annonces as $a)
        <tr data-statut="{{ $a['statut'] }}" data-type="{{ $a['type_annonce'] }}"
            data-titre="{{ strtolower($a['titre']) }}">
            <td>{{ $a['id_annonce'] }}</td>
            <td style="font-weight: 600;">{{ $a['titre'] }}</td>
            <td><span class="badge badge-waiting">{{ ucfirst($a['type_annonce']) }}</span></td>
            <td>{{ ucfirst(str_replace('_', ' ', $a['mode_remise'])) }}</td>
            <td>{{ isset($a['prix']) && $a['prix'] > 0 ? number_format($a['prix'], 2, ',', ' ') . ' €' : 'Gratuit' }}</td>
            <td style="font-size: 0.9rem;">{{ \Carbon\Carbon::parse($a['date_creation'])->format('d/m/Y') }}</td>
            <td>
                @if($a['statut'] === 'validee')
                    <span class="badge badge-valid">Validée</span>
                @elseif($a['statut'] === 'refusee')
                    <span class="badge badge-refused">Refusée</span>
                @elseif($a['statut'] === 'annulee')
                    <span class="badge badge-refused">Annulée</span>
                @elseif($a['statut'] === 'vendue')
                    <span class="badge badge-valid">Vendue</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.annonces.show', $a['id_annonce']) }}" class="btn-secondary btn-sm">Voir</a>
                    @if($a['statut'] === 'en_attente')
                    <form action="{{ route('admin.annonces.valider', $a['id_annonce']) }}" method="POST">
                        @csrf @method('PUT')
                        <button type="submit" class="btn-success btn-sm">Valider</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr id="empty-row">
            <td colspan="8" style="text-align: center; padding: 24px;">Aucune annonce.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<script>
function filterTable() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const statut = document.getElementById('filter-statut').value;
    const type = document.getElementById('filter-type').value;
    let visible = 0;
    document.querySelectorAll('#annonces-table tbody tr[data-statut]').forEach(row => {
        const matchSearch = !search || row.dataset.titre.includes(search);
        const matchStatut = !statut || row.dataset.statut === statut;
        const matchType = !type || row.dataset.type === type;
        const show = matchSearch && matchStatut && matchType;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    document.getElementById('no-results').style.display = visible === 0 ? 'block' : 'none';
}

function resetFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('filter-statut').value = '';
    document.getElementById('filter-type').value = '';
    filterTable();
}

document.addEventListener('DOMContentLoaded', filterTable);
</script>
@endsection

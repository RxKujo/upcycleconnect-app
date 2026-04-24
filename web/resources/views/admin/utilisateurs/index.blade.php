@extends('layouts.admin')
@section('title', 'Utilisateurs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Utilisateurs</h1>
</div>

<div style="display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap;">
    <select id="filter-role" class="form-select" style="width: auto; min-width: 160px;" onchange="filterTable()">
        <option value="">Tous les rôles</option>
        <option value="particulier">Particulier</option>
        <option value="professionnel">Professionnel</option>
        <option value="salarie">Salarié</option>
        <option value="admin">Admin</option>
    </select>
    <select id="filter-statut" class="form-select" style="width: auto; min-width: 160px;" onchange="filterTable()">
        <option value="">Tous les statuts</option>
        <option value="actif">Actif</option>
        <option value="banni">Banni</option>
    </select>
</div>

<div class="table-container">
<table id="utilisateurs-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($utilisateurs as $u)
        <tr data-role="{{ $u['role'] }}" data-statut="{{ $u['est_banni'] ? 'banni' : 'actif' }}">
            <td>{{ $u['id_utilisateur'] }}</td>
            <td>{{ $u['nom'] }}</td>
            <td>{{ $u['prenom'] }}</td>
            <td>{{ $u['email'] }}</td>
            <td><span class="badge badge-waiting">{{ $u['role'] }}</span></td>
            <td>
                @if($u['est_banni'])
                    <span class="badge badge-refused">Banni</span>
                @else
                    <span class="badge badge-valid">Actif</span>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.utilisateurs.show', $u['id_utilisateur']) }}" class="btn-secondary btn-sm">Voir</a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 24px;">Aucun utilisateur trouvé.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<script>
function filterTable() {
    const role = document.getElementById('filter-role').value;
    const statut = document.getElementById('filter-statut').value;
    document.querySelectorAll('#utilisateurs-table tbody tr[data-role]').forEach(row => {
        const matchRole = !role || row.dataset.role === role;
        const matchStatut = !statut || row.dataset.statut === statut;
        row.style.display = matchRole && matchStatut ? '' : 'none';
    });
}
</script>
@endsection

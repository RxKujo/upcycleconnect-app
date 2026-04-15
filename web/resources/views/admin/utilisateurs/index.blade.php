@extends('layouts.admin')
@section('title', 'Utilisateurs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Utilisateurs</h1>
</div>

<!-- Filters -->
<div class="card" style="cursor: default; transform: none; padding: 24px; margin-bottom: 32px;">
    <form method="GET" action="{{ route('admin.utilisateurs.index') }}" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 1; min-width: 160px;">
            <label class="form-label" style="margin-bottom: 6px;">Role</label>
            <select name="role" class="form-select" style="padding: 10px 14px; font-size: 0.95rem;">
                <option value="">Tous</option>
                <option value="particulier" {{ request('role') === 'particulier' ? 'selected' : '' }}>Particulier</option>
                <option value="professionnel" {{ request('role') === 'professionnel' ? 'selected' : '' }}>Professionnel</option>
                <option value="salarie" {{ request('role') === 'salarie' ? 'selected' : '' }}>Salarie</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <label class="form-label" style="margin-bottom: 6px;">Statut</label>
            <select name="est_banni" class="form-select" style="padding: 10px 14px; font-size: 0.95rem;">
                <option value="">Tous</option>
                <option value="false" {{ request('est_banni') === 'false' ? 'selected' : '' }}>Actifs</option>
                <option value="true" {{ request('est_banni') === 'true' ? 'selected' : '' }}>Bannis</option>
            </select>
        </div>
        <div style="flex: 2; min-width: 200px;">
            <label class="form-label" style="margin-bottom: 6px;">Recherche</label>
            <input type="text" name="search" class="form-input" style="padding: 10px 14px; font-size: 0.95rem;" placeholder="Chercher par email ou nom" value="{{ request('search') }}">
        </div>
        <button type="submit" class="btn-primary btn-sm" style="height: 44px;">Filtrer</button>
    </form>
</div>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prenom</th>
            <th>Email</th>
            <th>Role</th>
            <th>Ville</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($utilisateurs as $u)
        <tr>
            <td>{{ $u['id_utilisateur'] }}</td>
            <td>{{ $u['nom'] }}</td>
            <td>{{ $u['prenom'] }}</td>
            <td>{{ $u['email'] }}</td>
            <td><span class="badge badge-waiting">{{ ucfirst($u['role']) }}</span></td>
            <td>{{ $u['ville'] ?? '---' }}</td>
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
            <td colspan="8" style="text-align: center; padding: 24px;">Aucun utilisateur trouve.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

@if(isset($pagination) && $pagination['total_pages'] > 1)
<div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px;">
    @for($p = 1; $p <= $pagination['total_pages']; $p++)
        <a href="?page={{ $p }}&role={{ request('role') }}&est_banni={{ request('est_banni') }}&search={{ request('search') }}"
           class="{{ $p == ($pagination['page'] ?? 1) ? 'btn-primary' : 'btn-secondary' }} btn-sm">{{ $p }}</a>
    @endfor
</div>
@endif
@endsection

@extends('layouts.admin')
@section('title', 'Utilisateurs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Utilisateurs</h1>
</div>

<div class="table-container">

<table>
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
        <tr>
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
@endsection

@extends('layouts.admin')
@section('title', 'Conteneurs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Conteneurs</h1>
</div>

<div class="card" style="margin-bottom: 30px;">
    <h3>Nouveau Conteneur</h3>
    <form action="{{ route('admin.conteneurs.store') }}" method="POST">
        @csrf
        <div class="info-grid">
            <div class="form-group">
                <label class="form-label">Référence / Nom</label>
                <input type="text" name="conteneur_ref" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Capacité (objets)</label>
                <input type="number" name="capacite" class="form-input" value="50" required>
            </div>
            <div class="form-group">
                <label class="form-label">Adresse</label>
                <input type="text" name="adresse" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Ville</label>
                <input type="text" name="ville" class="form-input" required>
            </div>
        </div>
        <button type="submit" class="btn-primary">Ajouter Conteneur</button>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Référence</th>
                <th>Adresse</th>
                <th>Capacité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($conteneurs as $c)
            <tr>
                <td>{{ $c['id_conteneur'] }}</td>
                <td>{{ $c['conteneur_ref'] }}</td>
                <td>{{ $c['adresse'] }}, {{ $c['ville'] }}</td>
                <td>{{ $c['capacite'] }}</td>
                <td>
                    @if($c['statut'] == 'actif')
                        <span class="badge badge-valid">Actif</span>
                    @else
                        <span class="badge badge-waiting">{{ $c['statut'] }}</span>
                    @endif
                </td>
                <td>
                    <div class="action-cell">
                        <a href="{{ route('admin.conteneurs.show', $c['id_conteneur']) }}" class="btn-secondary btn-sm">Gérer</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 24px;">Aucun conteneur trouvé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

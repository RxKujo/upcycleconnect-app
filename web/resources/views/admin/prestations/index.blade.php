@extends('layouts.admin')
@section('title', 'Prestations')

@section('content')
<div class="page-header">
    <h1 class="page-title">Prestations</h1>
</div>

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
                    <span class="badge badge-valid">Validée</span>
                @elseif($p['statut'] === 'refusee')
                    <span class="badge badge-refused">Refusée</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.prestations.show', $p['id_prestation']) }}" class="btn-secondary btn-sm">Voir</a>
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

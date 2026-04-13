@extends('layouts.admin')
@section('title', 'Annonces')

@section('content')
<div class="page-header" style="align-items: center; justify-content: space-between;">
    <h1 class="page-title">Annonces</h1>
</div>

<div class="table-container">

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Type</th>
            <th>Remise</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($annonces as $a)
        <tr>
            <td>{{ $a['id_annonce'] }}</td>
            <td style="font-weight: 600;">{{ $a['titre'] }}</td>
            <td><span class="badge badge-waiting">{{ ucfirst($a['type_annonce']) }}</span></td>
            <td>{{ ucfirst(str_replace('_', ' ', $a['mode_remise'])) }}</td>
            <td>{{ isset($a['prix']) && $a['prix'] > 0 ? number_format($a['prix'], 2, ',', ' ') . ' €' : 'Gratuit' }}</td>
            <td>
                @if($a['statut'] === 'validee')
                    <span class="badge badge-valid">Validée</span>
                @elseif($a['statut'] === 'refusee')
                    <span class="badge badge-refused">Refusée</span>
                @elseif($a['statut'] === 'annulee')
                    <span class="badge badge-refused">Annulée</span>
                @elseif($a['statut'] === 'vendue')
                    <span class="badge badge-valid">Vendue / Donnée</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.annonces.show', $a['id_annonce']) }}" class="btn-secondary btn-sm">Voir</a>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align: center; padding: 24px;">Aucune annonce.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

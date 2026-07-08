@extends('layouts.admin')

@section('title', 'Gestion des Commandes')

@section('content')

{{-- === En-tête de page === --}}
<div class="page-header">
    <h1 class="page-title">Commandes</h1>
</div>

{{-- === Tableau des commandes === --}}
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Numéro</th>
                <th>Acheteur</th>
                <th>Annonce</th>
                <th>Commission</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commandes as $cmd)
            <tr>
                <td>#{{ $cmd['id_commande'] }}</td>
                <td>{{ $cmd['acheteur_prenom'] }} {{ $cmd['acheteur_nom'] }}</td>
                <td>{{ $cmd['titre_annonce'] }}</td>
                <td style="white-space: nowrap;">{{ number_format($cmd['montant_commission'], 2) }} € <span style="font-size: 0.8em; color: rgba(18,3,9,0.5);">({{ $cmd['commission_pct'] }}%)</span></td>
                <td>
                    @if($cmd['statut'] === 'commandee')
                        <span class="badge badge-waiting">Commandée (À remettre)</span>
                    @elseif($cmd['statut'] === 'deposee' || $cmd['statut'] === 'en_conteneur')
                        <span class="badge" style="background-color: var(--teal); color: white;">En transit</span>
                    @elseif($cmd['statut'] === 'recuperee')
                        <span class="badge badge-valid">Récupérée (Terminée)</span>
                    @else
                        <span class="badge badge-refused">{{ ucfirst($cmd['statut']) }}</span>
                    @endif
                </td>
                <td style="white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($cmd['date_commande'])) }}</td>
                <td class="action-cell">
                    <a href="{{ route('admin.commandes.show', $cmd['id_commande']) }}" class="btn-secondary btn-sm">Gérer</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 32px; color: rgba(18,3,9,0.5); font-family: 'DM Mono', monospace; text-transform: uppercase;">Aucune commande pour le moment.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Gestion des Annonces')

@section('content')
<div class="page-header">
    <h2 class="page-title">Annonces (Catalogue)</h2>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Prix</th>
                <th>Remise</th>
                <th>Statut</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
            <tr>
                <td>#{{ $item['id_annonce'] }}</td>
                <td><strong>{{ $item['titre'] }}</strong></td>
                <td><span style="font-weight: bold; color: {{ $item['type_annonce'] == 'don' ? 'var(--forest)' : 'var(--cherry)' }}">{{ strtoupper($item['type_annonce']) }}</span></td>
                <td>{{ $item['type_annonce'] == 'vente' && isset($item['prix']) ? number_format($item['prix'], 2) . ' €' : '-' }}</td>
                <td>{{ $item['mode_remise'] == 'conteneur' ? 'Conteneur' : 'Main Propre' }}</td>
                <td>
                    @if($item['statut'] === 'validee')
                        <span class="badge badge-valid">Validée</span>
                    @elseif($item['statut'] === 'en_attente')
                        <span class="badge badge-waiting">En attente</span>
                    @elseif($item['statut'] === 'vendue')
                        <span class="badge" style="background-color: var(--coffee); color: white;">Vendue</span>
                    @else
                        <span class="badge badge-refused">{{ ucfirst($item['statut']) }}</span>
                    @endif
                </td>
                <td style="white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($item['date_creation'])) }}</td>
                <td class="action-cell">
                    <a href="{{ route('admin.catalogue.show', $item['id_annonce']) }}" class="btn-secondary btn-sm" style="font-family: 'DM Mono', monospace; letter-spacing: 0;">Voir</a>
                    
                    @if($item['statut'] === 'en_attente')
                    <form action="{{ route('admin.catalogue.valider', $item['id_annonce']) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn-success btn-sm">Valider</button>
                    </form>
                    @endif

                    <form action="{{ route('admin.catalogue.destroy', $item['id_annonce']) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm" style="font-family: 'DM Mono', monospace; font-size: 0.9rem;">Supprimer</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 40px; font-style: italic; color: #666;">Aucune annonce dans le catalogue.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

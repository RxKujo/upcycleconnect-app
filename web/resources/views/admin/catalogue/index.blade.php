@extends('layouts.admin')
@section('title', 'Gestion des Annonces')

@section('content')
<x-page-header title="Annonces (Catalogue)" />

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
                        <x-badge variant="valid">Validée</x-badge>
                    @elseif($item['statut'] === 'en_attente')
                        <x-badge>En attente</x-badge>
                    @elseif($item['statut'] === 'vendue')
                        <x-badge style="background-color: var(--coffee); color: white;">Vendue</x-badge>
                    @else
                        <x-badge variant="refused">{{ ucfirst($item['statut']) }}</x-badge>
                    @endif
                </td>
                <td style="white-space: nowrap;">{{ date('d/m/Y H:i', strtotime($item['date_creation'])) }}</td>
                <td class="action-cell">
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.catalogue.show', $item['id_annonce']) }}">Voir</x-btn>

                    @if($item['statut'] === 'en_attente')
                    <form action="{{ route('admin.catalogue.valider', $item['id_annonce']) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <x-btn variant="success" size="sm" type="submit">Valider</x-btn>
                    </form>
                    @endif

                    <form action="{{ route('admin.catalogue.destroy', $item['id_annonce']) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?');" style="display:inline;">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger" size="sm" type="submit">Supprimer</x-btn>
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

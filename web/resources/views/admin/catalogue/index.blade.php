@extends('layouts.admin')
@section('title', 'Catalogue')

@section('content')
<div class="page-header">
    <h1 class="page-title">Catalogue</h1>
    <a href="{{ route('admin.catalogue.create') }}" class="btn-primary btn-sm">+ Nouvel élément</a>
</div>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Catégorie</th>
            <th>Prix</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
        <tr>
            <td>{{ $item['id_catalogue_item'] }}</td>
            <td style="font-weight: 600;">{{ $item['titre'] }}</td>
            <td>{{ $item['categorie'] }}</td>
            <td>{{ number_format($item['prix'], 2, ',', ' ') }} €</td>
            <td>
                <span class="badge {{ $item['statut'] === 'publie' ? 'badge-valid' : ($item['statut'] === 'en_attente' ? 'badge-waiting' : 'badge-refused') }}">
                    @if($item['statut'] === 'publie')
                        Publié
                    @elseif($item['statut'] === 'en_attente')
                        En attente
                    @elseif($item['statut'] === 'annule')
                        Annulé
                    @else
                        Brouillon
                    @endif
                </span>
            </td>
            <td>
                <div class="action-cell">
                    <a href="{{ route('admin.catalogue.show', $item['id_catalogue_item']) }}" class="btn-secondary btn-sm">Voir</a>
                    @if($item['statut'] !== 'publie')
                        <a href="{{ route('admin.catalogue.edit', $item['id_catalogue_item']) }}" class="btn-secondary btn-sm">Modifier</a>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 24px;">Aucun élément dans le catalogue.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

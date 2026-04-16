@extends('layouts.admin')
@section('title', 'Catégories')

@section('content')
<x-page-header title="Catégories">
    <x-btn href="{{ route('admin.categories.create') }}" size="sm">+ Nouvelle</x-btn>
</x-page-header>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $cat)
        <tr>
            <td>{{ $cat['id_categorie'] }}</td>
            <td style="font-weight: 600;">{{ $cat['nom'] }}</td>
            <td>{{ $cat['description'] }}</td>
            <td>
                <div class="action-cell">
                    <x-btn variant="secondary" size="sm" href="{{ route('admin.categories.edit', $cat['id_categorie']) }}">Modifier</x-btn>
                    <form action="{{ route('admin.categories.destroy', $cat['id_categorie']) }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <x-btn variant="danger" type="submit" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</x-btn>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="4" style="text-align: center; padding: 24px;">Aucune catégorie.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>
@endsection

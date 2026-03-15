@extends('layouts.admin')
@section('title', 'Catégories')

@section('content')
<div class="page-header">
    <h1 class="page-title">Catégories</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn-primary btn-sm">+ Nouvelle</a>
</div>

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
                    <a href="{{ route('admin.categories.edit', $cat['id_categorie']) }}" class="btn-secondary btn-sm">Modifier</a>
                    <form action="{{ route('admin.categories.destroy', $cat['id_categorie']) }}" method="POST" style="margin: 0;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
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

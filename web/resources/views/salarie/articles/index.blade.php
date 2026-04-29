@extends('layouts.salarie')

@section('title', 'Articles & News')

@section('content')
<div class="page-header">
    <h1 class="page-title">Articles & News</h1>
    <a href="{{ route('salarie.articles.create') }}" class="btn-primary">+ Nouvel article</a>
</div>

@if(empty($articles))
<div class="card" style="text-align:center; padding:80px 20px;">
    <h3 class="font-bebas" style="font-size:1.6rem;">Aucun article</h3>
    <p style="opacity:0.7; margin:12px 0 24px;">Rédigez le premier article de la communauté.</p>
    <a href="{{ route('salarie.articles.create') }}" class="btn-primary">+ Rédiger un article</a>
</div>
@else
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Publication</th>
                <th>Auteur</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($articles as $a)
            <tr>
                <td><strong>{{ $a['titre'] }}</strong></td>
                <td>{{ $a['categorie'] ?? '—' }}</td>
                <td>
                    @if($a['statut'] === 'publie')
                        <span class="badge badge-valid">Publié</span>
                    @elseif($a['statut'] === 'brouillon')
                        <span class="badge badge-waiting">Brouillon</span>
                    @else
                        <span class="badge">Archivé</span>
                    @endif
                </td>
                <td class="font-mono" style="font-size:0.85rem;">
                    {{ $a['date_publication'] ? \Carbon\Carbon::parse($a['date_publication'])->format('d/m/Y') : '—' }}
                </td>
                <td class="font-mono" style="font-size:0.85rem;">#{{ $a['id_auteur'] }}</td>
                <td class="action-cell">
                    <a href="{{ route('salarie.articles.edit', $a['id_article']) }}" class="btn-secondary btn-sm">Modifier</a>
                    @if($a['id_auteur'] == session('salarie_id') || session('salarie_role') === 'admin')
                    <form action="{{ route('salarie.articles.destroy', $a['id_article']) }}" method="POST" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cet article ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm">Suppr.</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

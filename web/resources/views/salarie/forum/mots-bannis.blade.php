@extends('layouts.salarie')

@section('title', 'Mots bannis')

@section('content')
<div class="page-header">
    <h1 class="page-title">Mots bannis</h1>
    <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">{{ count($mots) }} mot(s)</span>
</div>

<div class="card" style="margin-bottom:32px;">
    <h3 class="font-bebas" style="font-size:1.4rem; margin:0 0 16px;">Ajouter un mot</h3>
    <form action="{{ route('salarie.forum.mots-bannis.add') }}" method="POST" style="display:flex; gap:12px; align-items:flex-end;">
        @csrf
        <div class="form-group" style="flex:1; margin:0;">
            <label class="form-label" for="mot">Mot ou expression</label>
            <input type="text" name="mot" id="mot" class="form-input" required maxlength="100" placeholder="ex: insulte, terme inapproprié...">
        </div>
        <button type="submit" class="btn-primary">Ajouter</button>
    </form>
</div>

@if(empty($mots))
<div class="card" style="text-align:center; padding:60px;"><p style="opacity:0.6;">Aucun mot banni pour le moment.</p></div>
@else
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Mot</th>
                <th>Ajouté le</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($mots as $m)
            <tr>
                <td><strong>{{ $m['mot'] }}</strong></td>
                <td class="font-mono" style="font-size:0.85rem;">{{ \Carbon\Carbon::parse($m['date_ajout'])->format('d/m/Y') }}</td>
                <td class="action-cell">
                    <form action="{{ route('salarie.forum.mots-bannis.delete', $m['id_mot']) }}" method="POST" style="display:inline;" onsubmit="return confirm('Retirer ce mot de la liste ?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

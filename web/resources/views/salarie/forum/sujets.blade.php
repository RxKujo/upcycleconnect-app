@extends('layouts.salarie')

@section('title', 'Sujets forum')

@section('content')
<div class="page-header">
    <h1 class="page-title">Sujets forum</h1>
    <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">{{ count($sujets) }} sujet(s)</span>
</div>

@if(empty($sujets))
<div class="card" style="text-align:center; padding:60px;"><p style="opacity:0.6;">Aucun sujet pour l'instant.</p></div>
@else
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Créateur</th>
                <th>Messages</th>
                <th>Créé</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sujets as $s)
            <tr>
                <td><a href="/forum/{{ $s['id_sujet'] }}" target="_blank" style="color:var(--teal); font-weight:600;">{{ $s['titre'] }}</a></td>
                <td>{{ $s['createur'] }}</td>
                <td>{{ $s['nb_messages'] }}</td>
                <td class="font-mono" style="font-size:0.85rem;">{{ \Carbon\Carbon::parse($s['date_creation'])->format('d/m/Y') }}</td>
                <td>
                    @if($s['statut'] === 'ouvert')
                        <span class="badge badge-valid">Ouvert</span>
                    @elseif($s['statut'] === 'ferme')
                        <span class="badge badge-refused">Verrouillé</span>
                    @else
                        <span class="badge">{{ $s['statut'] }}</span>
                    @endif
                </td>
                <td class="action-cell">
                    @if($s['statut'] === 'ouvert')
                    <form action="{{ route('salarie.forum.sujets.lock', $s['id_sujet']) }}" method="POST" style="display:inline;">
                        @csrf @method('PUT')
                        <button type="submit" class="btn-danger btn-sm">Verrouiller</button>
                    </form>
                    @elseif($s['statut'] === 'ferme')
                    <form action="{{ route('salarie.forum.sujets.unlock', $s['id_sujet']) }}" method="POST" style="display:inline;">
                        @csrf @method('PUT')
                        <button type="submit" class="btn-success btn-sm">Rouvrir</button>
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

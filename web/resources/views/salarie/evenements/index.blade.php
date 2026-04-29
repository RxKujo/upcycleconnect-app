@extends('layouts.salarie')

@section('title', 'Mes événements')

@section('content')
<div class="page-header">
    <h1 class="page-title">Mes événements</h1>
    <a href="{{ route('salarie.evenements.create') }}" class="btn-primary">+ Nouvel événement</a>
</div>

@if(empty($evenements))
<div class="card" style="text-align:center; padding:80px 20px;">
    <h3 class="font-bebas" style="font-size:1.6rem;">Aucun événement</h3>
    <p style="opacity:0.7; margin:12px 0 24px;">Créez votre premier événement (formation, atelier, conférence). Il sera soumis à la validation de l'admin.</p>
    <a href="{{ route('salarie.evenements.create') }}" class="btn-primary">+ Créer un événement</a>
</div>
@else
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Format</th>
                <th>Dates</th>
                <th>Places</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($evenements as $e)
            <tr>
                <td><strong>{{ $e['titre'] }}</strong></td>
                <td>{{ ucfirst($e['type_evenement']) }}</td>
                <td>{{ ucfirst($e['format']) }}</td>
                <td class="font-mono" style="font-size:0.85rem;">
                    {{ \Carbon\Carbon::parse($e['date_debut'])->format('d/m/Y H:i') }}
                </td>
                <td>{{ $e['nb_places_dispo'] }} / {{ $e['nb_places_total'] }}</td>
                <td>
                    @php $st = $e['statut']; @endphp
                    @if($st === 'valide')
                        <span class="badge badge-valid">Validé</span>
                    @elseif($st === 'en_attente')
                        <span class="badge badge-waiting">En attente</span>
                    @elseif($st === 'refuse')
                        <span class="badge badge-refused">Refusé</span>
                    @else
                        <span class="badge">{{ $st }}</span>
                    @endif
                </td>
                <td class="action-cell">
                    @if($e['statut'] === 'en_attente')
                    <a href="{{ route('salarie.evenements.edit', $e['id_evenement']) }}" class="btn-secondary btn-sm">Modifier</a>
                    @else
                    <span style="opacity:0.4; font-size:0.85rem;">Verrouillé</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection

@extends('layouts.admin')
@section('title', 'Gestion Conteneur')

@section('content')
<div class="page-header">
    <h1 class="page-title">Conteneur : {{ $conteneur['conteneur_ref'] }}</h1>
    <a href="{{ route('admin.conteneurs.index') }}" class="btn-secondary">Retour aux conteneurs</a>
</div>

<div class="info-grid" style="margin-bottom: 30px;">
    <div class="card">
        <span class="info-label">Détails du conteneur</span>
        <p class="info-value"><strong>Adresse :</strong> {{ $conteneur['adresse'] }}, {{ $conteneur['ville'] }}</p>
        <p class="info-value"><strong>Capacité :</strong> {{ $conteneur['capacite'] }} objets</p>
        <p class="info-value"><strong>Statut :</strong> <span class="badge badge-waiting">{{ $conteneur['statut'] }}</span></p>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Scanner Code Barre</h3>
        <p>Utilisez ce champ pour lire le code avec une douchette ou copier-coller manuellement.</p>
        <form action="{{ route('admin.conteneurs.scan', $conteneur['id_conteneur']) }}" method="POST" style="display:flex; gap:10px;">
            @csrf
            <input type="text" name="code_valeur" class="form-input" placeholder="UC-XXXXXX..." required autofocus autocomplete="off">
            <button type="submit" class="btn-primary">Valider</button>
        </form>
    </div>
</div>

<h2>Commandes Associées</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID Cmd</th>
                <th>Acheteur</th>
                <th>Statut</th>
                <th>Générer Code (Dépôt)</th>
                <th>Générer Code (Récup)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commandes as $cmd)
            <tr>
                <td>CMD-{{ $cmd['id_commande'] }}</td>
                <td>Usr #{{ $cmd['id_acheteur'] }}</td>
                <td>
                    @if(in_array($cmd['statut'], ['recuperee']))
                        <span class="badge badge-valid">{{ $cmd['statut'] }}</span>
                    @elseif(in_array($cmd['statut'], ['deposee', 'en_conteneur']))
                        <span class="badge badge-waiting">{{ $cmd['statut'] }}</span>
                    @else
                        <span class="badge" style="background:#eee;color:#333;">{{ $cmd['statut'] }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'depot_particulier']) }}" class="btn-secondary btn-sm" target="_blank">Dépôt PDF</a>
                </td>
                <td>
                    <a href="{{ route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'recuperation_pro']) }}" class="btn-secondary btn-sm" target="_blank">Récup PDF</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucune commande dans ce conteneur.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2>Tickets Incidents</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sujet</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $tck)
            <tr>
                <td>{{ substr($tck['date_creation'], 0, 10) }}</td>
                <td>{{ $tck['sujet'] }}</td>
                <td>{{ Str::limit($tck['description'], 50) }}</td>
                <td>
                    @if($tck['statut'] == 'resolu')
                        <span class="badge badge-valid">{{ $tck['statut'] }}</span>
                    @else
                        <span class="badge badge-refused">{{ $tck['statut'] }}</span>
                    @endif
                </td>
                <td>
                    @if($tck['statut'] != 'resolu')
                    <form action="{{ route('admin.conteneurs.tickets.resolve', [$conteneur['id_conteneur'], $tck['id_ticket']]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn-success btn-sm">Marquer Résolu</button>
                    </form>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucun ticket incident pour ce conteneur.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

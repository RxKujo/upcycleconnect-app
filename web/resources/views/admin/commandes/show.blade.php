@extends('layouts.admin')

@section('title', 'Gestion de la Commande')

@section('content')
<div class="page-header" style="align-items: flex-start; flex-direction: column; gap: 16px;">
    <div>
        <a href="{{ route('admin.commandes.index') }}" style="color: var(--cherry); text-decoration: none; font-family: 'DM Mono', monospace; font-size: 0.9rem; font-weight: bold;">← RETOUR AUX COMMANDES</a>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <h2 class="page-title">Commande #{{ $commande['id_commande'] }}</h2>
        <div>
            @if($commande['statut'] === 'commandee')
                <span class="badge badge-waiting" style="font-size: 1.1rem; padding: 10px 20px;">COMMANDÉE</span>
            @elseif($commande['statut'] === 'deposee' || $commande['statut'] === 'en_conteneur')
                <span class="badge" style="font-size: 1.1rem; padding: 10px 20px; background-color: var(--teal); color: white;">EN TRANSIT</span>
            @elseif($commande['statut'] === 'recuperee')
                <span class="badge badge-valid" style="font-size: 1.1rem; padding: 10px 20px;">RÉCUPÉRÉE</span>
            @else
                <span class="badge badge-refused" style="font-size: 1.1rem; padding: 10px 20px;">{{ strtoupper($commande['statut']) }}</span>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Acheteur</span>
            <p class="info-value-large">{{ $commande['acheteur_prenom'] }} {{ $commande['acheteur_nom'] }}</p>
        </div>

        <div class="info-item">
            <span class="info-label">Annonce Réservée</span>
            <p class="info-value">
                <a href="{{ route('admin.catalogue.show', $commande['id_annonce']) }}" style="color: var(--cherry); text-decoration: underline; font-weight: bold;">
                    {{ $commande['titre_annonce'] }}
                </a>
            </p>
        </div>

        <div class="info-item">
            <span class="info-label">Mode de Remise Prévu</span>
            <p class="info-value">{{ $commande['mode_remise'] == 'conteneur' ? 'Dépôt en Conteneur' : 'Remise en Main Propre' }}</p>
        </div>

        <div class="info-item">
            <span class="info-label">Commission (UpcycleConnect)</span>
            <p class="info-value">{{ number_format($commande['montant_commission'], 2) }} €</p>
        </div>

        <div class="info-item">
            <span class="info-label">Date de la commande</span>
            <p class="info-value">{{ date('d/m/Y à H:i', strtotime($commande['date_commande'])) }}</p>
        </div>

        @if(isset($commande['date_limite_recuperation']))
        <div class="info-item">
            <span class="info-label">Date Limite de Récupération</span>
            <p class="info-value" style="color: var(--cherry); font-weight: bold;">{{ date('d/m/Y', strtotime($commande['date_limite_recuperation'])) }}</p>
        </div>
        @endif

    </div>
</div>

<div class="card" style="border-top: 5px solid var(--cherry);">
    <h3 class="font-bebas" style="font-size: 2rem; margin-top: 0; margin-bottom: 24px;">Forçage Administratif du Statut</h3>
    <p style="margin-bottom: 24px; font-family: 'Outfit';">Cette section vous permet de forcer manuellement le statut de la commande en cas de litige ou de problème technique avec le conteneur / processus de remise.</p>
    
    <form action="{{ route('admin.commandes.updateStatut', $commande['id_commande']) }}" method="POST" style="display: flex; gap: 16px; align-items: flex-end;">
        @csrf
        @method('PUT')
        
        <div style="flex: 1;">
            <label class="form-label" style="margin-bottom: 8px;">Nouveau Statut</label>
            <select name="statut" class="form-select">
                <option value="commandee" {{ $commande['statut'] == 'commandee' ? 'selected' : '' }}>Commandée</option>
                <option value="deposee" {{ $commande['statut'] == 'deposee' ? 'selected' : '' }}>Déposée (Attente transporteur)</option>
                <option value="en_conteneur" {{ $commande['statut'] == 'en_conteneur' ? 'selected' : '' }}>En Conteneur d'arrivée</option>
                <option value="recuperee" {{ $commande['statut'] == 'recuperee' ? 'selected' : '' }}>Récupérée (Terminée)</option>
                <option value="annulee" {{ $commande['statut'] == 'annulee' ? 'selected' : '' }}>Annulée (Litige/Remboursement)</option>
            </select>
        </div>

        <button type="submit" class="btn-primary" style="padding: 14px 28px;">METTRE À JOUR</button>
    </form>
</div>
@endsection

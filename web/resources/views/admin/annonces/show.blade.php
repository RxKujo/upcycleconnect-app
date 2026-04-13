@extends('layouts.admin')
@section('title', 'Annonce #' . $annonce['id_annonce'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Annonce #{{ $annonce['id_annonce'] }}</h1>
    <a href="{{ route('admin.annonces.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Titre</span>
            <p class="info-value">{{ $annonce['titre'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Type</span>
            <p class="info-value"><span class="badge badge-waiting">{{ ucfirst($annonce['type_annonce']) }}</span></p>
        </div>
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value">{{ $annonce['description'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Mode de Remise</span>
            <p class="info-value">{{ ucfirst(str_replace('_', ' ', $annonce['mode_remise'])) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Date Création</span>
            <p class="info-value">{{ \Carbon\Carbon::parse($annonce['date_creation'])->format('d/m/Y H:i') }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">{{ isset($annonce['prix']) && $annonce['prix'] > 0 ? number_format($annonce['prix'], 2, ',', ' ') . ' €' : 'Gratuit' }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Statut</span>
            <p class="info-value">
                @if($annonce['statut'] === 'validee')
                    <span class="badge badge-valid">Validée</span>
                @elseif($annonce['statut'] === 'refusee')
                    <span class="badge badge-refused">Refusée</span>
                @elseif($annonce['statut'] === 'annulee')
                    <span class="badge badge-refused">Annulée</span>
                @elseif($annonce['statut'] === 'vendue')
                    <span class="badge badge-valid">Vendue / Donnée</span>
                @else
                    <span class="badge badge-waiting">En attente</span>
                @endif
            </p>
        </div>

        @if(in_array($annonce['statut'], ['refusee']) && !empty($annonce['motif_refus']))
        <div class="info-item full-width" style="margin-top: 10px;">
            <span class="info-label" style="color: var(--cherry);">Motif du refus</span>
            <p class="info-value">{{ $annonce['motif_refus'] }}</p>
        </div>
        @endif
    </div>
</div>

<div class="action-cell" style="margin-top: 24px;">
    <form action="{{ route('admin.annonces.attente', $annonce['id_annonce']) }}" method="POST">
        @csrf
        @method('PUT')
        <button type="submit" class="btn-secondary">Mettre en attente</button>
    </form>
    <form action="{{ route('admin.annonces.valider', $annonce['id_annonce']) }}" method="POST">
        @csrf
        @method('PUT')
        <button type="submit" class="btn-success">Valider</button>
    </form>
    <form action="{{ route('admin.annonces.refuser', $annonce['id_annonce']) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="motif_refus" id="motif_refus_input" value="Non conforme aux règles">
        <button type="button" class="btn-danger" onclick="refuserAnnonce()">Refuser</button>
    </form>
</div>

<script>
function refuserAnnonce() {
    let motif = prompt("Veuillez indiquer le motif du refus de cette annonce :");
    if (motif !== null && motif.trim() !== "") {
        document.getElementById('motif_refus_input').value = motif;
        document.getElementById('motif_refus_input').closest('form').submit();
    }
}
</script>
@endsection

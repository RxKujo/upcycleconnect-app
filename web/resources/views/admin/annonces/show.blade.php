@extends('layouts.admin')
@section('title', 'Annonce #' . $annonce['id_annonce'])

@section('content')
<div class="page-header">
    <h1 class="page-title">Annonce #{{ $annonce['id_annonce'] }}</h1>
    <a href="{{ route('admin.annonces.index') }}" class="btn-secondary btn-sm">← Retour</a>
</div>

<div class="card" style="cursor: default; transform: none; margin-bottom: 24px;">
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Titre</span>
            <p class="info-value">{{ $annonce['titre'] }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Type</span>
            <p class="info-value"><span class="badge badge-waiting">{{ ucfirst($annonce['type_annonce']) }}</span></p>
        </div>
        <div class="info-item">
            <span class="info-label">Mode de remise</span>
            <p class="info-value">{{ ucfirst(str_replace('_', ' ', $annonce['mode_remise'])) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Prix</span>
            <p class="info-value-large">
                {{ isset($annonce['prix']) && $annonce['prix'] > 0 ? number_format($annonce['prix'], 2, ',', ' ') . ' €' : 'Gratuit' }}
            </p>
        </div>
        <div class="info-item">
            <span class="info-label">Déposé le</span>
            <p class="info-value">{{ \Carbon\Carbon::parse($annonce['date_creation'])->format('d/m/Y à H:i') }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Particulier ID</span>
            <p class="info-value">
                <a href="{{ route('admin.utilisateurs.show', $annonce['id_particulier']) }}" style="color: var(--cherry);">
                    #{{ $annonce['id_particulier'] }}
                </a>
            </p>
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
                    <span class="badge badge-waiting">En attente de validation</span>
                @endif
            </p>
        </div>
        <div class="info-item full-width">
            <span class="info-label">Description</span>
            <p class="info-value" style="white-space: pre-line;">{{ $annonce['description'] }}</p>
        </div>
        @if(!empty($annonce['motif_refus']))
        <div class="info-item full-width">
            <span class="info-label" style="color: var(--cherry);">Motif du refus</span>
            <p class="info-value">{{ $annonce['motif_refus'] }}</p>
        </div>
        @endif
    </div>
</div>

@if(!empty($annonce['objets']))
<h2 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; margin-bottom: 16px;">
    Objets ({{ count($annonce['objets']) }})
</h2>
@foreach($annonce['objets'] as $i => $objet)
<div class="card" style="cursor: default; transform: none; margin-bottom: 20px;">
    <h3 style="font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.9rem; margin: 0 0 20px; color: var(--cherry);">
        Objet #{{ $i + 1 }}
    </h3>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Catégorie</span>
            <p class="info-value">{{ ucfirst($objet['categorie']) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Matériau</span>
            <p class="info-value">{{ ucfirst($objet['materiau']) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">État</span>
            <p class="info-value">{{ ucfirst(str_replace('_', ' ', $objet['etat'])) }}</p>
        </div>
        <div class="info-item">
            <span class="info-label">Poids</span>
            <p class="info-value">{{ isset($objet['poids_kg']) ? $objet['poids_kg'] . ' kg' : '—' }}</p>
        </div>
    </div>
    @if(!empty($objet['photos']))
    <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
        @foreach($objet['photos'] as $photo)
        <div style="width: 140px; height: 140px; border: var(--border); overflow: hidden; background: var(--wheat);">
            <img src="/uploads/{{ $photo['url'] }}" alt="Photo objet"
                 style="width: 100%; height: 100%; object-fit: cover;"
                 onerror="this.style.display='none'">
        </div>
        @endforeach
    </div>
    @endif
</div>
@endforeach
@endif

<div class="action-cell" style="margin-top: 32px; flex-wrap: wrap; gap: 12px;">
    @if($annonce['statut'] === 'en_attente')
        <form action="{{ route('admin.annonces.valider', $annonce['id_annonce']) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn-success">Valider et publier</button>
        </form>
        <button type="button" class="btn-danger" onclick="document.getElementById('refus-form').style.display='block'">
            Refuser
        </button>
        <div id="refus-form" style="display:none; width:100%; margin-top:12px;">
            <form action="{{ route('admin.annonces.refuser', $annonce['id_annonce']) }}" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Motif du refus</label>
                    <textarea name="motif_refus" class="form-textarea" style="min-height:100px;" required
                              placeholder="Expliquez la raison du refus au particulier..."></textarea>
                </div>
                <div class="action-cell">
                    <button type="submit" class="btn-danger">Confirmer le refus</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('refus-form').style.display='none'">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    @elseif(in_array($annonce['statut'], ['validee', 'refusee']))
        <form action="{{ route('admin.annonces.attente', $annonce['id_annonce']) }}" method="POST">
            @csrf @method('PUT')
            <button type="submit" class="btn-secondary">Remettre en attente</button>
        </form>
        @if($annonce['statut'] === 'validee')
        <button type="button" class="btn-danger" onclick="document.getElementById('refus-form').style.display='block'">
            Refuser
        </button>
        <div id="refus-form" style="display:none; width:100%; margin-top:12px;">
            <form action="{{ route('admin.annonces.refuser', $annonce['id_annonce']) }}" method="POST">
                @csrf @method('PUT')
                <div class="form-group">
                    <label class="form-label">Motif du refus</label>
                    <textarea name="motif_refus" class="form-textarea" style="min-height:100px;" required
                              placeholder="Expliquez la raison du refus au particulier..."></textarea>
                </div>
                <div class="action-cell">
                    <button type="submit" class="btn-danger">Confirmer le refus</button>
                    <button type="button" class="btn-secondary" onclick="document.getElementById('refus-form').style.display='none'">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
        @endif
    @endif
</div>
@endsection

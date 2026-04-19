@extends('layouts.public')

@section('title', 'Marché')
@section('meta_description', 'Parcourez les annonces de matériaux et objets recyclés sur UpcycleConnect.')

@section('content')
<div class="page-container">
    <p class="section-label">Marketplace</p>
    <h1 class="page-title">Le Marché</h1>
    <p class="page-subtitle">Parcourez les annonces de don et de vente de la communauté</p>

    <div class="filters-row" id="filters">
        <button class="filter-btn active" data-filter="all">Tout</button>
        <button class="filter-btn" data-filter="don">Dons</button>
        <button class="filter-btn" data-filter="vente">Ventes</button>
        <button class="filter-btn" data-filter="bois">Bois</button>
        <button class="filter-btn" data-filter="metal">Métal</button>
        <button class="filter-btn" data-filter="textile">Textile</button>
        <button class="filter-btn" data-filter="electronique">Électronique</button>
        <button class="filter-btn" data-filter="verre">Verre</button>
    </div>

    @if(count($annonces) > 0)
    <div class="grid-4" id="annonces-grid">
        @foreach($annonces as $annonce)
        <a href="{{ route('marche.show', $annonce['id_annonce']) }}" class="card annonce-card"
           data-type="{{ $annonce['type_annonce'] }}"
           data-materiau="{{ !empty($annonce['objets']) ? $annonce['objets'][0]['materiau'] : '' }}"
           style="padding:0; display:flex; flex-direction:column; text-decoration:none;">
            <div style="height:200px; background:var(--wheat); border-bottom:var(--border); display:flex; align-items:center; justify-content:center; overflow:hidden;">
                @if(!empty($annonce['objets']) && !empty($annonce['objets'][0]['photos']))
                <img src="/uploads/{{ $annonce['objets'][0]['photos'][0]['url'] }}" alt="{{ $annonce['titre'] }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                <span class="font-mono" style="font-size:0.75rem; opacity:0.4;">Photo</span>
                @endif
            </div>
            <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                <div style="display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap;">
                    <span class="badge {{ $annonce['type_annonce'] === 'don' ? 'badge-valid' : 'badge-cherry' }}" style="font-size:0.65rem; padding:3px 10px;">{{ $annonce['type_annonce'] === 'don' ? 'Don' : 'Vente' }}</span>
                    @if(!empty($annonce['objets']))
                    @php
                        $materiauLabels = [
                            'bois' => 'Bois', 'metal' => 'Métal', 'textile' => 'Textile',
                            'plastique' => 'Plastique', 'verre' => 'Verre',
                            'electronique' => 'Électronique', 'autre' => 'Autre',
                        ];
                    @endphp
                    <span class="badge badge-waiting" style="font-size:0.65rem; padding:3px 10px;">{{ $materiauLabels[$annonce['objets'][0]['materiau']] ?? ucfirst($annonce['objets'][0]['materiau']) }}</span>
                    @endif
                </div>
                <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.3rem; letter-spacing:0.04em; margin-bottom:8px; line-height:1.1;">{{ $annonce['titre'] }}</h3>
                <p style="font-size:0.85rem; opacity:0.6; margin-bottom:12px;">
                    {{ $annonce['vendeur']['prenom'] ?? '' }} {{ $annonce['vendeur']['nom_initiale'] ?? '' }}
                    @if($annonce['vendeur']['certifie'] ?? false)
                    <span style="color:var(--forest); font-weight:700;" title="Compte certifié">&#10003;</span>
                    @endif
                    &middot; {{ $annonce['ville'] ?? '' }}
                </p>
                <div style="margin-top:auto;">
                    @if($annonce['type_annonce'] === 'don')
                    <span class="badge badge-valid">Gratuit</span>
                    @else
                    <span style="font-family:'Bebas Neue',sans-serif; font-size:1.6rem; color:var(--cherry);">{{ number_format($annonce['prix'] ?? 0, 2) }}&euro;</span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div style="text-align:center; padding:80px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Aucune annonce</h3>
        <p style="opacity:0.6;">Les annonces apparaîtront ici une fois validées par l'équipe.</p>
    </div>
    @endif
</div>
@endsection

@section('styles')
.filters-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 36px;
}
.filter-btn {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 9px 18px;
    border: 2px solid var(--coffee);
    background: var(--cream);
    color: var(--coffee);
    cursor: pointer;
    box-shadow: 3px 3px 0 var(--coffee);
    transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease, color 0.12s ease;
    line-height: 1;
}
.filter-btn:hover {
    transform: translate(-1px, -1px);
    box-shadow: 4px 4px 0 var(--coffee);
    background: var(--wheat);
}
.filter-btn:active {
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 var(--coffee);
}
.filter-btn.active {
    background: var(--coffee);
    color: var(--cream);
    transform: translate(2px, 2px);
    box-shadow: 1px 1px 0 var(--coffee);
}
@endsection

@section('scripts')
<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;
        document.querySelectorAll('.annonce-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'flex';
            } else if (filter === 'don' || filter === 'vente') {
                card.style.display = card.dataset.type === filter ? 'flex' : 'none';
            } else {
                card.style.display = card.dataset.materiau === filter ? 'flex' : 'none';
            }
        });
    });
});
</script>
@endsection

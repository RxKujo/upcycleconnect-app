@extends('layouts.public')

@section('pub_slot')@include('partials.pub-slot')@endsection

@section('title', 'Marché')
@section('meta_description', 'Parcourez les annonces de matériaux et objets recyclés sur UpcycleConnect.')

@section('content')
<div class="page-container">
    <p class="section-label" data-i18n="market.kicker">Marketplace</p>
    <h1 class="page-title" data-i18n="market.title">Le Marché</h1>
    <p class="page-subtitle" data-i18n="market.subtitle">Parcourez les annonces de don et de vente de la communauté</p>

    <div style="margin-bottom:16px;">
        <input type="search" id="search-marche" placeholder="Rechercher une annonce…" data-i18n-ph="market.search"
               style="width:100%;max-width:480px;padding:10px 16px;border:var(--border);font-family:'DM Mono',monospace;font-size:0.9rem;background:white;outline:none;">
    </div>

    @php
        $materiauLabels = collect($materiaux)->pluck('libelle', 'code')->all();
    @endphp
    <div class="filters-row" id="filters">
        <button class="filter-btn active" data-filter="all" data-i18n="market.filter.all">Tout</button>
        <button class="filter-btn" data-filter="don" data-i18n="market.filter.don">Dons</button>
        <button class="filter-btn" data-filter="vente" data-i18n="market.filter.vente">Ventes</button>
        @foreach($materiaux as $m)
        <button class="filter-btn" data-filter="{{ $m['code'] }}">{{ $m['libelle'] }}</button>
        @endforeach
    </div>

    @if(count($annonces) > 0)
    <div class="grid-4" id="annonces-grid">
        @foreach($annonces as $annonce)
        <a href="{{ route('annonces.show', $annonce['id_annonce']) }}" class="card annonce-card"
           data-type="{{ $annonce['type_annonce'] }}"
           data-materiau="{{ !empty($annonce['objets']) ? $annonce['objets'][0]['materiau'] : '' }}"
           data-titre="{{ strtolower($annonce['titre'] ?? '') }}"
           style="padding:0; display:flex; flex-direction:column; text-decoration:none;">
            <div style="height:200px; background:repeating-linear-gradient(45deg, var(--wheat), var(--wheat) 14px, #c9b97f 14px, #c9b97f 28px); border-bottom:var(--border); display:flex; align-items:center; justify-content:center; overflow:hidden;">
                @if(!empty($annonce['objets']) && !empty($annonce['objets'][0]['photos']))
                <img src="{{ media_url($annonce['objets'][0]['photos'][0]['url']) }}" alt="{{ $annonce['titre'] }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                <span class="font-mono" data-i18n="market.nophoto" style="font-size:0.72rem; letter-spacing:0.08em; color:var(--coffee); background:var(--cream); border:2px solid var(--coffee); box-shadow:2px 2px 0 rgba(18,3,9,0.3); padding:6px 14px;">PAS DE PHOTO</span>
                @endif
            </div>
            <div style="padding:20px; flex:1; display:flex; flex-direction:column;">
                <div style="display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap;">
                    <span class="badge {{ $annonce['type_annonce'] === 'don' ? 'badge-valid' : 'badge-cherry' }}" data-i18n="{{ $annonce['type_annonce'] === 'don' ? 'status.don' : 'status.vente' }}" style="font-size:0.65rem; padding:3px 10px;">{{ $annonce['type_annonce'] === 'don' ? 'Don' : 'Vente' }}</span>
                    @if(!empty($annonce['objets']))
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
                    <span class="badge badge-valid" data-i18n="status.free">Gratuit</span>
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
        <h3 data-i18n="market.empty.title" style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Aucune annonce</h3>
        <p data-i18n="market.empty.body" style="opacity:0.6;">Les annonces apparaîtront ici une fois validées par l'équipe.</p>
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
let activeFilter = 'all';
let searchTerm = '';

function applyFilters() {
    document.querySelectorAll('.annonce-card').forEach(card => {
        const matchFilter = activeFilter === 'all'
            || (activeFilter === 'don' || activeFilter === 'vente' ? card.dataset.type === activeFilter : card.dataset.materiau === activeFilter);
        const matchSearch = !searchTerm || (card.dataset.titre || '').includes(searchTerm);
        card.style.display = (matchFilter && matchSearch) ? 'flex' : 'none';
    });
}

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        applyFilters();
    });
});

document.getElementById('search-marche').addEventListener('input', function() {
    searchTerm = this.value.trim().toLowerCase();
    applyFilters();
});
</script>
@endsection

@extends('layouts.salarie')

@section('title', 'Articles & News')

{{-- Liste des articles --}}

{{-- === Styles === --}}
@section('styles')
<style>
.toolbar { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
.filter-tabs { display: flex; gap: 4px; border-bottom: 4px solid var(--coffee); flex-wrap: wrap; flex: 1; min-width: 260px; }
.filter-tab { font-family: 'Bebas Neue', sans-serif; font-size: 1.15rem; letter-spacing: 0.05em; text-transform: uppercase; padding: 10px 20px; cursor: pointer; background: transparent; border: 3px solid transparent; border-bottom: none; color: var(--coffee); opacity: 0.5; margin-bottom: -4px; transition: opacity 0.12s; }
.filter-tab:hover { opacity: 0.85; }
.filter-tab.active { opacity: 1; background: var(--cream); border-color: var(--coffee); box-shadow: 3px -2px 0 rgba(18,3,9,0.12); }
.filter-tab .c { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.6; margin-left: 5px; }
.filter-search { border: 3px solid var(--coffee); background: white; font-family: 'DM Mono', monospace; font-size: 0.9rem; padding: 11px 14px; outline: none; box-shadow: 3px 3px 0 rgba(18,3,9,0.1); min-width: 240px; }
.filter-search:focus { border-color: var(--forest); }

.art-list { display: flex; flex-direction: column; gap: 14px; }
.art-row { display: flex; align-items: center; gap: 20px; background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 18px 22px; transition: transform 0.12s, box-shadow 0.12s; }
.art-row:hover { transform: translate(-2px,-2px); box-shadow: var(--shadow); }
.art-main { flex: 1 1 auto; min-width: 0; }
.art-head { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap; }
.art-statut { font-family: 'DM Mono', monospace; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 4px 11px; border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.25); white-space: nowrap; }
.art-cat { font-family: 'DM Mono', monospace; font-size: 0.66rem; text-transform: uppercase; background: var(--wheat); border: 2px solid var(--coffee); padding: 2px 8px; letter-spacing: 0.04em; }
.art-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; margin: 0 0 4px; line-height: 1.1; letter-spacing: 0.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.art-excerpt { font-size: 0.92rem; line-height: 1.4; color: rgba(18,3,9,0.7); margin: 0 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.art-meta { font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; opacity: 0.45; }
.art-actions { flex-shrink: 0; display: flex; gap: 8px; }
.art-act { display: inline-flex; align-items: center; gap: 6px; font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; padding: 9px 14px; cursor: pointer; background: white; color: var(--coffee); border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.15); transition: all 0.12s ease; text-decoration: none; }
.art-act:hover { background: var(--wheat); transform: translate(-1px,-1px); box-shadow: 3px 3px 0 rgba(18,3,9,0.2); }
.art-act.danger { color: var(--cherry); border-color: var(--cherry); }
.art-act.danger:hover { background: var(--cherry); color: var(--cream); }

.empty-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 60px 40px; }
.empty-box .big { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; opacity: 0.3; margin: 0; }
.empty-box .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }

@media (max-width: 720px) {
    .art-row { flex-wrap: wrap; }
    .art-actions { width: 100%; }
}
</style>
@endsection

@section('content')
{{-- Compteurs par statut + libellés de badge --}}
@php
    $total = count($articles);
    $nbPub = count(array_filter($articles, fn($a) => ($a['statut'] ?? '') === 'publie'));
    $nbBrouillon = count(array_filter($articles, fn($a) => ($a['statut'] ?? '') === 'brouillon'));
    $nbArchive = count(array_filter($articles, fn($a) => ($a['statut'] ?? '') === 'archive'));
    $statuts = [
        'publie'    => ['label' => 'Publié',    'bg' => '#244F26', 'text' => '#F5F0E1'],
        'brouillon' => ['label' => 'Brouillon', 'bg' => '#D8C99B', 'text' => '#120309'],
        'archive'   => ['label' => 'Archivé',   'bg' => '#120309', 'text' => '#F5F0E1'],
    ];
@endphp

{{-- === En-tête === --}}
<div class="page-header">
    <h1 class="page-title">Articles & News</h1>
    <a href="{{ route('salarie.articles.create') }}" class="btn-primary">+ Nouvel article</a>
</div>

{{-- === Stats === --}}
<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value">{{ $total }}</div></div>
    <div class="stat-card"><div class="stat-label">Publiés</div><div class="stat-value">{{ $nbPub }}</div></div>
    <div class="stat-card"><div class="stat-label">Brouillons</div><div class="stat-value">{{ $nbBrouillon }}</div></div>
    <div class="stat-card"><div class="stat-label">Archivés</div><div class="stat-value">{{ $nbArchive }}</div></div>
</div>

{{-- === Liste (ou état vide) === --}}
@if($total === 0)
    <div class="empty-box">
        <p class="big">Aucun article</p>
        <p class="sub">Rédigez le premier article de la communauté.</p>
        <p style="margin-top:24px;"><a href="{{ route('salarie.articles.create') }}" class="btn-primary">+ Rédiger un article</a></p>
    </div>
@else
    <div class="toolbar">
        <div class="filter-tabs">
            <button type="button" class="filter-tab active" data-filter="all">Tous<span class="c">({{ $total }})</span></button>
            <button type="button" class="filter-tab" data-filter="publie">Publiés<span class="c">({{ $nbPub }})</span></button>
            <button type="button" class="filter-tab" data-filter="brouillon">Brouillons<span class="c">({{ $nbBrouillon }})</span></button>
            <button type="button" class="filter-tab" data-filter="archive">Archivés<span class="c">({{ $nbArchive }})</span></button>
        </div>
        <input type="text" class="filter-search" id="art-search" placeholder="Rechercher un article…">
    </div>

    {{-- data-statut / data-search : filtrage JS --}}
    <div class="art-list" id="art-list">
        @foreach($articles as $a)
        @php
            $stKey = $a['statut'] ?? 'brouillon';
            $st = $statuts[$stKey] ?? $statuts['brouillon'];
            $canManage = ($a['id_auteur'] ?? 0) == session('salarie_id') || session('salarie_role') === 'admin';
            $search = strtolower(($a['titre'] ?? '') . ' ' . ($a['categorie'] ?? ''));
        @endphp
        <div class="art-row" data-statut="{{ $stKey }}" data-search="{{ $search }}" style="border-left:8px solid {{ $st['bg'] }};">
            <div class="art-main">
                <div class="art-head">
                    <span class="art-statut" style="background:{{ $st['bg'] }};color:{{ $st['text'] }};">{{ $st['label'] }}</span>
                    @if(!empty($a['categorie']))<span class="art-cat">{{ config('articles.categories')[$a['categorie']] ?? $a['categorie'] }}</span>@endif
                </div>
                <h3 class="art-title">{{ $a['titre'] }}</h3>
                <p class="art-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($a['contenu'] ?? ''), 140) }}</p>
                <div class="art-meta">
                    Par {{ $a['auteur_prenom'] ?? '' }} {{ $a['auteur_nom_initiale'] ?? '' }}
                    @if(!empty($a['date_publication'])) · Publié le {{ \Carbon\Carbon::parse($a['date_publication'])->format('d/m/Y') }} @endif
                </div>
            </div>
            <div class="art-actions">
                <a href="{{ route('salarie.articles.edit', $a['id_article']) }}" class="art-act">Modifier</a>
                @if($canManage)
                <form action="{{ route('salarie.articles.destroy', $a['id_article']) }}" method="POST" style="margin:0;" data-confirm="Supprimer définitivement cet article ?">
                    @csrf @method('DELETE')
                    <button type="submit" class="art-act danger">Supprimer</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="empty-box" id="art-empty" style="display:none;">
        <p class="big">Aucun résultat</p>
        <p class="sub">Aucun article ne correspond à ce filtre.</p>
    </div>
@endif
@endsection

{{-- === Scripts : filtrage === --}}
@section('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.filter-tab');
    const search = document.getElementById('art-search');
    const list = document.getElementById('art-list');
    if (!list) return;
    const rows = Array.from(list.querySelectorAll('.art-row'));
    const empty = document.getElementById('art-empty');
    let currentFilter = 'all';

    function apply() {
        const q = (search.value || '').toLowerCase().trim();
        let visible = 0;
        rows.forEach(row => {
            const okStatut = currentFilter === 'all' || row.dataset.statut === currentFilter;
            const okSearch = !q || (row.dataset.search || '').includes(q);
            const show = okStatut && okSearch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (empty) empty.style.display = visible === 0 ? '' : 'none';
        list.style.display = visible === 0 ? 'none' : 'flex';
    }

    tabs.forEach(t => t.addEventListener('click', () => {
        tabs.forEach(x => x.classList.toggle('active', x === t));
        currentFilter = t.dataset.filter;
        apply();
    }));
    search.addEventListener('input', apply);
})();
</script>
@endsection

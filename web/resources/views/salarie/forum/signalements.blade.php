@extends('layouts.salarie')

@section('title', 'Signalements')
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

.sig-list { display: flex; flex-direction: column; gap: 16px; }
.sig-card { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 22px 24px; border-left: 8px solid var(--cherry); }
.sig-card.is-traite { border-left-color: var(--forest); }
.sig-card.is-rejete { border-left-color: rgba(18,3,9,0.35); }
.sig-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 14px; flex-wrap: wrap; }
.sig-sujet { font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; opacity: 0.55; margin: 0 0 6px; }
.sig-sujet a { color: var(--teal); text-decoration: none; font-weight: 700; }
.sig-auteur { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; letter-spacing: 0.03em; margin: 0; }
.sig-date { font-family: 'DM Mono', monospace; font-size: 0.68rem; opacity: 0.5; margin: 4px 0 0; }
.sig-badges { display: flex; gap: 6px; flex-shrink: 0; flex-wrap: wrap; }
.sig-quote { border-left: 4px solid var(--cherry); background: #faf2f3; padding: 14px 16px; margin: 0 0 12px; font-size: 0.95rem; line-height: 1.5; white-space: pre-line; }
.sig-motif { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.65; margin: 0 0 14px; }
.sig-motif b { color: var(--cherry); }
.sig-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.empty-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 60px 40px; }
.empty-box .big { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; opacity: 0.3; margin: 0; }
.empty-box .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }
</style>
@endsection

@section('content')
{{-- Compteurs --}}
@php
    $total = count($items);
    $nbEnCours = count(array_filter($items, fn($s) => ($s['statut'] ?? '') === 'en_cours'));
    $nbTraite  = count(array_filter($items, fn($s) => ($s['statut'] ?? '') === 'traite'));
    $nbMasque  = count(array_filter($items, fn($s) => !empty($s['est_masque'])));
@endphp

<div class="page-header">
    <h1 class="page-title"><span data-i18n="sal.reports">Signalements</span></h1>
</div>

{{-- === Stats === --}}
<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">À traiter</div><div class="stat-value" style="color:var(--cherry);">{{ $nbEnCours }}</div></div>
    <div class="stat-card"><div class="stat-label">Traités</div><div class="stat-value">{{ $nbTraite }}</div></div>
    <div class="stat-card"><div class="stat-label">Messages masqués</div><div class="stat-value">{{ $nbMasque }}</div></div>
    <div class="stat-card"><div class="stat-label">Total</div><div class="stat-value">{{ $total }}</div></div>
</div>

{{-- === Liste (ou état vide) === --}}
@if($total === 0)
    <div class="empty-box">
        <p class="big">Aucun signalement</p>
        <p class="sub">La communauté est calme.</p>
    </div>
@else
    <div class="toolbar">
        <div class="filter-tabs">
            <button type="button" class="filter-tab {{ $nbEnCours > 0 ? 'active' : '' }}" data-filter="en_cours">À traiter<span class="c">({{ $nbEnCours }})</span></button>
            <button type="button" class="filter-tab {{ $nbEnCours === 0 ? 'active' : '' }}" data-filter="all">Tous<span class="c">({{ $total }})</span></button>
            <button type="button" class="filter-tab" data-filter="traite">Traités<span class="c">({{ $nbTraite }})</span></button>
            <button type="button" class="filter-tab" data-filter="masque">Masqués<span class="c">({{ $nbMasque }})</span></button>
        </div>
        <input type="text" class="filter-search" id="sig-search" placeholder="Rechercher (auteur, contenu…)">
    </div>

    {{-- data-statut / data-masque / data-search : filtrage JS --}}
    <div class="sig-list" id="sig-list">
        @foreach($items as $s)
        @php
            $stKey = $s['statut'] ?? 'en_cours';
            $masque = !empty($s['est_masque']);
            $cls = $stKey === 'traite' ? 'is-traite' : ($stKey === 'rejete' ? 'is-rejete' : '');
            $search = strtolower(($s['auteur_message'] ?? '') . ' ' . ($s['contenu'] ?? '') . ' ' . ($s['titre_sujet'] ?? '') . ' ' . ($s['motif'] ?? ''));
        @endphp
        <div class="sig-card {{ $cls }}" data-statut="{{ $stKey }}" data-masque="{{ $masque ? '1' : '0' }}" data-search="{{ $search }}">
            <div class="sig-top">
                <div>
                    <p class="sig-sujet">Sujet : <a href="/forum/{{ $s['id_sujet'] }}" target="_blank">{{ $s['titre_sujet'] }}</a></p>
                    <p class="sig-auteur">{{ $s['auteur_message'] }}</p>
                    <p class="sig-date">Signalé le {{ \Carbon\Carbon::parse($s['date_signalement'])->format('d/m/Y à H:i') }}</p>
                </div>
                <div class="sig-badges">
                    @if($stKey === 'en_cours')
                        <span class="badge badge-waiting">En cours</span>
                    @elseif($stKey === 'traite')
                        <span class="badge badge-valid">Traité</span>
                    @else
                        <span class="badge">Rejeté</span>
                    @endif
                    @if($masque)<span class="badge badge-refused">Masqué</span>@endif
                </div>
            </div>

            <div class="sig-quote">{{ $s['contenu'] }}</div>

            @if(!empty($s['motif']))
                <p class="sig-motif"><b>Motif déclaré :</b> {{ $s['motif'] }}</p>
            @endif

            <div class="sig-actions">
                @if(!$masque)
                <form action="{{ route('salarie.forum.masquer', $s['id_message']) }}" method="POST" style="margin:0;" data-confirm="Masquer ce message du forum ?">
                    @csrf @method('PUT')
                    <button type="submit" class="btn-danger btn-sm">Masquer le message</button>
                </form>
                @else
                <form action="{{ route('salarie.forum.restaurer', $s['id_message']) }}" method="POST" style="margin:0;">
                    @csrf @method('PUT')
                    <button type="submit" class="btn-success btn-sm">Restaurer le message</button>
                </form>
                @endif
                <a href="/forum/{{ $s['id_sujet'] }}" target="_blank" class="btn-secondary btn-sm">Voir le sujet</a>
            </div>
        </div>
        @endforeach
    </div>

    <div class="empty-box" id="sig-empty" style="display:none;">
        <p class="big">Rien ici</p>
        <p class="sub">Aucun signalement ne correspond à ce filtre.</p>
    </div>
@endif
@endsection

{{-- === Scripts : filtrage === --}}
@section('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.filter-tab');
    const search = document.getElementById('sig-search');
    const list = document.getElementById('sig-list');
    if (!list) return;
    const cards = Array.from(list.querySelectorAll('.sig-card'));
    const empty = document.getElementById('sig-empty');
    let currentFilter = document.querySelector('.filter-tab.active')?.dataset.filter || 'all';

    function apply() {
        const q = (search.value || '').toLowerCase().trim();
        let visible = 0;
        cards.forEach(card => {
            let okFilter = true;
            if (currentFilter === 'masque') okFilter = card.dataset.masque === '1';
            else if (currentFilter !== 'all') okFilter = card.dataset.statut === currentFilter;
            const okSearch = !q || (card.dataset.search || '').includes(q);
            const show = okFilter && okSearch;
            card.style.display = show ? '' : 'none';
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
    apply();
})();
</script>
@endsection

@extends('layouts.salarie')

@section('title', 'Sujets forum')

{{-- Modération : sujets forum --}}

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
.msg-pill { display: inline-flex; align-items: center; gap: 5px; font-family: 'DM Mono', monospace; font-weight: 700; font-size: 0.85rem; background: var(--wheat); border: 2px solid var(--coffee); padding: 2px 10px; }
.sujet-titre { color: var(--teal); font-weight: 700; text-decoration: none; }
.sujet-titre:hover { text-decoration: underline; }
.empty-row td { text-align: center; padding: 40px; font-family: 'DM Mono', monospace; text-transform: uppercase; opacity: 0.4; font-size: 0.85rem; }
.empty-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 60px 40px; }
.empty-box .big { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; opacity: 0.3; margin: 0; }
.empty-box .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }
</style>
@endsection

@section('content')
{{-- Compteurs --}}
@php
    $total = count($sujets);
    $nbOuvert = count(array_filter($sujets, fn($s) => ($s['statut'] ?? '') === 'ouvert'));
    $nbFerme  = count(array_filter($sujets, fn($s) => ($s['statut'] ?? '') === 'ferme'));
    $totalMsg = array_sum(array_map(fn($s) => (int)($s['nb_messages'] ?? 0), $sujets));
@endphp

<div class="page-header">
    <h1 class="page-title">Sujets forum</h1>
</div>

{{-- === Stats === --}}
<div class="stats-grid">
    <div class="stat-card"><div class="stat-label">Sujets</div><div class="stat-value">{{ $total }}</div></div>
    <div class="stat-card"><div class="stat-label">Ouverts</div><div class="stat-value">{{ $nbOuvert }}</div></div>
    <div class="stat-card"><div class="stat-label">Verrouillés</div><div class="stat-value" style="color:var(--cherry);">{{ $nbFerme }}</div></div>
    <div class="stat-card"><div class="stat-label">Messages cumulés</div><div class="stat-value">{{ $totalMsg }}</div></div>
</div>

{{-- === Tableau (ou état vide) === --}}
@if($total === 0)
    <div class="empty-box">
        <p class="big">Aucun sujet</p>
        <p class="sub">Le forum n'a pas encore de discussion.</p>
    </div>
@else
    <div class="toolbar">
        <div class="filter-tabs">
            <button type="button" class="filter-tab active" data-filter="all">Tous<span class="c">({{ $total }})</span></button>
            <button type="button" class="filter-tab" data-filter="ouvert">Ouverts<span class="c">({{ $nbOuvert }})</span></button>
            <button type="button" class="filter-tab" data-filter="ferme">Verrouillés<span class="c">({{ $nbFerme }})</span></button>
        </div>
        <input type="text" class="filter-search" id="suj-search" placeholder="Rechercher un sujet…">
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Créateur</th>
                    <th>Messages</th>
                    <th>Créé le</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="suj-body">
                @foreach($sujets as $s)
                @php
                    $stKey = $s['statut'] ?? 'ouvert';
                    $search = strtolower(($s['titre'] ?? '') . ' ' . ($s['createur'] ?? ''));
                @endphp
                <tr data-statut="{{ $stKey }}" data-search="{{ $search }}">
                    <td><a href="/forum/{{ $s['id_sujet'] }}" target="_blank" class="sujet-titre">{{ $s['titre'] }}</a></td>
                    <td>{{ $s['createur'] }}</td>
                    <td><span class="msg-pill">{{ $s['nb_messages'] }} msg</span></td>
                    <td class="font-mono" style="font-size:0.85rem;">{{ \Carbon\Carbon::parse($s['date_creation'])->format('d/m/Y') }}</td>
                    <td>
                        @if($stKey === 'ouvert')
                            <span class="badge badge-valid">Ouvert</span>
                        @elseif($stKey === 'ferme')
                            <span class="badge badge-refused">Verrouillé</span>
                        @else
                            <span class="badge">{{ $stKey }}</span>
                        @endif
                    </td>
                    <td class="action-cell">
                        @if($stKey === 'ouvert')
                        <form action="{{ route('salarie.forum.sujets.lock', $s['id_sujet']) }}" method="POST" style="display:inline;" data-confirm="Verrouiller ce sujet ? Plus aucune réponse ne sera possible.">
                            @csrf @method('PUT')
                            <button type="submit" class="btn-danger btn-sm">Verrouiller</button>
                        </form>
                        @elseif($stKey === 'ferme')
                        <form action="{{ route('salarie.forum.sujets.unlock', $s['id_sujet']) }}" method="POST" style="display:inline;">
                            @csrf @method('PUT')
                            <button type="submit" class="btn-success btn-sm">Rouvrir</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
                <tr class="empty-row" id="suj-empty" style="display:none;"><td colspan="6">Aucun sujet ne correspond à ce filtre.</td></tr>
            </tbody>
        </table>
    </div>
@endif
@endsection

{{-- === Scripts : filtrage === --}}
@section('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.filter-tab');
    const search = document.getElementById('suj-search');
    const body = document.getElementById('suj-body');
    if (!body) return;
    const rows = Array.from(body.querySelectorAll('tr[data-statut]'));
    const empty = document.getElementById('suj-empty');
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

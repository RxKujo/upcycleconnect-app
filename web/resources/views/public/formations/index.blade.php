@extends('layouts.public')

@section('title', 'Formations & Ateliers')
@section('meta_description', 'Ateliers, formations, conférences et conseils proposés par les professionnels UpcycleConnect. Réservez votre place.')

@section('content')
<div class="page-container">
    <p class="section-label">Catalogue Pro</p>
    <h1 class="page-title">Formations &amp; Ateliers</h1>
    <p class="page-subtitle">Ateliers, formations, conférences et conseils proposés par les professionnels et l'équipe</p>

    <div class="filters-row" id="filters">
        <button class="filter-btn active" data-filter="all">Tout</button>
        <button class="filter-btn" data-filter="atelier">Ateliers</button>
        <button class="filter-btn" data-filter="formation">Formations</button>
        <button class="filter-btn" data-filter="evenement">Événements</button>
        <button class="filter-btn" data-filter="conseil">Conseils</button>
        <button class="filter-btn" data-filter="presentiel">Présentiel</button>
        <button class="filter-btn" data-filter="distanciel">Distanciel</button>
    </div>

    @php
        $categorieLabels = [
            'formation' => 'Formation', 'atelier' => 'Atelier',
            'evenement' => 'Événement', 'conseil' => 'Conseil',
        ];
        $formatLabels = ['presentiel' => 'Présentiel', 'distanciel' => 'Distanciel'];
    @endphp

    @if(count($items) > 0)
    <div class="grid-3" id="formations-grid">
        @foreach($items as $item)
        @php $date = \Carbon\Carbon::parse($item['date_debut']); @endphp
        <a href="{{ route('formations.show', $item['id_catalogue_item']) }}" class="card formation-card"
           data-categorie="{{ $item['categorie'] }}"
           data-format="{{ $item['format'] }}"
           style="padding:0; display:flex; flex-direction:column; text-decoration:none;">
            <div style="padding:20px 24px; border-bottom:var(--border); background:var(--wheat); display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div style="text-align:center; line-height:1; flex-shrink:0;">
                    <span style="display:block; font-family:'Bebas Neue',sans-serif; font-size:2.2rem;">{{ $date->format('d') }}</span>
                    <span class="font-mono" style="font-size:0.7rem; text-transform:uppercase; opacity:0.7;">{{ strtoupper($date->locale('fr')->isoFormat('MMM')) }}</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px; align-items:flex-end;">
                    <span class="badge badge-cherry" style="font-size:0.65rem; padding:3px 10px;">{{ $categorieLabels[$item['categorie']] ?? ucfirst($item['categorie']) }}</span>
                    <span class="badge badge-waiting" style="font-size:0.65rem; padding:3px 10px;">{{ $formatLabels[$item['format']] ?? ucfirst($item['format']) }}</span>
                </div>
            </div>
            <div style="padding:20px 24px; flex:1; display:flex; flex-direction:column;">
                <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.3rem; letter-spacing:0.04em; margin-bottom:8px; line-height:1.1;">{{ $item['titre'] }}</h3>
                <p style="font-size:0.85rem; opacity:0.65; margin-bottom:14px;">{{ \Illuminate\Support\Str::limit($item['description'] ?? '', 110) }}</p>
                <div class="font-mono" style="font-size:0.72rem; opacity:0.6; margin-bottom:6px;">
                    {{ $date->locale('fr')->isoFormat('ddd D MMM Y') }} &middot; {{ $date->format('H\hi') }}
                </div>
                <div class="font-mono" style="font-size:0.72rem; opacity:0.6; margin-bottom:14px;">
                    {{ $item['lieu'] ?? 'En ligne' }}
                </div>
                <div style="margin-top:auto; display:flex; justify-content:space-between; align-items:center;">
                    @if(($item['prix'] ?? 0) > 0)
                    <span style="font-family:'Bebas Neue',sans-serif; font-size:1.6rem; color:var(--cherry);">{{ number_format($item['prix'], 2) }}&euro;</span>
                    @else
                    <span class="badge badge-valid">Gratuit</span>
                    @endif
                    <span class="font-mono" style="font-size:0.7rem; opacity:0.6;">
                        {{ $item['nb_places_dispo'] }} place{{ $item['nb_places_dispo'] > 1 ? 's' : '' }}
                    </span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div style="text-align:center; padding:80px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Aucune formation</h3>
        <p style="opacity:0.6;">Le catalogue de formations et d'ateliers apparaîtra ici une fois publié.</p>
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
        const formats = ['presentiel', 'distanciel'];
        document.querySelectorAll('.formation-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'flex';
            } else if (formats.includes(filter)) {
                card.style.display = card.dataset.format === filter ? 'flex' : 'none';
            } else {
                card.style.display = card.dataset.categorie === filter ? 'flex' : 'none';
            }
        });
    });
});
</script>
@endsection

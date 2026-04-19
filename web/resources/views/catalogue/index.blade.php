@extends('layouts.public')

@section('title', 'Événements & formations')
@section('meta_description', 'Ateliers, formations et conférences UpcycleConnect. Consultez le catalogue et inscrivez-vous.')

@section('content')
<div class="page-container">
    <p class="section-label">Agenda</p>
    <h1 class="page-title">Événements &amp; formations</h1>
    <p class="page-subtitle">Ateliers, formations et conférences organisés par la communauté</p>

    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:36px;" id="filters">
        <button class="filter-btn active" data-filter="all">Tout</button>
        <button class="filter-btn" data-filter="atelier">Ateliers</button>
        <button class="filter-btn" data-filter="formation">Formations</button>
        <button class="filter-btn" data-filter="conference">Conférences</button>
        <button class="filter-btn" data-filter="presentiel">Présentiel</button>
        <button class="filter-btn" data-filter="distanciel">Distanciel</button>
    </div>

    @if(count($evenements) > 0)
    <div class="grid-3" id="events-grid">
        @php
            $typeLabels = [
                'atelier' => 'Atelier',
                'formation' => 'Formation',
                'conference' => 'Conférence',
            ];
            $formatLabels = [
                'presentiel' => 'Présentiel',
                'distanciel' => 'Distanciel',
            ];
        @endphp

        @foreach($evenements as $e)
        @php $date = \Carbon\Carbon::parse($e['date_debut']); @endphp
        <a href="{{ route('evenements.show', $e['id_evenement']) }}" class="event-card"
           data-type="{{ $e['type_evenement'] }}"
           data-format="{{ $e['format'] }}">
            <div class="event-card-header">
                <div class="event-card-date">
                    <span class="event-card-day">{{ $date->format('d') }}</span>
                    <span class="event-card-month">{{ strtoupper($date->locale('fr')->isoFormat('MMM')) }}</span>
                </div>
                <div class="event-card-tags">
                    <span class="event-card-tag event-card-tag-{{ $e['type_evenement'] }}">{{ $typeLabels[$e['type_evenement']] ?? '' }}</span>
                    <span class="event-card-format">{{ $formatLabels[$e['format']] ?? '' }}</span>
                </div>
            </div>

            <h3 class="event-card-title">{{ $e['titre'] }}</h3>
            <p class="event-card-desc">{{ \Illuminate\Support\Str::limit($e['description'] ?? '', 130) }}</p>

            <div class="event-card-meta">
                <p class="event-card-meta-line"><span>Date</span> {{ $date->locale('fr')->isoFormat('dddd D MMMM Y') }}</p>
                <p class="event-card-meta-line"><span>Horaire</span> {{ $date->format('H\hi') }} — {{ \Carbon\Carbon::parse($e['date_fin'])->format('H\hi') }}</p>
                <p class="event-card-meta-line"><span>Lieu</span> {{ $e['lieu'] ?? 'En ligne' }}</p>
            </div>

            <div class="event-card-footer">
                @if(($e['prix'] ?? 0) > 0)
                <span class="event-card-price">{{ number_format($e['prix'], 2) }}&euro;</span>
                @else
                <span class="event-card-free">Gratuit</span>
                @endif
                <span class="event-card-places {{ ($e['nb_places_dispo'] ?? 0) <= 3 ? 'low' : '' }}">
                    {{ $e['nb_places_dispo'] ?? 0 }} / {{ $e['nb_places_total'] ?? 0 }} places
                </span>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div class="empty-state">
        <h3>Aucun événement à venir</h3>
        <p>Les prochains ateliers et formations apparaîtront ici.</p>
    </div>
    @endif
</div>
@endsection

@section('styles')
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

.event-card {
    display: flex;
    flex-direction: column;
    background: var(--cream);
    border: var(--border);
    box-shadow: 4px 4px 0 var(--coffee);
    padding: 28px;
    transition: transform 0.15s, box-shadow 0.15s;
}
.event-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 7px 7px 0 var(--coffee);
}
.event-card-header {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    align-items: flex-start;
}
.event-card-date {
    flex-shrink: 0;
    width: 64px;
    padding: 8px 4px;
    text-align: center;
    background: var(--cherry);
    color: var(--cream);
    border: 2px solid var(--coffee);
}
.event-card-day {
    display: block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.7rem;
    letter-spacing: 0.02em;
    line-height: 1;
}
.event-card-month {
    display: block;
    font-family: 'DM Mono', monospace;
    font-size: 0.65rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-top: 2px;
}
.event-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
    flex: 1;
}
.event-card-tag {
    font-family: 'DM Mono', monospace;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 10px;
    border: 2px solid var(--coffee);
}
.event-card-tag-atelier { background: var(--teal); color: var(--cream); }
.event-card-tag-formation { background: var(--forest); color: var(--cream); }
.event-card-tag-conference { background: var(--wheat); color: var(--coffee); }
.event-card-format {
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    color: var(--coffee);
    opacity: 0.6;
    letter-spacing: 0.04em;
}
.event-card-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    letter-spacing: 0.03em;
    line-height: 1.15;
    margin-bottom: 12px;
    color: var(--coffee);
}
.event-card-desc {
    font-size: 0.9rem;
    color: var(--coffee);
    opacity: 0.7;
    line-height: 1.6;
    margin-bottom: 20px;
}
.event-card-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 20px;
    padding: 14px 16px;
    background: var(--wheat);
    border: 2px solid var(--coffee);
}
.event-card-meta-line {
    font-size: 0.82rem;
    color: var(--coffee);
}
.event-card-meta-line span {
    display: inline-block;
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--coffee);
    opacity: 0.6;
    min-width: 60px;
}
.event-card-footer {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 2px solid rgba(18,3,9,0.1);
}
.event-card-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    color: var(--cherry);
    letter-spacing: 0.03em;
}
.event-card-free {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--forest);
    padding: 5px 12px;
    background: rgba(36, 79, 38, 0.1);
    border: 2px solid var(--forest);
}
.event-card-places {
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    color: var(--coffee);
    opacity: 0.6;
    letter-spacing: 0.04em;
}
.event-card-places.low { color: var(--cherry); opacity: 1; font-weight: 700; }

.empty-state {
    text-align: center;
    padding: 80px 20px;
    border: var(--border);
    background: var(--cream);
    box-shadow: 4px 4px 0 var(--coffee);
}
.empty-state h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2rem;
    margin-bottom: 12px;
}
.empty-state p { opacity: 0.6; }
@endsection

@section('scripts')
<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;
        document.querySelectorAll('.event-card').forEach(card => {
            if (filter === 'all') {
                card.style.display = 'flex';
            } else {
                const matchesType = card.dataset.type === filter;
                const matchesFormat = card.dataset.format === filter;
                card.style.display = (matchesType || matchesFormat) ? 'flex' : 'none';
            }
        });
    });
});
</script>
@endsection

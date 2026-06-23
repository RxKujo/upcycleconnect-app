@extends('layouts.admin')

@section('title', 'Rotation WRR publicités')

@section('content')
<div class="page-header">
    <h1 class="page-title">Rotation WRR — Publicités</h1>
    <div style="display:flex;gap:12px;">
        <a href="{{ route('admin.publicites.stats') }}" class="btn-secondary">← Statistiques</a>
        <a href="{{ route('admin.publicites.index') }}" class="btn-secondary">Modération</a>
    </div>
</div>

{{-- Explication algorithme --}}
<div class="card" style="background:var(--coffee);color:var(--cream);margin-bottom:32px;">
    <h2 class="font-bebas" style="font-size:1.6rem;margin:0 0 12px;color:var(--wheat);">Algorithme Weighted Round-Robin</h2>
    <p style="font-family:'DM Mono',monospace;font-size:0.85rem;line-height:1.6;opacity:0.85;margin:0;">
        {{ $data['description'] ?? 'Score WRR : plus le score est élevé, plus la pub sera sélectionnée au prochain appel WRR.' }}
        La pub avec le score le plus élevé est sélectionnée en priorité lors de chaque affichage.
        Son score est ensuite décrémenté de son poids × 1, garantissant une rotation équitable selon le poids configuré.
    </p>
</div>

@php $pubs = $data['pubs_actives'] ?? []; @endphp

@if(empty($pubs))
    <div class="card" style="text-align:center;padding:60px 40px;">
        <p style="font-family:'Bebas Neue',sans-serif;font-size:2rem;opacity:0.3;margin:0;">Aucune publicité active</p>
    </div>
@else
    @php $maxScore = max(array_column($pubs, 'score_rotation') ?: [1]); @endphp
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 8px;">
            État actuel — {{ count($pubs) }} pub(s) active(s)
        </h2>
        <p style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.4;margin:0 0 24px;">
            La prochaine pub sélectionnée est celle avec le score le plus élevé
        </p>

        <div style="display:flex;flex-direction:column;gap:16px;">
            @foreach($pubs as $i => $pub)
            @php
                $score   = $pub['score_rotation'] ?? 0;
                $poids   = $pub['poids_affichage'] ?? 1;
                $pct     = $maxScore > 0 ? ($score / $maxScore * 100) : 0;
                $isNext  = $i === 0;
            @endphp
            <div style="border:var(--border);padding:24px;box-shadow:var(--shadow-sm);{{ $isNext ? 'border-color:var(--forest);border-left:6px solid var(--forest);' : '' }}">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                    <div>
                        @if($isNext)
                            <span class="badge badge-valid" style="margin-bottom:8px;display:inline-block;">Prochaine sélection</span><br>
                        @endif
                        <span style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;">{{ $pub['titre'] ?? '—' }}</span>
                        <span style="font-family:'DM Mono',monospace;font-size:0.8rem;opacity:0.5;margin-left:10px;">{{ $pub['nom_entreprise'] ?? '' }}</span>
                    </div>
                    <div style="text-align:right;flex-shrink:0;margin-left:16px;">
                        <div style="font-family:'Bebas Neue',sans-serif;font-size:2rem;color:{{ $isNext ? 'var(--forest)' : 'var(--coffee)' }};">
                            {{ number_format($score) }}
                        </div>
                        <div style="font-family:'DM Mono',monospace;font-size:0.7rem;text-transform:uppercase;opacity:0.5;">score WRR</div>
                    </div>
                </div>

                {{-- Barre score --}}
                <div style="height:8px;background:rgba(18,3,9,0.08);border:1px solid rgba(18,3,9,0.15);margin-bottom:12px;">
                    <div style="height:100%;width:{{ $pct }}%;background:{{ $isNext ? 'var(--forest)' : 'var(--teal)' }};transition:width 0.4s;"></div>
                </div>

                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;">
                        <span style="opacity:0.5;">Poids :</span> <strong>{{ $poids }}</strong>
                    </span>
                    <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;">
                        <span style="opacity:0.5;">Affichages :</span> <strong>{{ number_format($pub['nb_affichages'] ?? 0) }}</strong>
                    </span>
                    @if(!empty($pub['derniere_vue']))
                    <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;">
                        <span style="opacity:0.5;">Dernière vue :</span>
                        <strong>{{ date('d/m/Y H:i', strtotime($pub['derniere_vue'])) }}</strong>
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Légende poids --}}
    <div class="card" style="margin-top:24px;">
        <h3 class="font-bebas" style="font-size:1.4rem;margin:0 0 16px;">Répartition théorique des affichages</h3>
        @php $totalPoids = array_sum(array_column($pubs, 'poids_affichage')); @endphp
        @if($totalPoids > 0)
        <div style="height:32px;display:flex;border:var(--border);overflow:hidden;margin-bottom:16px;">
            @foreach($pubs as $pub)
                @php $pct = ($pub['poids_affichage'] ?? 1) / $totalPoids * 100; @endphp
                <div style="width:{{ $pct }}%;background:var(--teal);border-right:2px solid var(--cream);"
                     title="{{ $pub['titre'] ?? '' }} — {{ number_format($pct, 1) }}%"></div>
            @endforeach
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:12px;">
            @foreach($pubs as $pub)
                @php $pct = ($pub['poids_affichage'] ?? 1) / $totalPoids * 100; @endphp
                <span style="font-family:'DM Mono',monospace;font-size:0.75rem;display:flex;align-items:center;gap:6px;">
                    <span style="width:12px;height:12px;background:var(--teal);display:inline-block;border:2px solid var(--coffee);"></span>
                    {{ Str::limit($pub['titre'] ?? '', 20) }} ({{ number_format($pct, 1) }}%)
                </span>
            @endforeach
        </div>
        @endif
    </div>
@endif
@endsection

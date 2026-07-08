@extends('layouts.professionnel')

@section('title', 'Dashboard Expert Pro — ' . $annee)

{{-- Dashboard annuel Expert Pro : impact, matériaux, badges --}}

@section('content')
<div class="main-content">

    {{-- === En-tête === --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <div>
            <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.expert.title">Dashboard Expert Pro</span></h1>
            <p class="font-mono" style="font-size:0.8rem; color:#666;">Année {{ $annee }}</p>
        </div>
        <div style="display:flex; gap:12px;">
            <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm"><span data-i18n="prod.monthlyview">Vue mensuelle</span></a>
            <a href="{{ route('pro.dashboard.export-pdf') }}" class="btn-primary btn-sm"><span data-i18n="prod.exportpdf">Exporter PDF</span></a>
        </div>
    </div>

    @if(session('error'))
        <div style="background:#fee;border:2px solid #A4243B;padding:12px 20px;margin-bottom:24px;font-family:'DM Mono',monospace;font-size:0.85rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Impact écologique annuel --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:24px;">Impact écologique — Année {{ $annee }}</h2>
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px;">
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3.5rem; color:#244F26;">{{ $impact['nb_objets_recuperes'] ?? 0 }}</div>
                <div class="font-mono" style="font-size:0.7rem;"><span data-i18n="prod.itemssaved">Objets récupérés</span></div>
            </div>
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3.5rem; color:#18607D;">{{ number_format($impact['poids_dechet_kg'] ?? 0, 1) }}<span style="font-size:1.5rem"> kg</span></div>
                <div class="font-mono" style="font-size:0.7rem;"><span data-i18n="dash.wasteavoided">Déchets évités</span></div>
            </div>
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3.5rem; color:#A4243B;">{{ number_format($impact['co2_evite_kg'] ?? 0, 1) }}<span style="font-size:1.5rem"> kg</span></div>
                <div class="font-mono" style="font-size:0.7rem;"><span data-i18n="prod.co2">CO₂ évité</span></div>
            </div>
        </div>
    </div>

    {{-- Stats matériaux --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:24px;"><span data-i18n="prod.materials10km">Matériaux disponibles — rayon 10 km</span></h2>
        @forelse($stats_materiaux as $stat)
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid #ccc;">
                <span>{{ $stat['libelle'] ?? ucfirst($stat['materiau']) }}</span>
                <span class="font-bebas" style="font-size:1.4rem;">{{ $stat['nb_annonces'] }} annonce(s)</span>
            </div>
        @empty
            <p style="color:#666; font-style:italic;">Aucune annonce trouvée dans votre zone.</p>
        @endforelse
    </div>

    {{-- Badges --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:24px;"><span data-i18n="prod.badges">Vos badges</span></h2>
        @if(empty($badges))
            <p style="color:#666; font-style:italic;"><span data-i18n="prod.badges.empty">Aucun badge obtenu pour le moment. Continuez à récupérer des objets !</span></p>
        @else
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(200px,1fr)); gap:16px;">
                @foreach($badges as $badge)
                <div style="border:3px solid #244F26; padding:16px; text-align:center;">
                    <div style="font-size:2rem; margin-bottom:8px;">🏅</div>
                    <div class="font-bebas" style="font-size:1.1rem; color:#244F26;">{{ $badge['nom'] }}</div>
                    <div style="font-size:0.8rem; color:#666; margin-top:4px;">{{ $badge['description'] }}</div>
                    <div class="font-mono" style="font-size:0.65rem; margin-top:8px; color:#999;">
                        Obtenu le {{ \Carbon\Carbon::parse($badge['date_obtention'])->format('d/m/Y') }}
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <div style="margin-top:24px;">
            <form method="POST" action="{{ route('pro.badges.recalculer') }}">
                @csrf
                <button type="submit" class="btn-secondary btn-sm"><span data-i18n="prod.badges.recalc">Recalculer mes badges</span></button>
            </form>
        </div>
    </div>

    {{-- Navigation --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
        <a href="{{ route('pro.alertes.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.alerts.title">Alertes matériaux</span></a>
        <a href="{{ route('pro.publicites.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.myads">Mes publicités</span></a>
        <a href="{{ route('pro.conteneurs.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.mycontainers">Mes conteneurs</span></a>
    </div>

</div>
@endsection

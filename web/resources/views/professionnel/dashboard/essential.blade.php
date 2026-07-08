@extends('layouts.professionnel')

@section('title', 'Dashboard Essential Pro')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <div>
            <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.title">Dashboard Pro</span></h1>
            <p class="font-mono" style="font-size:0.8rem; color:#666;">Période : {{ $periode }}</p>
        </div>
        <a href="{{ route('pro.dashboard.expert') }}" class="btn-secondary btn-sm"><span data-i18n="prod.yearlyview">Vue annuelle (Expert Pro)</span></a>
    </div>

    @if(session('error'))
        <div style="background:#fee;border:2px solid #A4243B;padding:12px 20px;margin-bottom:24px;font-family:'DM Mono',monospace;font-size:0.85rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Impact écologique --}}
    <div class="card" x-data="barChart({{ json_encode($impact) }})">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:24px;"><span data-i18n="prod.impact">Impact écologique — Ce mois</span></h2>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-bottom:32px;">
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3rem; color:#244F26;">{{ $impact['nb_objets_recuperes'] ?? 0 }}</div>
                <div class="font-mono" style="font-size:0.7rem; margin-top:4px;"><span data-i18n="prod.itemssaved">Objets récupérés</span></div>
            </div>
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3rem; color:#18607D;">{{ number_format($impact['poids_dechet_kg'] ?? 0, 1) }} kg</div>
                <div class="font-mono" style="font-size:0.7rem; margin-top:4px;"><span data-i18n="dash.wasteavoided">Déchets évités</span></div>
            </div>
            <div style="border:3px solid #120309; padding:24px; text-align:center;">
                <div class="font-bebas" style="font-size:3rem; color:#A4243B;">{{ number_format($impact['co2_evite_kg'] ?? 0, 1) }} kg</div>
                <div class="font-mono" style="font-size:0.7rem; margin-top:4px;"><span data-i18n="prod.co2">CO₂ évité</span></div>
            </div>
        </div>

        {{-- Graphique en barres (Alpine.js) --}}
        <div style="border:3px solid #120309; padding:24px;">
            <h3 class="font-mono" style="font-size:0.8rem; margin-bottom:16px;"><span data-i18n="prod.visualsummary">Récapitulatif visuel</span></h3>
            <div style="display:flex; align-items:flex-end; gap:24px; height:120px;">
                <template x-for="bar in bars" :key="bar.label">
                    <div style="display:flex; flex-direction:column; align-items:center; flex:1;">
                        <div :style="`height:${bar.pct}%; background:${bar.color}; width:100%; border:2px solid #120309; min-height:4px;`"></div>
                        <div class="font-mono" style="font-size:0.6rem; margin-top:6px; text-align:center;" x-text="bar.label"></div>
                        <div class="font-bebas" style="font-size:1rem;" x-text="bar.value"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Dépenses du mois (objets, publicité, abonnement) --}}
    @if($depenses)
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:6px;"><span data-i18n="prod.spending">Dépenses — Ce mois</span></h2>
        <p class="font-mono" style="font-size:0.72rem; color:#666; margin-bottom:24px;"><span data-i18n="prod.spending.note">Tout mois de publicité entamé est dû en entier.</span></p>

        <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin-bottom:24px;">
            <div style="border:3px solid #120309; padding:20px; text-align:center;">
                <div class="font-bebas" style="font-size:2.2rem; color:#18607D;">{{ number_format($depenses['objets'] ?? 0, 2) }} €</div>
                <div class="font-mono" style="font-size:0.68rem; margin-top:4px;"><span data-i18n="prod.spending.items">Objets achetés</span></div>
            </div>
            <div style="border:3px solid #120309; padding:20px; text-align:center;">
                <div class="font-bebas" style="font-size:2.2rem; color:#A4243B;">{{ number_format($depenses['publicite'] ?? 0, 2) }} €</div>
                <div class="font-mono" style="font-size:0.68rem; margin-top:4px;"><span data-i18n="prod.spending.ads">Publicités</span> ({{ $depenses['nb_pubs_actives'] ?? 0 }})</div>
            </div>
            <div style="border:3px solid #120309; padding:20px; text-align:center;">
                <div class="font-bebas" style="font-size:2.2rem; color:#244F26;">{{ number_format($depenses['abonnement'] ?? 0, 2) }} €</div>
                <div class="font-mono" style="font-size:0.68rem; margin-top:4px;"><span data-i18n="prod.spending.sub">Abonnement</span></div>
            </div>
            <div style="border:3px solid #120309; background:#120309; color:#F5F0E1; padding:20px; text-align:center;">
                <div class="font-bebas" style="font-size:2.2rem;">{{ number_format($depenses['total'] ?? 0, 2) }} €</div>
                <div class="font-mono" style="font-size:0.68rem; margin-top:4px;"><span data-i18n="prod.spending.total">Total du mois</span></div>
            </div>
        </div>

        {{-- Répartition par catégorie --}}
        @php $tot = max($depenses['total'] ?? 0, 0.01); @endphp
        <div style="display:flex; height:26px; border:3px solid #120309; overflow:hidden;">
            @foreach([['objets','#18607D'],['publicite','#A4243B'],['abonnement','#244F26']] as [$k,$c])
                @php $pct = ($depenses[$k] ?? 0) / $tot * 100; @endphp
                @if($pct > 0)<div style="width:{{ $pct }}%; background:{{ $c }};" title="{{ ucfirst($k) }} : {{ number_format($depenses[$k], 2) }} €"></div>@endif
            @endforeach
        </div>
        <div style="display:flex; gap:20px; margin-top:10px; font-family:'DM Mono',monospace; font-size:0.68rem; flex-wrap:wrap;">
            <span><span style="display:inline-block;width:10px;height:10px;background:#18607D;vertical-align:middle;"></span> Objets</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:#A4243B;vertical-align:middle;"></span> Publicité</span>
            <span><span style="display:inline-block;width:10px;height:10px;background:#244F26;vertical-align:middle;"></span> Abonnement</span>
        </div>
    </div>
    @endif

    {{-- Stats matériaux --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.6rem; margin-bottom:24px;"><span data-i18n="prod.materials10km">Matériaux disponibles — rayon 10 km</span></h2>

        @if(empty($stats_materiaux))
            <p style="color:#666; font-style:italic;">Aucune annonce trouvée dans votre zone.</p>
        @else
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:3px solid #120309;">
                        <th class="font-mono" style="text-align:left; padding:10px; font-size:0.75rem;">Matériau</th>
                        <th class="font-mono" style="text-align:right; padding:10px; font-size:0.75rem;">Annonces disponibles</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats_materiaux as $stat)
                    <tr style="border-bottom:1px solid #ccc;">
                        <td style="padding:10px; font-family:'Outfit',sans-serif;">{{ $stat['libelle'] ?? ucfirst($stat['materiau']) }}</td>
                        <td style="padding:10px; text-align:right;" class="font-bebas" style="font-size:1.2rem;">{{ $stat['nb_annonces'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Navigation rapide --}}
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
        <a href="{{ route('pro.alertes.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.myalerts">Mes alertes matériaux</span></a>
        <a href="{{ route('pro.publicites.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.myads">Mes publicités</span></a>
        <a href="{{ route('pro.conteneurs.index') }}" class="btn-secondary" style="text-align:center;"><span data-i18n="prod.mycontainers">Mes conteneurs</span></a>
    </div>

</div>
@endsection

@push('scripts')
<script>
function barChart(impact) {
    const vals = [
        impact.nb_objets_recuperes ?? 0,
        impact.poids_dechet_kg    ?? 0,
        impact.co2_evite_kg       ?? 0,
    ];
    const max = Math.max(...vals, 1);
    return {
        bars: [
            { label: 'Objets',     value: vals[0],                       pct: (vals[0]/max)*100, color: '#244F26' },
            { label: 'Déchets kg', value: vals[1].toFixed(1) + ' kg',    pct: (vals[1]/max)*100, color: '#18607D' },
            { label: 'CO₂ kg',     value: vals[2].toFixed(1) + ' kg',    pct: (vals[2]/max)*100, color: '#A4243B' },
        ],
    };
}
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Statistiques publicités')

@section('content')
<div class="page-header">
    <h1 class="page-title">Statistiques publicités</h1>
    <div style="display:flex;gap:12px;">
        <a href="{{ route('admin.publicites.rotation') }}" class="btn-secondary">Voir rotation WRR</a>
        <a href="{{ route('admin.publicites.index') }}" class="btn-secondary">← Modération</a>
    </div>
</div>

@if(empty($stats))
    <div class="card" style="text-align:center;padding:60px 40px;">
        <p style="font-family:'Bebas Neue',sans-serif;font-size:2rem;opacity:0.3;margin:0;">Aucune publicité</p>
    </div>
@else
    {{-- KPIs globaux --}}
    @php
        $totalVues  = array_sum(array_column($stats, 'nb_vues'));
        $totalClics = array_sum(array_column($stats, 'nb_clics'));
        $ctrGlobal  = $totalVues > 0 ? $totalClics / $totalVues * 100 : 0;
    @endphp
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Vues totales</div>
            <div class="stat-value">{{ number_format($totalVues) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Clics totaux</div>
            <div class="stat-value">{{ number_format($totalClics) }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">CTR global</div>
            <div class="stat-value">{{ number_format($ctrGlobal, 2) }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Campagnes actives</div>
            <div class="stat-value">{{ count(array_filter($stats, fn($s) => ($s['statut'] ?? '') === 'active')) }}</div>
        </div>
    </div>

    <div class="card">
        <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Performance par campagne</h2>
        <div class="table-container" style="box-shadow:none;border:none;">
            <table>
                <thead>
                    <tr>
                        <th>Campagne</th>
                        <th>Entreprise</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Vues</th>
                        <th style="text-align:right;">Clics</th>
                        <th style="text-align:right;">CTR</th>
                        <th style="text-align:right;">Coût/mois</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats as $s)
                    <tr>
                        <td style="font-weight:600;">{{ $s['titre'] ?? '—' }}</td>
                        <td>{{ $s['nom_entreprise'] ?? '—' }}</td>
                        <td>
                            @php $st = $s['statut'] ?? ''; @endphp
                            <span class="badge {{ $st === 'active' ? 'badge-valid' : ($st === 'en_attente' ? 'badge-waiting' : 'badge-refused') }}">
                                {{ $st }}
                            </span>
                        </td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">{{ number_format($s['nb_vues'] ?? 0) }}</td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">{{ number_format($s['nb_clics'] ?? 0) }}</td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;font-weight:700;color:{{ ($s['ctr_pct'] ?? 0) > 2 ? 'var(--forest)' : 'var(--coffee)' }};">
                            {{ number_format($s['ctr_pct'] ?? 0, 2) }}%
                        </td>
                        <td style="text-align:right;font-family:'DM Mono',monospace;">{{ number_format($s['cout_mensuel'] ?? 0, 2, ',', ' ') }} €</td>
                    </tr>
                    {{-- Barre CTR visuelle --}}
                    <tr style="border-bottom:none;">
                        <td colspan="7" style="padding:0 24px 10px;border-bottom:2px solid rgba(18,3,9,0.08);">
                            @php $pctBar = min(100, ($s['ctr_pct'] ?? 0) * 10); @endphp
                            <div style="height:4px;background:rgba(18,3,9,0.08);border-radius:0;">
                                <div style="height:4px;width:{{ $pctBar }}%;background:{{ ($s['ctr_pct'] ?? 0) > 2 ? 'var(--forest)' : 'var(--teal)' }};transition:width 0.3s;"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection

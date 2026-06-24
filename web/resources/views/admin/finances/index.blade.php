@extends('layouts.admin')

@section('title', 'Pilotage Financier')

@section('content')
<div class="page-header">
    <h1 class="page-title">Pilotage Financier</h1>
    <div style="display:flex;gap:12px;">
        <a href="{{ route('admin.finances.export-csv', request()->only(['type','mois','annee'])) }}"
           class="btn-secondary">
            ↓ Export CSV
        </a>
        <a href="{{ route('admin.finances.export-pdf', request()->only(['type','mois','annee'])) }}"
           class="btn-primary">
            ↓ Export PDF
        </a>
    </div>
</div>

{{-- KPIs --}}
@if(!empty($dashboard))
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:24px;margin-bottom:40px;">
    <div class="card" style="text-align:center;padding:28px 20px;margin-bottom:0;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Revenus ce mois (HT)</p>
        <p class="font-bebas" style="font-size:2.6rem;color:var(--forest);line-height:1;margin:0;">{{ number_format($dashboard['total_ht_mois'] ?? 0, 2, ',', ' ') }} €</p>
    </div>
    <div class="card" style="text-align:center;padding:28px 20px;margin-bottom:0;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Revenus ce mois (TTC)</p>
        <p class="font-bebas" style="font-size:2.6rem;color:var(--coffee);line-height:1;margin:0;">{{ number_format($dashboard['total_ttc_mois'] ?? 0, 2, ',', ' ') }} €</p>
    </div>
    <div class="card" style="text-align:center;padding:28px 20px;margin-bottom:0;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Revenus année (HT)</p>
        <p class="font-bebas" style="font-size:2.6rem;color:var(--teal);line-height:1;margin:0;">{{ number_format($dashboard['total_ht_annee'] ?? 0, 2, ',', ' ') }} €</p>
    </div>
    <div class="card" style="text-align:center;padding:28px 20px;margin-bottom:0;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Transactions</p>
        <p class="font-bebas" style="font-size:2.6rem;color:var(--coffee);line-height:1;margin:0;">{{ $dashboard['nb_transactions'] ?? 0 }}</p>
    </div>
    <div class="card" style="text-align:center;padding:28px 20px;margin-bottom:0;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Abonnements actifs</p>
        <p class="font-bebas" style="font-size:2.6rem;color:var(--cherry);line-height:1;margin:0;">{{ $dashboard['nb_abonnements_actifs'] ?? 0 }}</p>
    </div>
</div>
@endif

{{-- Filtres --}}
<form method="GET" style="display:flex;gap:16px;margin-bottom:32px;flex-wrap:wrap;align-items:flex-end;">
    <div>
        <label class="form-label" style="font-size:0.8rem;">Source</label>
        <select name="type" class="form-select" style="width:200px;">
            <option value="">Toutes les sources</option>
            <option value="abonnement" {{ request('type') === 'abonnement' ? 'selected' : '' }}>Abonnements</option>
            <option value="commande" {{ request('type') === 'commande' ? 'selected' : '' }}>Commandes</option>
            <option value="evenement" {{ request('type') === 'evenement' ? 'selected' : '' }}>Événements</option>
            <option value="publicite" {{ request('type') === 'publicite' ? 'selected' : '' }}>Publicités</option>
        </select>
    </div>
    <div>
        <label class="form-label" style="font-size:0.8rem;">Mois</label>
        <select name="mois" class="form-select" style="width:150px;">
            <option value="">Tous</option>
            @foreach(['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'] as $i => $m)
                <option value="{{ $i+1 }}" {{ request('mois') == $i+1 ? 'selected' : '' }}>{{ $m }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="form-label" style="font-size:0.8rem;">Année</label>
        <select name="annee" class="form-select" style="width:120px;">
            @for($y = date('Y'); $y >= date('Y') - 4; $y--)
                <option value="{{ $y }}" {{ request('annee', date('Y')) == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endfor
        </select>
    </div>
    <button type="submit" class="btn-primary" style="padding:14px 24px;">Filtrer</button>
    <a href="{{ route('admin.finances.index') }}" class="btn-secondary" style="padding:14px 24px;">Réinitialiser</a>
</form>

{{-- Graphique revenus par source --}}
@if(!empty($revenus))
<div class="card" style="margin-bottom:40px;">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Revenus par source</h2>

    @php
        $sources = [];
        $grandTotalHT = 0;
        $grandTotalTTC = 0;
        foreach ($revenus as $r) {
            $src = $r['type_source'] ?? 'autre';
            if (!isset($sources[$src])) $sources[$src] = ['ht' => 0, 'ttc' => 0, 'nb' => 0];
            $sources[$src]['ht']  += $r['total_ht'] ?? 0;
            $sources[$src]['ttc'] += $r['total_ttc'] ?? 0;
            $sources[$src]['nb']  += $r['nb_transactions'] ?? 0;
            $grandTotalHT  += $r['total_ht'] ?? 0;
            $grandTotalTTC += $r['total_ttc'] ?? 0;
        }
        $colors = ['abonnement' => '#244F26', 'commande' => '#18607D', 'evenement' => '#6c5ce7', 'publicite' => '#A4243B'];
    @endphp

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:32px;">
        @foreach($sources as $src => $vals)
        <div style="border:var(--border);padding:20px;box-shadow:var(--shadow-sm);border-left:6px solid {{ $colors[$src] ?? '#120309' }};">
            <div style="font-family:'DM Mono',monospace;font-size:0.75rem;text-transform:uppercase;opacity:0.5;margin-bottom:8px;">{{ ucfirst($src) }}</div>
            <div style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;color:{{ $colors[$src] ?? '#120309' }};">{{ number_format($vals['ttc'], 2, ',', ' ') }} €</div>
            <div style="font-family:'DM Mono',monospace;font-size:0.75rem;opacity:0.5;">{{ $vals['nb'] }} transaction(s)</div>
        </div>
        @endforeach
    </div>

    {{-- Barre de répartition --}}
    @if($grandTotalTTC > 0)
    <div style="margin-bottom:12px;">
        <div style="height:24px;display:flex;border:var(--border);overflow:hidden;">
            @foreach($sources as $src => $vals)
                @php $pct = $grandTotalTTC > 0 ? ($vals['ttc'] / $grandTotalTTC * 100) : 0; @endphp
                <div style="width:{{ $pct }}%;background:{{ $colors[$src] ?? '#120309' }};"
                     title="{{ ucfirst($src) }} : {{ number_format($pct, 1) }}%"></div>
            @endforeach
        </div>
        <div style="display:flex;gap:16px;margin-top:10px;flex-wrap:wrap;">
            @foreach($sources as $src => $vals)
                @php $pct = $grandTotalTTC > 0 ? ($vals['ttc'] / $grandTotalTTC * 100) : 0; @endphp
                <span style="font-family:'DM Mono',monospace;font-size:0.75rem;display:flex;align-items:center;gap:6px;">
                    <span style="width:12px;height:12px;background:{{ $colors[$src] ?? '#120309' }};display:inline-block;border:2px solid var(--coffee);"></span>
                    {{ ucfirst($src) }} ({{ number_format($pct, 1) }}%)
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Tableau détail --}}
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <h2 class="font-bebas" style="font-size:1.8rem;margin:0;">Détail des revenus</h2>
        <span style="font-family:'DM Mono',monospace;font-size:0.9rem;font-weight:700;">
            Total TTC : {{ number_format($grandTotalTTC, 2, ',', ' ') }} €
        </span>
    </div>
    <div class="table-container" style="box-shadow:none;border:none;">
        <table>
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Mois</th>
                    <th>Nb transactions</th>
                    <th>Total HT</th>
                    <th>Total TTC</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revenus as $r)
                <tr>
                    <td><span class="badge" style="background:{{ $colors[$r['type_source'] ?? ''] ?? 'var(--coffee)' }};color:var(--cream);border-color:var(--coffee);">{{ $r['type_source'] ?? '—' }}</span></td>
                    <td>{{ $r['mois'] ?? '—' }}</td>
                    <td style="text-align:center;">{{ $r['nb_transactions'] ?? 0 }}</td>
                    <td>{{ number_format($r['total_ht'] ?? 0, 2, ',', ' ') }} €</td>
                    <td style="font-weight:700;">{{ number_format($r['total_ttc'] ?? 0, 2, ',', ' ') }} €</td>
                </tr>
                @endforeach
                <tr style="background:var(--wheat);font-weight:700;">
                    <td colspan="3" style="font-family:'DM Mono',monospace;text-transform:uppercase;">TOTAL</td>
                    <td>{{ number_format($grandTotalHT, 2, ',', ' ') }} €</td>
                    <td>{{ number_format($grandTotalTTC, 2, ',', ' ') }} €</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@else
<div class="card" style="text-align:center;padding:60px 40px;">
    <p style="font-family:'Bebas Neue',sans-serif;font-size:2rem;opacity:0.3;margin:0;">Aucune donnée financière</p>
    <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;opacity:0.4;margin:12px 0 0;">
        Ajustez les filtres ou attendez les premières transactions.
    </p>
</div>
@endif
@endsection

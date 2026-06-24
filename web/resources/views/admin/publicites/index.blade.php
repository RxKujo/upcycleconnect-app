@extends('layouts.admin')
@section('title', 'Publicités')

@section('content')
<div class="page-header">
    <h1 class="page-title">Publicités</h1>
    <div style="display:flex;gap:12px;">
        <a href="{{ route('admin.publicites.stats') }}" class="btn-secondary">Statistiques CTR</a>
        <a href="{{ route('admin.publicites.rotation') }}" class="btn-secondary">Rotation WRR</a>
    </div>
</div>

@php
    $enAttente = collect($publicites)->where('statut', 'en_attente')->count();
@endphp

@if(session('success'))
    <div class="alert alert-success">
        <span style="font-size:1.4rem;">✓</span> {{ session('success') }}
    </div>
@endif

@if($enAttente > 0)
    <div class="alert" style="background:var(--wheat);border-color:var(--coffee);color:var(--coffee);">
        <span style="font-size:1.4rem;">⚠</span>
        {{ $enAttente }} publicité{{ $enAttente > 1 ? 's' : '' }} en attente de validation
    </div>
@endif

{{-- Filtres --}}
<div style="display:flex;gap:12px;margin-bottom:32px;flex-wrap:wrap;align-items:center;">
    @foreach([''=>'Tous','en_attente'=>'En attente','active'=>'Active','validee'=>'Validée','refusee'=>'Refusée','expiree'=>'Expirée'] as $val => $label)
        <a href="{{ route('admin.publicites.index', $val ? ['statut'=>$val] : []) }}"
           class="font-mono"
           style="padding:8px 18px;border:2px solid var(--coffee);font-size:0.8rem;text-decoration:none;box-shadow:2px 2px 0px rgba(18,3,9,0.15);
                  {{ $statut_filtre === $val ? 'background:var(--coffee);color:var(--cream);' : 'background:var(--cream);color:var(--coffee);' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Titre</th>
                <th>Entreprise</th>
                <th>Statut</th>
                <th style="text-align:right;">Vues</th>
                <th style="text-align:right;">Clics</th>
                <th>Période</th>
                <th style="text-align:center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($publicites as $pub)
            @php
                $st = $pub['statut'] ?? '';
                $badgeClass = match($st) {
                    'validee', 'active' => 'badge-valid',
                    'refusee'           => 'badge-refused',
                    'en_attente'        => 'badge-waiting',
                    default             => '',
                };
            @endphp
            <tr>
                <td style="font-weight:600;">{{ $pub['titre'] }}</td>
                <td>{{ $pub['nom_entreprise'] ?? '—' }}</td>
                <td>
                    <span class="badge {{ $badgeClass }}">{{ $st }}</span>
                </td>
                <td style="text-align:right;" class="font-mono">{{ number_format($pub['nb_vues'] ?? 0) }}</td>
                <td style="text-align:right;" class="font-mono">{{ number_format($pub['nb_clics'] ?? 0) }}</td>
                <td class="font-mono" style="font-size:0.85rem;">
                    @if($pub['date_debut']){{ \Carbon\Carbon::parse($pub['date_debut'])->format('d/m/Y') }}@endif
                    @if($pub['date_debut'] && $pub['date_fin']) → @endif
                    @if($pub['date_fin']){{ \Carbon\Carbon::parse($pub['date_fin'])->format('d/m/Y') }}@endif
                    @if(!$pub['date_debut'] && !$pub['date_fin'])—@endif
                </td>
                <td>
                    @if($pub['statut'] === 'en_attente')
                        <div class="action-cell" style="justify-content:center;">
                            <form method="POST" action="{{ route('admin.publicites.valider', $pub['id_publicite']) }}">
                                @csrf @method('PUT')
                                <button type="submit" class="btn-success btn-sm">Valider</button>
                            </form>
                            <form method="POST" action="{{ route('admin.publicites.refuser', $pub['id_publicite']) }}"
                                  onsubmit="return confirm('Refuser cette publicité ?')">
                                @csrf @method('PUT')
                                <button type="submit" class="btn-danger btn-sm">Refuser</button>
                            </form>
                        </div>
                    @else
                        <div style="text-align:center;color:#999;font-family:'DM Mono',monospace;font-size:0.8rem;">—</div>
                    @endif
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="7" style="padding:48px;text-align:center;color:#999;font-family:'DM Mono',monospace;font-size:0.9rem;">
                        Aucune publicité trouvée.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

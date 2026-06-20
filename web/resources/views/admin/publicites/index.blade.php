@extends('layouts.admin')
@section('title', 'Publicités')

@section('content')
<div class="page-header">
    <h1 class="page-title">Publicités</h1>
</div>

@php
    $enAttente = collect($publicites)->where('statut', 'en_attente')->count();
@endphp

@if(session('success'))
    <div class="alert alert-success" style="background:#d1e7dd;border:2px solid #0a3622;padding:12px 20px;margin-bottom:20px;font-family:'DM Mono',monospace;font-size:0.85rem;">
        {{ session('success') }}
    </div>
@endif

@if($enAttente > 0)
    <div class="alert alert-error" style="background:#fff3cd;border-color:var(--coffee);color:var(--coffee);padding:12px 20px;margin-bottom:20px;font-family:'DM Mono',monospace;font-size:0.85rem;">
        <span style="font-size:1.4rem;">⚠</span>
        {{ $enAttente }} publicité{{ $enAttente > 1 ? 's' : '' }} en attente de validation
    </div>
@endif

{{-- Filtres --}}
<div style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap;align-items:center;">
    @foreach([''=>'Tous','en_attente'=>'En attente','active'=>'Active','validee'=>'Validée','refusee'=>'Refusée','expiree'=>'Expirée'] as $val => $label)
        <a href="{{ route('admin.publicites.index', $val ? ['statut'=>$val] : []) }}"
           style="padding:6px 16px;border:2px solid #120309;font-family:'DM Mono',monospace;font-size:0.75rem;text-decoration:none;
                  {{ $statut_filtre === $val ? 'background:#120309;color:#F5F0E1;' : 'background:transparent;color:#120309;' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<table style="width:100%;border-collapse:collapse;">
    <thead>
        <tr style="border-bottom:3px solid #120309;">
            <th class="font-mono" style="text-align:left;padding:10px;font-size:0.72rem;">Titre</th>
            <th class="font-mono" style="text-align:left;padding:10px;font-size:0.72rem;">Entreprise</th>
            <th class="font-mono" style="text-align:left;padding:10px;font-size:0.72rem;">Statut</th>
            <th class="font-mono" style="text-align:right;padding:10px;font-size:0.72rem;">Vues</th>
            <th class="font-mono" style="text-align:right;padding:10px;font-size:0.72rem;">Clics</th>
            <th class="font-mono" style="text-align:left;padding:10px;font-size:0.72rem;">Période</th>
            <th class="font-mono" style="text-align:center;padding:10px;font-size:0.72rem;">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($publicites as $pub)
        @php
            $statutColors = [
                'en_attente' => '#fff3cd',
                'validee'    => '#d1e7dd',
                'active'     => '#d1e7dd',
                'refusee'    => '#f8d7da',
                'expiree'    => '#e9ecef',
            ];
            $bg = $statutColors[$pub['statut']] ?? '#eee';
        @endphp
        <tr style="border-bottom:1px solid #ddd;">
            <td style="padding:12px;font-weight:500;">{{ $pub['titre'] }}</td>
            <td style="padding:12px;font-size:0.9rem;">{{ $pub['nom_entreprise'] ?? '—' }}</td>
            <td style="padding:12px;">
                <span style="background:{{ $bg }};padding:2px 10px;font-family:'DM Mono',monospace;font-size:0.7rem;border:1px solid #ccc;">
                    {{ $pub['statut'] }}
                </span>
            </td>
            <td style="padding:12px;text-align:right;" class="font-mono">{{ $pub['nb_vues'] ?? 0 }}</td>
            <td style="padding:12px;text-align:right;" class="font-mono">{{ $pub['nb_clics'] ?? 0 }}</td>
            <td style="padding:12px;font-size:0.8rem;color:#666;">
                @if($pub['date_debut']){{ \Carbon\Carbon::parse($pub['date_debut'])->format('d/m/Y') }}@endif
                @if($pub['date_debut'] && $pub['date_fin']) → @endif
                @if($pub['date_fin']){{ \Carbon\Carbon::parse($pub['date_fin'])->format('d/m/Y') }}@endif
                @if(!$pub['date_debut'] && !$pub['date_fin'])—@endif
            </td>
            <td style="padding:12px;text-align:center;">
                @if($pub['statut'] === 'en_attente')
                    <div style="display:flex;gap:8px;justify-content:center;">
                        <form method="POST" action="{{ route('admin.publicites.valider', $pub['id_publicite']) }}">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#244F26;color:white;border:2px solid #120309;padding:4px 12px;font-family:'DM Mono',monospace;font-size:0.7rem;cursor:pointer;">
                                Valider
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.publicites.refuser', $pub['id_publicite']) }}"
                              onsubmit="return confirm('Refuser cette publicité ?')">
                            @csrf @method('PUT')
                            <button type="submit" style="background:#A4243B;color:white;border:2px solid #120309;padding:4px 12px;font-family:'DM Mono',monospace;font-size:0.7rem;cursor:pointer;">
                                Refuser
                            </button>
                        </form>
                    </div>
                @else
                    <span style="color:#999;font-family:'DM Mono',monospace;font-size:0.7rem;">—</span>
                @endif
            </td>
        </tr>
        @empty
            <tr>
                <td colspan="7" style="padding:32px;text-align:center;color:#999;font-family:'DM Mono',monospace;font-size:0.8rem;">
                    Aucune publicité trouvée.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection

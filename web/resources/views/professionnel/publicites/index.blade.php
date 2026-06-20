@extends('layouts.professionnel')

@section('title', 'Mes publicités')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;">Mes publicités</h1>
        <div style="display:flex; gap:12px;">
            <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm">← Dashboard</a>
            <a href="{{ route('pro.publicites.create') }}" class="btn-primary btn-sm">Nouvelle publicité</a>
        </div>
    </div>

    @if(session('success'))
        <div style="background:#e8f5e9;border:2px solid #244F26;padding:12px 20px;margin-bottom:24px;font-family:'DM Mono',monospace;font-size:0.85rem;">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee;border:2px solid #A4243B;padding:12px 20px;margin-bottom:24px;font-family:'DM Mono',monospace;font-size:0.85rem;">
            {{ session('error') }}
        </div>
    @endif

    {{-- Info tarif --}}
    <div class="card" style="background:#f9f5e7; border:2px solid #D8C99B; padding:16px 24px; margin-bottom:24px;">
        <p class="font-mono" style="font-size:0.8rem; color:#444;">
            💡 Tarif : <strong>100 €/mois</strong> par publicité — Maximum <strong>5 publicités actives</strong>.
            Toute publicité est soumise à validation par l'équipe UpcycleConnect avant mise en ligne.
        </p>
    </div>

    @forelse($publicites as $pub)
    <div class="card" style="margin-bottom:20px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:16px;">
            <div style="flex:1;">
                <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
                    <h3 class="font-bebas" style="font-size:1.3rem;">{{ $pub['titre'] }}</h3>
                    @php
                        $statutColors = [
                            'en_attente' => ['bg'=>'#fff3cd','border'=>'#856404','text'=>'En attente'],
                            'validee'    => ['bg'=>'#d1e7dd','border'=>'#0a3622','text'=>'Validée'],
                            'active'     => ['bg'=>'#d1e7dd','border'=>'#244F26','text'=>'Active'],
                            'refusee'    => ['bg'=>'#f8d7da','border'=>'#842029','text'=>'Refusée'],
                            'expiree'    => ['bg'=>'#e9ecef','border'=>'#6c757d','text'=>'Expirée'],
                        ];
                        $sc = $statutColors[$pub['statut']] ?? ['bg'=>'#eee','border'=>'#999','text'=>$pub['statut']];
                    @endphp
                    <span style="background:{{ $sc['bg'] }};border:2px solid {{ $sc['border'] }};padding:2px 10px;font-family:'DM Mono',monospace;font-size:0.7rem;">
                        {{ $sc['text'] }}
                    </span>
                </div>

                <div class="font-mono" style="font-size:0.75rem; color:#666; display:flex; gap:24px; flex-wrap:wrap;">
                    <span>{{ number_format($pub['cout_mensuel'], 2) }} €/mois</span>
                    <span>{{ $pub['nb_vues'] }} vue(s)</span>
                    <span>{{ $pub['nb_clics'] }} clic(s)</span>
                    @if($pub['date_debut'])
                        <span>Du {{ \Carbon\Carbon::parse($pub['date_debut'])->format('d/m/Y') }}</span>
                    @endif
                    @if($pub['date_fin'])
                        <span>Au {{ \Carbon\Carbon::parse($pub['date_fin'])->format('d/m/Y') }}</span>
                    @endif
                </div>

                @if($pub['visuel_url'])
                    <div style="margin-top:12px;">
                        <img src="{{ $pub['visuel_url'] }}" alt="Visuel" style="max-height:80px; border:2px solid #ccc;">
                    </div>
                @endif
            </div>

            @if(in_array($pub['statut'], ['en_attente','refusee','expiree']))
            <form method="POST" action="{{ route('pro.publicites.destroy', $pub['id_publicite']) }}"
                  onsubmit="return confirm('Supprimer cette publicité ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary btn-sm" style="color:#A4243B;">Supprimer</button>
            </form>
            @endif
        </div>
    </div>
    @empty
        <div class="card" style="text-align:center; padding:48px;">
            <p style="color:#666; margin-bottom:16px;">Vous n'avez pas encore de publicité.</p>
            <a href="{{ route('pro.publicites.create') }}" class="btn-primary">Créer ma première publicité</a>
        </div>
    @endforelse

</div>
@endsection

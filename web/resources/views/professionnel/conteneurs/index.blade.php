@extends('layouts.professionnel')

@section('title', 'Mes commandes en conteneur')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.containers.title">Commandes en conteneur</span></h1>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('pro.conteneurs.historique') }}" class="btn-secondary btn-sm"><span data-i18n="prod.history">Historique</span></a>
            <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm">← Dashboard</a>
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

    {{-- Scanner code-barre --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;"><span data-i18n="prod.containers.validate">Valider une récupération</span></h2>
        <form method="POST" action="{{ route('pro.conteneurs.valider') }}" style="display:flex; gap:16px; align-items:flex-end;">
            @csrf
            <div style="flex:1;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.containers.barcode">Code-barre de récupération</span></label>
                <input type="text" name="code_barre" required placeholder="Saisir ou scanner le code" data-i18n-ph="prod.containers.barcode.ph"
                    style="width:100%; padding:12px; border:3px solid #120309; font-family:'DM Mono',monospace; font-size:1rem; letter-spacing:0.05em;">
                @error('code_barre')<p style="color:#A4243B;font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary"><span data-i18n="prod.containers.validatebtn">Valider la réception</span></button>
        </form>
        <p class="font-mono" style="font-size:0.72rem; color:#888; margin-top:10px;">
Délai de récupération : <strong>7 jours</strong> à compter du dépôt. Passé ce délai, contactez le support.
        </p>
    </div>

    {{-- Liste des commandes en attente --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;"><span data-i18n="prod.containers.pending">En attente de récupération</span></h2>

        @forelse($commandes as $cmd)
        <div style="border:2px solid #120309; padding:20px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="font-bebas" style="font-size:1.2rem;">{{ $cmd['titre_annonce'] }}</div>
                    <div class="font-mono" style="font-size:0.72rem; color:#666; margin-top:4px;">
                        Commande #{{ $cmd['id_commande'] }} · Conteneur {{ $cmd['conteneur_ref'] }}
                    </div>
                    <div class="font-mono" style="font-size:0.72rem; color:#666;">
                        {{ $cmd['adresse_conteneur'] }}
                    </div>
                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($cmd['adresse_conteneur']) }}"
                       target="_blank" rel="noopener"
                       class="font-mono" style="display:inline-block; margin-top:8px; font-size:0.7rem; color:#18607D; text-decoration:underline;">
                        → Itinéraire Google Maps
                    </a>
                </div>
                <div style="text-align:right;">
                    @if(!empty($cmd['date_limite_recuperation']))
                        @php
                            $limite = \Carbon\Carbon::parse($cmd['date_limite_recuperation']);
                            // diffInDays signé (Carbon 3) : > 0 = jours restants, < 0 = déjà dépassé
                            $joursRestants = now()->diffInDays($limite, false);
                            $depasse = $joursRestants < 0;
                            $urgence = !$depasse && $joursRestants <= 2;
                            $alerte  = $depasse || $urgence;
                        @endphp
                        <div class="font-mono" style="font-size:0.72rem; color:{{ $alerte ? '#A4243B' : '#666' }}; font-weight:{{ $alerte ? 'bold' : 'normal' }};">
                            @if($depasse)
                                Délai dépassé le {{ $limite->format('d/m/Y') }}
                            @else
                                Récupérer avant le {{ $limite->format('d/m/Y') }}@if($urgence) — URGENT @endif
                            @endif
                        </div>
                    @endif
                    @if($cmd['code_barre'])
                        <div class="font-mono" style="font-size:0.85rem; margin-top:8px; letter-spacing:0.1em; background:#f5f0e1; padding:6px 12px; border:2px solid #ccc;">
                            {{ $cmd['code_barre'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
            <p style="color:#666; font-style:italic; text-align:center; padding:32px 0;">
                Aucune commande en attente de récupération.
            </p>
        @endforelse
    </div>

</div>
@endsection

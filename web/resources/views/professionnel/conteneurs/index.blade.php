@extends('layouts.professionnel')

@section('title', 'Mes commandes en conteneur')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;">Commandes en conteneur</h1>
        <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm">← Dashboard</a>
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
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;">Valider une récupération</h2>
        <form method="POST" action="{{ route('pro.conteneurs.valider') }}" style="display:flex; gap:16px; align-items:flex-end;">
            @csrf
            <div style="flex:1;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Code-barre de récupération</label>
                <input type="text" name="code_barre" required placeholder="Saisir ou scanner le code"
                    style="width:100%; padding:12px; border:3px solid #120309; font-family:'DM Mono',monospace; font-size:1rem; letter-spacing:0.05em;">
                @error('code_barre')<p style="color:#A4243B;font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-primary">Valider la réception</button>
        </form>
        <p class="font-mono" style="font-size:0.72rem; color:#888; margin-top:10px;">
Délai de récupération : <strong>7 jours</strong> à compter du dépôt. Passé ce délai, contactez le support.
        </p>
    </div>

    {{-- Liste des commandes en attente --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;">En attente de récupération</h2>

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
                </div>
                <div style="text-align:right;">
                    @if($cmd['date_limite'])
                        @php
                            $limite = \Carbon\Carbon::parse($cmd['date_limite']);
                            $urgence = $limite->diffInDays(now()) <= 2;
                        @endphp
                        <div class="font-mono" style="font-size:0.72rem; color:{{ $urgence ? '#A4243B' : '#666' }}; font-weight:{{ $urgence ? 'bold' : 'normal' }};">
                            Récupérer avant le {{ $limite->format('d/m/Y') }}
                            @if($urgence) — URGENT @endif
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

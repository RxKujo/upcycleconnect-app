@extends('layouts.professionnel')

@section('title', 'Mes alertes matériaux')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.alerts.title">Alertes matériaux</span></h1>
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

    {{-- Créer une alerte --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;"><span data-i18n="prod.alerts.new">Nouvelle alerte</span></h2>
        <form method="POST" action="{{ route('pro.alertes.store') }}" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div style="flex:1; min-width:180px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Matériau</label>
                <select name="materiau" required style="width:100%; padding:10px; border:3px solid #120309; font-family:'DM Mono',monospace; font-size:0.85rem; background:white;">
                    <option value="">-- Choisir --</option>
                    @foreach($materiaux as $m)
                        <option value="{{ $m['code'] }}" {{ old('materiau') == $m['code'] ? 'selected' : '' }}>{{ $m['libelle'] }}</option>
                    @endforeach
                </select>
            </div>

            @if(isset($plan) && $plan['rayon_alerte_max_km'] === null)
            <div style="flex:1; min-width:140px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.radius">Rayon (km)</span></label>
                <input type="number" name="rayon_km" min="1" max="500" value="{{ old('rayon_km', 10) }}"
                    style="width:100%; padding:10px; border:3px solid #120309; font-family:'DM Mono',monospace; font-size:0.85rem;">
            </div>
            @else
            <div style="flex:1; min-width:140px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Rayon</label>
                <div style="padding:10px; border:3px solid #ccc; background:#f5f5f5; font-family:'DM Mono',monospace; font-size:0.85rem; color:#666;">
                    10 km (fixe — Essential Pro)
                </div>
            </div>
            @endif

            <button type="submit" class="btn-primary btn-sm"><span data-i18n="prod.alerts.create">Créer l'alerte</span></button>
        </form>
        @error('materiau')<p style="color:#A4243B;font-size:0.8rem;margin-top:6px;">{{ $message }}</p>@enderror
    </div>

    {{-- Liste des alertes --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;"><span data-i18n="prod.alerts.active">Mes alertes actives</span></h2>

        @forelse($alertes as $alerte)
        <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 0; border-bottom:1px solid #ddd;">
            <div>
                <span class="font-bebas" style="font-size:1.2rem; text-transform:capitalize;">{{ $alerte['materiau'] }}</span>
                <span class="font-mono" style="font-size:0.72rem; margin-left:12px; color:#666;">
                    Rayon {{ $alerte['rayon_km'] }} km
                </span>
                @if(!$alerte['est_active'])
                    <span style="margin-left:8px; background:#ccc; color:#333; padding:2px 8px; font-size:0.7rem; font-family:'DM Mono',monospace;">INACTIVE</span>
                @endif
            </div>
            <form method="POST" action="{{ route('pro.alertes.destroy', $alerte['id_alerte']) }}"
                  data-confirm="Supprimer cette alerte ? Cette action est irréversible.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary btn-sm" style="color:#A4243B;"><span data-i18n="btn.delete">Supprimer</span></button>
            </form>
        </div>
        @empty
            <p style="color:#666; font-style:italic;"><span data-i18n="prod.alerts.empty">Aucune alerte configurée.</span></p>
        @endforelse
    </div>

    {{-- Info plan --}}
    <div style="border:3px solid var(--coffee); box-shadow:4px 4px 0 var(--coffee); padding:20px 24px; margin-bottom:32px; background:var(--wheat);">
        @if(isset($plan) && $plan['nb_alertes_max'] === null)
            <span class="font-mono" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.08em; background:var(--forest); color:var(--cream); padding:2px 10px; border:2px solid var(--coffee); display:inline-block; margin-bottom:10px;">Expert Pro</span>
            <p style="font-family:'DM Mono',monospace; font-size:0.82rem; color:var(--coffee);">
                Alertes illimitées — rayon modulable — canal email + push notifications.
            </p>
        @else
            <span class="font-mono" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.08em; background:var(--teal); color:var(--cream); padding:2px 10px; border:2px solid var(--coffee); display:inline-block; margin-bottom:10px;">Essential Pro</span>
            <p style="font-family:'DM Mono',monospace; font-size:0.82rem; color:var(--coffee); margin-bottom:8px;">
                Maximum 3 alertes — rayon fixe 10 km — canal email uniquement.
            </p>
            <p style="font-family:'DM Mono',monospace; font-size:0.78rem;">
                Passez à <a href="{{ route('pro.abonnement.index') }}" style="color:var(--cherry); font-weight:bold;">Expert Pro</a> pour lever ces limites.
            </p>
        @endif
    </div>

</div>

@endsection

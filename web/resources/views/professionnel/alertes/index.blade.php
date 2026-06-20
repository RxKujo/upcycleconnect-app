@extends('layouts.professionnel')

@section('title', 'Mes alertes matériaux')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;">Alertes matériaux</h1>
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
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;">Nouvelle alerte</h2>
        <form method="POST" action="{{ route('pro.alertes.store') }}" style="display:flex; gap:16px; flex-wrap:wrap; align-items:flex-end;">
            @csrf
            <div style="flex:1; min-width:180px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Matériau</label>
                <select name="materiau" required style="width:100%; padding:10px; border:3px solid #120309; font-family:'DM Mono',monospace; font-size:0.85rem; background:white;">
                    <option value="">-- Choisir --</option>
                    @foreach(['bois','metal','textile','plastique','verre','electronique','autre'] as $m)
                        <option value="{{ $m }}" {{ old('materiau') == $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Rayon : visible seulement pour Expert Pro (rayon_alerte_max_km NULL) --}}
            @if(isset($plan) && $plan['rayon_alerte_max_km'] === null)
            <div style="flex:1; min-width:140px;">
                <label class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Rayon (km)</label>
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

            <button type="submit" class="btn-primary btn-sm">Créer l'alerte</button>
        </form>
        @error('materiau')<p style="color:#A4243B;font-size:0.8rem;margin-top:6px;">{{ $message }}</p>@enderror
    </div>

    {{-- Liste des alertes --}}
    <div class="card">
        <h2 class="font-bebas" style="font-size:1.4rem; margin-bottom:20px;">Mes alertes actives</h2>

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
            <form method="POST" action="{{ route('pro.alertes.destroy', $alerte['id_alerte']) }}" onsubmit="return confirm('Supprimer cette alerte ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary btn-sm" style="color:#A4243B;">Supprimer</button>
            </form>
        </div>
        @empty
            <p style="color:#666; font-style:italic;">Aucune alerte configurée.</p>
        @endforelse
    </div>

    {{-- Info plan --}}
    <div class="card" style="background:#f9f5e7; border:2px solid #D8C99B;">
        <p class="font-mono" style="font-size:0.8rem; color:#666;">
            @if(isset($plan) && $plan['nb_alertes_max'] === null)
                ✅ <strong>Expert Pro</strong> — Alertes illimitées, rayon modulable, canal email + push OneSignal.
            @else
                ℹ️ <strong>Essential Pro</strong> — Maximum 3 alertes, rayon fixe 10 km, canal email uniquement.
                <br>Passez à <a href="{{ route('professionnel.abonnement.index') }}" style="color:#A4243B;">Expert Pro</a> pour lever ces limites.
            @endif
        </p>
    </div>

</div>
@endsection

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
            <form method="POST" action="{{ route('pro.alertes.destroy', $alerte['id_alerte']) }}" class="delete-form">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-secondary btn-sm btn-delete" style="color:#A4243B;">Supprimer</button>
            </form>
        </div>
        @empty
            <p style="color:#666; font-style:italic;">Aucune alerte configurée.</p>
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

{{-- Modale de confirmation --}}
<div id="confirm-modal" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.55); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); padding:40px 48px; max-width:420px; width:90%; text-align:center;">
        <p class="font-bebas" style="font-size:1.8rem; letter-spacing:0.08em; margin-bottom:12px;">Supprimer l'alerte ?</p>
        <p style="font-family:'DM Mono',monospace; font-size:0.85rem; color:#666; margin-bottom:32px;">Cette action est irréversible.</p>
        <div style="display:flex; gap:16px; justify-content:center;">
            <button id="confirm-cancel" class="btn-secondary btn-sm">Annuler</button>
            <button id="confirm-ok" class="btn-primary btn-sm" style="background:var(--cherry);">Supprimer</button>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('confirm-modal');
    var pendingForm = null;

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingForm = btn.closest('.delete-form');
            modal.style.display = 'flex';
        });
    });

    document.getElementById('confirm-cancel').addEventListener('click', function () {
        modal.style.display = 'none';
        pendingForm = null;
    });

    document.getElementById('confirm-ok').addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            pendingForm = null;
        }
    });
})();
</script>
@endsection

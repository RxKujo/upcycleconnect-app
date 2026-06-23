@extends('layouts.professionnel')

@section('title', 'Mes publicités')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;">Mes publicités</h1>
        <div style="display:flex; gap:12px;">
            <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm">← Dashboard</a>
            <button type="button" id="btn-open-modal" class="btn-primary btn-sm">Nouvelle publicité</button>
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
    <div style="border:3px solid var(--coffee); box-shadow:4px 4px 0 var(--coffee); padding:20px 24px; margin-bottom:24px; background:var(--wheat);">
        <span class="font-mono" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.08em; background:var(--teal); color:var(--cream); padding:2px 10px; border:2px solid var(--coffee); display:inline-block; margin-bottom:10px;">Tarifs & conditions</span>
        <p style="font-family:'DM Mono',monospace; font-size:0.82rem; color:var(--coffee);">
            <strong>100 €/mois</strong> par publicité — Maximum <strong>5 publicités actives</strong>.<br>
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
            <form method="POST" action="{{ route('pro.publicites.destroy', $pub['id_publicite']) }}" class="delete-form">
                @csrf
                @method('DELETE')
                <button type="button" class="btn-secondary btn-sm btn-delete" style="color:#A4243B;">Supprimer</button>
            </form>
            @endif
        </div>
    </div>
    @empty
        <div class="card" style="text-align:center; padding:48px;">
            <p style="color:#666; margin-bottom:16px;">Vous n'avez pas encore de publicité.</p>
            <button type="button" id="btn-open-modal-empty" class="btn-primary">Créer ma première publicité</button>
        </div>
    @endforelse

</div>

{{-- Modale : nouvelle publicité --}}
<div id="pub-modal" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px; overflow-y:auto;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); padding:40px; max-width:600px; width:100%; position:relative;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;">
            <h2 class="font-bebas" style="font-size:2rem; letter-spacing:0.08em;">Nouvelle publicité</h2>
            <button id="btn-close-modal" style="background:none; border:none; font-size:1.4rem; cursor:pointer; font-family:'DM Mono',monospace; color:var(--coffee);">✕</button>
        </div>

        <form method="POST" action="{{ route('pro.publicites.store') }}">
            @csrf

            <div style="margin-bottom:20px;">
                <label for="pub-titre" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Titre <span style="color:var(--cherry);">*</span></label>
                <input id="pub-titre" type="text" name="titre" value="{{ old('titre') }}" required maxlength="200"
                    style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:1rem; background:white;">
                @error('titre')<p style="color:var(--cherry);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:20px;">
                <label for="pub-visuel" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">URL du visuel (image)</label>
                <input id="pub-visuel" type="url" name="visuel_url" value="{{ old('visuel_url') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:1rem; background:white;">
                @error('visuel_url')<p style="color:var(--cherry);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:20px;">
                <label for="pub-url" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">URL de destination (clic)</label>
                <input id="pub-url" type="url" name="url_cible" value="{{ old('url_cible') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:1rem; background:white;">
                @error('url_cible')<p style="color:var(--cherry);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                <div>
                    <label for="pub-debut" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Date de début</label>
                    <input id="pub-debut" type="date" name="date_debut" value="{{ old('date_debut') }}"
                        style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'DM Mono',monospace; background:white;">
                </div>
                <div>
                    <label for="pub-fin" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;">Date de fin</label>
                    <input id="pub-fin" type="date" name="date_fin" value="{{ old('date_fin') }}"
                        style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'DM Mono',monospace; background:white;">
                </div>
            </div>

            <div style="border:3px solid var(--coffee); padding:14px 18px; margin-bottom:24px; background:var(--wheat); font-family:'DM Mono',monospace; font-size:0.8rem; color:var(--coffee);">
                Tarif : <strong>100 €/mois</strong> — facturation mensuelle via Stripe.<br>
                Votre publicité sera <strong>soumise à validation</strong> avant mise en ligne.
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" id="btn-close-modal-2" class="btn-secondary btn-sm">Annuler</button>
                <button type="submit" class="btn-primary btn-sm">Soumettre</button>
            </div>
        </form>
    </div>
</div>

{{-- Modale de confirmation suppression --}}
<div id="confirm-modal" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.55); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); padding:40px 48px; max-width:420px; width:90%; text-align:center;">
        <p class="font-bebas" style="font-size:1.8rem; letter-spacing:0.08em; margin-bottom:12px;">Supprimer la publicité ?</p>
        <p style="font-family:'DM Mono',monospace; font-size:0.85rem; color:#666; margin-bottom:32px;">Cette action est irréversible.</p>
        <div style="display:flex; gap:16px; justify-content:center;">
            <button id="confirm-cancel" class="btn-secondary btn-sm">Annuler</button>
            <button id="confirm-ok" class="btn-primary btn-sm" style="background:var(--cherry);">Supprimer</button>
        </div>
    </div>
</div>

<script>
(function () {
    var pubModal    = document.getElementById('pub-modal');
    var confirmModal = document.getElementById('confirm-modal');
    var pendingForm = null;

    function openPubModal() { pubModal.style.display = 'flex'; }
    function closePubModal() { pubModal.style.display = 'none'; }

    document.getElementById('btn-open-modal').addEventListener('click', openPubModal);
    document.getElementById('btn-close-modal').addEventListener('click', closePubModal);
    document.getElementById('btn-close-modal-2').addEventListener('click', closePubModal);
    var emptyBtn = document.getElementById('btn-open-modal-empty');
    if (emptyBtn) emptyBtn.addEventListener('click', openPubModal);

    pubModal.addEventListener('click', function (e) {
        if (e.target === pubModal) closePubModal();
    });

    // Rouvre la modale si erreurs de validation
    @if($errors->any() || old('titre'))
    openPubModal();
    @endif

    // Suppression
    document.querySelectorAll('.btn-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingForm = btn.closest('.delete-form');
            confirmModal.style.display = 'flex';
        });
    });

    document.getElementById('confirm-cancel').addEventListener('click', function () {
        confirmModal.style.display = 'none';
        pendingForm = null;
    });

    document.getElementById('confirm-ok').addEventListener('click', function () {
        if (pendingForm) pendingForm.submit();
    });

    confirmModal.addEventListener('click', function (e) {
        if (e.target === confirmModal) {
            confirmModal.style.display = 'none';
            pendingForm = null;
        }
    });
})();
</script>
@endsection

@extends('layouts.professionnel')

@section('title', 'Mes publicités')

@section('content')
<div class="main-content">

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
        <h1 class="font-bebas" style="font-size:2.4rem;"><span data-i18n="prod.myads">Mes publicités</span></h1>
        <div style="display:flex; gap:12px;">
            <a href="{{ route('pro.dashboard.essential') }}" class="btn-secondary btn-sm">← Dashboard</a>
            <button type="button" id="btn-open-modal" class="btn-primary btn-sm"><span data-i18n="prod.ads.new">Nouvelle publicité</span></button>
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
        <span class="font-mono" style="font-size:0.68rem; text-transform:uppercase; letter-spacing:0.08em; background:var(--teal); color:var(--cream); padding:2px 10px; border:2px solid var(--coffee); display:inline-block; margin-bottom:10px;"><span data-i18n="prod.ads.terms">Tarifs & conditions</span></span>
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

                @if($pub['statut'] === 'refusee' && !empty($pub['motif_refus']))
                    <div style="margin-top:12px;background:#f8d7da;border:2px solid #842029;padding:8px 12px;font-size:0.82rem;color:#842029;">
                        <strong><span data-i18n="prod.ads.refusal">Motif du refus :</span></strong> {{ $pub['motif_refus'] }}
                    </div>
                @endif

                @if($pub['visuel_url'])
                    <div style="margin-top:12px;">
                        <img src="{{ $pub['visuel_url'] }}" alt="Visuel" style="max-height:80px; border:2px solid #ccc;">
                    </div>
                @endif
            </div>

            @if(in_array($pub['statut'], ['en_attente','refusee','expiree']))
            <form method="POST" action="{{ route('pro.publicites.destroy', $pub['id_publicite']) }}"
                  data-confirm="Supprimer cette publicité ? Cette action est irréversible.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary btn-sm" style="color:#A4243B;"><span data-i18n="btn.delete">Supprimer</span></button>
            </form>
            @endif
        </div>
    </div>
    @empty
        <div class="card" style="text-align:center; padding:48px;">
            <p style="color:#666; margin-bottom:16px;"><span data-i18n="prod.ads.empty">Vous n'avez pas encore de publicité.</span></p>
            <button type="button" id="btn-open-modal-empty" class="btn-primary"><span data-i18n="prod.ads.createfirst">Créer ma première publicité</span></button>
        </div>
    @endforelse

</div>

{{-- Modale : nouvelle publicité --}}
<div id="pub-modal" style="display:none; position:fixed; inset:0; background:rgba(18,3,9,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px; overflow-y:auto;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); padding:40px; max-width:600px; width:100%; position:relative;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;">
            <h2 class="font-bebas" style="font-size:2rem; letter-spacing:0.08em;"><span data-i18n="prod.ads.new">Nouvelle publicité</span></h2>
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
                <label for="pub-visuel-file" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.visual">Visuel de la publicité</span></label>
                <input id="pub-visuel-file" type="file" accept="image/jpeg,image/png,image/webp"
                    style="width:100%; padding:10px; border:3px solid var(--coffee); font-family:'DM Mono',monospace; font-size:0.82rem; background:white; cursor:pointer; box-sizing:border-box;">
                <div id="pub-visuel-preview" style="margin-top:10px; display:none;">
                    <img id="pub-visuel-preview-img" alt="Aperçu" style="max-height:120px; border:2px solid var(--coffee);">
                </div>
                <p class="font-mono" style="font-size:0.7rem; color:#666; margin:10px 0 4px;" data-i18n="prod.ads.f.orurl">— ou coller l'URL d'une image —</p>
                <input id="pub-visuel" type="url" name="visuel_url" value="{{ old('visuel_url') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:1rem; background:white; box-sizing:border-box;">
                <input type="hidden" name="visuel_base64" id="pub-visuel-base64">
                @error('visuel_url')<p style="color:var(--cherry);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:20px;">
                <label for="pub-url" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.url">URL de destination (clic)</span></label>
                <input id="pub-url" type="url" name="url_cible" value="{{ old('url_cible') }}" maxlength="500"
                    placeholder="https://..."
                    style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'Outfit',sans-serif; font-size:1rem; background:white;">
                @error('url_cible')<p style="color:var(--cherry);font-size:0.8rem;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px;">
                <div>
                    <label for="pub-debut" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.start">Date de début</span></label>
                    <input id="pub-debut" type="date" name="date_debut" value="{{ old('date_debut') }}" min="{{ date('Y-m-d') }}"
                        style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'DM Mono',monospace; background:white;">
                </div>
                <div>
                    <label for="pub-fin" class="font-mono" style="font-size:0.75rem; display:block; margin-bottom:6px;"><span data-i18n="prod.ads.f.end">Date de fin</span></label>
                    <input id="pub-fin" type="date" name="date_fin" value="{{ old('date_fin') }}" min="{{ date('Y-m-d') }}"
                        style="width:100%; padding:12px; border:3px solid var(--coffee); font-family:'DM Mono',monospace; background:white;">
                </div>
            </div>

            <div style="border:3px solid var(--coffee); padding:14px 18px; margin-bottom:24px; background:var(--wheat); font-family:'DM Mono',monospace; font-size:0.8rem; color:var(--coffee); line-height:1.65;">
                Tarif : <strong>100 €/mois</strong> par publicité — facturation via Stripe.<br>
                <strong data-i18n="prod.ads.monthpolicy">Tout mois entamé est dû en entier</strong> (facturation par mois calendaire).<br>
                <span id="pub-cout-estim" style="display:none; margin-top:8px; padding-top:8px; border-top:2px solid rgba(18,3,9,0.15);"></span>
                <span data-i18n="prod.ads.validnote">Votre publicité sera <strong>soumise à validation</strong> avant mise en ligne.</span>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button type="button" id="btn-close-modal-2" class="btn-secondary btn-sm"><span data-i18n="btn.cancel">Annuler</span></button>
                <button type="submit" class="btn-primary btn-sm"><span data-i18n="prod.ads.submit">Soumettre</span></button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var pubModal = document.getElementById('pub-modal');
    if (!pubModal) return;

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

    // Visuel : upload de fichier (lu en base64) OU URL. L'upload a la priorité.
    var fileInput = document.getElementById('pub-visuel-file');
    var b64Input  = document.getElementById('pub-visuel-base64');
    var urlInput  = document.getElementById('pub-visuel');
    var preview   = document.getElementById('pub-visuel-preview');
    var previewImg = document.getElementById('pub-visuel-preview-img');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            var f = fileInput.files && fileInput.files[0];
            if (!f) { b64Input.value = ''; preview.style.display = 'none'; return; }
            if (['image/jpeg', 'image/png', 'image/webp'].indexOf(f.type) === -1) {
                alert('Format non supporté (JPG, PNG ou WEBP).'); fileInput.value = ''; return;
            }
            if (f.size > 5 * 1024 * 1024) {
                alert('Image trop lourde (max 5 Mo).'); fileInput.value = ''; return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                b64Input.value = ev.target.result;
                previewImg.src = ev.target.result;
                preview.style.display = 'block';
                urlInput.value = ''; // l'upload prime sur l'URL
            };
            reader.readAsDataURL(f);
        });
        urlInput.addEventListener('input', function () {
            if (urlInput.value) { fileInput.value = ''; b64Input.value = ''; preview.style.display = 'none'; }
        });
    }

    // Estimation du coût — politique « tout mois entamé = mois payé » (mois calendaires).
    var debutInput = document.getElementById('pub-debut');
    var finInput   = document.getElementById('pub-fin');
    var estimEl    = document.getElementById('pub-cout-estim');
    function moisEntames(d1, d2) {
        var a = d1.split('-'), b = d2.split('-');
        return (parseInt(b[0], 10) - parseInt(a[0], 10)) * 12 + (parseInt(b[1], 10) - parseInt(a[1], 10)) + 1;
    }
    function majEstimation() {
        var d = debutInput.value, f = finInput.value;
        if (d && f) {
            var m = moisEntames(d, f);
            if (m < 1) { estimEl.style.display = 'none'; return; }
            estimEl.innerHTML = 'Estimation : <strong>' + m + (m > 1 ? ' mois entamés' : ' mois entamé') + ' × 100 € = ' + (m * 100) + ' €</strong>';
            estimEl.style.display = 'block';
        } else if (d) {
            estimEl.innerHTML = 'À partir du <strong>' + d.split('-').reverse().join('/') + '</strong> — 100 €/mois (ajoute une date de fin pour l\'estimation).';
            estimEl.style.display = 'block';
        } else {
            estimEl.style.display = 'none';
        }
    }
    if (debutInput && finInput) {
        debutInput.addEventListener('change', majEstimation);
        finInput.addEventListener('change', majEstimation);
        majEstimation();
    }

    // Rouvre la modale si erreurs de validation
    @if($errors->any() || old('titre'))
    openPubModal();
    @endif

    // La suppression utilise la confirmation globale (data-confirm) — voir partials/_toast.
})();
</script>
@endsection

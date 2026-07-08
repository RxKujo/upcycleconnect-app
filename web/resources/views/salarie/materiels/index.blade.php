@extends('layouts.salarie')
@section('title', 'Matériel')
@section('content')
{{-- Libellés et couleurs d'état --}}
@php
    $etatLabels = ['neuf' => 'Neuf', 'bon' => 'Bon état', 'use' => 'Usé', 'a_reparer' => 'À réparer'];
    $etatColors = ['neuf' => 'var(--forest)', 'bon' => 'var(--teal, #2a9d8f)', 'use' => 'var(--wheat, #e0b970)', 'a_reparer' => 'var(--cherry, #b5203b)'];
@endphp

{{-- === En-tête === --}}
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title"><span data-i18n="sal.mat.title">Matériel &amp; inventaire</span></h1>
    @if($monSite)
    <button type="button" class="btn-primary" onclick="document.getElementById('mat-modal').classList.add('active')"><span data-i18n="sal.mat.add">+ Ajouter</span></button>
    @endif
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.8rem; color:#666; margin-bottom:24px; max-width:760px; line-height:1.6;">
    Objets mis à disposition pour les ateliers et conférences. Gérez l'état, les photos et les réservations pour un événement.
    @if($monSite && !empty($monSite['nom']))<br><strong style="color:var(--coffee);">Inventaire du site : {{ $monSite['nom'] }}</strong>@endif
</p>

@if(session('success'))<div class="badge badge-forest" style="display:block; margin-bottom:16px; padding:12px 20px; background:var(--forest); color:#fff;">{{ session('success') }}</div>@endif
@if(session('error'))<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ session('error') }}</div>@endif
@if($errors->any())<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ $errors->first() }}</div>@endif

{{-- === Aucun site rattaché === --}}
@if(!$monSite)
    <div style="border:3px solid var(--cherry,#b5203b); background:#fdecef; padding:28px 32px; box-shadow:5px 5px 0 var(--coffee); max-width:780px; display:flex; gap:18px; align-items:flex-start;">
        <svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="var(--cherry,#b5203b)" viewBox="0 0 16 16" style="flex-shrink:0;" aria-hidden="true"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
        <div>
            <strong style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; letter-spacing:0.04em; display:block; margin-bottom:6px;">Aucun site rattaché</strong>
            <p style="margin:0; font-family:'DM Mono',monospace; font-size:0.8rem; line-height:1.7; color:#333;">
                Vous n'êtes rattaché à aucune antenne. Un administrateur doit vous affecter à un site
                pour que vous puissiez consulter et gérer un inventaire.
            </p>
        </div>
    </div>
@else

{{-- === Grille (onglets) ou état vide === --}}
@if(empty($materiels))
    <div style="border:2px dashed rgba(18,3,9,0.2); padding:48px; text-align:center;">
        <p style="opacity:0.6;"><span data-i18n="sal.mat.empty">Aucun matériel pour le moment. Cliquez sur « Ajouter ».</span></p>
    </div>
@else
@php
    $nbDispo    = collect($materiels)->where('est_reserve', false)->count();
    $nbReserves = collect($materiels)->where('est_reserve', true)->count();
@endphp
<div style="display:flex; gap:10px; margin-bottom:22px; flex-wrap:wrap;">
    <button type="button" id="tab-dispo" class="mat-tab active" onclick="setMatMode('dispo')"><span data-i18n="sal.mat.tab.available">Disponibles</span> ({{ $nbDispo }})</button>
    <button type="button" id="tab-reserves" class="mat-tab" onclick="setMatMode('reserves')"><span data-i18n="sal.mat.tab.reserved">Réservés</span> ({{ $nbReserves }})</button>
</div>
<div id="mat-grid" class="mode-dispo" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px;">
    @foreach($materiels as $m)
    <div class="mat-card" data-reserve="{{ $m['est_reserve'] ? '1' : '0' }}" style="border:3px solid var(--coffee); background:var(--cream); box-shadow:4px 4px 0 var(--coffee); display:flex; flex-direction:column;">
        @if(!empty($m['photos']))
        <div style="height:150px; background:#eee center/cover no-repeat; border-bottom:3px solid var(--coffee); background-image:url('{{ media_url($m['photos'][0]['url_photo']) }}');"></div>
        @else
        <div style="height:150px; background:var(--wheat,#e0d3a8); border-bottom:3px solid var(--coffee); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:6px; color:var(--coffee); opacity:0.5;">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5 8 5.961 14.154 3.5 8.186 1.113zM15 4.239l-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/></svg>
            <span style="font-family:'DM Mono',monospace; font-size:0.6rem; text-transform:uppercase; letter-spacing:0.05em;">Pas de photo</span>
        </div>
        @endif
        <div style="padding:14px 16px; flex:1; display:flex; flex-direction:column; gap:10px;">
            <div style="display:flex; justify-content:space-between; align-items:start; gap:8px;">
                <strong style="font-family:'Bebas Neue',sans-serif; font-size:1.3rem; line-height:1.1;">{{ $m['nom'] }}</strong>
                <span style="flex-shrink:0; font-family:'DM Mono',monospace; font-size:0.62rem; text-transform:uppercase; font-weight:bold; padding:3px 8px; border:2px solid var(--coffee); background:{{ $etatColors[$m['etat']] ?? '#ccc' }}; color:#fff;">{{ $etatLabels[$m['etat']] ?? $m['etat'] }}</span>
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; font-family:'DM Mono',monospace; font-size:0.62rem; text-transform:uppercase;">
                @if($m['est_reserve'])
                    <span style="padding:3px 8px; border:2px solid var(--coffee); background:var(--cherry,#b5203b); color:#fff;">Réservé@if(!empty($m['reservation']['titre_evenement'])) · {{ $m['reservation']['titre_evenement'] }}@endif</span>
                @elseif(!$m['est_disponible'])
                    <span style="padding:3px 8px; border:2px solid var(--coffee); background:#888; color:#fff;">Indisponible</span>
                @else
                    <span style="padding:3px 8px; border:2px solid var(--coffee); background:var(--forest); color:#fff;">Disponible</span>
                @endif
            </div>
            <div style="margin-top:auto; display:flex; gap:8px;">
                <a href="{{ route('salarie.materiels.show', $m['id_materiel']) }}" class="btn-secondary" style="flex:1; min-width:0; text-align:center; padding:8px 10px; font-size:0.95rem;">Gérer</a>
                @if($m['est_reserve'])
                    <form method="POST" action="{{ route('salarie.materiels.retour', $m['id_materiel']) }}" style="flex:1; min-width:0;">@csrf
                        <button type="submit" class="btn-primary" style="width:100%; padding:8px 10px; font-size:0.95rem;"><span data-i18n="sal.mat.return">Retour</span></button>
                    </form>
                @else
                    <a href="{{ route('salarie.materiels.show', $m['id_materiel']) }}" class="btn-primary" style="flex:1; min-width:0; text-align:center; padding:8px 10px; font-size:0.95rem;"><span data-i18n="sal.mat.reserve">Réserver</span></a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
<div id="mat-empty-dispo" class="mat-empty" style="display:none; border:2px dashed rgba(18,3,9,0.2); padding:40px; text-align:center; opacity:0.6; font-family:'DM Mono',monospace; font-size:0.8rem;">Aucun matériel disponible pour le moment.</div>
<div id="mat-empty-reserves" class="mat-empty" style="display:none; border:2px dashed rgba(18,3,9,0.2); padding:40px; text-align:center; opacity:0.6; font-family:'DM Mono',monospace; font-size:0.8rem;">Aucun matériel réservé pour l'instant.</div>
@endif
@endif

{{-- === Modale : ajout (photos en base64) === --}}
<div id="mat-modal" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="document.getElementById('mat-modal').classList.remove('active')">&times;</button>
        <h3><span data-i18n="sal.mat.addmodal">Ajouter un matériel</span></h3>
        <p class="modal-sub">Nouvel objet d'inventaire</p>
        <form method="POST" action="{{ route('salarie.materiels.store') }}" onsubmit="return prepareMatImages(this)">
            @csrf
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom" required maxlength="200" placeholder="Ex : Perceuse Bosch">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" maxlength="2000" placeholder="Caractéristiques, accessoires…"></textarea>
            </div>
            <div class="form-group">
                <label>État *</label>
                <select name="etat" required>
                    <option value="neuf">Neuf</option>
                    <option value="bon" selected>Bon état</option>
                    <option value="use">Usé</option>
                    <option value="a_reparer">À réparer</option>
                </select>
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="est_disponible" value="1" checked> Disponible</label>
            </div>
            @if(!empty($sites))
            <div class="form-group">
                <label>Site de rattachement</label>
                <select name="id_site">
                    <option value="">Automatique (mon site)</option>
                    @foreach($sites as $s)
                        <option value="{{ $s['id_site'] }}">{{ $s['nom_site'] }}@if(!empty($s['ville'])) — {{ $s['ville'] }}@endif</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group">
                <label>Photos</label>
                <label for="mat-photos" id="mat-photos-drop" style="display:flex; flex-direction:column; align-items:center; gap:6px; border:3px dashed var(--coffee); background:var(--wheat,#e0d3a8); padding:18px; text-align:center; cursor:pointer; font-family:'DM Mono',monospace; font-size:0.74rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--coffee);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M.002 3a2 2 0 0 1 2-2h3.5a2 2 0 0 1 1.6.8l.7.933a.5.5 0 0 0 .4.2H13a2 2 0 0 1 2 2H2a2 2 0 0 0-2 2z"/><path d="M1.002 5.5A1.5 1.5 0 0 1 2.5 4h11A1.5 1.5 0 0 1 15 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 12.5z"/></svg>
                    <span id="mat-photos-label">Cliquez pour choisir des images</span>
                </label>
                <input type="file" id="mat-photos" accept="image/*" multiple style="display:none;"
                    onchange="document.getElementById('mat-photos-label').textContent = this.files.length ? this.files.length + ' image(s) sélectionnée(s)' : 'Cliquez pour choisir des images';">
                <div id="mat-photos-hidden"></div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; margin-top:8px; font-size:1.05rem;"><span data-i18n="btn.add">Ajouter</span></button>
        </form>
    </div>
</div>

{{-- === Styles === --}}
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:3px solid var(--coffee); box-shadow:8px 8px 0 var(--coffee); width:100%; max-width:520px; padding:32px 34px; position:relative; }
    .modal-box h3 { margin:0 0 4px; font-size:1.9rem; font-family:'Bebas Neue',sans-serif; letter-spacing:0.05em; text-transform:uppercase; }
    .modal-box .modal-sub { font-family:'DM Mono',monospace; font-size:0.7rem; color:#888; text-transform:uppercase; letter-spacing:0.05em; margin:0 0 24px; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-box label { display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px; }
    .modal-box input[type=text], .modal-box textarea, .modal-box select { width:100%; border:3px solid var(--coffee); padding:10px 12px; font-family:'Outfit',sans-serif; box-sizing:border-box; background:#fff; }

    /* Onglets */
    .mat-tab { font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.05em; font-weight:bold; padding:10px 18px; border:3px solid var(--coffee); background:var(--cream); color:var(--coffee); cursor:pointer; box-shadow:3px 3px 0 var(--coffee); }
    .mat-tab.active { background:var(--coffee); color:var(--cream); box-shadow:none; transform:translate(3px,3px); }
    #mat-grid.mode-dispo .mat-card[data-reserve="1"] { display:none !important; }
    #mat-grid.mode-reserves .mat-card[data-reserve="0"] { display:none !important; }
</style>

{{-- === Scripts : images base64 + onglets === --}}
<script>
// Convertit les images en data URLs base64 (image_base64[]) avant envoi
function prepareMatImages(form) {
    var input = document.getElementById('mat-photos');
    if (!input || !input.files || input.files.length === 0) return true;
    if (form.dataset.ready === '1') return true;
    var container = document.getElementById('mat-photos-hidden');
    container.innerHTML = '';
    var files = Array.prototype.slice.call(input.files);
    var pending = files.length;
    files.forEach(function (f) {
        var reader = new FileReader();
        reader.onload = function () {
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = 'image_base64[]'; h.value = reader.result;
            container.appendChild(h);
            if (--pending === 0) { form.dataset.ready = '1'; form.submit(); }
        };
        reader.readAsDataURL(f);
    });
    return false; // on soumet après lecture des fichiers
}

// Bascule Disponibles / Réservés (réservés masqués de la grille par défaut)
function setMatMode(mode) {
    var grid = document.getElementById('mat-grid');
    if (!grid) return;
    grid.className = 'mode-' + mode;
    var tabD = document.getElementById('tab-dispo');
    var tabR = document.getElementById('tab-reserves');
    if (tabD) tabD.classList.toggle('active', mode === 'dispo');
    if (tabR) tabR.classList.toggle('active', mode === 'reserves');
    // État vide selon les cartes visibles
    var visible = grid.querySelectorAll('.mat-card[data-reserve="' + (mode === 'dispo' ? '0' : '1') + '"]').length;
    var emptyD = document.getElementById('mat-empty-dispo');
    var emptyR = document.getElementById('mat-empty-reserves');
    if (emptyD) emptyD.style.display = (mode === 'dispo' && visible === 0) ? 'block' : 'none';
    if (emptyR) emptyR.style.display = (mode === 'reserves' && visible === 0) ? 'block' : 'none';
    grid.style.display = visible === 0 ? 'none' : 'grid';
}
document.addEventListener('DOMContentLoaded', function () { setMatMode('dispo'); });
</script>
@endsection

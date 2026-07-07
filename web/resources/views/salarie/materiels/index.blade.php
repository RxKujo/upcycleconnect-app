@extends('layouts.salarie')
@section('title', 'Matériel')

@section('content')
@php
    $etatLabels = ['neuf' => 'Neuf', 'bon' => 'Bon état', 'use' => 'Usé', 'a_reparer' => 'À réparer'];
    $etatColors = ['neuf' => 'var(--forest)', 'bon' => 'var(--teal, #2a9d8f)', 'use' => 'var(--wheat, #e0b970)', 'a_reparer' => 'var(--cherry, #b5203b)'];
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Matériel &amp; inventaire</h1>
    <button type="button" class="btn-primary" onclick="document.getElementById('mat-modal').classList.add('active')">+ Ajouter</button>
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.8rem; color:#666; margin-bottom:24px; max-width:760px; line-height:1.6;">
    Objets mis à disposition pour les ateliers et conférences. Gérez l'état, les photos et les réservations pour un événement.
</p>

@if(session('success'))<div class="badge badge-forest" style="display:block; margin-bottom:16px; padding:12px 20px; background:var(--forest); color:#fff;">{{ session('success') }}</div>@endif
@if(session('error'))<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ session('error') }}</div>@endif
@if($errors->any())<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ $errors->first() }}</div>@endif

@if(empty($materiels))
    <div style="border:2px dashed rgba(18,3,9,0.2); padding:48px; text-align:center;">
        <p style="opacity:0.6;">Aucun matériel pour le moment. Cliquez sur « Ajouter ».</p>
    </div>
@else
<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:20px;">
    @foreach($materiels as $m)
    <div style="border:3px solid var(--coffee); background:var(--cream); box-shadow:4px 4px 0 var(--coffee); display:flex; flex-direction:column;">
        <div style="height:150px; background:#eee center/cover no-repeat; border-bottom:3px solid var(--coffee);
            @if(!empty($m['photos'])) background-image:url('{{ media_url($m['photos'][0]['url_photo']) }}'); @endif">
        </div>
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
                <a href="{{ route('salarie.materiels.show', $m['id_materiel']) }}" class="btn-secondary" style="flex:1; text-align:center; padding:8px;">Gérer</a>
                @if($m['est_reserve'])
                    <form method="POST" action="{{ route('salarie.materiels.retour', $m['id_materiel']) }}" style="flex:1;">@csrf
                        <button type="submit" class="btn-primary" style="width:100%; padding:8px;">Retour</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('salarie.materiels.reserver', $m['id_materiel']) }}" style="flex:1;">@csrf
                        <button type="submit" class="btn-primary" style="width:100%; padding:8px;">Réserver</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Modal d'ajout --}}
<div id="mat-modal" class="modal-overlay">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="document.getElementById('mat-modal').classList.remove('active')">&times;</button>
        <h3>Ajouter un matériel</h3>
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
            <div class="form-group">
                <label>Photos</label>
                <input type="file" id="mat-photos" accept="image/*" multiple>
                <div id="mat-photos-hidden"></div>
            </div>
            <button type="submit" class="btn-primary" style="width:100%; margin-top:8px;">Ajouter</button>
        </form>
    </div>
</div>

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:3px solid var(--coffee); box-shadow:6px 6px 0 var(--coffee); width:100%; max-width:520px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 4px; font-size:1.3rem; font-family:'Bebas Neue',sans-serif; }
    .modal-box .modal-sub { font-family:'DM Mono',monospace; font-size:0.7rem; color:#888; text-transform:uppercase; margin:0 0 20px; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-box label { display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px; }
    .modal-box input[type=text], .modal-box textarea, .modal-box select { width:100%; border:3px solid var(--coffee); padding:10px 12px; font-family:'Outfit',sans-serif; box-sizing:border-box; background:#fff; }
</style>

<script>
// Convertit les fichiers image sélectionnés en data URLs base64 (champs cachés
// image_base64[]) avant l'envoi — l'API les pousse ensuite sur S3.
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
</script>
@endsection

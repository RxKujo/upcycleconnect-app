@extends('layouts.salarie')
@section('title', $materiel['nom'])

@section('content')
@php
    $etatLabels = ['neuf' => 'Neuf', 'bon' => 'Bon état', 'use' => 'Usé', 'a_reparer' => 'À réparer'];
@endphp

<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">{{ $materiel['nom'] }}</h1>
    <a href="{{ route('salarie.materiels.index') }}" class="btn-secondary">← Inventaire</a>
</div>

@if(session('success'))<div class="badge" style="display:block; margin-bottom:16px; padding:12px 20px; background:var(--forest); color:#fff;">{{ session('success') }}</div>@endif
@if(session('error'))<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ session('error') }}</div>@endif
@if($errors->any())<div class="badge badge-cherry" style="display:block; margin-bottom:16px; padding:12px 20px;">{{ $errors->first() }}</div>@endif

<div style="display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:start;">

    {{-- Colonne gauche : photos + réservation --}}
    <div>
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.4rem; margin-bottom:12px;"><span data-i18n="sal.mat.photos">Photos</span></h3>
        @if(empty($materiel['photos']))
            <p style="opacity:0.55; font-size:0.9rem;">Aucune photo.</p>
        @else
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px;">
            @foreach($materiel['photos'] as $ph)
            <div style="position:relative; border:3px solid var(--coffee);">
                <img src="{{ media_url($ph['url_photo']) }}" alt="" style="width:100%; height:110px; object-fit:cover; display:block;">
                <form method="POST" action="{{ route('salarie.materiels.photos.delete', [$materiel['id_materiel'], $ph['id_photo']]) }}" style="position:absolute; top:4px; right:4px;">
                    @csrf @method('DELETE')
                    <button type="submit" title="Supprimer" style="border:2px solid var(--coffee); background:var(--cherry,#b5203b); color:#fff; cursor:pointer; width:26px; height:26px; line-height:1;">&times;</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif

        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.4rem; margin:28px 0 12px;"><span data-i18n="sal.mat.reservation">Réservation</span></h3>
        @if($materiel['est_reserve'])
            <div style="border:3px solid var(--coffee); background:var(--wheat,#f0e0c0); padding:16px 18px;">
                <p style="margin:0 0 6px;"><strong>Actuellement réservé</strong>
                @if(!empty($materiel['reservation']['titre_evenement'])) pour « {{ $materiel['reservation']['titre_evenement'] }} »@endif.</p>
                <form method="POST" action="{{ route('salarie.materiels.retour', $materiel['id_materiel']) }}">@csrf
                    <button type="submit" class="btn-primary" style="margin-top:8px;">Enregistrer le retour</button>
                </form>
            </div>
        @else
            <form method="POST" action="{{ route('salarie.materiels.reserver', $materiel['id_materiel']) }}" style="border:3px solid var(--coffee); padding:16px 18px;">@csrf
                <label style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px;">Réserver pour un événement (optionnel)</label>
                <select name="id_evenement" style="width:100%; border:3px solid var(--coffee); padding:10px 12px; box-sizing:border-box; background:#fff; margin-bottom:12px;">
                    <option value="">— Sans événement —</option>
                    @foreach($evenements as $ev)
                        <option value="{{ $ev['id_evenement'] ?? '' }}">{{ $ev['titre'] ?? ('Événement #'.($ev['id_evenement'] ?? '')) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">Réserver</button>
            </form>
        @endif
    </div>

    {{-- Colonne droite : édition --}}
    <div>
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.4rem; margin-bottom:12px;"><span data-i18n="prof.myinfo">Informations</span></h3>
        <form method="POST" action="{{ route('salarie.materiels.update', $materiel['id_materiel']) }}" onsubmit="return prepareMatImages(this)"
              style="border:3px solid var(--coffee); padding:20px 22px; display:flex; flex-direction:column; gap:16px;">
            @csrf @method('PUT')
            <div>
                <label style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px;">Nom *</label>
                <input type="text" name="nom" required maxlength="200" value="{{ $materiel['nom'] }}" style="width:100%; border:3px solid var(--coffee); padding:10px 12px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px;">Description</label>
                <textarea name="description" rows="3" maxlength="2000" style="width:100%; border:3px solid var(--coffee); padding:10px 12px; box-sizing:border-box;">{{ $materiel['description'] }}</textarea>
            </div>
            <div>
                <label style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px;">État *</label>
                <select name="etat" required style="width:100%; border:3px solid var(--coffee); padding:10px 12px; box-sizing:border-box; background:#fff;">
                    @foreach($etatLabels as $val => $lab)
                        <option value="{{ $val }}" @selected($materiel['etat'] === $val)>{{ $lab }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold;">
                    <input type="checkbox" name="est_disponible" value="1" @checked($materiel['est_disponible'])> Disponible
                </label>
            </div>
            <div>
                <label style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; font-weight:bold; margin-bottom:6px;">Ajouter des photos</label>
                <input type="file" id="mat-photos" accept="image/*" multiple>
                <div id="mat-photos-hidden"></div>
            </div>
            <button type="submit" class="btn-primary">Enregistrer</button>
        </form>

        <form method="POST" action="{{ route('salarie.materiels.destroy', $materiel['id_materiel']) }}" style="margin-top:16px;"
              onsubmit="return confirm('Supprimer définitivement ce matériel ?');">
            @csrf @method('DELETE')
            <button type="submit" style="border:3px solid var(--cherry,#b5203b); background:transparent; color:var(--cherry,#b5203b); padding:10px 18px; font-family:'DM Mono',monospace; text-transform:uppercase; font-size:0.75rem; font-weight:bold; cursor:pointer;">Supprimer ce matériel</button>
        </form>
    </div>
</div>

<script>
function prepareMatImages(form) {
    var input = form.querySelector('#mat-photos');
    if (!input || !input.files || input.files.length === 0) return true;
    if (form.dataset.ready === '1') return true;
    var container = form.querySelector('#mat-photos-hidden');
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
    return false;
}
</script>
@endsection

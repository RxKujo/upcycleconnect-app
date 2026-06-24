@extends('layouts.admin')
@section('title', 'Gestion Conteneur')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h1 class="page-title">Conteneur : {{ $conteneur['conteneur_ref'] }}</h1>
    <div style="display:flex; gap:10px;">
        <button type="button" class="btn-primary" onclick="openEditModal()">Modifier</button>
        <a href="{{ route('admin.conteneurs.index') }}" class="btn-secondary">Retour</a>
    </div>
</div>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:640px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 20px; font-size:1.3rem; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 18px; }
    .modal-box .form-group { margin-bottom:0; }
    .modal-box .form-label { font-size:0.72rem; margin-bottom:5px; }
    .modal-box .form-input, .modal-box .form-select { font-size:0.95rem; padding:9px 12px; box-shadow:2px 2px 0px rgba(18,3,9,0.1); }
    .modal-box .form-input:focus { transform:none; box-shadow:3px 3px 0px rgba(164,36,59,0.2); }
    .modal-box .full { grid-column:1 / -1; }
    .autocomplete-wrapper { position:relative; }
    .autocomplete-dropdown { display:none; position:absolute; top:100%; left:0; right:0; z-index:50; background:var(--cream); border:3px solid var(--coffee); border-top:none; max-height:240px; overflow-y:auto; box-shadow:var(--shadow-sm); }
    .autocomplete-item { padding:10px 14px; cursor:pointer; font-family:'DM Mono',monospace; font-size:0.85rem; border-bottom:1px solid rgba(18,3,9,0.1); }
    .autocomplete-item:last-child { border-bottom:none; }
    .autocomplete-item:hover { background:var(--wheat); }
    .photo-previews { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    .photo-preview { position:relative; width:80px; height:60px; border:2px solid var(--coffee); }
    .photo-preview img { width:100%; height:100%; object-fit:cover; display:block; }
    .photo-preview .remove-photo { position:absolute; top:-8px; right:-8px; background:var(--cherry); color:var(--cream); border:1px solid var(--coffee); width:20px; height:20px; font-size:0.8rem; cursor:pointer; line-height:1; display:flex; align-items:center; justify-content:center; padding:0; }
</style>

<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeEditModal()">&times;</button>
        <h3>Modifier le conteneur</h3>
        <form action="{{ route('admin.conteneurs.update', $conteneur['id_conteneur']) }}" method="POST" id="editForm">
            @csrf
            @method('PUT')
            <div class="info-grid">
                <div class="form-group">
                    <label class="form-label" for="e_ref">Référence / Nom</label>
                    <input type="text" name="conteneur_ref" id="e_ref" class="form-input" value="{{ $conteneur['conteneur_ref'] }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="e_capacite">Capacité (objets)</label>
                    <input type="number" name="capacite" id="e_capacite" class="form-input" value="{{ $conteneur['capacite'] }}" required>
                </div>

                <div class="form-group full">
                    <label class="form-label" for="e_adresseSearch">Adresse (recherche)</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="e_adresseSearch" class="form-input" placeholder="Rechercher pour modifier l'adresse…" autocomplete="off">
                        <div class="autocomplete-dropdown" id="e_adresseSuggestions"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="e_adresse">Adresse</label>
                    <input type="text" name="adresse" id="e_adresse" class="form-input" value="{{ $conteneur['adresse'] }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="e_ville">Ville</label>
                    <input type="text" name="ville" id="e_ville" class="form-input" value="{{ $conteneur['ville'] }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="e_cp">Code postal</label>
                    <input type="text" name="code_postal" id="e_cp" class="form-input" value="{{ $conteneur['code_postal'] ?? '' }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="e_statut">Statut</label>
                    <select name="statut" id="e_statut" class="form-select form-input">
                        @foreach(['actif','plein','maintenance','hors_service'] as $s)
                            <option value="{{ $s }}" {{ ($conteneur['statut'] ?? '') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group full">
                    <label class="form-label" for="e_imageInput">Ajouter des photos (les photos existantes se gèrent ci-dessous)</label>
                    <input type="file" id="e_imageInput" class="form-input" accept="image/jpeg,image/png,image/webp" multiple>
                    <div id="e_previews" class="photo-previews"></div>
                    <div id="e_hiddenImages"></div>
                </div>

                <input type="hidden" name="latitude"  id="e_latitude"  value="{{ $conteneur['latitude'] ?? '' }}">
                <input type="hidden" name="longitude" id="e_longitude" value="{{ $conteneur['longitude'] ?? '' }}">
            </div>
            <button type="submit" class="btn-primary" style="margin-top:16px;">Enregistrer</button>
        </form>
    </div>
</div>

<div class="info-grid" style="margin-bottom: 30px;">
    <div class="card">
        <span class="info-label">Détails du conteneur</span>
        <p class="info-value"><strong>Adresse :</strong> {{ $conteneur['adresse'] }}, {{ $conteneur['ville'] }}</p>
        <p class="info-value"><strong>Capacité :</strong> {{ $conteneur['capacite'] }} objets</p>
        <p class="info-value"><strong>Statut :</strong> <span class="badge badge-waiting">{{ $conteneur['statut'] }}</span></p>
        @php
            $dest = (!empty($conteneur['latitude']) && !empty($conteneur['longitude']))
                ? $conteneur['latitude'] . ',' . $conteneur['longitude']
                : $conteneur['adresse'] . ', ' . $conteneur['ville'];
        @endphp
        <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode($dest) }}"
           target="_blank" rel="noopener" class="btn-secondary btn-sm" style="margin-top:10px;">Itinéraire Google Maps</a>
    </div>

    <div class="card">
        <h3 style="margin-top:0;">Scanner Code Barre</h3>
        <p>Utilisez ce champ pour lire le code avec une douchette ou copier-coller manuellement.</p>
        <form action="{{ route('admin.conteneurs.scan', $conteneur['id_conteneur']) }}" method="POST" style="display:flex; gap:10px;">
            @csrf
            <input type="text" name="code_valeur" class="form-input" placeholder="UC-XXXXXX..." required autofocus autocomplete="off">
            <button type="submit" class="btn-primary">Valider</button>
        </form>
    </div>
</div>

<h2>Photos du conteneur</h2>
<div class="card" style="margin-bottom:30px;">
    @if(empty($photos))
        <p style="color:#888; font-style:italic;">Aucune photo. Utilisez « Modifier » pour en ajouter.</p>
    @else
        <div style="display:flex; gap:16px; flex-wrap:wrap;">
            @foreach($photos as $photo)
            <div style="position:relative; width:160px;">
                <img src="/uploads/{{ $photo['url_photo'] }}" alt="Conteneur {{ $conteneur['conteneur_ref'] }}"
                     style="width:160px; height:120px; object-fit:cover; border:3px solid var(--coffee);">
                <form action="{{ route('admin.conteneurs.photos.delete', [$conteneur['id_conteneur'], $photo['id_photo']]) }}"
                      method="POST" data-confirm="Supprimer cette photo ?" style="margin-top:6px;">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="url_photo" value="{{ $photo['url_photo'] }}">
                    <button type="submit" class="btn-danger btn-sm" style="width:100%;">Supprimer</button>
                </form>
            </div>
            @endforeach
        </div>
    @endif
</div>

<h2>Commandes Associées</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID Cmd</th>
                <th>Acheteur</th>
                <th>Statut</th>
                <th>Générer Code (Dépôt)</th>
                <th>Générer Code (Récup)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commandes as $cmd)
            <tr>
                <td>CMD-{{ $cmd['id_commande'] }}</td>
                <td>Usr #{{ $cmd['id_acheteur'] }}</td>
                <td>
                    @if(in_array($cmd['statut'], ['recuperee']))
                        <span class="badge badge-valid">{{ $cmd['statut'] }}</span>
                    @elseif(in_array($cmd['statut'], ['deposee', 'en_conteneur']))
                        <span class="badge badge-waiting">{{ $cmd['statut'] }}</span>
                    @else
                        <span class="badge" style="background:#eee;color:#333;">{{ $cmd['statut'] }}</span>
                    @endif
                </td>
                <td>
                    <a href="{{ route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'depot_particulier']) }}" class="btn-secondary btn-sm" target="_blank">Dépôt PDF</a>
                </td>
                <td>
                    <a href="{{ route('admin.commandes.barcode.pdf', ['idCommande' => $cmd['id_commande'], 'type_code' => 'recuperation_pro']) }}" class="btn-secondary btn-sm" target="_blank">Récup PDF</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucune commande dans ce conteneur.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<h2>Tickets Incidents</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Sujet</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $tck)
            <tr>
                <td>{{ substr($tck['date_creation'], 0, 10) }}</td>
                <td>{{ $tck['sujet'] }}</td>
                <td>{{ Str::limit($tck['description'], 50) }}</td>
                <td>
                    @if($tck['statut'] == 'resolu')
                        <span class="badge badge-valid">{{ $tck['statut'] }}</span>
                    @else
                        <span class="badge badge-refused">{{ $tck['statut'] }}</span>
                    @endif
                </td>
                <td>
                    @if($tck['statut'] != 'resolu')
                    <form action="{{ route('admin.conteneurs.tickets.resolve', [$conteneur['id_conteneur'], $tck['id_ticket']]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn-success btn-sm">Marquer Résolu</button>
                    </form>
                    @else
                    -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 24px;">Aucun ticket incident pour ce conteneur.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    // === Modale d'édition ===
    function openEditModal() { document.getElementById('editModal').classList.add('active'); }
    function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }
    document.getElementById('editModal').addEventListener('mousedown', (e) => {
        if (e.target.id === 'editModal') closeEditModal();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeEditModal(); });
    @if($errors->any()) openEditModal(); @endif

    // === Autocomplétion adresse ===
    const eSearch = document.getElementById('e_adresseSearch');
    const eSugg   = document.getElementById('e_adresseSuggestions');
    let eTimer = null;
    const eClose = () => { eSugg.style.display = 'none'; eSugg.innerHTML = ''; };

    eSearch.addEventListener('input', () => {
        const value = eSearch.value.trim();
        clearTimeout(eTimer);
        if (value.length < 3) { eClose(); return; }
        eTimer = setTimeout(async () => {
            try {
                const res = await fetch(`https://data.geopf.fr/geocodage/search/?q=${encodeURIComponent(value)}&limit=5`);
                if (!res.ok) throw new Error();
                const data = await res.json();
                const features = data.features || [];
                if (!features.length) { eClose(); return; }
                eSugg.innerHTML = '';
                features.forEach(feature => {
                    const props = feature.properties;
                    const coords = (feature.geometry && feature.geometry.coordinates) || [];
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = props.label;
                    item.addEventListener('mousedown', (ev) => {
                        ev.preventDefault();
                        eSearch.value = '';
                        document.getElementById('e_adresse').value   = props.name     || props.label || '';
                        document.getElementById('e_ville').value      = props.city     || '';
                        document.getElementById('e_cp').value         = props.postcode || '';
                        document.getElementById('e_longitude').value  = coords[0] ?? '';
                        document.getElementById('e_latitude').value   = coords[1] ?? '';
                        eClose();
                    });
                    eSugg.appendChild(item);
                });
                eSugg.style.display = 'block';
            } catch { eClose(); }
        }, 300);
    });
    eSearch.addEventListener('blur', () => setTimeout(eClose, 150));

    // === Photos multiples à ajouter (base64) + aperçus supprimables ===
    const eImg = document.getElementById('e_imageInput');
    const ePreviews = document.getElementById('e_previews');
    const eHidden = document.getElementById('e_hiddenImages');
    let ePhotos = [];

    function eRender() {
        ePreviews.innerHTML = '';
        eHidden.innerHTML = '';
        ePhotos.forEach((src, i) => {
            const cell = document.createElement('div');
            cell.className = 'photo-preview';
            cell.innerHTML = `<img src="${src}" alt="Aperçu ${i + 1}"><button type="button" class="remove-photo" title="Retirer">&times;</button>`;
            cell.querySelector('.remove-photo').addEventListener('click', () => { ePhotos.splice(i, 1); eRender(); });
            ePreviews.appendChild(cell);
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'image_base64[]';
            input.value = src;
            eHidden.appendChild(input);
        });
    }

    eImg.addEventListener('change', () => {
        Array.from(eImg.files).forEach(file => {
            if (file.size > 5 * 1024 * 1024) { alert('Photo trop lourde (max 5 Mo) : ' + file.name); return; }
            const reader = new FileReader();
            reader.onload = (e) => { ePhotos.push(e.target.result); eRender(); };
            reader.readAsDataURL(file);
        });
        eImg.value = '';
    });
</script>
@endsection

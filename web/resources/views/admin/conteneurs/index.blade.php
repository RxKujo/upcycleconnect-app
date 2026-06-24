@extends('layouts.admin')
@section('title', 'Conteneurs')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Conteneurs</h1>
    <button type="button" class="btn-primary" onclick="openConteneurModal()">+ Nouveau conteneur</button>
</div>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">
        {{ $errors->first() }}
    </div>
@endif

<style>
    /* Modale */
    .modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6);
        z-index: 1000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 40px 20px;
    }
    .modal-overlay.active { display: flex; }
    .modal-box {
        background: var(--cream); border: var(--border); box-shadow: var(--shadow);
        width: 100%; max-width: 640px; padding: 28px 32px; position: relative;
    }
    .modal-box h3 { margin: 0 0 20px; font-size: 1.3rem; }
    .modal-close {
        position: absolute; top: 14px; right: 16px; background: none; border: none;
        font-size: 1.6rem; cursor: pointer; color: var(--coffee); line-height: 1;
    }
    /* Form resserré, scoped à la modale uniquement */
    .modal-box .info-grid { grid-template-columns: 1fr 1fr; gap: 14px 18px; }
    .modal-box .form-group { margin-bottom: 0; }
    .modal-box .form-label { font-size: 0.72rem; margin-bottom: 5px; }
    .modal-box .form-input { font-size: 0.95rem; padding: 9px 12px; box-shadow: 2px 2px 0px rgba(18,3,9,0.1); }
    .modal-box .form-input:focus { transform: none; box-shadow: 3px 3px 0px rgba(164,36,59,0.2); }
    .modal-box .full { grid-column: 1 / -1; }
    /* Autocomplétion */
    .autocomplete-wrapper { position: relative; }
    .autocomplete-dropdown {
        display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
        background: var(--cream); border: 3px solid var(--coffee); border-top: none;
        max-height: 240px; overflow-y: auto; box-shadow: var(--shadow-sm);
    }
    .autocomplete-item { padding: 10px 14px; cursor: pointer; font-family: 'DM Mono', monospace; font-size: 0.85rem; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover { background: var(--wheat); }
    .conteneur-thumb { width: 56px; height: 40px; object-fit: cover; border: 2px solid var(--coffee); display: inline-block; vertical-align: middle; }
    .photo-previews { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .photo-preview { position: relative; width: 80px; height: 60px; border: 2px solid var(--coffee); }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .photo-preview .remove-photo { position: absolute; top: -8px; right: -8px; background: var(--cherry); color: var(--cream); border: 1px solid var(--coffee); width: 20px; height: 20px; font-size: 0.8rem; cursor: pointer; line-height: 1; display: flex; align-items: center; justify-content: center; padding: 0; }
</style>

<div class="modal-overlay" id="conteneurModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeConteneurModal()">&times;</button>
        <h3 style="margin-top:0;">Nouveau Conteneur</h3>
        <form action="{{ route('admin.conteneurs.store') }}" method="POST" id="conteneurForm">
            @csrf
            <div class="info-grid">
                <div class="form-group">
                    <label class="form-label" for="conteneur_ref">Référence / Nom</label>
                    <input type="text" name="conteneur_ref" id="conteneur_ref" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="capacite">Capacité (objets)</label>
                    <input type="number" name="capacite" id="capacite" class="form-input" value="50" required>
                </div>

                <div class="form-group full">
                    <label class="form-label" for="adresseSearch">Adresse (recherche)</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="adresseSearch" class="form-input" placeholder="Commencez à taper une adresse…" autocomplete="off">
                        <div class="autocomplete-dropdown" id="adresseSuggestions"></div>
                    </div>
                    <small style="color:#888; font-family:'DM Mono',monospace; font-size:0.7rem;">Sélectionnez une adresse dans la liste pour remplir automatiquement ville et coordonnées GPS.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="adresse">Adresse</label>
                    <input type="text" name="adresse" id="adresse" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ville">Ville</label>
                    <input type="text" name="ville" id="ville" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="code_postal">Code postal</label>
                    <input type="text" name="code_postal" id="code_postal" class="form-input">
                </div>
                <div class="form-group full">
                    <label class="form-label" for="imageInput">Photos (jpg, png, webp — max 5 Mo chacune)</label>
                    <input type="file" id="imageInput" class="form-input" accept="image/jpeg,image/png,image/webp" multiple>
                    <div id="previews" class="photo-previews"></div>
                    <div id="hiddenImages"></div>
                </div>

                <input type="hidden" name="latitude"  id="latitude">
                <input type="hidden" name="longitude" id="longitude">
            </div>
            <button type="submit" class="btn-primary" style="margin-top:16px;">Ajouter Conteneur</button>
        </form>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Référence</th>
                <th>Adresse</th>
                <th>Capacité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($conteneurs as $c)
            <tr>
                <td>{{ $c['id_conteneur'] }}</td>
                <td>
                    @if(!empty($c['photos']))
                        <img src="/uploads/{{ $c['photos'][0]['url_photo'] }}" alt="Conteneur {{ $c['conteneur_ref'] }}" class="conteneur-thumb">
                        @if(count($c['photos']) > 1)
                            <span style="font-size:0.65rem; color:#888;">+{{ count($c['photos']) - 1 }}</span>
                        @endif
                    @else
                        <span style="color:#bbb; font-size:0.75rem;">—</span>
                    @endif
                </td>
                <td>{{ $c['conteneur_ref'] }}</td>
                <td>{{ $c['adresse'] }}, {{ $c['ville'] }}</td>
                <td>{{ $c['capacite'] }}</td>
                <td>
                    @if($c['statut'] == 'actif')
                        <span class="badge badge-valid">Actif</span>
                    @else
                        <span class="badge badge-waiting">{{ $c['statut'] }}</span>
                    @endif
                </td>
                <td>
                    <div class="action-cell">
                        <a href="{{ route('admin.conteneurs.show', $c['id_conteneur']) }}" class="btn-secondary btn-sm">Gérer</a>
                        @if(!empty($c['latitude']) && !empty($c['longitude']))
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $c['latitude'] }},{{ $c['longitude'] }}"
                               target="_blank" rel="noopener" class="btn-secondary btn-sm">Itinéraire</a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 24px;">Aucun conteneur trouvé.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    // === Modale ===
    function openConteneurModal() { document.getElementById('conteneurModal').classList.add('active'); }
    function closeConteneurModal() { document.getElementById('conteneurModal').classList.remove('active'); }
    document.getElementById('conteneurModal').addEventListener('mousedown', (e) => {
        if (e.target.id === 'conteneurModal') closeConteneurModal();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeConteneurModal(); });
    @if($errors->any()) openConteneurModal(); @endif

    // === Autocomplétion adresse (Base Adresse Nationale - data.geopf.fr) ===
    const adresseSearch      = document.getElementById('adresseSearch');
    const adresseSuggestions = document.getElementById('adresseSuggestions');
    let debounceTimer = null;

    const closeSuggestions = () => {
        adresseSuggestions.style.display = 'none';
        adresseSuggestions.innerHTML = '';
    };

    adresseSearch.addEventListener('input', () => {
        const value = adresseSearch.value.trim();
        clearTimeout(debounceTimer);
        if (value.length < 3) { closeSuggestions(); return; }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`https://data.geopf.fr/geocodage/search/?q=${encodeURIComponent(value)}&limit=5`);
                if (!res.ok) throw new Error();
                const data = await res.json();
                const features = data.features || [];
                if (features.length === 0) { closeSuggestions(); return; }

                adresseSuggestions.innerHTML = '';
                features.forEach(feature => {
                    const props = feature.properties;
                    const coords = (feature.geometry && feature.geometry.coordinates) || [];
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = props.label;
                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        adresseSearch.value = props.label;
                        document.getElementById('adresse').value     = props.name     || props.label || '';
                        document.getElementById('ville').value        = props.city     || '';
                        document.getElementById('code_postal').value  = props.postcode || '';
                        document.getElementById('longitude').value    = coords[0] ?? '';
                        document.getElementById('latitude').value     = coords[1] ?? '';
                        closeSuggestions();
                    });
                    adresseSuggestions.appendChild(item);
                });
                adresseSuggestions.style.display = 'block';
            } catch {
                closeSuggestions();
            }
        }, 300);
    });

    adresseSearch.addEventListener('blur', () => setTimeout(closeSuggestions, 150));

    // === Photos multiples : lecture base64 (comme les annonces) + aperçus supprimables ===
    const imageInput   = document.getElementById('imageInput');
    const previews     = document.getElementById('previews');
    const hiddenImages = document.getElementById('hiddenImages');
    let photos = [];

    function renderPhotos() {
        previews.innerHTML = '';
        hiddenImages.innerHTML = '';
        photos.forEach((src, i) => {
            const cell = document.createElement('div');
            cell.className = 'photo-preview';
            cell.innerHTML = `<img src="${src}" alt="Aperçu ${i + 1}"><button type="button" class="remove-photo" title="Retirer">&times;</button>`;
            cell.querySelector('.remove-photo').addEventListener('click', () => { photos.splice(i, 1); renderPhotos(); });
            previews.appendChild(cell);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'image_base64[]';
            input.value = src;
            hiddenImages.appendChild(input);
        });
    }

    imageInput.addEventListener('change', () => {
        Array.from(imageInput.files).forEach(file => {
            if (file.size > 5 * 1024 * 1024) { alert('Photo trop lourde (max 5 Mo) : ' + file.name); return; }
            const reader = new FileReader();
            reader.onload = (e) => { photos.push(e.target.result); renderPhotos(); };
            reader.readAsDataURL(file);
        });
        imageInput.value = ''; // permet de re-sélectionner d'autres fichiers
    });
</script>
@endsection

@extends('layouts.particulier')
@section('title', 'Deposer une annonce')

@section('styles')
<style>
    .neo-progress-container { margin-bottom: 40px; }
    .neo-progress-header { display: flex; justify-content: space-between; font-family: 'DM Mono', monospace; font-size: 0.85rem; font-weight: 600; color: var(--teal); margin-bottom: 8px; letter-spacing: 0.05em; text-transform: uppercase; }
    .neo-progress-track { width: 100%; height: 12px; border: var(--border); background: var(--cream); position: relative; margin-bottom: 12px; }
    .neo-progress-fill { height: 100%; background: var(--cherry); transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .neo-progress-labels { display: flex; justify-content: space-between; font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; letter-spacing: 0.08em; color: var(--coffee); position: relative; }
    .neo-progress-label { flex: 1; text-align: center; cursor: pointer; opacity: 0.5; transition: all 0.2s; position: relative; }
    .neo-progress-label:first-child { text-align: left; }
    .neo-progress-label:last-child { text-align: right; }
    .neo-progress-label.active { opacity: 1; color: var(--cherry); }
    .neo-progress-label.completed { opacity: 1; text-decoration: underline; text-decoration-color: var(--cherry); text-underline-offset: 4px; }

    .step-content { display: none; }
    .step-content.active { display: block; }

    .radio-group { display: flex; gap: 16px; flex-wrap: wrap; }
    .radio-option { position: relative; }
    .radio-option input { position: absolute; opacity: 0; }
    .radio-option label { display: inline-block; padding: 10px 24px; border: var(--border); font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; letter-spacing: 0.08em; cursor: pointer; background: white; }
    .radio-option input:checked + label { background: var(--cherry); color: var(--cream); box-shadow: var(--shadow-sm); }

    .objet-card { border: var(--border); padding: 24px; margin-bottom: 20px; background: white; box-shadow: var(--shadow-sm); position: relative; }
    .objet-card h4 { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; margin-bottom: 16px; }
    .objet-remove { position: absolute; top: 12px; right: 12px; background: var(--cherry); color: var(--cream); border: 2px solid var(--coffee); width: 32px; height: 32px; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; }
    .objet-remove:active { transform: translate(2px, 2px); }

    .objet-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 768px) { .objet-grid { grid-template-columns: 1fr; } }

    .photo-zone { border: 3px dashed var(--coffee); padding: 24px; text-align: center; background: var(--cream); cursor: pointer; min-height: 100px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; }
    .photo-zone.dragover { border-color: var(--cherry); background: rgba(164,36,59,0.05); }
    .photo-zone p { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; color: var(--coffee); }

    .photo-previews { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 12px; }
    .photo-preview { position: relative; width: 80px; height: 80px; border: 2px solid var(--coffee); }
    .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
    .photo-preview .remove-photo { position: absolute; top: -8px; right: -8px; background: var(--cherry); color: var(--cream); border: 1px solid var(--coffee); width: 20px; height: 20px; font-size: 0.7rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .photo-preview .remove-photo:active { transform: scale(1.1); }

    .prix-group { display: none; }
    .prix-group.visible { display: block; }

    .progress-bar { width: 100%; height: 24px; border: var(--border); background: white; margin-top: 16px; display: none; }
    .progress-bar.active { display: block; }
    .progress-fill { height: 100%; background: var(--forest); transition: width 0.3s; }
    .progress-text { text-align: center; font-family: 'DM Mono', monospace; font-size: 0.8rem; margin-top: 4px; }

    .form-container { max-width: 900px; margin: 0 auto; }
    .btn-row { display: flex; gap: 16px; margin-top: 24px; }

    .autocomplete-wrapper { position: relative; }
    .autocomplete-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: white; border: 3px solid var(--coffee); border-top: none; box-shadow: var(--shadow-sm); max-height: 240px; overflow-y: auto; }
    .autocomplete-item { padding: 10px 14px; cursor: pointer; font-size: 0.92rem; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .autocomplete-item:last-child { border-bottom: none; }
    .autocomplete-item:hover { background: var(--wheat); }
</style>
@endsection

@section('content')
<div class="form-container">
    <div class="page-header">
        <h1 class="page-title"><span data-i18n="create.title">Deposer une annonce</span></h1>
    </div>

    <div class="neo-progress-container">
        <div class="neo-progress-header">
            <span id="step-counter">ETAPE 1 SUR 3</span>
        </div>
        <div class="neo-progress-track">
            <div class="neo-progress-fill" id="step-fill" style="width: 33.33%;"></div>
        </div>
        <div class="neo-progress-labels">
            <div class="neo-progress-label active" id="label-1" onclick="goToStep(1)"><span data-i18n="create.tab.description">DESCRIPTION</span></div>
            <div class="neo-progress-label" id="label-2" onclick="goToStep(2)"><span data-i18n="create.tab.photos">PHOTOS & LIVRAISON</span></div>
            <div class="neo-progress-label" id="label-3" onclick="goToStep(3)"><span data-i18n="create.tab.confirm">CONFIRMATION</span></div>
        </div>
    </div>

    <form id="annonce-form" onsubmit="return false;">
        
        <div class="step-content active" id="step-1">
            <div class="card">
                <div class="form-group">
                    <label class="form-label"><span data-i18n="create.f.title">Titre *</span></label>
                    <input type="text" class="form-input" id="titre" placeholder="Ex: Chaise vintage" data-i18n-ph="create.f.title.ph" maxlength="200" oninput="validateField('titre')">
                    <div id="titre-feedback"></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><span data-i18n="create.f.desc">Description *</span></label>
                    <textarea class="form-textarea" id="description" placeholder="Decrivez votre objet en detail..." data-i18n-ph="create.f.desc.ph" maxlength="5000" oninput="validateField('description')"></textarea>
                    <div id="description-feedback"></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><span data-i18n="create.f.type">Type d'annonce *</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="type_annonce" id="type_don" value="don" onchange="togglePrix()">
                            <label for="type_don"><span data-i18n="status.don">Don</span></label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="type_annonce" id="type_vente" value="vente" onchange="togglePrix()">
                            <label for="type_vente"><span data-i18n="status.vente">Vente</span></label>
                        </div>
                    </div>
                </div>

                <div class="form-group prix-group" id="prix-group">
                    <label class="form-label"><span data-i18n="create.f.price">Prix (EUR) *</span></label>
                    <input type="number" class="form-input" id="prix" placeholder="0.00" min="0" step="0.01" style="max-width: 200px;">
                </div>

                <div class="btn-row">
                    <x-btn onclick="goToStep(2)"><span data-i18n="btn.next">Suivant</span></x-btn>
                </div>
            </div>
        </div>

        <div class="step-content" id="step-2">
            <div class="card">
                <div id="objets-container"></div>

                <x-btn variant="secondary" onclick="addObjet()" style="margin-bottom: 24px;">
                    + Ajouter un objet
                </x-btn>

                <div class="form-group" style="margin-top: 32px; padding-top: 24px; border-top: 2px dashed var(--coffee);">
                    <label class="form-label"><span data-i18n="create.f.handover">Mode de remise *</span></label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="mode_remise" id="mode_conteneur" value="conteneur" onchange="toggleRemise()">
                            <label for="mode_conteneur"><span data-i18n="create.opt.container">Conteneur</span></label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="mode_remise" id="mode_main" value="main_propre" onchange="toggleRemise()">
                            <label for="mode_main"><span data-i18n="create.opt.hand">Main propre</span></label>
                        </div>
                    </div>

                    {{-- Panneau conteneur : carte interactive des points de collecte --}}
                    <div id="conteneur-panel" style="display:none; margin-top:20px;">
                        <label class="form-label"><span data-i18n="create.f.choosecontainer">Choisir le conteneur sur la carte *</span></label>
                        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.6; margin-bottom:10px;" data-i18n="depot.map.hint">Cliquez un conteneur sur la carte pour le sélectionner — ou « Autour de moi » pour le plus proche.</p>
                        <div id="annonce-map" data-conteneurs-map data-api="{{ config('services.api.public_url') }}" data-selectable="1" style="height:380px; border:var(--border); box-shadow:var(--shadow-sm);"></div>
                        <input type="hidden" id="conteneur-select">
                        <div id="conteneur-info" style="display:none; margin-top:14px; padding:14px 16px; background:white; border:2px solid var(--coffee); box-shadow:var(--shadow-sm);">
                            <div style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; color:var(--cherry); margin-bottom:6px;"><span data-i18n="create.containeraddr">Adresse du conteneur</span></div>
                            <div id="conteneur-adresse" style="font-size:0.98rem; line-height:1.4; margin-bottom:12px;"></div>
                            <a id="conteneur-maps" href="#" target="_blank" rel="noopener" class="btn-secondary btn-sm" style="text-decoration:none;"><span data-i18n="market.directions">Itinéraire Google Maps →</span></a>
                        </div>
                    </div>

                    {{-- Panneau main propre --}}
                    <div id="mainpropre-panel" style="display:none; margin-top:20px;">
                        <label class="form-label" for="adresseSearch"><span data-i18n="create.f.handoveraddr">Adresse de remise *</span></label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="adresseSearch" class="form-input" placeholder="Ex: 10 Rue de la Paix, Paris..." data-i18n-ph="create.f.handoveraddr.ph" autocomplete="off">
                            <div class="autocomplete-dropdown" id="adresseSuggestions"></div>
                        </div>
                        <input type="hidden" id="adresse_remise_value">
                        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.55; margin-top:8px;">
                            Adresse où l'acheteur viendra récupérer l'objet. Sélectionnez une proposition dans la liste.
                        </p>
                    </div>
                </div>

                <div class="btn-row">
                    <x-btn variant="secondary" onclick="goToStep(1)"><span data-i18n="btn.prev">Precedent</span></x-btn>
                    <x-btn onclick="goToStep(3)"><span data-i18n="btn.next">Suivant</span></x-btn>
                </div>
            </div>
        </div>

        <div class="step-content" id="step-3">
            <div class="card" style="text-align: center; padding: 40px 20px;">
                <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; margin-bottom: 16px;"><span data-i18n="create.ready">PRET A PUBLIER ?</span></h3>
                <p style="margin-bottom: 32px; font-size: 1.1rem;"><span data-i18n="create.ready.desc">Verifiez bien toutes les informations avant de creer votre annonce.</span></p>
                
                <div id="recap-container" style="text-align: left; background: white; border: 2px solid var(--coffee); padding: 24px; margin-bottom: 32px; box-shadow: var(--shadow-sm);">
                </div>
                
                <div class="progress-bar" id="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text" id="progress-text"></div>

                <div class="btn-row" style="justify-content: center;">
                    <x-btn variant="secondary" onclick="goToStep(2)"><span data-i18n="btn.prev">Precedent</span></x-btn>
                    <x-btn id="submit-btn" onclick="submitAnnonce()"><span data-i18n="create.submit">Creer l'annonce</span></x-btn>
                    <x-btn variant="secondary" href="/"><span data-i18n="btn.cancel">Annuler</span></x-btn>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@vite('resources/js/conteneurs-map.js')
<script>
let currentStep = 1;
let objetCount = 0;
let objets = {};

function goToStep(step) {
    if (step > 1 && !validateStep1()) return;
    if (step > 2 && !validateStep2()) return;

    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');

    document.getElementById('step-counter').innerText = `ETAPE ${step} SUR 3`;
    document.getElementById('step-fill').style.width = `${(step / 3) * 100}%`;

    for (let i = 1; i <= 3; i++) {
        const lbl = document.getElementById('label-' + i);
        lbl.classList.remove('active', 'completed');
        if (i === step) lbl.classList.add('active');
        else if (i < step) lbl.classList.add('completed');
    }

    if (step === 3) {
        buildRecap();
    }

    currentStep = step;
    if (step === 2 && Object.keys(objets).length === 0) {
        addObjet();
    }
}

function buildRecap() {
    const recapContainer = document.getElementById('recap-container');
    const titre = document.getElementById('titre').value;
    const typeObj = document.querySelector('input[name="type_annonce"]:checked');
    const type = typeObj ? typeObj.value : '';
    const prix = type === 'vente' ? document.getElementById('prix').value : '';
    const modeObj = document.querySelector('input[name="mode_remise"]:checked');
    const mode = modeObj ? modeObj.value : '';
    const objetIds = Object.keys(objets);
    let totalPhotos = 0;

    objetIds.forEach(id => { totalPhotos += objets[id].photos.length; });

    let remiseLabel = '—';
    if (mode === 'conteneur') {
        remiseLabel = 'Conteneur' + (selectedConteneur ? ' — ' + selectedConteneur.adresse + ', ' + selectedConteneur.ville : '');
    } else if (mode === 'main_propre') {
        remiseLabel = 'Main propre — ' + (document.getElementById('adresse_remise_value').value || '');
    }

    let html = `
        <h4 style="font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; margin-bottom: 16px; border-bottom: 2px solid var(--coffee); padding-bottom: 8px;">RESUME DE L'ANNONCE</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-family: 'DM Mono', monospace; font-size: 1rem; color: var(--coffee);">
            <div><strong>Titre :</strong> ${titre}</div>
            <div><strong>Type :</strong> ${type.toUpperCase()}${type === 'vente' ? ' (' + prix + ' €)' : ''}</div>
            <div style="grid-column:1 / -1;"><strong>Remise :</strong> ${remiseLabel}</div>
            <div><strong>Nombre d'objets :</strong> ${objetIds.length}</div>
            <div><strong>Total Photos :</strong> ${totalPhotos}</div>
        </div>
    `;

    recapContainer.innerHTML = html;
}

function validateField(field) {
    const el = document.getElementById(field);
    const fb = document.getElementById(field + '-feedback');
    const val = el.value;

    if (field === 'titre') {
        if (val.length < 3) {
            fb.innerHTML = '<span class="field-error">Minimum 3 caracteres (' + val.length + '/3)</span>';
            return false;
        } else {
            fb.innerHTML = '<span class="field-valid">OK (' + val.length + '/200)</span>';
            return true;
        }
    }
    if (field === 'description') {
        if (val.length < 10) {
            fb.innerHTML = '<span class="field-error">Minimum 10 caracteres (' + val.length + '/10)</span>';
            return false;
        } else {
            fb.innerHTML = '<span class="field-valid">OK (' + val.length + '/5000)</span>';
            return true;
        }
    }
    return true;
}

function togglePrix() {
    const isVente = document.getElementById('type_vente').checked;
    document.getElementById('prix-group').classList.toggle('visible', isVente);
}

// ── Mode de remise ──────────────────────────────────────────────────────────
function toggleRemise() {
    const mode = (document.querySelector('input[name="mode_remise"]:checked') || {}).value;
    document.getElementById('conteneur-panel').style.display = mode === 'conteneur' ? 'block' : 'none';
    document.getElementById('mainpropre-panel').style.display = mode === 'main_propre' ? 'block' : 'none';
}

// Sélection d'un conteneur depuis la carte (module conteneurs-map.js).
let selectedConteneur = null;
function selectConteneur(c) {
    if (!c) return;
    selectedConteneur = c;
    document.getElementById('conteneur-select').value = c.id_conteneur;
    document.getElementById('conteneur-adresse').textContent =
        c.adresse + ', ' + (c.code_postal ? c.code_postal + ' ' : '') + c.ville;
    const dest = (c.latitude != null && c.longitude != null)
        ? `${c.latitude},${c.longitude}`
        : `${c.adresse}, ${c.code_postal || ''} ${c.ville}`;
    document.getElementById('conteneur-maps').href =
        'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest);
    document.getElementById('conteneur-info').style.display = 'block';
}

// ── Autocomplétion d'adresse (API geopf.fr), comme à l'inscription ──────────
function initAdresseAutocomplete() {
    const input = document.getElementById('adresseSearch');
    const box   = document.getElementById('adresseSuggestions');
    const hidden= document.getElementById('adresse_remise_value');
    if (!input) return;
    let debounce = null;

    const close = () => { box.style.display = 'none'; box.innerHTML = ''; };

    input.addEventListener('input', () => {
        const value = input.value.trim();
        hidden.value = '';
        clearTimeout(debounce);
        if (value.length < 3) { close(); return; }
        debounce = setTimeout(async () => {
            try {
                const res = await fetch(`https://data.geopf.fr/geocodage/search/?q=${encodeURIComponent(value)}&limit=5`);
                if (!res.ok) throw new Error();
                const data = await res.json();
                const features = data.features || [];
                if (features.length === 0) { close(); return; }
                box.innerHTML = '';
                features.forEach(f => {
                    const item = document.createElement('div');
                    item.className = 'autocomplete-item';
                    item.textContent = f.properties.label;
                    item.addEventListener('mousedown', (e) => {
                        e.preventDefault();
                        input.value = f.properties.label;
                        hidden.value = f.properties.label;
                        close();
                    });
                    box.appendChild(item);
                });
                box.style.display = 'block';
            } catch { close(); }
        }, 300);
    });
    input.addEventListener('blur', () => setTimeout(close, 150));
}

function validateStep1() {
    let valid = true;
    if (!validateField('titre')) valid = false;
    if (!validateField('description')) valid = false;
    const typeObj = document.querySelector('input[name="type_annonce"]:checked');
    if (!typeObj) {
        showAlert('Veuillez choisir un type d\'annonce', 'error');
        valid = false;
    } else if (typeObj.value === 'vente') {
        const prix = parseFloat(document.getElementById('prix').value);
        if (isNaN(prix) || prix <= 0) {
            showAlert('Pour une vente, le prix doit être strictement supérieur à 0 €', 'error');
            valid = false;
        }
    }
    return valid;
}

function validateStep2() {
    let valid = true;
    const objetIds = Object.keys(objets);
    if (objetIds.length === 0) {
        showAlert('Ajoutez au moins un objet', 'error');
        valid = false;
    } else {
        for (const id of objetIds) {
            const cat = document.getElementById('cat-' + id).value.trim();
            const mat = document.getElementById('mat-' + id).value;
            const etat = document.getElementById('etat-' + id).value;
            const poids = parseFloat(document.getElementById('poids-' + id).value);

            if (!cat || !mat || !etat) {
                showAlert('Veuillez remplir tous les champs obligatoires des objets', 'error');
                valid = false;
                break;
            }
            if (isNaN(poids) || poids <= 0) {
                showAlert('Indiquez un poids (même approximatif) pour chaque objet', 'error');
                valid = false;
                break;
            }
            if (objets[id].photos.length === 0) {
                showAlert('Au moins une photo est requise pour chaque objet', 'error');
                valid = false;
                break;
            }
        }
    }
    const modeObj = document.querySelector('input[name="mode_remise"]:checked');
    if (!modeObj) {
        showAlert('Veuillez choisir un mode de remise', 'error');
        valid = false;
    } else if (modeObj.value === 'conteneur') {
        if (!document.getElementById('conteneur-select').value) {
            showAlert('Veuillez sélectionner un conteneur', 'error');
            valid = false;
        }
    } else if (modeObj.value === 'main_propre') {
        if (!document.getElementById('adresse_remise_value').value) {
            showAlert('Veuillez sélectionner une adresse de remise dans la liste', 'error');
            valid = false;
        }
    }
    return valid;
}

const MATERIAUX  = @json($materiaux ?? []);
const CATEGORIES = @json($categories ?? []);

function matOptions() {
    return '<option value="">-- Choisir --</option>' +
        MATERIAUX.map(m => `<option value="${m.code}">${m.libelle}</option>`).join('');
}
function catOptions() {
    return '<option value="">-- Choisir --</option>' +
        CATEGORIES.map(c => `<option value="${c.nom}">${c.nom}</option>`).join('');
}

function addObjet() {
    objetCount++;
    const id = objetCount;
    objets[id] = { photos: [] };

    const html = `
    <div class="objet-card" id="objet-${id}">
        <button type="button" class="objet-remove" onclick="removeObjet(${id})">X</button>
        <h4>Objet #${id}</h4>
        <div class="objet-grid">
            <div class="form-group">
                <label class="form-label">Categorie *</label>
                <select class="form-select" id="cat-${id}">
                    ${catOptions()}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Materiau *</label>
                <select class="form-select" id="mat-${id}">
                    ${matOptions()}
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Etat *</label>
                <select class="form-select" id="etat-${id}">
                    <option value="">-- Choisir --</option>
                    <option value="neuf">Neuf</option>
                    <option value="bon">Bon</option>
                    <option value="use">Use</option>
                    <option value="a_reparer">A reparer</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Poids (kg) *</label>
                <input type="number" class="form-input" id="poids-${id}" placeholder="Ex : 12" min="0.1" step="0.1">
                <div style="font-family:'DM Mono',monospace; font-size:0.68rem; opacity:0.6; margin-top:4px;">Une estimation approximative suffit — c'est juste pour calculer l'impact (déchets évités / CO₂).</div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">Photos * (jpg, png, webp - max 5 par objet)</label>
            <div class="photo-zone" id="photo-zone-${id}"
                 ondragover="handleDragOver(event, ${id})"
                 ondragleave="handleDragLeave(event, ${id})"
                 ondrop="handleDrop(event, ${id})"
                 onclick="document.getElementById('photo-input-${id}').click()">
                <p>Glissez vos photos ici ou cliquez pour selectionner</p>
                <p style="font-size: 0.7rem; color: var(--cherry);">Max 5 Mo par photo</p>
            </div>
            <input type="file" id="photo-input-${id}" multiple accept="image/jpeg,image/png,image/webp" style="display:none" onchange="handleFileSelect(event, ${id})">
            <div class="photo-previews" id="previews-${id}"></div>
        </div>
    </div>`;

    document.getElementById('objets-container').insertAdjacentHTML('beforeend', html);
}

function removeObjet(id) {
    if (Object.keys(objets).length <= 1) {
        showAlert('Au moins un objet est requis', 'error');
        return;
    }
    delete objets[id];
    document.getElementById('objet-' + id).remove();
}

function handleDragOver(e, id) {
    e.preventDefault();
    document.getElementById('photo-zone-' + id).classList.add('dragover');
}

function handleDragLeave(e, id) {
    document.getElementById('photo-zone-' + id).classList.remove('dragover');
}

function handleDrop(e, id) {
    e.preventDefault();
    document.getElementById('photo-zone-' + id).classList.remove('dragover');
    const files = e.dataTransfer.files;
    processFiles(files, id);
}

function handleFileSelect(e, id) {
    processFiles(e.target.files, id);
}

function processFiles(files, objetId) {
    const existing = objets[objetId].photos.length;
    const maxPerObj = 5;

    for (let i = 0; i < files.length && existing + i < maxPerObj; i++) {
        const file = files[i];
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            showAlert('Format non supporte: ' + file.name, 'error');
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            showAlert('Photo trop lourde (max 5 Mo): ' + file.name, 'error');
            continue;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const photoIndex = objets[objetId].photos.length;
            objets[objetId].photos.push(e.target.result);
            renderPreviews(objetId);
        };
        reader.readAsDataURL(file);
    }
}

function renderPreviews(objetId) {
    const container = document.getElementById('previews-' + objetId);
    container.innerHTML = '';
    objets[objetId].photos.forEach((src, idx) => {
        container.innerHTML += `
        <div class="photo-preview">
            <img src="${src}" alt="Photo ${idx + 1}">
            <button type="button" class="remove-photo" onclick="removePhoto(${objetId}, ${idx})">X</button>
        </div>`;
    });
}

function removePhoto(objetId, photoIndex) {
    objets[objetId].photos.splice(photoIndex, 1);
    renderPreviews(objetId);
}

async function submitAnnonce() {
    if (!validateStep1() || !validateStep2()) { 
        if (!validateStep1()) goToStep(1);
        else goToStep(2);
        return; 
    }

    let totalPhotos = 0;
    const objetsData = [];
    const objetIds = Object.keys(objets);

    for (const id of objetIds) {
        const cat = document.getElementById('cat-' + id).value.trim();
        const mat = document.getElementById('mat-' + id).value;
        const etat = document.getElementById('etat-' + id).value;
        const poids = document.getElementById('poids-' + id).value;

        totalPhotos += objets[id].photos.length;

        const obj = {
            categorie: cat,
            materiau: mat,
            etat: etat,
            photos: objets[id].photos
        };
        if (poids) obj.poids_kg = parseFloat(poids);
        objetsData.push(obj);
    }

    if (totalPhotos > 10) {
        showAlert('Maximum 10 photos par annonce (vous en avez ' + totalPhotos + ')', 'error');
        return;
    }

    const typeAnnonce = document.querySelector('input[name="type_annonce"]:checked').value;
    const modeRemise = document.querySelector('input[name="mode_remise"]:checked').value;
    const payload = {
        titre: document.getElementById('titre').value.trim(),
        description: document.getElementById('description').value.trim(),
        type_annonce: typeAnnonce,
        mode_remise: modeRemise,
        objets: objetsData
    };

    if (typeAnnonce === 'vente') {
        const prix = parseFloat(document.getElementById('prix').value);
        if (isNaN(prix) || prix <= 0) {
            showAlert('Pour une vente, le prix doit être strictement supérieur à 0 €', 'error');
            return;
        }
        payload.prix = prix;
    }

    if (modeRemise === 'conteneur') {
        const idc = parseInt(document.getElementById('conteneur-select').value, 10);
        if (!idc) { showAlert('Veuillez sélectionner un conteneur', 'error'); return; }
        payload.id_conteneur = idc;
    } else if (modeRemise === 'main_propre') {
        const adr = document.getElementById('adresse_remise_value').value;
        if (!adr) { showAlert('Veuillez sélectionner une adresse de remise', 'error'); return; }
        payload.adresse_remise = adr;
    }

    // Show progress
    const btn = document.getElementById('submit-btn');
    btn.classList.add('btn-disabled');
    btn.textContent = 'Envoi en cours...';
    document.getElementById('progress-bar').classList.add('active');
    document.getElementById('progress-fill').style.width = '30%';
    document.getElementById('progress-text').textContent = 'Envoi des donnees...';

    try {
        document.getElementById('progress-fill').style.width = '60%';
        document.getElementById('progress-text').textContent = 'Traitement des photos...';

        const response = await apiFetch('/api/v1/annonces', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        document.getElementById('progress-fill').style.width = '100%';

        if (!response) return;

        const data = await response.json();

        if (response.ok) {
            document.getElementById('progress-text').textContent = 'Termine !';
            showAlert('Annonce creee avec succes ! Redirection...', 'success');
            setTimeout(() => {
                window.location.href = '/particulier/annonces';
            }, 2000);
        } else {
            showAlert(data.erreur || 'Erreur lors de la creation', 'error');
            btn.classList.remove('btn-disabled');
            btn.textContent = 'Creer l\'annonce';
            document.getElementById('progress-bar').classList.remove('active');
        }
    } catch (err) {
        showAlert('Erreur de connexion au serveur', 'error');
        btn.classList.remove('btn-disabled');
        btn.textContent = 'Creer l\'annonce';
        document.getElementById('progress-bar').classList.remove('active');
    }
}

// Initialisation au chargement
initAdresseAutocomplete();
const _annonceMap = document.getElementById('annonce-map');
if (_annonceMap) {
    _annonceMap.addEventListener('conteneur:selected', function (e) { selectConteneur(e.detail.conteneur); });
}
</script>
@endsection

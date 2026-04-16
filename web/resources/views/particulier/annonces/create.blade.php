@extends('layouts.particulier')
@section('title', 'Deposer une annonce')

@section('styles')
<style>
    .step-indicator { display: flex; gap: 0; margin-bottom: 32px; }
    .step { flex: 1; padding: 14px; text-align: center; font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; letter-spacing: 0.08em; border: var(--border); cursor: pointer; }
    .step.active { background: var(--cherry); color: var(--cream); }
    .step.completed { background: var(--forest); color: var(--cream); }
    .step.inactive { background: var(--wheat); color: var(--coffee); }

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

    .form-container { max-width: 900px; }
    .btn-row { display: flex; gap: 16px; margin-top: 24px; }
</style>
@endsection

@section('content')
<div class="form-container">
    <div class="page-header">
        <h1 class="page-title">Deposer une annonce</h1>
    </div>

    <div class="step-indicator">
        <div class="step active" id="step-ind-1" onclick="goToStep(1)">1. Details de l'annonce</div>
        <div class="step inactive" id="step-ind-2" onclick="goToStep(2)">2. Ajouter des objets</div>
    </div>

    <form id="annonce-form" onsubmit="return false;">
        <!-- Step 1 -->
        <div class="step-content active" id="step-1">
            <div class="card">
                <div class="form-group">
                    <label class="form-label">Titre *</label>
                    <input type="text" class="form-input" id="titre" placeholder="Ex: Chaise vintage" maxlength="200" oninput="validateField('titre')">
                    <div id="titre-feedback"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea class="form-textarea" id="description" placeholder="Decrivez votre objet en detail..." maxlength="5000" oninput="validateField('description')"></textarea>
                    <div id="description-feedback"></div>
                </div>

                <div class="form-group">
                    <label class="form-label">Type d'annonce *</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="type_annonce" id="type_don" value="don" onchange="togglePrix()">
                            <label for="type_don">Don</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="type_annonce" id="type_vente" value="vente" onchange="togglePrix()">
                            <label for="type_vente">Vente</label>
                        </div>
                    </div>
                </div>

                <div class="form-group prix-group" id="prix-group">
                    <label class="form-label">Prix (EUR) *</label>
                    <input type="number" class="form-input" id="prix" placeholder="0.00" min="0" step="0.01" style="max-width: 200px;">
                </div>

                <div class="form-group">
                    <label class="form-label">Mode de remise *</label>
                    <div class="radio-group">
                        <div class="radio-option">
                            <input type="radio" name="mode_remise" id="mode_conteneur" value="conteneur">
                            <label for="mode_conteneur">Conteneur</label>
                        </div>
                        <div class="radio-option">
                            <input type="radio" name="mode_remise" id="mode_main" value="main_propre">
                            <label for="mode_main">Main propre</label>
                        </div>
                    </div>
                </div>

                <div class="btn-row">
                    <x-btn onclick="goToStep(2)">Suivant</x-btn>
                </div>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="step-content" id="step-2">
            <div class="card">
                <div id="objets-container"></div>

                <x-btn variant="secondary" onclick="addObjet()" style="margin-bottom: 24px;">
                    + Ajouter un objet
                </x-btn>

                <div class="progress-bar" id="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
                <div class="progress-text" id="progress-text"></div>

                <div class="btn-row">
                    <x-btn variant="secondary" onclick="goToStep(1)">Precedent</x-btn>
                    <x-btn id="submit-btn" onclick="submitAnnonce()">Creer l'annonce</x-btn>
                    <x-btn variant="secondary" href="/">Annuler</x-btn>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
let currentStep = 1;
let objetCount = 0;
let objets = {};

function goToStep(step) {
    if (step === 2 && !validateStep1()) return;

    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');

    document.querySelectorAll('.step').forEach((el, i) => {
        el.classList.remove('active', 'completed', 'inactive');
        if (i + 1 === step) el.classList.add('active');
        else if (i + 1 < step) el.classList.add('completed');
        else el.classList.add('inactive');
    });

    currentStep = step;
    if (step === 2 && Object.keys(objets).length === 0) {
        addObjet();
    }
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

function validateStep1() {
    let valid = true;
    if (!validateField('titre')) valid = false;
    if (!validateField('description')) valid = false;
    if (!document.querySelector('input[name="type_annonce"]:checked')) {
        showAlert('Veuillez choisir un type d\'annonce', 'error');
        valid = false;
    }
    if (!document.querySelector('input[name="mode_remise"]:checked')) {
        showAlert('Veuillez choisir un mode de remise', 'error');
        valid = false;
    }
    return valid;
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
                <input type="text" class="form-input" id="cat-${id}" placeholder="Ex: Meubles">
            </div>
            <div class="form-group">
                <label class="form-label">Materiau *</label>
                <select class="form-select" id="mat-${id}">
                    <option value="">-- Choisir --</option>
                    <option value="bois">Bois</option>
                    <option value="metal">Metal</option>
                    <option value="textile">Textile</option>
                    <option value="plastique">Plastique</option>
                    <option value="verre">Verre</option>
                    <option value="electronique">Electronique</option>
                    <option value="autre">Autre</option>
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
                <label class="form-label">Poids (kg)</label>
                <input type="number" class="form-input" id="poids-${id}" placeholder="Optionnel" min="0" step="0.1">
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
    if (!validateStep1()) { goToStep(1); return; }

    // Validate objects
    const objetIds = Object.keys(objets);
    if (objetIds.length === 0) {
        showAlert('Ajoutez au moins un objet', 'error');
        return;
    }

    let totalPhotos = 0;
    const objetsData = [];

    for (const id of objetIds) {
        const cat = document.getElementById('cat-' + id).value.trim();
        const mat = document.getElementById('mat-' + id).value;
        const etat = document.getElementById('etat-' + id).value;
        const poids = document.getElementById('poids-' + id).value;

        if (!cat) { showAlert('Categorie requise pour l\'objet #' + id, 'error'); return; }
        if (!mat) { showAlert('Materiau requis pour l\'objet #' + id, 'error'); return; }
        if (!etat) { showAlert('Etat requis pour l\'objet #' + id, 'error'); return; }
        if (objets[id].photos.length === 0) { showAlert('Au moins une photo pour l\'objet #' + id, 'error'); return; }

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
    const payload = {
        titre: document.getElementById('titre').value.trim(),
        description: document.getElementById('description').value.trim(),
        type_annonce: typeAnnonce,
        mode_remise: document.querySelector('input[name="mode_remise"]:checked').value,
        objets: objetsData
    };

    if (typeAnnonce === 'vente') {
        const prix = parseFloat(document.getElementById('prix').value);
        if (isNaN(prix) || prix <= 0) {
            showAlert('Prix invalide pour une vente', 'error');
            return;
        }
        payload.prix = prix;
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
                window.location.href = '/particulier/profile';
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
</script>
@endsection

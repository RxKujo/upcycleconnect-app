@extends('layouts.professionnel')
@section('title', 'Mon Profil Pro')

@section('styles')
<style>
    .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
    @media (max-width: 900px) { .profile-grid { grid-template-columns: 1fr; } }

    .card-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 0.08em; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 3px solid var(--coffee); }

    .avatar-section { display: flex; align-items: center; gap: 24px; margin-bottom: 24px; }
    .avatar { width: 120px; height: 120px; border: var(--border); object-fit: cover; background: var(--wheat); display: flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--coffee); overflow: hidden; border-radius: 50%; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }

    .info-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .info-row:last-child { border-bottom: none; }
    .info-key { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; font-weight: bold; color: var(--cherry); }
    .info-val { font-size: 1rem; }

    .siret-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; font-family: 'DM Mono', monospace; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; border: 2px solid var(--coffee); }
    .siret-verified { background-color: var(--forest); color: var(--cream); }
    .siret-pending { background-color: var(--wheat); color: var(--coffee); }

    .score-display { text-align: center; padding: 20px 12px 8px; }
    .score-level { display: inline-block; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; padding: 4px 14px; border: 2px solid var(--coffee); background: var(--wheat); color: var(--cream); margin-bottom: 10px; }
    .score-number { font-family: 'Bebas Neue', sans-serif; font-size: 4rem; color: var(--coffee); line-height: 1; }
    .score-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.78rem; color: var(--cherry); margin-top: 4px; }
    .certif-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; background: var(--forest); color: var(--cream); border: 2px solid var(--coffee); font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; margin-top: 12px; }
    .score-progress { padding: 8px 12px 0; }
    .score-progress-head { display: flex; justify-content: space-between; font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px; }
    .score-bar { height: 14px; border: 2px solid var(--coffee); background: var(--cream); overflow: hidden; }
    .score-bar-fill { height: 100%; background: var(--cherry); width: 0; transition: width 0.6s ease; }
    .score-ladder { margin-top: 18px; padding-top: 16px; border-top: 2px solid rgba(18,3,9,0.1); display: flex; flex-direction: column; gap: 6px; }
    .ladder-item { display: flex; align-items: center; gap: 10px; font-family: 'DM Mono', monospace; font-size: 0.78rem; opacity: 0.45; }
    .ladder-item.reached { opacity: 1; }
    .ladder-dot { width: 12px; height: 12px; border: 2px solid var(--coffee); flex-shrink: 0; }
    .ladder-name { flex: 1; text-transform: uppercase; letter-spacing: 0.04em; }
    .ladder-seuil { color: var(--teal); }

    .edit-input { width: 100%; border: 2px solid var(--coffee); padding: 8px 12px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; display: none; border-radius: 0; }
    .editing .info-val { display: none; }
    .editing .edit-input { display: block; }

    .full-width { grid-column: 1 / -1; }

    .photo-upload-zone { border: 3px dashed var(--coffee); padding: 16px; text-align: center; cursor: pointer; background: white; margin-top: 8px; display: none; }
    .editing .photo-upload-zone { display: block; }
</style>
@endsection

@section('content')
<x-page-header title="Mon Profil Professionnel" />

<div id="loading" class="loading">Chargement...</div>

<div id="profile-content" style="display: none;">
    <div class="profile-grid">
        
        <div class="card" id="info-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Mes Informations</h3>
                <x-btn variant="secondary" size="sm" id="edit-toggle" onclick="toggleEdit()">Modifier</x-btn>
            </div>

            <div class="avatar-section">
                <div class="avatar" id="avatar-display">
                    <span id="avatar-initials"></span>
                </div>
                <div>
                    <div style="font-size: 1.4rem; font-weight: 600;" id="display-name"></div>
                    <div style="font-family: 'DM Mono', monospace; font-size: 0.8rem; color: var(--cherry); text-transform: uppercase;" id="display-role"></div>
                </div>
            </div>

            <div class="photo-upload-zone" onclick="document.getElementById('photo-input').click()">
                <p style="font-family: 'DM Mono', monospace; font-size: 0.8rem;">Cliquez pour changer la photo</p>
                <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewProfilePhoto(event)">
            </div>

            <div class="info-row">
                <span class="info-key">Email</span>
                <span class="info-val" id="val-email"></span>
            </div>
            <div class="info-row">
                <span class="info-key">Telephone</span>
                <span class="info-val" id="val-telephone"></span>
                <input class="edit-input" id="edit-telephone" placeholder="Telephone">
            </div>
            <div class="info-row">
                <span class="info-key">Ville</span>
                <span class="info-val" id="val-ville"></span>
                <input class="edit-input" id="edit-ville" placeholder="Ville">
            </div>
            <div class="info-row">
                <span class="info-key">Adresse</span>
                <span class="info-val" id="val-adresse"></span>
                <input class="edit-input" id="edit-adresse" placeholder="Adresse complete">
            </div>
            <div class="info-row">
                <span class="info-key">Inscription</span>
                <span class="info-val" id="val-date"></span>
            </div>

            <div id="edit-buttons" style="display: none; margin-top: 16px; display: flex; gap: 12px;">
                <x-btn size="sm" onclick="saveProfile()" id="save-btn" style="display: none;">Sauvegarder</x-btn>
                <x-btn variant="secondary" size="sm" onclick="cancelEdit()" id="cancel-btn" style="display: none;">Annuler</x-btn>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Mon Entreprise</h3>
            <div class="info-row">
                <span class="info-key">Nom entreprise</span>
                <span class="info-val" id="val-entreprise"></span>
            </div>
            <div class="info-row">
                <span class="info-key">Numero SIRET</span>
                <span class="info-val" id="val-siret"></span>
            </div>
            <div class="info-row">
                <span class="info-key">Statut SIRET</span>
                <span class="info-val" id="val-siret-status"></span>
            </div>
        </div>

        <div class="card" id="score-card">
            <h3 class="card-title">Upcycling Score</h3>
            <div class="score-display">
                <div class="score-level" id="score-level" style="display:none;"></div>
                <div class="score-number" id="score-value">0</div>
                <div class="score-label">points &middot; <span id="score-dechets">0</span> kg de dechets evites</div>
                <div id="certif-container"></div>
            </div>
            <div class="score-progress" id="score-progress" style="display:none;">
                <div class="score-progress-head">
                    <span id="score-next-label"></span>
                    <span id="score-next-points"></span>
                </div>
                <div class="score-bar"><div class="score-bar-fill" id="score-bar-fill"></div></div>
            </div>
            <div class="score-ladder" id="score-ladder"></div>
        </div>

        <div class="card">
            <h3 class="card-title">Preferences de Notifications</h3>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(18,3,9,0.1);">
                <div>
                    <div style="font-size: 0.95rem;">Notifications push</div>
                    <div style="font-size: 0.8rem; color: rgba(18,3,9,0.6); margin-top: 2px;">Recevez des alertes en temps reel</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-push" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 0;">
                <div>
                    <div style="font-size: 0.95rem;">Notifications email</div>
                    <div style="font-size: 0.8rem; color: rgba(18,3,9,0.6); margin-top: 2px;">Recevez les mises a jour par email</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-email" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Donnees Personnelles</h3>
            <p style="margin-bottom: 16px; font-size: 0.95rem;">Recuperez un fichier contenant toutes vos informations</p>
            <x-btn size="sm" onclick="downloadPDF()">Telecharger mes donnees</x-btn>
        </div>

        <div class="card full-width">
            <h3 class="card-title">Securite</h3>
            <x-btn variant="secondary" size="sm" class="btn-disabled" disabled>Modifier mon mot de passe</x-btn>
            <p style="font-size: 0.8rem; margin-top: 8px; color: rgba(18,3,9,0.5);">Fonctionnalite a venir</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let userData = null;
let isEditing = false;
let profilePhotoB64 = null;

async function loadProfile() {
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me');
        if (!resp) return;
        userData = await resp.json();

        document.getElementById('loading').style.display = 'none';
        document.getElementById('profile-content').style.display = 'block';

        // Fill profile info
        document.getElementById('display-name').textContent = userData.prenom + ' ' + userData.nom;
        document.getElementById('display-role').textContent = 'Professionnel';
        document.getElementById('avatar-initials').textContent = (userData.prenom[0] || '') + (userData.nom[0] || '');

        if (userData.photo_profil_url) {
            document.getElementById('avatar-display').innerHTML = '<img src="/uploads/' + userData.photo_profil_url + '" alt="Avatar">';
        }

        document.getElementById('val-email').textContent = userData.email;
        document.getElementById('val-telephone').textContent = userData.telephone || 'Non renseigne';
        document.getElementById('val-ville').textContent = userData.ville || 'Non renseigne';
        document.getElementById('val-adresse').textContent = userData.adresse_complete || 'Non renseigne';
        document.getElementById('val-date').textContent = new Date(userData.date_creation).toLocaleDateString('fr-FR');

        // Enterprise info
        document.getElementById('val-entreprise').textContent = userData.nom_entreprise || 'Non renseigne';
        document.getElementById('val-siret').textContent = userData.numero_siret || 'Non renseigne';

        const siretStatusEl = document.getElementById('val-siret-status');
        if (userData.numero_siret) {
            if (userData.est_certifie || userData.siret_verifie) {
                siretStatusEl.innerHTML = '<span class="siret-badge siret-verified">Verifie</span>';
            } else {
                siretStatusEl.innerHTML = '<span class="siret-badge siret-pending">En attente</span>';
            }
        } else {
            siretStatusEl.textContent = '-';
        }

        // Notifications
        document.getElementById('notif-push').checked = userData.notif_push_active;
        document.getElementById('notif-email').checked = userData.notif_email_active;

        // Score
        document.getElementById('score-value').textContent = userData.upcycling_score || 0;
        loadScore();

    } catch (err) {
        showAlert('Erreur de chargement du profil', 'error');
    }
}

function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

async function loadScore() {
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me/score');
        if (!resp || !resp.ok) return;
        const s = await resp.json();

        document.getElementById('score-value').textContent = s.score;
        document.getElementById('score-dechets').textContent = (s.dechets_evites_kg || 0).toLocaleString('fr-FR', { maximumFractionDigits: 1 });

        const lvl = document.getElementById('score-level');
        if (s.niveau_actuel && s.niveau_actuel.nom) {
            lvl.textContent = s.niveau_actuel.nom;
            lvl.style.background = s.niveau_actuel.couleur || 'var(--wheat)';
            lvl.style.display = 'inline-block';
        }

        document.getElementById('certif-container').innerHTML = s.est_certifie
            ? '<div class="certif-badge">Compte Certifie</div>' : '';

        const prog = document.getElementById('score-progress');
        if (s.prochain_palier) {
            prog.style.display = 'block';
            document.getElementById('score-next-label').textContent = 'Vers ' + s.prochain_palier.nom;
            document.getElementById('score-next-points').textContent = s.points_manquants + ' pts';
            document.getElementById('score-bar-fill').style.width = (s.progression_pct || 0) + '%';
        } else {
            prog.style.display = 'none';
        }

        if (Array.isArray(s.paliers)) {
            document.getElementById('score-ladder').innerHTML = s.paliers.map(p => {
                const reached = s.score >= p.seuil_min;
                const cert = p.confere_certification ? ' &#10003;' : '';
                return '<div class="ladder-item' + (reached ? ' reached' : '') + '">'
                    + '<span class="ladder-dot" style="background:' + (reached ? (p.couleur || 'var(--forest)') : 'transparent') + ';"></span>'
                    + '<span class="ladder-name">' + escapeHtml(p.nom) + cert + '</span>'
                    + '<span class="ladder-seuil">' + p.seuil_min + '</span>'
                    + '</div>';
            }).join('');
        }
    } catch (err) { /* score indisponible */ }
}

function toggleEdit() {
    isEditing = !isEditing;
    const card = document.getElementById('info-card');

    if (isEditing) {
        card.classList.add('editing');
        document.getElementById('edit-toggle').textContent = 'Annuler';
        document.getElementById('save-btn').style.display = 'inline-flex';
        document.getElementById('cancel-btn').style.display = 'inline-flex';

        document.getElementById('edit-telephone').value = userData.telephone || '';
        document.getElementById('edit-ville').value = userData.ville || '';
        document.getElementById('edit-adresse').value = userData.adresse_complete || '';
    } else {
        cancelEdit();
    }
}

function cancelEdit() {
    isEditing = false;
    profilePhotoB64 = null;
    document.getElementById('info-card').classList.remove('editing');
    document.getElementById('edit-toggle').textContent = 'Modifier';
    document.getElementById('save-btn').style.display = 'none';
    document.getElementById('cancel-btn').style.display = 'none';
}

function previewProfilePhoto(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        showAlert('Photo trop lourde (max 5 Mo)', 'error');
        return;
    }
    const reader = new FileReader();
    reader.onload = function(ev) {
        profilePhotoB64 = ev.target.result;
        document.getElementById('avatar-display').innerHTML = '<img src="' + ev.target.result + '" alt="Avatar">';
    };
    reader.readAsDataURL(file);
}

async function saveProfile() {
    const payload = {
        telephone: document.getElementById('edit-telephone').value || null,
        ville: document.getElementById('edit-ville').value || null,
        adresse_complete: document.getElementById('edit-adresse').value || null
    };
    if (profilePhotoB64) {
        payload.photo_profil = profilePhotoB64;
    }

    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
        if (!resp) return;
        const data = await resp.json();

        if (resp.ok) {
            showAlert('Profil mis a jour avec succes', 'success');
            cancelEdit();
            loadProfile();
        } else {
            showAlert(data.erreur || 'Erreur de mise a jour', 'error');
        }
    } catch (err) {
        showAlert('Erreur de connexion', 'error');
    }
}

let notifTimeout = null;
async function updateNotifs() {
    clearTimeout(notifTimeout);
    notifTimeout = setTimeout(async () => {
        const payload = {
            notif_push_active: document.getElementById('notif-push').checked,
            notif_email_active: document.getElementById('notif-email').checked
        };
        try {
            const resp = await apiFetch('/api/v1/utilisateurs/me/notifications', {
                method: 'PUT',
                body: JSON.stringify(payload)
            });
            if (resp && resp.ok) {
                showAlert('Preferences mises a jour', 'success');
            }
        } catch (err) {
            showAlert('Erreur', 'error');
        }
    }, 500);
}

async function downloadPDF() {
    try {
        const token = getToken();
        const resp = await fetch(API_BASE + '/api/v1/utilisateurs/me/export-pdf', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!resp.ok) {
            showAlert('Erreur lors du telechargement', 'error');
            return;
        }
        const blob = await resp.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'mes_donnees_upcycleconnect.pdf';
        a.click();
        window.URL.revokeObjectURL(url);
        showAlert('Telechargement lance', 'success');
    } catch (err) {
        showAlert('Erreur de telechargement', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadProfile);
</script>
@endsection

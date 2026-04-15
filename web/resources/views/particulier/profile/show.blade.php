@extends('layouts.particulier')
@section('title', 'Mon Profil')

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

    .score-display { text-align: center; padding: 20px; }
    .score-number { font-family: 'Bebas Neue', sans-serif; font-size: 4rem; color: var(--coffee); line-height: 1; }
    .score-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; color: var(--cherry); margin-top: 4px; }
    .certif-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; background: var(--forest); color: var(--cream); border: 2px solid var(--coffee); font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; margin-top: 12px; }

    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .toggle-label { font-size: 0.95rem; }
    .toggle-desc { font-size: 0.8rem; color: rgba(18,3,9,0.6); margin-top: 2px; }

    .edit-input { width: 100%; border: 2px solid var(--coffee); padding: 8px 12px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; display: none; border-radius: 0; }
    .editing .info-val { display: none; }
    .editing .edit-input { display: block; }

    .full-width { grid-column: 1 / -1; }

    .photo-upload-zone { border: 3px dashed var(--coffee); padding: 16px; text-align: center; cursor: pointer; background: white; margin-top: 8px; display: none; }
    .editing .photo-upload-zone { display: block; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">Mon Profil</h1>
</div>

<div id="loading" class="loading">Chargement...</div>

<div id="profile-content" style="display: none;">
    <div class="profile-grid">
        <!-- Mes Informations -->
        <div class="card" id="info-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Mes Informations</h3>
                <button class="btn-secondary btn-sm" id="edit-toggle" onclick="toggleEdit()">Modifier</button>
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
                <button class="btn-primary btn-sm" onclick="saveProfile()" id="save-btn" style="display: none;">Sauvegarder</button>
                <button class="btn-secondary btn-sm" onclick="cancelEdit()" id="cancel-btn" style="display: none;">Annuler</button>
            </div>
        </div>

        <!-- Upcycling Score -->
        <div class="card">
            <h3 class="card-title">Upcycling Score</h3>
            <div class="score-display">
                <div class="score-number" id="score-value">0</div>
                <div class="score-label">Upcycling Score</div>
                <div id="certif-container"></div>
            </div>
        </div>

        <!-- Evenements inscrits -->
        <div class="card full-width">
            <h3 class="card-title">Mes Evenements Inscrits</h3>
            <div id="events-container">
                <div class="loading">Chargement des evenements...</div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card">
            <h3 class="card-title">Preferences de Notifications</h3>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Notifications push</div>
                    <div class="toggle-desc">Recevez des alertes en temps reel</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-push" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Notifications email</div>
                    <div class="toggle-desc">Recevez les mises a jour par email</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-email" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p style="font-size: 0.8rem; margin-top: 12px; color: rgba(18,3,9,0.5);">Vous recevrez les mises a jour sur vos annonces et evenements</p>
        </div>

        <!-- Donnees personnelles -->
        <div class="card">
            <h3 class="card-title">Donnees Personnelles</h3>
            <p style="margin-bottom: 16px; font-size: 0.95rem;">Recuperez un fichier contenant toutes vos informations</p>
            <button class="btn-primary btn-sm" onclick="downloadPDF()">Telecharger mes donnees</button>
        </div>

        <!-- Securite -->
        <div class="card full-width">
            <h3 class="card-title">Securite</h3>
            <button class="btn-secondary btn-sm btn-disabled">Modifier mon mot de passe</button>
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
        document.getElementById('display-role').textContent = userData.role;
        document.getElementById('avatar-initials').textContent = (userData.prenom[0] || '') + (userData.nom[0] || '');

        if (userData.photo_profil_url) {
            document.getElementById('avatar-display').innerHTML = '<img src="/uploads/' + userData.photo_profil_url + '" alt="Avatar">';
        }

        document.getElementById('val-email').textContent = userData.email;
        document.getElementById('val-telephone').textContent = userData.telephone || 'Non renseigne';
        document.getElementById('val-ville').textContent = userData.ville || 'Non renseigne';
        document.getElementById('val-adresse').textContent = userData.adresse_complete || 'Non renseigne';
        document.getElementById('val-date').textContent = new Date(userData.date_creation).toLocaleDateString('fr-FR');

        // Score
        document.getElementById('score-value').textContent = userData.upcycling_score || 0;
        if (userData.est_certifie) {
            document.getElementById('certif-container').innerHTML = '<div class="certif-badge">Compte Certifie</div>';
        }

        // Notifications
        document.getElementById('notif-push').checked = userData.notif_push_active;
        document.getElementById('notif-email').checked = userData.notif_email_active;

        // Load events
        loadEvents();
    } catch (err) {
        showAlert('Erreur de chargement du profil', 'error');
    }
}

async function loadEvents() {
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me/evenements-inscrits');
        if (!resp) return;
        const events = await resp.json();
        const container = document.getElementById('events-container');

        if (events.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px; font-family: \'DM Mono\', monospace; color: rgba(18,3,9,0.5);">Vous n\'etes inscrit a aucun evenement</p>';
            return;
        }

        let html = '<div class="table-container"><table><thead><tr><th>Titre</th><th>Date</th><th>Statut Paiement</th></tr></thead><tbody>';
        events.forEach(ev => {
            const date = new Date(ev.date_debut).toLocaleDateString('fr-FR');
            html += '<tr><td>' + ev.titre + '</td><td>' + date + '</td><td><span class="badge badge-waiting">' + ev.statut_paiement + '</span></td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (err) {
        document.getElementById('events-container').innerHTML = '<p style="color: var(--cherry);">Erreur de chargement</p>';
    }
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
        a.download = 'mes_donnees_upcycleconnect.txt';
        a.click();
        window.URL.revokeObjectURL(url);
        showAlert('Telechargement lance', 'success');
    } catch (err) {
        showAlert('Erreur de telechargement', 'error');
    }
}

// Load on page ready
document.addEventListener('DOMContentLoaded', loadProfile);
</script>
@endsection

@extends('layouts.particulier')
@section('title', 'Profil & paramètres')

{{-- Profil particulier : infos, score, notifications, export RGPD, mot de passe, suppression. --}}

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

    .toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(18,3,9,0.1); }
    .toggle-label { font-size: 0.95rem; }
    .toggle-desc { font-size: 0.8rem; color: rgba(18,3,9,0.6); margin-top: 2px; }

    .edit-input { width: 100%; border: 2px solid var(--coffee); padding: 8px 12px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; display: none; border-radius: 0; }
    .editing .info-val { display: none; }
    .editing .edit-input { display: block; }
    /* En édition : label au-dessus du champ. */
    .editing .info-row { flex-direction: column; align-items: stretch; gap: 6px; padding: 14px 0; }
    .editing .info-key { margin-bottom: 2px; }
    /* Wrapper autocomplete visible en édition seulement (sinon casse l'alignement en lecture). */
    .addr-ac-wrap { display: none; }
    .editing .addr-ac-wrap { display: block; }
    .edit-input[readonly] { background: rgba(18,3,9,0.04); color: var(--coffee); cursor: default; }

    .full-width { grid-column: 1 / -1; }

    .photo-upload-zone { border: 3px dashed var(--coffee); padding: 16px; text-align: center; cursor: pointer; background: white; margin-top: 8px; display: none; }
    .editing .photo-upload-zone { display: block; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title" data-i18n="nav.profile">Profil &amp; paramètres</h1>
</div>

<div id="loading" class="loading" data-i18n="common.loading">Chargement...</div>

<div id="profile-content" style="display: none;">
    <div class="profile-grid">

        {{-- === Mes informations === --}}
        <div class="card" id="info-card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title"><span data-i18n="prof.myinfo">Mes informations</span></h3>
                <button class="btn-secondary btn-sm" id="edit-toggle" onclick="toggleEdit()"><span data-i18n="btn.edit">Modifier</span></button>
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
                <p style="font-family: 'DM Mono', monospace; font-size: 0.8rem;"><span data-i18n="prof.changephoto">Cliquez pour changer la photo</span></p>
                <input type="file" id="photo-input" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewProfilePhoto(event)">
            </div>

            <div class="info-row">
                <span class="info-key"><span data-i18n="prof.email">Email</span></span>
                <span class="info-val" id="val-email"></span>
            </div>
            <div class="info-row">
                <span class="info-key"><span data-i18n="prof.phone">Telephone</span></span>
                <span class="info-val" id="val-telephone"></span>
                <input class="edit-input" id="edit-telephone" placeholder="Telephone" data-i18n-ph="prof.phone">
            </div>
            <div class="info-row">
                <span class="info-key"><span data-i18n="prof.city">Ville</span></span>
                <span class="info-val" id="val-ville"></span>
                <input class="edit-input" id="edit-ville" placeholder="Renseignée via l'adresse" data-i18n-ph="prof.cityauto" readonly>
            </div>
            <div class="info-row">
                <span class="info-key"><span data-i18n="prof.address">Adresse</span></span>
                <span class="info-val" id="val-adresse"></span>
                <input class="edit-input" id="edit-adresse" placeholder="Adresse complete" data-i18n-ph="prof.address.ph">
            </div>
            <div class="info-row">
                <span class="info-key"><span data-i18n="prof.joined">Inscription</span></span>
                <span class="info-val" id="val-date"></span>
            </div>

            <div id="edit-buttons" style="display: none; margin-top: 16px; gap: 12px;">
                <button class="btn-primary btn-sm" onclick="saveProfile()" id="save-btn" style="display: none;"><span data-i18n="btn.save">Sauvegarder</span></button>
                <button class="btn-secondary btn-sm" onclick="cancelEdit()" id="cancel-btn" style="display: none;"><span data-i18n="btn.cancel">Annuler</span></button>
            </div>
        </div>

        {{-- === Upcycling Score === --}}
        <div class="card" id="score-card">
            <h3 class="card-title"><span data-i18n="prof.score">Upcycling Score</span></h3>
            <div class="score-display">
                <div class="score-level" id="score-level" style="display:none;"></div>
                <div class="score-number" id="score-value">0</div>
                <div class="score-label"><span data-i18n="prof.points">points</span> &middot; <span id="score-dechets">0</span> <span data-i18n="prof.wastekg">kg de dechets evites</span></div>
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

        {{-- === Notifications === --}}
        <div class="card">
            <h3 class="card-title"><span data-i18n="prof.notifprefs">Préférences de notifications</span></h3>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label"><span data-i18n="prof.notifpush">Notifications push</span></div>
                    <div class="toggle-desc"><span data-i18n="prof.notifpush.desc">Recevez des alertes en temps reel</span></div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-push" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="toggle-row">
                <div>
                    <div class="toggle-label"><span data-i18n="prof.notifemail">Notifications email</span></div>
                    <div class="toggle-desc"><span data-i18n="prof.notifemail.desc">Recevez les mises a jour par email</span></div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notif-email" onchange="updateNotifs()">
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <p style="font-size: 0.8rem; margin-top: 12px; color: rgba(18,3,9,0.5);"><span data-i18n="prof.notif.note">Vous recevrez les mises a jour sur vos annonces et evenements</span></p>
        </div>

        {{-- === Données personnelles (RGPD) === --}}
        <div class="card">
            <h3 class="card-title"><span data-i18n="prof.personaldata">Données personnelles</span></h3>
            <p style="margin-bottom: 16px; font-size: 0.95rem;"><span data-i18n="prof.personaldata.desc">Recuperez un fichier contenant toutes vos informations</span></p>
            <button class="btn-primary btn-sm" onclick="downloadPDF()"><span data-i18n="prof.download">Telecharger mes donnees</span></button>
        </div>

        {{-- === Sécurité === --}}
        <div class="card full-width">
            <h3 class="card-title"><span data-i18n="prof.security">Sécurité</span></h3>
            <button class="btn-secondary btn-sm" type="button" onclick="togglePwdForm()"><span data-i18n="prof.changepwd">Modifier mon mot de passe</span></button>
            <form id="pwd-form" style="display:none; margin-top:16px; max-width:380px;" onsubmit="changePassword(event)">
                <div class="form-group">
                    <label class="form-label" for="pwd-old"><span data-i18n="prof.pwd.current">Mot de passe actuel</span></label>
                    <input type="password" id="pwd-old" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pwd-new"><span data-i18n="prof.pwd.new">Nouveau mot de passe (min. 8 caractères)</span></label>
                    <input type="password" id="pwd-new" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pwd-confirm"><span data-i18n="prof.pwd.confirm">Confirmer le nouveau mot de passe</span></label>
                    <input type="password" id="pwd-confirm" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary btn-sm"><span data-i18n="btn.save">Enregistrer</span></button>
                    <button type="button" class="btn-secondary btn-sm" onclick="togglePwdForm()"><span data-i18n="btn.cancel">Annuler</span></button>
                </div>
            </form>
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(164,36,59,0.2);">
                <p style="color:var(--cherry); margin-bottom:12px; font-family:'DM Mono',monospace; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.06em;">Zone dangereuse</p>
                <button type="button" onclick="deleteMyAccount()" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:var(--cherry);background:none;border:2px solid var(--cherry);padding:8px 20px;cursor:pointer;letter-spacing:0.04em;">Supprimer mon compte</button>
                <p style="font-size:0.75rem; color:rgba(18,3,9,0.5); margin-top:8px;">Vos donnees personnelles seront effacees (RGPD). Cette action est irreversible.</p>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- === Scripts === --}}
@section('scripts')
<script>
let userData = null;
let isEditing = false;
let profilePhotoB64 = null;

// Charge le profil et remplit l'affichage.
async function loadProfile() {
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me');
        if (!resp) return;
        userData = await resp.json();

        document.getElementById('loading').style.display = 'none';
        document.getElementById('profile-content').style.display = 'block';

        document.getElementById('display-name').textContent = userData.prenom + ' ' + userData.nom;
        document.getElementById('display-role').textContent = userData.role;

        // Reconstruit l'avatar à chaque appel (idempotent) : évite un null.textContent au rechargement.
        const initials = ((userData.prenom || ' ')[0] || '') + ((userData.nom || ' ')[0] || '');
        const avatarEl = document.getElementById('avatar-display');
        if (userData.photo_profil_url) {
            avatarEl.innerHTML = '<img src="' + window.MEDIA_BASE + '/' + userData.photo_profil_url + '" alt="Avatar">';
        } else {
            avatarEl.innerHTML = '<span id="avatar-initials">' + initials + '</span>';
        }

        document.getElementById('val-email').textContent = userData.email;
        document.getElementById('val-telephone').textContent = userData.telephone || 'Non renseigne';
        document.getElementById('val-ville').textContent = userData.ville || 'Non renseigne';
        document.getElementById('val-adresse').textContent = userData.adresse_complete || 'Non renseigne';
        document.getElementById('val-date').textContent = new Date(userData.date_creation).toLocaleDateString('fr-FR');

        document.getElementById('score-value').textContent = userData.upcycling_score || 0;
        loadScore();

        document.getElementById('notif-push').checked = userData.notif_push_active;
        document.getElementById('notif-email').checked = userData.notif_email_active;
    } catch (err) {
        showAlert('Erreur de chargement du profil', 'error');
    }
}

function togglePwdForm() {
    const f = document.getElementById('pwd-form');
    if (!f) return;
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
    if (f.style.display === 'none') f.reset();
}

async function changePassword(e) {
    e.preventDefault();
    const oldp = document.getElementById('pwd-old').value;
    const newp = document.getElementById('pwd-new').value;
    const conf = document.getElementById('pwd-confirm').value;
    if (newp.length < 8) { showAlert('Le nouveau mot de passe doit faire au moins 8 caractères', 'error'); return; }
    if (newp !== conf) { showAlert('Les deux mots de passe ne correspondent pas', 'error'); return; }
    if (newp === oldp) { showAlert('Le nouveau mot de passe doit être différent de l\'ancien', 'error'); return; }
    const resp = await apiFetch('/api/v1/utilisateurs/me/password', {
        method: 'PUT',
        body: JSON.stringify({ ancien_mot_de_passe: oldp, nouveau_mot_de_passe: newp })
    });
    if (resp && resp.ok) {
        showAlert('Mot de passe modifié avec succès', 'success');
        togglePwdForm();
    } else {
        const d = resp ? await resp.json() : {};
        showAlert(d.erreur || 'Erreur lors de la modification', 'error');
    }
}

async function deleteMyAccount() {
    if (!await confirmAction('Supprimer définitivement votre compte ? Vos données personnelles seront effacées. Cette action est irréversible.')) return;
    const resp = await apiFetch('/api/v1/utilisateurs/me', { method: 'DELETE' });
    if (resp && resp.ok) {
        localStorage.removeItem('auth_token');
        alert('Votre compte a ete supprime. Vous allez etre redirige vers l\'accueil.');
        window.location.href = '/';
    } else {
        const d = resp ? await resp.json() : {};
        showAlert(d.erreur || 'Erreur lors de la suppression', 'error');
    }
}

// Charge le détail du score.
async function loadScore() {
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me/score');
        if (!resp || !resp.ok) return;
        const s = await resp.json();

        document.getElementById('score-value').textContent = s.score;
        const dechets = (s.dechets_evites_kg || 0).toLocaleString('fr-FR', { maximumFractionDigits: 1 });
        document.getElementById('score-dechets').textContent = dechets;

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
                    + '<span class="ladder-name">' + (p.nom || '') + cert + '</span>'
                    + '<span class="ladder-seuil">' + p.seuil_min + '</span>'
                    + '</div>';
            }).join('');
        }
    } catch (err) { /* score indisponible : on garde la valeur de base */ }
}

// Passe la carte infos en mode édition.
function toggleEdit() {
    isEditing = !isEditing;
    const card = document.getElementById('info-card');

    if (isEditing) {
        card.classList.add('editing');
        document.getElementById('edit-toggle').textContent = 'Annuler';
        document.getElementById('edit-buttons').style.display = 'flex';
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
    document.getElementById('edit-buttons').style.display = 'none';
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

// Enregistre les modifications du profil.
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

document.addEventListener('DOMContentLoaded', function () {
    loadProfile();
    // Autocomplétion d'adresse : remplit aussi la ville à la sélection.
    if (window.initAddressAutocomplete) {
        window.initAddressAutocomplete(
            document.getElementById('edit-adresse'),
            { city: document.getElementById('edit-ville') }
        );
    }
});
</script>
@include('partials.address-autocomplete')
@endsection

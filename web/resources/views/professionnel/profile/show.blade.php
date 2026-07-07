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

        <div class="card full-width" id="card-ventes">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title" style="margin-bottom:0;">Mes Ventes</h3>
            </div>
            <div id="ventes-container" style="margin-top:16px;">
                <div style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">Chargement…</div>
            </div>
        </div>

        <div class="card full-width">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 class="card-title" style="margin-bottom:0;">Mes Annonces</h3>
                <button class="btn btn-sm" onclick="window.location.href='/particulier/annonces/create'">Deposer une annonce</button>
            </div>
            <div id="pro-annonces-container">
                <div style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">Chargement…</div>
            </div>
        </div>

        <div class="card full-width" id="card-reservations">
            <h3 class="card-title">Mes Réservations Formations</h3>
            <div id="reservations-container">
                <div style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">Chargement…</div>
            </div>
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

        <div class="card" id="card-abonnement">
            <h3 class="card-title">Mon Abonnement</h3>
            <div id="abo-loading" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">Chargement…</div>
            <div id="abo-content" style="display:none">
                <div id="abo-badge" style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border:2px solid var(--coffee);font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:16px;background:var(--forest);color:var(--cream);">
                    <span id="abo-nom">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(18,3,9,0.1);">
                    <span class="info-key">Prix</span>
                    <span id="abo-prix" class="info-val">—</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(18,3,9,0.1);">
                    <span class="info-key">Actif depuis</span>
                    <span id="abo-depuis" class="info-val">—</span>
                </div>
                <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                    <a href="/professionnel/abonnement" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">Changer de plan</a>
                    <button onclick="openPortail()" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">Gérer / Facturas</button>
                </div>
            </div>
            <div id="abo-freemium" style="display:none">
                <p style="font-size:0.9rem;margin-bottom:16px;color:var(--teal);">Vous êtes sur le plan <strong>Freemium</strong>. Passez à un plan Pro pour accéder à plus de fonctionnalités.</p>
                <a href="/professionnel/abonnement" class="btn btn-primary" style="font-size:0.9rem;">Découvrir les offres Pro</a>
            </div>
        </div>

        <div class="card">
            <h3 class="card-title">Donnees Personnelles</h3>
            <p style="margin-bottom: 16px; font-size: 0.95rem;">Recuperez un fichier contenant toutes vos informations</p>
            <x-btn size="sm" onclick="downloadPDF()">Telecharger mes donnees</x-btn>
        </div>

        <div class="card full-width" id="card-badges-pro" style="display:none;">
            <h3 class="card-title">Mes Badges UpcycleConnect</h3>
            <div id="badges-pro-container">
                <div style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">Chargement…</div>
            </div>
        </div>

        <div class="card full-width">
            <h3 class="card-title">Securite</h3>
            <x-btn variant="secondary" size="sm" type="button" onclick="togglePwdForm()">Modifier mon mot de passe</x-btn>
            <form id="pwd-form" style="display:none; margin-top:16px; max-width:380px;" onsubmit="changePassword(event)">
                <div class="form-group">
                    <label class="form-label" for="pwd-old">Mot de passe actuel</label>
                    <input type="password" id="pwd-old" class="form-input" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pwd-new">Nouveau mot de passe (min. 8 caractères)</label>
                    <input type="password" id="pwd-new" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label class="form-label" for="pwd-confirm">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="pwd-confirm" class="form-input" required minlength="8" autocomplete="new-password">
                </div>
                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="submit" class="btn-primary btn-sm">Enregistrer</button>
                    <button type="button" class="btn-secondary btn-sm" onclick="togglePwdForm()">Annuler</button>
                </div>
            </form>
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(164,36,59,0.2);">
                <p style="font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.75rem;letter-spacing:0.06em;color:var(--cherry);margin-bottom:12px;">Zone dangereuse</p>
                <button type="button" onclick="deleteMyAccount()" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:var(--cherry);background:none;border:2px solid var(--cherry);padding:8px 20px;cursor:pointer;letter-spacing:0.04em;">Supprimer mon compte</button>
                <p style="font-size:0.75rem;color:rgba(18,3,9,0.5);margin-top:8px;">Vos donnees personnelles seront effacees (RGPD). Cette action est irreversible.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal modification annonce pro -->
<div id="modal-pro-edit-annonce" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.55);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:32px;width:100%;max-width:520px;position:relative;">
        <button id="modal-pro-edit-annonce-close" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--coffee);">&times;</button>
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:0.06em;margin-bottom:24px;">Modifier l'annonce</h3>
        <form id="form-pro-edit-annonce">
            <input type="hidden" id="pro-edit-annonce-id">
            <div class="form-group" style="margin-bottom:16px;">
                <label for="pro-edit-titre" class="form-label">Titre</label>
                <input type="text" id="pro-edit-titre" class="form-input" required maxlength="120">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="pro-edit-description" class="form-label">Description</label>
                <textarea id="pro-edit-description" class="form-input" rows="4" required style="resize:vertical;"></textarea>
            </div>
            <div id="pro-edit-prix-row" class="form-group" style="margin-bottom:16px;">
                <label for="pro-edit-prix" class="form-label">Prix (€)</label>
                <input type="number" id="pro-edit-prix" class="form-input" step="0.01" min="0">
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label for="pro-edit-mode" class="form-label">Mode de remise</label>
                <select id="pro-edit-mode" class="form-input">
                    <option value="main_propre">En main propre</option>
                    <option value="conteneur">Via conteneur</option>
                </select>
            </div>
            <p style="font-family:'DM Mono',monospace;font-size:0.75rem;color:var(--cherry);margin-bottom:16px;">La modification repasse l'annonce en attente de validation.</p>
            <button type="submit" class="btn btn-primary btn-block">Enregistrer les modifications</button>
        </form>
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
            document.getElementById('avatar-display').innerHTML = '<img src="' + window.MEDIA_BASE + '/' + userData.photo_profil_url + '" alt="Avatar">';
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

async function loadAbonnement() {
    try {
        const resp = await apiFetch('/api/v1/stripe/facturation');
        if (!resp || !resp.ok) return;
        const data = await resp.json();
        document.getElementById('abo-loading').style.display = 'none';
        if (data.abonnement_actif && data.abonnement_actif.prix_mensuel > 0) {
            document.getElementById('abo-nom').textContent = data.abonnement_actif.nom_plan;
            document.getElementById('abo-prix').textContent = data.abonnement_actif.prix_mensuel.toFixed(2) + ' € / mois';
            document.getElementById('abo-depuis').textContent = new Date(data.abonnement_actif.date_debut).toLocaleDateString('fr-FR');
            document.getElementById('abo-content').style.display = 'block';
        } else {
            document.getElementById('abo-freemium').style.display = 'block';
        }
    } catch(e) {
        document.getElementById('abo-loading').style.display = 'none';
        document.getElementById('abo-freemium').style.display = 'block';
    }
}

async function openPortail() {
    try {
        const resp = await apiFetch('/api/v1/stripe/abonnement/portail', { method: 'POST' });
        if (!resp) return;
        const data = await resp.json();
        if (data.url) window.location.href = data.url;
    } catch(e) {}
}

const STATUT_VENTE_LABELS = {
    'commandee':    { txt: 'Commandée', color: '#b07a00' },
    'deposee':      { txt: 'Déposée',   color: '#1a6b8a' },
    'en_conteneur': { txt: 'En conteneur', color: '#1a6b8a' },
    'recuperee':    { txt: 'Récupérée', color: '#2a6b3c' },
    'annulee':      { txt: 'Annulée',   color: '#a72f43' }
};

async function loadVentes() {
    const container = document.getElementById('ventes-container');
    try {
        const resp = await apiFetch('/api/v1/ventes/me');
        if (!resp) return;
        const ventes = await resp.json();
        if (!ventes || ventes.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;padding:12px 0;">Aucune vente pour le moment.</p>';
            return;
        }
        const rows = ventes.slice(0, 5).map(v => {
            const st = STATUT_VENTE_LABELS[v.statut] || { txt: v.statut, color: '#666' };
            const date = new Date(v.date_commande).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
            const prixNet = v.type_annonce === 'don' ? 'Gratuit' : ((parseFloat(v.prix) - parseFloat(v.montant_commission)).toFixed(2).replace('.', ',') + ' €');
            return `<tr>
                <td><a href="/annonces/${v.id_annonce}" style="font-weight:600;text-decoration:none;">${v.titre}</a></td>
                <td style="font-size:0.8rem;opacity:0.7;">${v.acheteur_prenom} ${v.acheteur_nom_initiale}</td>
                <td style="white-space:nowrap;font-family:'DM Mono',monospace;font-size:0.8rem;">${date}</td>
                <td><span style="font-size:0.75rem;padding:3px 8px;border:1px solid ${st.color};color:${st.color};font-family:'DM Mono',monospace;text-transform:uppercase;letter-spacing:0.04em;">${st.txt}</span></td>
                <td style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;text-align:right;color:var(--forest);">${prixNet}</td>
            </tr>`;
        }).join('');
        container.innerHTML = `<div class="table-container" style="margin-top:0;"><table><thead><tr><th>Annonce</th><th>Acheteur</th><th>Date</th><th>Statut</th><th style="text-align:right;">Net reçu</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    } catch(e) {
        container.innerHTML = '<p style="color:var(--cherry,#a72f43);font-size:0.85rem;">Erreur de chargement</p>';
    }
}

const PRO_ANNONCE_STATUTS = {
    en_attente: { label: 'En attente', cls: 'badge-waiting' },
    validee:    { label: 'Validee',    cls: 'badge-valid' },
    refusee:    { label: 'Refusee',    cls: 'badge-cherry' },
    vendue:     { label: 'Vendue',     cls: 'badge-valid' },
    annulee:    { label: 'Annulee',    cls: '' },
};

async function loadProAnnonces() {
    const container = document.getElementById('pro-annonces-container');
    try {
        const resp = await apiFetch('/api/v1/annonces/me');
        if (!resp) return;
        const annonces = await resp.json();
        if (!annonces || annonces.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;padding:12px 0;">Aucune annonce deposee.</p>';
            return;
        }
        let html = '<div class="table-container" style="margin-top:0;"><table><thead><tr><th>Titre</th><th>Type</th><th>Prix</th><th>Statut</th><th>Date</th><th></th></tr></thead><tbody>';
        annonces.forEach(a => {
            const st = PRO_ANNONCE_STATUTS[a.statut] || { label: a.statut, cls: '' };
            const date = new Date(a.date_creation).toLocaleDateString('fr-FR');
            const prix = a.type_annonce === 'don' ? 'Gratuit' : (parseFloat(a.prix || 0).toFixed(2) + ' €');
            const editBtn = (a.statut === 'en_attente' || a.statut === 'validee')
                ? '<button class="pro-btn-edit-annonce" data-id="' + a.id_annonce + '" data-titre="' + escapeHtml(a.titre) + '" data-description="' + escapeHtml(a.description || '') + '" data-prix="' + (a.prix || '') + '" data-mode="' + (a.mode_remise || '') + '" data-type="' + a.type_annonce + '" style="font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;color:var(--teal);background:none;border:1px solid var(--teal);padding:4px 10px;cursor:pointer;">Modifier</button>' : '';
            html += '<tr><td><a href="/annonces/' + a.id_annonce + '" style="font-weight:600;text-decoration:none;">' + escapeHtml(a.titre) + '</a></td>'
                + '<td>' + (a.type_annonce === 'don' ? 'Don' : 'Vente') + '</td>'
                + '<td>' + prix + '</td>'
                + '<td><span class="badge ' + st.cls + '">' + st.label + '</span></td>'
                + '<td>' + date + '</td>'
                + '<td>' + editBtn + '</td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
        container.querySelectorAll('.pro-btn-edit-annonce').forEach(btn => {
            btn.addEventListener('click', () => openProEditModal(btn));
        });
    } catch(e) {
        container.innerHTML = '<p style="color:var(--cherry);font-size:0.85rem;">Erreur de chargement</p>';
    }
}

function openProEditModal(btn) {
    const isVente = btn.dataset.type === 'vente';
    document.getElementById('pro-edit-annonce-id').value = btn.dataset.id;
    document.getElementById('pro-edit-titre').value = btn.dataset.titre;
    document.getElementById('pro-edit-description').value = btn.dataset.description;
    document.getElementById('pro-edit-prix').value = btn.dataset.prix;
    document.getElementById('pro-edit-mode').value = btn.dataset.mode;
    document.getElementById('pro-edit-prix-row').style.display = isVente ? 'block' : 'none';
    document.getElementById('modal-pro-edit-annonce').style.display = 'flex';
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
        alert('Votre compte a ete supprime.');
        window.location.href = '/';
    } else {
        const d = resp ? await resp.json() : {};
        showAlert(d.erreur || 'Erreur lors de la suppression', 'error');
    }
}

async function loadReservations() {
    const container = document.getElementById('reservations-container');
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me/reservations');
        if (!resp || !resp.ok) { container.innerHTML = ''; return; }
        const items = await resp.json();
        if (!items || items.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;padding:12px 0;">Aucune réservation pour le moment</p>';
            return;
        }
        const STATUTS = { en_attente: 'En attente', confirme: 'Confirmée', annule: 'Annulée' };
        container.innerHTML = items.map(r => {
            const d = new Date(r.date_debut).toLocaleDateString('fr-FR', { day:'numeric', month:'short', year:'numeric' });
            const st = STATUTS[r.statut_paiement] || r.statut_paiement;
            const prix = r.prix === 0 ? 'Gratuit' : parseFloat(r.prix).toFixed(2).replace('.', ',') + ' €';
            return `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(18,3,9,0.08);">
                <div>
                    <a href="/formations/${r.id_catalogue_item}" style="font-weight:600;font-size:0.9rem;text-decoration:none;">${r.titre}</a>
                    <p style="font-family:'DM Mono',monospace;font-size:0.68rem;opacity:0.5;margin-top:2px;">${r.categorie} · ${d}${r.lieu ? ' · ' + r.lieu : ''}</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <span style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;color:var(--cherry);">${prix}</span>
                    <span style="font-family:'DM Mono',monospace;font-size:0.68rem;text-transform:uppercase;padding:3px 8px;border:1px solid rgba(18,3,9,0.15);">${st}</span>
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        container.innerHTML = '';
    }
}

const BADGE_NIVEAU_COLORS = {
    general:       { bg: '#244F26', label: 'Général' },
    intermediaire: { bg: '#1a6b8a', label: 'Intermédiaire' },
    avance:        { bg: '#A4243B', label: 'Avancé' },
};

async function loadBadgesPro() {
    const container = document.getElementById('badges-pro-container');
    try {
        const resp = await apiFetch('/api/v1/pro/badges');
        if (!resp || resp.status === 403) return; // non-Pro : on cache le bloc
        if (!resp.ok) return;
        const badges = await resp.json();
        if (!Array.isArray(badges) || badges.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;padding:12px 0;">Aucun badge obtenu pour l\'instant.</p>';
            document.getElementById('card-badges-pro').style.display = 'block';
            return;
        }
        const html = '<div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">'
            + badges.map(b => {
                const niv = BADGE_NIVEAU_COLORS[b.niveau] || { bg: '#555', label: b.niveau };
                const mat = b.type_materiau && b.type_materiau !== 'tous'
                    ? ' <span style="font-size:0.65rem;opacity:0.75;">(' + escapeHtml(b.type_materiau) + ')</span>' : '';
                const date = b.date_obtention
                    ? '<div style="font-size:0.65rem;opacity:0.6;margin-top:4px;">' + new Date(b.date_obtention).toLocaleDateString('fr-FR') + '</div>'
                    : '';
                return '<div title="' + escapeHtml(b.description || '') + '" style="display:flex;flex-direction:column;align-items:center;padding:14px 16px;border:2px solid #120309;min-width:120px;max-width:148px;text-align:center;background:var(--cream);">'
                    + '<div style="width:44px;height:44px;border-radius:50%;background:' + niv.bg + ';display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:8px;">★</div>'
                    + '<div style="font-family:\'Bebas Neue\',sans-serif;font-size:0.9rem;letter-spacing:0.04em;line-height:1.2;">' + escapeHtml(b.nom) + mat + '</div>'
                    + '<div style="font-family:\'DM Mono\',monospace;font-size:0.62rem;text-transform:uppercase;color:' + niv.bg + ';margin-top:4px;">' + niv.label + '</div>'
                    + date
                    + '</div>';
            }).join('')
            + '</div>';
        container.innerHTML = html;
        document.getElementById('card-badges-pro').style.display = 'block';
    } catch (e) { /* badges indisponibles */ }
}

document.addEventListener('DOMContentLoaded', () => {
    loadProfile(); loadAbonnement(); loadVentes(); loadProAnnonces(); loadReservations(); loadBadgesPro();

    document.getElementById('modal-pro-edit-annonce-close').addEventListener('click', () => {
        document.getElementById('modal-pro-edit-annonce').style.display = 'none';
    });
    document.getElementById('modal-pro-edit-annonce').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    document.getElementById('form-pro-edit-annonce').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('pro-edit-annonce-id').value;
        const body = {
            titre: document.getElementById('pro-edit-titre').value.trim(),
            description: document.getElementById('pro-edit-description').value.trim(),
            mode_remise: document.getElementById('pro-edit-mode').value,
        };
        const prixVal = document.getElementById('pro-edit-prix').value;
        if (prixVal) body.prix = parseFloat(prixVal);
        const submitBtn = this.querySelector('button[type=submit]');
        submitBtn.disabled = true;
        const resp = await apiFetch('/api/v1/annonces/' + id, { method: 'PUT', body: JSON.stringify(body) });
        submitBtn.disabled = false;
        if (resp && resp.ok) {
            document.getElementById('modal-pro-edit-annonce').style.display = 'none';
            showAlert('Annonce mise a jour — repassee en attente de validation', 'success');
            loadProAnnonces();
        } else {
            const d = resp ? await resp.json() : {};
            showAlert(d.erreur || 'Erreur lors de la modification', 'error');
        }
    });
});
</script>
@endsection

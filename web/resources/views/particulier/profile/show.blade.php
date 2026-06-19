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

    .full-width { grid-column: 1 / -1; }

    .photo-upload-zone { border: 3px dashed var(--coffee); padding: 16px; text-align: center; cursor: pointer; background: white; margin-top: 8px; display: none; }
    .editing .photo-upload-zone { display: block; }
</style>
@endsection

@section('content')
<x-page-header title="Mon Profil" />

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

        <div class="card full-width">
            <h3 class="card-title">Mes Evenements Inscrits</h3>
            <div id="events-container">
                <div class="loading">Chargement des evenements...</div>
            </div>
        </div>

        <div class="card full-width">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title">Mes Annonces</h3>
                <x-btn size="sm" onclick="window.location.href='/particulier/annonces/create'">Deposer une annonce</x-btn>
            </div>
            <div id="annonces-container">
                <div class="loading">Chargement des annonces...</div>
            </div>
        </div>

        <div class="card full-width" id="card-commandes">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title">Mes Achats</h3>
                <a href="/mes-commandes" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">Voir tout</a>
            </div>
            <div id="commandes-container">
                <div class="loading">Chargement des commandes...</div>
            </div>
        </div>

        <div class="card full-width" id="card-reservations">
            <h3 class="card-title">Mes Réservations Formations</h3>
            <div id="reservations-container">
                <div class="loading">Chargement des réservations...</div>
            </div>
        </div>

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

        <div class="card">
            <h3 class="card-title">Donnees Personnelles</h3>
            <p style="margin-bottom: 16px; font-size: 0.95rem;">Recuperez un fichier contenant toutes vos informations</p>
            <x-btn size="sm" onclick="downloadPDF()">Telecharger mes donnees</x-btn>
        </div>

        <div class="card full-width">
            <h3 class="card-title">Securite</h3>
            <x-btn variant="secondary" size="sm" class="btn-disabled" disabled>Modifier mon mot de passe</x-btn>
            <p style="font-size: 0.8rem; margin-top: 8px; color: rgba(18,3,9,0.5);">Fonctionnalite a venir</p>
            <div style="margin-top:24px; padding-top:20px; border-top:1px solid rgba(164,36,59,0.2);">
                <p style="font-size:0.85rem; color:var(--cherry); margin-bottom:12px; font-family:'DM Mono',monospace; text-transform:uppercase; font-size:0.75rem; letter-spacing:0.06em;">Zone dangereuse</p>
                <button type="button" onclick="deleteMyAccount()" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:var(--cherry);background:none;border:2px solid var(--cherry);padding:8px 20px;cursor:pointer;letter-spacing:0.04em;">Supprimer mon compte</button>
                <p style="font-size:0.75rem; color:rgba(18,3,9,0.5); margin-top:8px;">Vos donnees personnelles seront effacees (RGPD). Cette action est irreversible.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal modification annonce -->
<div id="modal-edit-annonce" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.55);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:32px;width:100%;max-width:520px;position:relative;">
        <button id="modal-edit-annonce-close" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--coffee);">&times;</button>
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:0.06em;margin-bottom:24px;">Modifier l'annonce</h3>
        <form id="form-edit-annonce">
            <input type="hidden" id="edit-annonce-id">
            <div class="form-group" style="margin-bottom:16px;">
                <label for="edit-titre" class="form-label">Titre</label>
                <input type="text" id="edit-titre" class="form-input" required maxlength="120">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="edit-description" class="form-label">Description</label>
                <textarea id="edit-description" class="form-input" rows="4" required style="resize:vertical;"></textarea>
            </div>
            <div id="edit-prix-row" class="form-group" style="margin-bottom:16px;">
                <label for="edit-prix" class="form-label">Prix (€)</label>
                <input type="number" id="edit-prix" class="form-input" step="0.01" min="0">
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label for="edit-mode" class="form-label">Mode de remise</label>
                <select id="edit-mode" class="form-input">
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

        // Score (detail complet via endpoint dedie)
        document.getElementById('score-value').textContent = userData.upcycling_score || 0;
        loadScore();

        // Notifications
        document.getElementById('notif-push').checked = userData.notif_push_active;
        document.getElementById('notif-email').checked = userData.notif_email_active;

        // Load events
        loadEvents();
        loadAnnonces();
    } catch (err) {
        showAlert('Erreur de chargement du profil', 'error');
    }
}

const ANNONCE_STATUTS = {
    en_attente: { label: 'En attente', cls: 'badge-waiting' },
    validee:    { label: 'Validee',    cls: 'badge-valid' },
    refusee:    { label: 'Refusee',    cls: 'badge-cherry' },
    vendue:     { label: 'Vendue',     cls: 'badge-valid' },
    annulee:    { label: 'Annulee',    cls: 'badge-waiting' },
    retiree:    { label: 'Retiree',    cls: 'badge-waiting' }
};

function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

async function loadAnnonces() {
    const container = document.getElementById('annonces-container');
    try {
        const resp = await apiFetch('/api/v1/annonces/me');
        if (!resp) return;
        const annonces = await resp.json();

        if (!annonces || annonces.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 20px; font-family: \'DM Mono\', monospace; color: rgba(18,3,9,0.5);">Vous n\'avez publie aucune annonce</p>';
            return;
        }

        let html = '<div class="table-container"><table><thead><tr><th></th><th>Titre</th><th>Type</th><th>Prix</th><th>Statut</th><th>Date</th><th></th></tr></thead><tbody>';
        annonces.forEach(a => {
            const st = ANNONCE_STATUTS[a.statut] || { label: a.statut, cls: 'badge-waiting' };
            const date = new Date(a.date_creation).toLocaleDateString('fr-FR');
            const prix = a.type_annonce === 'don' ? 'Gratuit' : (parseFloat(a.prix || 0).toFixed(2) + ' €');
            const thumb = a.photo
                ? '<img src="/uploads/' + escapeHtml(a.photo) + '" alt="" style="width:48px;height:48px;object-fit:cover;border:1px solid rgba(18,3,9,0.15);">'
                : '<div style="width:48px;height:48px;background:var(--wheat);"></div>';
            const refus = (a.statut === 'refusee' && a.motif_refus)
                ? '<div style="font-size:0.75rem;color:var(--cherry);margin-top:4px;">Motif : ' + escapeHtml(a.motif_refus) + '</div>' : '';
            const cancelBtn = a.statut === 'en_attente'
                ? '<button type="button" class="btn-cancel-annonce" data-id="' + a.id_annonce + '" style="font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;color:var(--cherry);background:none;border:1px solid var(--cherry);padding:4px 10px;cursor:pointer;">Annuler</button>'
                : '';
            const editBtn = (a.statut === 'en_attente' || a.statut === 'validee')
                ? '<button type="button" class="btn-edit-annonce" data-id="' + a.id_annonce + '" data-titre="' + escapeHtml(a.titre) + '" data-description="' + escapeHtml(a.description || '') + '" data-prix="' + (a.prix || '') + '" data-mode="' + (a.mode_remise || '') + '" data-type="' + a.type_annonce + '" style="font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;color:var(--teal);background:none;border:1px solid var(--teal);padding:4px 10px;cursor:pointer;margin-right:6px;">Modifier</button>'
                : '';
            html += '<tr>'
                + '<td>' + thumb + '</td>'
                + '<td><a href="/annonces/' + a.id_annonce + '" style="text-decoration:none;font-weight:600;">' + escapeHtml(a.titre) + '</a>' + refus + '</td>'
                + '<td>' + (a.type_annonce === 'don' ? 'Don' : 'Vente') + '</td>'
                + '<td>' + prix + '</td>'
                + '<td><span class="badge ' + st.cls + '">' + st.label + '</span></td>'
                + '<td>' + date + '</td>'
                + '<td>' + editBtn + cancelBtn + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;

        container.querySelectorAll('.btn-cancel-annonce').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (!confirm('Annuler cette annonce ? Cette action est definitive.')) return;
                btn.disabled = true;
                const resp = await apiFetch('/api/v1/annonces/' + btn.dataset.id + '/annuler', {
                    method: 'POST',
                    body: JSON.stringify({ motif_retrait: 'Annulee par le vendeur' })
                });
                if (resp && resp.ok) {
                    showAlert('Annonce annulee', 'success');
                    loadAnnonces();
                } else {
                    btn.disabled = false;
                    const d = resp ? await resp.json() : {};
                    showAlert(d.erreur || 'Annulation impossible', 'error');
                }
            });
        });
        container.querySelectorAll('.btn-edit-annonce').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(btn));
        });
    } catch (err) {
        container.innerHTML = '<p style="color: var(--cherry);">Erreur de chargement</p>';
    }
}

function openEditModal(btn) {
    const isVente = btn.dataset.type === 'vente';
    document.getElementById('edit-annonce-id').value = btn.dataset.id;
    document.getElementById('edit-titre').value = btn.dataset.titre;
    document.getElementById('edit-description').value = btn.dataset.description;
    document.getElementById('edit-prix').value = btn.dataset.prix;
    document.getElementById('edit-mode').value = btn.dataset.mode;
    document.getElementById('edit-prix-row').style.display = isVente ? 'block' : 'none';
    document.getElementById('modal-edit-annonce').style.display = 'flex';
}

async function deleteMyAccount() {
    if (!confirm('Supprimer definitivement votre compte ? Vos donnees personnelles seront effacees. Cette action est irreversible.')) return;
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

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modal-edit-annonce-close').addEventListener('click', () => {
        document.getElementById('modal-edit-annonce').style.display = 'none';
    });
    document.getElementById('modal-edit-annonce').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
    document.getElementById('form-edit-annonce').addEventListener('submit', async function(e) {
        e.preventDefault();
        const id = document.getElementById('edit-annonce-id').value;
        const body = {
            titre: document.getElementById('edit-titre').value.trim(),
            description: document.getElementById('edit-description').value.trim(),
            mode_remise: document.getElementById('edit-mode').value,
        };
        const prixVal = document.getElementById('edit-prix').value;
        if (prixVal) body.prix = parseFloat(prixVal);
        const btn = this.querySelector('button[type=submit]');
        btn.disabled = true;
        const resp = await apiFetch('/api/v1/annonces/' + id, { method: 'PUT', body: JSON.stringify(body) });
        btn.disabled = false;
        if (resp && resp.ok) {
            document.getElementById('modal-edit-annonce').style.display = 'none';
            showAlert('Annonce mise a jour — repassee en attente de validation', 'success');
            loadAnnonces();
        } else {
            const d = resp ? await resp.json() : {};
            showAlert(d.erreur || 'Erreur lors de la modification', 'error');
        }
    });
});

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

        let html = '<div class="table-container"><table><thead><tr><th>Titre</th><th>Date</th><th>Statut</th><th></th></tr></thead><tbody>';
        events.forEach(ev => {
            const date = new Date(ev.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
            const payeBadge = ev.statut_paiement === 'paye'
                ? '<span class="badge badge-valid">Payé</span>'
                : '<span class="badge badge-waiting">' + ev.statut_paiement + '</span>';
            const billetBtn = '<a href="' + (typeof API_BASE !== 'undefined' ? '' : '') + '/evenements/' + ev.id_evenement + '" style="font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;color:var(--teal);text-decoration:none;border:1px solid var(--teal);padding:3px 8px;">Voir</a>';
            html += '<tr><td style="font-weight:600;">' + escapeHtml(ev.titre) + '</td><td style="white-space:nowrap;">' + date + '</td><td>' + payeBadge + '</td><td>' + billetBtn + '</td></tr>';
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (err) {
        document.getElementById('events-container').innerHTML = '<p style="color: var(--cherry);">Erreur de chargement</p>';
    }
}

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
                    + '<span class="ladder-name">' + escapeHtml(p.nom) + cert + '</span>'
                    + '<span class="ladder-seuil">' + p.seuil_min + '</span>'
                    + '</div>';
            }).join('');
        }
    } catch (err) { /* score indisponible : on garde la valeur de base */ }
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

async function loadCommandes() {
    const container = document.getElementById('commandes-container');
    try {
        const resp = await apiFetch('/api/v1/commandes/me');
        if (!resp || !resp.ok) { container.innerHTML = ''; return; }
        const commandes = await resp.json();
        if (!commandes || commandes.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;padding:12px 0;">Aucun achat pour le moment</p>';
            return;
        }
        const STATUTS = { commandee:'Commandée', deposee:'Déposée', en_conteneur:'En conteneur', recuperee:'Récupérée', annulee:'Annulée' };
        const STATUT_COLORS = { commandee:'#fff4d6', deposee:'#e3eefd', en_conteneur:'#e3eefd', recuperee:'#dff5e1', annulee:'#fde2e2' };
        const recent = commandes.slice(0, 5);
        container.innerHTML = recent.map(c => {
            const st = STATUTS[c.statut] || c.statut;
            const bg = STATUT_COLORS[c.statut] || '#eee';
            const prix = c.type_annonce === 'don' ? 'Gratuit' : parseFloat(c.prix).toFixed(2).replace('.', ',') + ' €';
            return `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(18,3,9,0.08);">
                <div>
                    <a href="/annonces/${c.id_annonce}" style="font-weight:600;font-size:0.9rem;text-decoration:none;">${c.titre.substring(0,40)}${c.titre.length>40?'…':''}</a>
                    <p style="font-family:'DM Mono',monospace;font-size:0.68rem;opacity:0.5;margin-top:2px;">#${c.id_commande} · ${new Date(c.date_commande).toLocaleDateString('fr-FR')}</p>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <span style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;color:var(--cherry);">${prix}</span>
                    <span style="background:${bg};font-family:'DM Mono',monospace;font-size:0.68rem;text-transform:uppercase;padding:3px 8px;border:1px solid rgba(18,3,9,0.15);">${st}</span>
                </div>
            </div>`;
        }).join('');
    } catch(e) {
        container.innerHTML = '';
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

// Load on page ready
document.addEventListener('DOMContentLoaded', () => { loadProfile(); loadCommandes(); loadReservations(); });
</script>
@endsection

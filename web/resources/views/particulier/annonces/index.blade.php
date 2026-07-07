@extends('layouts.particulier')
@section('title', 'Mes annonces')

@section('styles')
<style>
    .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr)); gap: 14px; margin-bottom: 28px; }
    .stat-mini { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 16px 18px; }
    .stat-mini .l { font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cherry); }
    .stat-mini .v { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; line-height: 1; margin-top: 4px; }
    .empty-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 60px 40px; }
    .empty-box .big { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; opacity: 0.3; margin: 0; }
    .empty-box .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title" data-i18n="nav.mylistings">Mes annonces</h1>
    <a href="{{ route('particulier.annonces.create') }}" class="btn-primary" data-i18n="nav.postlisting">+ Déposer une annonce</a>
</div>

<div class="stats-row" id="stats-row" style="display:none;">
    <div class="stat-mini"><div class="l" data-i18n="stat.total">Total</div><div class="v" id="s-total">0</div></div>
    <div class="stat-mini"><div class="l" data-i18n="stat.online">En ligne</div><div class="v" id="s-validee">0</div></div>
    <div class="stat-mini"><div class="l" data-i18n="status.pending">En attente</div><div class="v" id="s-attente">0</div></div>
    <div class="stat-mini"><div class="l" data-i18n="stat.sold">Vendues</div><div class="v" id="s-vendue">0</div></div>
</div>

<div id="annonces-container">
    <div class="loading" data-i18n="common.loading">Chargement des annonces...</div>
</div>

<!-- Modal modification annonce -->
<div id="modal-edit-annonce" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.55);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:32px;width:100%;max-width:520px;position:relative;">
        <button id="modal-edit-annonce-close" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--coffee);">&times;</button>
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;letter-spacing:0.06em;margin-bottom:24px;" data-i18n="part.editlisting">Modifier l'annonce</h3>
        <form id="form-edit-annonce">
            <input type="hidden" id="edit-annonce-id">
            <div class="form-group" style="margin-bottom:16px;">
                <label for="edit-titre" class="form-label" data-i18n="field.title">Titre</label>
                <input type="text" id="edit-titre" class="form-input" required maxlength="120">
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="edit-description" class="form-label" data-i18n="field.description">Description</label>
                <textarea id="edit-description" class="form-input" rows="4" required style="resize:vertical;"></textarea>
            </div>
            <div id="edit-prix-row" class="form-group" style="margin-bottom:16px;">
                <label for="edit-prix" class="form-label" data-i18n="field.price">Prix (€)</label>
                <input type="number" id="edit-prix" class="form-input" step="0.01" min="0">
            </div>
            <div class="form-group" style="margin-bottom:24px;">
                <label for="edit-mode" class="form-label" data-i18n="field.handover">Mode de remise</label>
                <select id="edit-mode" class="form-input">
                    <option value="main_propre" data-i18n="handover.hand">En main propre</option>
                    <option value="conteneur" data-i18n="handover.container">Via conteneur</option>
                </select>
            </div>
            <p style="font-family:'DM Mono',monospace;font-size:0.75rem;color:var(--cherry);margin-bottom:16px;" data-i18n="part.editnote">La modification repasse l'annonce en attente de validation.</p>
            <button type="submit" class="btn btn-primary btn-block" style="width:100%;" data-i18n="btn.savechanges">Enregistrer les modifications</button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
const ANNONCE_STATUTS = {
    en_attente: { label: 'En attente', cls: 'badge-waiting' },
    validee:    { label: 'Validée',    cls: 'badge-valid' },
    refusee:    { label: 'Refusée',    cls: 'badge-cherry' },
    vendue:     { label: 'Vendue',     cls: 'badge-valid' },
    annulee:    { label: 'Annulée',    cls: 'badge-waiting' },
    retiree:    { label: 'Retirée',    cls: 'badge-waiting' }
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
            document.getElementById('stats-row').style.display = 'none';
            container.innerHTML = '<div class="empty-box"><p class="big">Aucune annonce</p><p class="sub">Déposez votre premier objet à donner ou à vendre.</p><p style="margin-top:24px;"><a href="{{ route('particulier.annonces.create') }}" class="btn-primary">+ Déposer une annonce</a></p></div>';
            return;
        }

        // Stats
        const count = (st) => annonces.filter(a => a.statut === st).length;
        document.getElementById('s-total').textContent = annonces.length;
        document.getElementById('s-validee').textContent = count('validee');
        document.getElementById('s-attente').textContent = count('en_attente');
        document.getElementById('s-vendue').textContent = count('vendue');
        document.getElementById('stats-row').style.display = 'grid';

        let html = '<div class="table-container"><table><thead><tr><th></th><th>Titre</th><th>Type</th><th>Prix</th><th>Statut</th><th>Date</th><th></th></tr></thead><tbody>';
        annonces.forEach(a => {
            const st = ANNONCE_STATUTS[a.statut] || { label: a.statut, cls: 'badge-waiting' };
            const date = new Date(a.date_creation).toLocaleDateString('fr-FR');
            const prix = a.type_annonce === 'don' ? 'Gratuit' : (parseFloat(a.prix || 0).toFixed(2) + ' €');
            const thumb = a.photo
                ? '<img src="' + window.MEDIA_BASE + '/' + escapeHtml(a.photo) + '" alt="" style="width:48px;height:48px;object-fit:cover;border:1px solid rgba(18,3,9,0.15);">'
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
                if (!await confirmAction('Annuler cette annonce ? Cette action est définitive.')) return;
                btn.disabled = true;
                const resp = await apiFetch('/api/v1/annonces/' + btn.dataset.id + '/annuler', {
                    method: 'POST',
                    body: JSON.stringify({ motif_retrait: 'Annulee par le vendeur' })
                });
                if (resp && resp.ok) {
                    showAlert('Annonce annulée', 'success');
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

document.addEventListener('DOMContentLoaded', function() {
    loadAnnonces();

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
            showAlert('Annonce mise à jour — repassée en attente de validation', 'success');
            loadAnnonces();
        } else {
            const d = resp ? await resp.json() : {};
            showAlert(d.erreur || 'Erreur lors de la modification', 'error');
        }
    });
});
</script>
@endsection

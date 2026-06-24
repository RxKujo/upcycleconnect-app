@extends('layouts.admin')

@section('title', 'Demandes de dépôt')

@section('content')
<div class="page-header">
    <h1 class="page-title">Demandes de dépôt</h1>
    <div class="action-cell">
        <select id="filtre-statut" onchange="filtrer()" class="form-select" style="width:auto;font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;">
            <option value="">Tous les statuts</option>
            <option value="en_attente">En attente</option>
            <option value="code_envoye">Code envoyé</option>
            <option value="refusee">Refusées</option>
        </select>
    </div>
</div>

<div id="alert" style="display:none;" class="alert alert-success"></div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Utilisateur</th>
                <th>Objet</th>
                <th>Type</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="demandes-tbody">
            <tr><td colspan="7" style="text-align:center;padding:40px;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.85rem;color:#999;">Chargement...</td></tr>
        </tbody>
    </table>
</div>

<!-- Modal détail -->
<div id="modal-detail" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:3px solid var(--coffee);box-shadow:var(--shadow);padding:40px;max-width:560px;width:90%;">
        <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;margin:0 0 20px;" id="modal-titre"></h2>
        <div id="modal-body"></div>
        <div style="display:flex;gap:12px;margin-top:28px;" id="modal-actions"></div>
    </div>
</div>

<!-- Modal refus -->
<div id="modal-refus" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:3px solid var(--coffee);box-shadow:var(--shadow);padding:40px;max-width:480px;width:90%;">
        <h2 style="font-family:'Bebas Neue',sans-serif;font-size:1.8rem;margin:0 0 20px;">Refuser la demande</h2>
        <input type="hidden" id="refus-id">
        <div style="margin-bottom:16px;">
            <label style="display:block;font-family:'DM Mono',monospace;font-size:0.82rem;text-transform:uppercase;font-weight:bold;margin-bottom:8px;">Motif du refus *</label>
            <textarea id="refus-motif" style="width:100%;border:3px solid var(--coffee);padding:12px;font-family:'Outfit',sans-serif;font-size:1rem;min-height:100px;resize:vertical;outline:none;box-sizing:border-box;" placeholder="Raison du refus..."></textarea>
        </div>
        <div style="display:flex;gap:12px;">
            <button class="btn-danger" onclick="submitRefus()" style="padding:10px 24px;">Confirmer le refus</button>
            <button class="btn-secondary" onclick="closeRefus()" style="padding:10px 24px;">Annuler</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const API = '{{ config("services.api.public_url") }}';
const TOKEN = '{{ session("admin_token") }}';
const statuts = { en_attente: 'En attente', validee: 'Validée', refusee: 'Refusée', code_envoye: 'Code envoyé' };
const badgeClass = { en_attente: 'badge-waiting', validee: 'badge-valid', refusee: 'badge-refused', code_envoye: 'badge-info' };
let allDemandes = [];

async function loadDemandes(statut = '') {
    const url = API + '/api/v1/admin/depot/demandes' + (statut ? '?statut=' + statut : '');
    const r = await fetch(url, { headers: { 'Authorization': 'Bearer ' + TOKEN } });
    if (!r.ok) return;
    allDemandes = await r.json();
    renderTable(allDemandes);
}

function renderTable(demandes) {
    const tbody = document.getElementById('demandes-tbody');
    if (!demandes || !demandes.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:40px;font-family:\'DM Mono\',monospace;text-transform:uppercase;font-size:0.85rem;color:#999;">Aucune demande</td></tr>';
        return;
    }
    tbody.innerHTML = demandes.map(d => `
        <tr>
            <td style="font-family:'DM Mono',monospace;font-size:0.85rem;">#${d.id_demande}</td>
            <td>
                <strong>${d.nom_utilisateur}</strong><br>
                <span style="font-size:0.85rem;color:#666;">${d.email}</span>
            </td>
            <td>${d.titre}</td>
            <td style="font-family:'DM Mono',monospace;font-size:0.82rem;text-transform:uppercase;">${d.type_objet}</td>
            <td style="font-size:0.9rem;">${new Date(d.date_demande).toLocaleDateString('fr-FR')}</td>
            <td><span class="badge ${badgeClass[d.statut] || 'badge-waiting'}">${statuts[d.statut] || d.statut}</span></td>
            <td>
                <div class="action-cell">
                    <button class="btn-secondary btn-sm" onclick="openDetail(${d.id_demande})">Détail</button>
                    ${d.statut === 'en_attente' ? `
                        <button class="btn-success btn-sm" onclick="valider(${d.id_demande})">Valider</button>
                        <button class="btn-danger btn-sm" onclick="openRefus(${d.id_demande})">Refuser</button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
}

function filtrer() {
    const statut = document.getElementById('filtre-statut').value;
    loadDemandes(statut);
}

function openDetail(id) {
    const d = allDemandes.find(x => x.id_demande === id);
    if (!d) return;
    document.getElementById('modal-titre').textContent = d.titre;
    document.getElementById('modal-body').innerHTML = `
        <table style="width:100%;font-size:0.95rem;border-collapse:collapse;">
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;width:140px;">Utilisateur</td><td><strong>${d.nom_utilisateur}</strong> — ${d.email}</td></tr>
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Type d'objet</td><td>${d.type_objet}</td></tr>
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Quantité</td><td>${d.quantite}</td></tr>
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Description</td><td>${d.description}</td></tr>
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Adresse retrait</td><td>${d.adresse_retrait || '—'} ${d.code_postal_retrait || ''} ${d.ville_retrait || ''}</td></tr>
            <tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Statut</td><td><span class="badge ${badgeClass[d.statut]}">${statuts[d.statut]}</span></td></tr>
            ${d.code_barre ? `<tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Code-barre</td><td><strong style="font-family:'DM Mono',monospace;font-size:1rem;background:var(--wheat);padding:4px 10px;border:2px solid var(--coffee);">${d.code_barre}</strong></td></tr>` : ''}
            ${d.motif_refus ? `<tr><td style="padding:8px 0;font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:#888;">Motif refus</td><td style="color:var(--cherry);">${d.motif_refus}</td></tr>` : ''}
        </table>
    `;
    document.getElementById('modal-actions').innerHTML = `
        ${d.statut === 'en_attente' ? `
            <button class="btn-success" onclick="valider(${d.id_demande})" style="padding:10px 20px;">Valider</button>
            <button class="btn-danger" onclick="openRefus(${d.id_demande})" style="padding:10px 20px;">Refuser</button>
        ` : ''}
        <button class="btn-secondary" onclick="closeDetail()" style="padding:10px 20px;">Fermer</button>
    `;
    document.getElementById('modal-detail').style.display = 'flex';
}

function closeDetail() { document.getElementById('modal-detail').style.display = 'none'; }

async function valider(id) {
    if (!await confirmAction('Valider cette demande et envoyer le code-barre ?')) return;
    const r = await fetch(API + '/api/v1/admin/depot/demandes/' + id + '/valider', {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' }
    });
    if (r.ok) {
        const d = await r.json();
        showAlert('Demande validée. Code-barre : ' + d.code_barre, 'success');
        closeDetail();
        loadDemandes();
    }
}

function openRefus(id) {
    document.getElementById('refus-id').value = id;
    document.getElementById('refus-motif').value = '';
    document.getElementById('modal-refus').style.display = 'flex';
    closeDetail();
}

function closeRefus() { document.getElementById('modal-refus').style.display = 'none'; }

async function submitRefus() {
    const id = document.getElementById('refus-id').value;
    const motif = document.getElementById('refus-motif').value.trim();
    if (!motif) { alert('Le motif de refus est requis.'); return; }
    const r = await fetch(API + '/api/v1/admin/depot/demandes/' + id + '/refuser', {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
        body: JSON.stringify({ motif_refus: motif })
    });
    if (r.ok) {
        showAlert('Demande refusée.', 'success');
        closeRefus();
        loadDemandes();
    }
}

function showAlert(msg, type) {
    const el = document.getElementById('alert');
    el.textContent = msg;
    el.className = 'alert alert-' + (type === 'success' ? 'success' : 'error');
    el.style.display = 'flex';
    setTimeout(() => el.style.display = 'none', 5000);
}

document.getElementById('modal-detail').addEventListener('click', e => { if (e.target === document.getElementById('modal-detail')) closeDetail(); });
document.getElementById('modal-refus').addEventListener('click', e => { if (e.target === document.getElementById('modal-refus')) closeRefus(); });

loadDemandes();
</script>
@endsection

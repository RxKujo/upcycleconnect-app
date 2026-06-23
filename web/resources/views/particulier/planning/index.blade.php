@extends('layouts.public')

@section('title', 'Mon Planning')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
<style>
.planning-wrap { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 4px solid var(--coffee); }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.8rem; color: var(--coffee); margin: 0; }
#calendar { border: 3px solid var(--coffee); background: white; box-shadow: var(--shadow); padding: 20px; }
.fc-toolbar-title { font-family: 'Bebas Neue', sans-serif !important; font-size: 1.8rem !important; color: var(--coffee); }
.fc-button { font-family: 'DM Mono', monospace !important; text-transform: uppercase !important; background: var(--forest) !important; border-color: var(--coffee) !important; border-width: 2px !important; border-radius: 0 !important; }
.fc-button:hover { background: var(--cherry) !important; }
.fc-daygrid-event { border-radius: 0 !important; font-size: 0.8rem; padding: 2px 4px; }
.ev-evenement { background-color: var(--teal) !important; border-color: var(--coffee) !important; }
.ev-formation { background-color: var(--forest) !important; border-color: var(--coffee) !important; }
.ev-perso { background-color: var(--wheat) !important; border-color: var(--coffee) !important; color: var(--coffee) !important; }
.ev-travail { background-color: #6b5c3e !important; border-color: var(--coffee) !important; }
.ev-reunion { background-color: var(--cherry) !important; border-color: var(--coffee) !important; }

.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6); z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal-box { background: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow); padding: 40px; width: 100%; max-width: 500px; }
.modal-title { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; margin: 0 0 24px; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
.form-input, .form-select { width: 100%; border: 3px solid var(--coffee); background: white; padding: 12px 16px; font-family: 'Outfit', sans-serif; font-size: 1rem; outline: none; box-sizing: border-box; }
.btn { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.1rem; box-shadow: var(--shadow-sm); transition: all 0.2s; }
.btn-primary { background: var(--forest); color: var(--cream); }
.btn-secondary { background: var(--cream); color: var(--coffee); }
.btn-danger { background: var(--cherry); color: var(--cream); }
.btn:hover { transform: translate(2px,2px); box-shadow: var(--shadow-hover); }
.legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
.legend-item { display: flex; align-items: center; gap: 8px; font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; }
.legend-dot { width: 14px; height: 14px; border: 2px solid var(--coffee); }
</style>
@endsection

@section('content')
<div class="planning-wrap">
    <div class="page-header">
        <h1 class="page-title">Mon Planning</h1>
        <button class="btn btn-primary" onclick="openAddModal()">+ Ajouter un créneau</button>
    </div>

    <div class="legend">
        <div class="legend-item"><div class="legend-dot" style="background:var(--teal)"></div> Événement</div>
        <div class="legend-item"><div class="legend-dot" style="background:var(--forest)"></div> Formation</div>
        <div class="legend-item"><div class="legend-dot" style="background:var(--cherry)"></div> Réunion</div>
        <div class="legend-item"><div class="legend-dot" style="background:#6b5c3e"></div> Travail</div>
        <div class="legend-item"><div class="legend-dot" style="background:var(--wheat);border-color:var(--coffee)"></div> Personnel</div>
    </div>

    <div id="calendar"></div>
</div>

<!-- Modal Ajout -->
<div class="modal-overlay" id="modal-add">
    <div class="modal-box">
        <h2 class="modal-title">Nouveau créneau</h2>
        <div class="form-group">
            <label class="form-label">Titre *</label>
            <input type="text" id="add-titre" class="form-input" placeholder="Mon créneau...">
        </div>
        <div class="form-group">
            <label class="form-label">Type</label>
            <select id="add-type" class="form-select">
                <option value="perso">Personnel</option>
                <option value="travail">Travail</option>
                <option value="reunion">Réunion</option>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Début *</label>
            <input type="datetime-local" id="add-debut" class="form-input">
        </div>
        <div class="form-group">
            <label class="form-label">Fin *</label>
            <input type="datetime-local" id="add-fin" class="form-input">
        </div>
        <div style="display:flex;gap:12px;margin-top:28px;">
            <button class="btn btn-primary" onclick="submitAdd()">Ajouter</button>
            <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
        </div>
        <div id="add-error" style="color:var(--cherry);font-family:'DM Mono',monospace;font-size:0.85rem;margin-top:12px;display:none;"></div>
    </div>
</div>

<!-- Modal Détail -->
<div class="modal-overlay" id="modal-detail">
    <div class="modal-box">
        <h2 class="modal-title" id="detail-titre"></h2>
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;margin-bottom:4px;" id="detail-type"></p>
        <p style="font-size:1rem;margin-bottom:4px;" id="detail-debut"></p>
        <p style="font-size:1rem;" id="detail-fin"></p>
        <div id="detail-actions" style="display:flex;gap:12px;margin-top:28px;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/fr.global.min.js"></script>
<script>
const API = '{{ config("services.api.public_url") }}';
const token = localStorage.getItem('uc_token');
let calendar;
let currentEvent = null;

if (!token) {
    window.location.href = '/login?return=' + encodeURIComponent(window.location.pathname);
}

const typeColors = {
    evenement: 'ev-evenement',
    formation: 'ev-formation',
    perso: 'ev-perso',
    travail: 'ev-travail',
    reunion: 'ev-reunion'
};

document.addEventListener('DOMContentLoaded', async function() {
    const items = await loadPlanning();

    const calEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
        events: items.map(p => ({
            id: p.id_planning,
            title: p.titre_creneau,
            start: p.date_debut,
            end: p.date_fin,
            classNames: [typeColors[p.type_creneau] || 'ev-perso'],
            extendedProps: { type: p.type_creneau, estManuel: p.est_manuel }
        })),
        eventClick: function(info) {
            openDetailModal(info.event);
        }
    });
    calendar.render();
});

async function loadPlanning() {
    try {
        const r = await fetch(API + '/api/v1/utilisateurs/me/planning', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!r.ok) return [];
        return await r.json();
    } catch(e) { return []; }
}

function openAddModal() {
    document.getElementById('modal-add').classList.add('active');
}

function closeModal() {
    document.getElementById('modal-add').classList.remove('active');
    document.getElementById('modal-detail').classList.remove('active');
}

async function submitAdd() {
    const titre = document.getElementById('add-titre').value.trim();
    const type = document.getElementById('add-type').value;
    const debut = document.getElementById('add-debut').value;
    const fin = document.getElementById('add-fin').value;
    const errEl = document.getElementById('add-error');
    errEl.style.display = 'none';

    if (!titre || !debut || !fin) {
        errEl.textContent = 'Titre, début et fin sont requis.';
        errEl.style.display = 'block';
        return;
    }

    const r = await fetch(API + '/api/v1/utilisateurs/me/planning', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
        body: JSON.stringify({ titre_creneau: titre, type_creneau: type,
            date_debut: debut.replace('T',' ') + ':00',
            date_fin: fin.replace('T',' ') + ':00' })
    });
    if (r.ok) {
        closeModal();
        const d = await r.json();
        calendar.addEvent({
            id: d.id_planning, title: titre, start: debut, end: fin,
            classNames: [typeColors[type] || 'ev-perso'],
            extendedProps: { type, estManuel: true }
        });
    } else {
        const err = await r.json();
        errEl.textContent = err.erreur || 'Erreur lors de l\'ajout.';
        errEl.style.display = 'block';
    }
}

function openDetailModal(event) {
    currentEvent = event;
    document.getElementById('detail-titre').textContent = event.title;
    document.getElementById('detail-type').textContent = event.extendedProps.type;
    document.getElementById('detail-debut').textContent = 'Du : ' + new Date(event.start).toLocaleString('fr-FR');
    document.getElementById('detail-fin').textContent = event.end ? 'Au : ' + new Date(event.end).toLocaleString('fr-FR') : '';

    const actions = document.getElementById('detail-actions');
    actions.innerHTML = '<button class="btn btn-secondary" onclick="closeModal()">Fermer</button>';

    if (event.extendedProps.estManuel) {
        actions.innerHTML += `<button class="btn btn-danger" onclick="deleteItem(${event.id})">Supprimer</button>`;
    }
    document.getElementById('modal-detail').classList.add('active');
}

async function deleteItem(id) {
    if (!confirm('Supprimer ce créneau ?')) return;
    const r = await fetch(API + '/api/v1/utilisateurs/me/planning/' + id, {
        method: 'DELETE',
        headers: { 'Authorization': 'Bearer ' + token }
    });
    if (r.ok) {
        if (currentEvent) currentEvent.remove();
        closeModal();
    }
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === el) closeModal();
    });
});
</script>
@endsection

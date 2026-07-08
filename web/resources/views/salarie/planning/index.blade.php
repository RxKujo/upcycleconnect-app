@extends('layouts.salarie')

@section('title', 'Mon planning')
@section('styles')
<style>
/* ─── Navigation calendrier ─── */
.cal-toolbar { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.cal-toolbar .nav-arrow { padding: 8px 16px; font-size: 1.2rem; line-height: 1; }
.cal-select { border: 3px solid var(--coffee); background: white; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.95rem; padding: 9px 14px; box-shadow: 3px 3px 0px rgba(18,3,9,0.12); cursor: pointer; outline: none; }
.cal-select:focus { border-color: var(--forest); }
.cal-spacer { flex: 1; }

/* ─── Grille calendrier ─── */
.calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 2px; border: var(--border); box-shadow: var(--shadow); margin-bottom: 32px; width: 100%; max-width: 100%; box-sizing: border-box; }
.calendar-day-label { background: var(--coffee); color: var(--wheat); font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; text-align: center; padding: 10px 4px; letter-spacing: 0.05em; min-width: 0; overflow: hidden; }
.calendar-cell { min-height: 92px; min-width: 0; overflow: hidden; background: white; border: 1px solid rgba(18,3,9,0.1); padding: 6px; position: relative; cursor: pointer; transition: background 0.12s ease; }
.calendar-cell:hover { background: rgba(216,201,155,0.25); }
.calendar-cell.empty { background: rgba(18,3,9,0.03); cursor: default; }
.calendar-cell.empty:hover { background: rgba(18,3,9,0.03); }
.calendar-cell.today { background: rgba(36,79,38,0.08); }
.calendar-cell.selected { outline: 3px solid var(--forest); outline-offset: -3px; background: rgba(36,79,38,0.14); }
.cal-date { font-family: 'DM Mono', monospace; font-size: 0.72rem; font-weight: 700; color: var(--coffee); opacity: 0.55; margin-bottom: 4px; }
.cal-date.today-num { color: var(--forest); opacity: 1; font-size: 0.9rem; }
.cal-event { font-size: 0.7rem; background: var(--forest); color: var(--cream); padding: 2px 5px; margin-bottom: 2px; font-family: 'DM Mono', monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cal-event.type-evenement { background: var(--teal); }
.cal-event.type-reunion { background: var(--cherry); }
.cal-event.type-formation { background: #6c5ce7; }
.cal-event.type-travail { background: var(--coffee); }
.cal-event.type-perso { background: #b2bec3; color: var(--coffee); }
.cal-more { font-size: 0.62rem; font-family: 'DM Mono', monospace; color: var(--coffee); opacity: 0.6; }

/* ─── Timeline journalière ─── */
.day-panel-head { display: flex; align-items: center; justify-content: space-between; margin: 0 0 24px; flex-wrap: wrap; gap: 12px; }
.day-panel-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; margin: 0; letter-spacing: 0.04em; }
.timeline { position: relative; border-left: 3px solid var(--coffee); margin-left: 60px; }
.timeline-hour { position: relative; height: 56px; border-top: 1px solid rgba(18,3,9,0.1); }
.timeline-hour:first-child { border-top: none; }
.timeline-hour .hour-label { position: absolute; left: -60px; top: -9px; width: 50px; text-align: right; font-family: 'DM Mono', monospace; font-size: 0.72rem; color: var(--coffee); opacity: 0.5; }
.timeline-events { position: absolute; inset: 0; pointer-events: none; }
.tl-block { position: absolute; left: 8px; right: 12px; background: var(--forest); color: var(--cream); border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.3); padding: 5px 9px; overflow: hidden; pointer-events: all; box-sizing: border-box; }
.tl-block.type-evenement { background: var(--teal); }
.tl-block.type-reunion { background: var(--cherry); }
.tl-block.type-formation { background: #6c5ce7; }
.tl-block.type-travail { background: var(--coffee); }
.tl-block.type-perso { background: #b2bec3; color: var(--coffee); }
.tl-block .tl-title { font-family: 'DM Mono', monospace; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.tl-block .tl-time { font-family: 'DM Mono', monospace; font-size: 0.68rem; opacity: 0.85; }
.tl-block .tl-del { position: absolute; top: 3px; right: 5px; cursor: pointer; font-family: 'DM Mono', monospace; font-size: 0.85rem; line-height: 1; background: none; border: none; color: inherit; opacity: 0.75; padding: 2px; }
.tl-block .tl-del:hover { opacity: 1; }
.day-empty { color: rgba(18,3,9,0.4); font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.9rem; padding: 20px 0; }
.day-empty-inline { position: absolute; top: 50%; left: 12px; right: 12px; transform: translateY(-50%); text-align: center; color: rgba(18,3,9,0.35); font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.82rem; letter-spacing: 0.04em; pointer-events: none; }
.tl-badge-auto { display: inline-block; font-size: 0.6rem; background: rgba(255,255,255,0.25); padding: 1px 5px; margin-left: 6px; letter-spacing: 0.04em; }

/* ─── Bandeaux « journée entière » ─── */
.allday-zone { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.allday-block { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; padding: 10px 14px; border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.3); background: var(--forest); color: var(--cream); cursor: pointer; }
.allday-block.type-evenement { background: var(--teal); }
.allday-block.type-reunion { background: var(--cherry); }
.allday-block.type-formation { background: #6c5ce7; }
.allday-block.type-travail { background: var(--coffee); }
.allday-block.type-perso { background: #b2bec3; color: var(--coffee); }
.allday-block .allday-title { font-family: 'DM Mono', monospace; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; }
.allday-block .allday-span { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.85; white-space: nowrap; }

/* ─── Modale ─── */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
.modal-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 40px; width: 100%; max-width: 620px; max-height: 92vh; overflow-y: auto; position: relative; }
.picker-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.picker-row .form-group { min-width: 0; }
.picker-date { display: grid; grid-template-columns: 1.4fr 1.8fr 1.2fr; gap: 8px; }
.picker-time { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: center; }
.picker-time .sep { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; }
.picker-sel { width: 100%; min-width: 0; border: 3px solid var(--coffee); background: white; font-family: 'DM Mono', monospace; font-size: 1rem; padding: 12px 8px; outline: none; box-shadow: 3px 3px 0px rgba(18,3,9,0.1); box-sizing: border-box; cursor: pointer; }
.picker-sel:focus { border-color: var(--forest); }
.date-alert { display: none; align-items: center; gap: 10px; background: #f8d7da; color: var(--cherry); border: 3px solid var(--cherry); padding: 12px 16px; margin-bottom: 20px; font-family: 'DM Mono', monospace; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.02em; box-shadow: var(--shadow-sm); }
.date-alert::before { content: '⚠'; font-size: 1.2rem; }
#planning-submit:disabled { opacity: 0.4; cursor: not-allowed; filter: grayscale(0.6); }
.detail-actions { display: flex; gap: 12px; margin-top: 28px; }
.detail-actions button { flex: 1; padding: 13px 0; font-size: 1.1rem; }

/* ─── Toast ─── */
#pl-toast-zone { position: fixed; top: 24px; right: 24px; z-index: 100001; display: flex; flex-direction: column; gap: 12px; max-width: 360px; }
.pl-toast { display: flex; align-items: flex-start; gap: 10px; padding: 14px 16px; border: 3px solid var(--coffee); box-shadow: 5px 5px 0 var(--coffee); background: var(--cherry); color: var(--cream); font-family: 'Outfit', sans-serif; font-size: 0.9rem; line-height: 1.4; animation: pl-toast-in 0.22s ease-out; }
.pl-toast .ic { font-family: 'DM Mono', monospace; font-weight: 700; }
@keyframes pl-toast-in { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
</style>
@endsection

@section('content')
{{-- === En-tête === --}}
<div class="page-header">
    <h1 class="page-title"><span data-i18n="part.planning.title">Mon Planning</span></h1>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <button class="btn-secondary" id="btn-export-ics" title="Exporter au format iCalendar (Google Agenda, Outlook, Apple…)">Exporter (.ics)</button>
        <button class="btn-primary" id="btn-open-add">+ Ajouter un créneau</button>
    </div>
</div>

{{-- Stats (recalculées en JS) --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Créneaux ce mois</div>
        <div class="stat-value" id="stat-mois">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total créneaux</div>
        <div class="stat-value" id="stat-total">0</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Événements liés</div>
        <div class="stat-value" id="stat-events">0</div>
    </div>
</div>

{{-- Navigation mois/année --}}
<div class="cal-toolbar">
    <button class="btn-secondary btn-sm nav-arrow" id="nav-prev">&larr;</button>
    <select class="cal-select" id="sel-mois"></select>
    <select class="cal-select" id="sel-annee"></select>
    <button class="btn-secondary btn-sm nav-arrow" id="nav-next">&rarr;</button>
    <div class="cal-spacer"></div>
    <button class="btn-primary btn-sm" id="nav-today">Aujourd'hui</button>
</div>

{{-- Calendrier --}}
<div class="calendar-grid" id="calendar-grid"></div>

{{-- Jour sélectionné --}}
<div class="card">
    <div class="day-panel-head">
        <h2 class="day-panel-title" id="day-title"><span data-i18n="sal.planning.selectday">Sélectionnez un jour</span></h2>
        <button class="btn-secondary btn-sm" id="btn-add-day">+ Créneau ce jour</button>
    </div>
    <div id="day-body"></div>
</div>

{{-- Modale création/édition --}}
<div class="modal-overlay" id="modal-add">
    <div class="modal-box">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;" id="modal-title"><span data-i18n="part.planning.newslot">Nouveau créneau</span></h2>
        <form action="{{ route('salarie.planning.store') }}" method="POST" id="planning-form">
            @csrf
            <input type="hidden" name="_method" id="form-method" disabled>
            <input type="hidden" name="date_debut" id="hid-debut">
            <input type="hidden" name="date_fin" id="hid-fin">

            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="titre_creneau" class="form-input" required maxlength="200" placeholder="Ex: Réunion équipe">
            </div>

            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type_creneau" class="form-select">
                    <option value="travail">Travail</option>
                    <option value="reunion">Réunion</option>
                    <option value="evenement">Événement</option>
                    <option value="formation">Formation</option>
                    <option value="perso">Personnel</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Date <span style="color:var(--cherry)">*</span></label>
                <div class="picker-date">
                    <select class="picker-sel" id="pk-jour"></select>
                    <select class="picker-sel" id="pk-mois"></select>
                    <select class="picker-sel" id="pk-annee"></select>
                </div>
            </div>

            <div class="picker-row">
                <div class="form-group">
                    <label class="form-label">Heure de début <span style="color:var(--cherry)">*</span></label>
                    <div class="picker-time">
                        <select class="picker-sel" id="pk-h-debut"></select>
                        <span class="sep">:</span>
                        <select class="picker-sel" id="pk-m-debut"></select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Heure de fin <span style="color:var(--cherry)">*</span></label>
                    <div class="picker-time">
                        <select class="picker-sel" id="pk-h-fin"></select>
                        <span class="sep">:</span>
                        <select class="picker-sel" id="pk-m-fin"></select>
                    </div>
                </div>
            </div>

            <div class="date-alert" id="planning-date-alert"></div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" style="min-height:80px;" placeholder="Détails optionnels…"></textarea>
            </div>

            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary" id="planning-submit">Ajouter</button>
                <button type="button" class="btn-secondary" id="btn-cancel-add">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- Modale détail --}}
<div class="modal-overlay" id="modal-detail">
    <div class="modal-box" style="max-width:520px;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 24px;"><span data-i18n="sal.planning.detail">Détail du créneau</span></h2>
        <div id="detail-body"></div>
        <div class="detail-actions">
            <button type="button" class="btn-success" id="detail-itineraire" style="display:none;">Itinéraire</button>
            <button type="button" class="btn-primary" id="detail-edit">Modifier</button>
            <button type="button" class="btn-danger" id="detail-delete">Supprimer</button>
            <button type="button" class="btn-secondary" id="detail-close">Fermer</button>
        </div>
    </div>
</div>

<div id="pl-toast-zone"></div>
@endsection

{{-- === Scripts : moteur du planning === --}}
@section('scripts')
<script>
(function () {
    'use strict';

    // ─── Données & constantes ───
    var ITEMS = @json($items ?? []);
    var CSRF = '{{ csrf_token() }}';
    var BASE_URL = '{{ url('/salarie/planning') }}';
    var TODAY = { y: {{ (int) date('Y') }}, m: {{ (int) date('n') - 1 }}, d: {{ (int) date('j') }} };
    var MOIS = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    var JOURS = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
    var HOUR_PX = 56;

    var view = { y: TODAY.y, m: TODAY.m };
    var selected = new Date(TODAY.y, TODAY.m, TODAY.d);
    var editId = null; // créneau en édition (null = création)
    var detailId = null;
    var STORE_URL = document.getElementById('planning-form').getAttribute('action');

    // ─── Helpers ───
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function parseDT(s) {
        if (!s) return null;
        var m = String(s).replace('T', ' ').match(/(\d{4})-(\d{2})-(\d{2})(?:[ ](\d{2}):(\d{2}))?/);
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0));
    }
    function ymd(dt) { return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()); }
    function sameDay(a, b) { return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    // Normalise les items (objets Date)
    var EVENTS = ITEMS.map(function (it) {
        return {
            id: it.id_planning,
            titre: it.titre_creneau || '—',
            type: it.type_creneau || 'travail',
            desc: it.description || '',
            debut: parseDT(it.date_debut),
            fin: parseDT(it.date_fin),
            manuel: it.est_manuel === true || it.est_manuel === 1,
            lieu: it.lieu || '',
            format: it.format || '',
            animateurs: it.animateurs || [],
            nbPlaces: (it.nb_places != null ? it.nb_places : null),
            nbDispo: (it.nb_dispo != null ? it.nb_dispo : null),
            prix: (it.prix != null ? it.prix : null)
        };
    }).filter(function (e) { return e.debut && e.fin; });

    var TYPE_LABELS = { travail: 'Travail', reunion: 'Réunion', evenement: 'Événement', formation: 'Formation', perso: 'Personnel' };

    function eventsOfDay(dt) {
        return EVENTS.filter(function (e) {
            var dayStart = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate(), 0, 0);
            var dayEnd = new Date(dt.getFullYear(), dt.getMonth(), dt.getDate(), 23, 59, 59);
            return e.debut <= dayEnd && e.fin >= dayStart;
        }).sort(function (a, b) { return a.debut - b.debut; });
    }

    // ─── Toast ───
    var toastTimer = null;
    function toast(msg) {
        var zone = document.getElementById('pl-toast-zone');
        var el = document.createElement('div');
        el.className = 'pl-toast';
        el.innerHTML = '<span class="ic">!</span><span>' + esc(msg) + '</span>';
        zone.appendChild(el);
        setTimeout(function () { el.remove(); }, 4000);
    }

    // ─── Stats ───
    function renderStats() {
        var moisCount = EVENTS.filter(function (e) {
            return e.debut.getFullYear() === view.y && e.debut.getMonth() === view.m;
        }).length;
        var evCount = EVENTS.filter(function (e) { return e.type === 'evenement'; }).length;
        document.getElementById('stat-mois').textContent = moisCount;
        document.getElementById('stat-total').textContent = EVENTS.length;
        document.getElementById('stat-events').textContent = evCount;
    }

    // ─── Sélecteurs mois / année ───
    function buildNavSelectors() {
        var selM = document.getElementById('sel-mois');
        var selY = document.getElementById('sel-annee');
        selM.innerHTML = MOIS.map(function (m, i) { return '<option value="' + i + '">' + m + '</option>'; }).join('');
        var years = [];
        var minY = TODAY.y - 2, maxY = TODAY.y + 3;
        EVENTS.forEach(function (e) {
            var y = e.debut.getFullYear();
            if (y < minY) minY = y;
            if (y > maxY) maxY = y;
        });
        for (var y = minY; y <= maxY; y++) years.push(y);
        selY.innerHTML = years.map(function (y) { return '<option value="' + y + '">' + y + '</option>'; }).join('');
        selM.value = view.m;
        selY.value = view.y;
        selM.addEventListener('change', function () { view.m = +selM.value; renderCalendar(); });
        selY.addEventListener('change', function () { view.y = +selY.value; renderCalendar(); });
    }
    function syncNavSelectors() {
        document.getElementById('sel-mois').value = view.m;
        document.getElementById('sel-annee').value = view.y;
    }

    // ─── Calendrier ───
    function renderCalendar() {
        syncNavSelectors();
        renderStats();
        var grid = document.getElementById('calendar-grid');
        var html = JOURS.map(function (j) { return '<div class="calendar-day-label">' + j + '</div>'; }).join('');

        var first = new Date(view.y, view.m, 1);
        var firstDow = (first.getDay() + 6) % 7; // lundi = 0
        var nbDays = new Date(view.y, view.m + 1, 0).getDate();

        for (var i = 0; i < firstDow; i++) html += '<div class="calendar-cell empty"></div>';

        for (var day = 1; day <= nbDays; day++) {
            var dt = new Date(view.y, view.m, day);
            var isToday = (view.y === TODAY.y && view.m === TODAY.m && day === TODAY.d);
            var isSel = sameDay(dt, selected);
            var evs = eventsOfDay(dt);
            var cls = 'calendar-cell' + (isToday ? ' today' : '') + (isSel ? ' selected' : '');
            var inner = '<div class="cal-date' + (isToday ? ' today-num' : '') + '">' + day + '</div>';
            evs.slice(0, 3).forEach(function (e) {
                inner += '<div class="cal-event type-' + esc(e.type) + '" title="' + esc(e.titre) + '">' + esc(e.titre) + '</div>';
            });
            if (evs.length > 3) inner += '<div class="cal-more">+' + (evs.length - 3) + ' autre' + (evs.length - 3 > 1 ? 's' : '') + '</div>';
            html += '<div class="' + cls + '" data-day="' + day + '">' + inner + '</div>';
        }

        var lastDow = (new Date(view.y, view.m, nbDays).getDay() + 6) % 7;
        for (var k = lastDow; k < 6; k++) html += '<div class="calendar-cell empty"></div>';

        grid.innerHTML = html;
        grid.querySelectorAll('.calendar-cell[data-day]').forEach(function (cell) {
            cell.addEventListener('click', function () {
                selected = new Date(view.y, view.m, +cell.dataset.day);
                renderCalendar();
                renderDay();
            });
        });
    }

    // ─── Timeline journalière ───
    function renderDay() {
        var title = document.getElementById('day-title');
        var body = document.getElementById('day-body');
        title.textContent = selected.getDate() + ' ' + MOIS[selected.getMonth()] + ' ' + selected.getFullYear();

        var evs = eventsOfDay(selected);

        // Sépare « journée entière / multi-jours » des créneaux horaires
        var allDay = [], timed = [];
        evs.forEach(function (e) {
            if (sameDay(e.debut, selected) && sameDay(e.fin, selected)) timed.push(e);
            else allDay.push(e);
        });

        var html = '';

        // Bandeaux « journée entière »
        if (allDay.length) {
            html += '<div class="allday-zone">';
            allDay.forEach(function (e) {
                var autoTag = e.manuel ? '' : '<span class="tl-badge-auto">auto</span>';
                var span = e.debut.getDate() + '/' + pad(e.debut.getMonth() + 1) + ' → ' +
                    e.fin.getDate() + '/' + pad(e.fin.getMonth() + 1);
                html += '<div class="allday-block type-' + esc(e.type) + '" data-id="' + e.id + '" title="Voir le détail">' +
                    '<span class="allday-title">' + esc(e.titre) + autoTag + '</span>' +
                    '<span class="allday-span">Journée entière · ' + span + '</span>' +
                    '</div>';
            });
            html += '</div>';
        }

        // Timeline horaire : plage ajustée aux créneaux, sinon 8h–19h
        var minH = 8, maxH = 19;
        timed.forEach(function (e) {
            var sh = e.debut.getHours();
            var eh = e.fin.getHours() + (e.fin.getMinutes() > 0 ? 1 : 0);
            if (sh < minH) minH = sh;
            if (eh > maxH) maxH = eh;
        });
        if (minH < 0) minH = 0;
        if (maxH > 24) maxH = 24;

        html += '<div class="timeline" style="height:' + ((maxH - minH) * HOUR_PX) + 'px;">';
        for (var h = minH; h < maxH; h++) {
            html += '<div class="timeline-hour"><span class="hour-label">' + pad(h) + ':00</span></div>';
        }
        html += '<div class="timeline-events">';
        var dayStart = new Date(selected.getFullYear(), selected.getMonth(), selected.getDate(), minH, 0);
        timed.forEach(function (e) {
            var top = ((e.debut - dayStart) / 3600000) * HOUR_PX;
            var height = Math.max(22, ((e.fin - e.debut) / 3600000) * HOUR_PX);
            var label = pad(e.debut.getHours()) + ':' + pad(e.debut.getMinutes()) + ' – ' +
                pad(e.fin.getHours()) + ':' + pad(e.fin.getMinutes());
            var autoTag = e.manuel ? '' : '<span class="tl-badge-auto">auto</span>';
            html += '<div class="tl-block type-' + esc(e.type) + '" data-id="' + e.id + '" title="Voir le détail" style="top:' + top + 'px;height:' + height + 'px;cursor:pointer;">' +
                '<div class="tl-title">' + esc(e.titre) + autoTag + '</div>' +
                '<div class="tl-time">' + label + '</div>' +
                '</div>';
        });
        if (!evs.length) {
            html += '<div class="day-empty-inline">Aucun créneau ce jour · cliquez sur « + Créneau ce jour »</div>';
        }
        html += '</div></div>';

        body.innerHTML = html;

        body.querySelectorAll('.tl-block, .allday-block').forEach(function (blk) {
            blk.addEventListener('click', function () { openDetail(+blk.dataset.id); });
        });
    }

    // ─── Suppression ───
    function deleteCreneau(id) {
        window.confirmAction('Supprimer ce créneau ?').then(function (ok) {
            if (!ok) return;
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = BASE_URL + '/' + id;
            f.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">' +
                '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(f);
            f.submit();
        });
    }

    // ─── Picker (modale) ───
    function fillSelect(sel, from, to, fmt) {
        var html = '';
        for (var i = from; i <= to; i++) html += '<option value="' + i + '">' + (fmt ? fmt(i) : i) + '</option>';
        sel.innerHTML = html;
    }
    function daysInMonth(y, m) { return new Date(y, m + 1, 0).getDate(); }

    function buildPicker() {
        fillSelect(document.getElementById('pk-mois'), 0, 11, function (i) { return MOIS[i]; });
        var selY = document.getElementById('pk-annee');
        fillSelect(selY, TODAY.y - 1, TODAY.y + 3);
        fillSelect(document.getElementById('pk-h-debut'), 0, 23, pad);
        fillSelect(document.getElementById('pk-h-fin'), 0, 23, pad);
        var minOpts = '';
        [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55].forEach(function (m) { minOpts += '<option value="' + m + '">' + pad(m) + '</option>'; });
        document.getElementById('pk-m-debut').innerHTML = minOpts;
        document.getElementById('pk-m-fin').innerHTML = minOpts;

        function refreshDays() {
            var y = +document.getElementById('pk-annee').value;
            var m = +document.getElementById('pk-mois').value;
            var jSel = document.getElementById('pk-jour');
            var prev = +jSel.value || 1;
            fillSelect(jSel, 1, daysInMonth(y, m));
            jSel.value = Math.min(prev, daysInMonth(y, m));
        }
        document.getElementById('pk-mois').addEventListener('change', function () { refreshDays(); validateModal(); });
        document.getElementById('pk-annee').addEventListener('change', function () { refreshDays(); validateModal(); });
        ['pk-jour', 'pk-h-debut', 'pk-m-debut', 'pk-h-fin', 'pk-m-fin'].forEach(function (id) {
            document.getElementById(id).addEventListener('change', validateModal);
        });
        refreshDays();
    }

    function setPickerDate(dt, startHour, endHour) {
        document.getElementById('pk-annee').value = dt.getFullYear();
        document.getElementById('pk-mois').value = dt.getMonth();
        fillSelect(document.getElementById('pk-jour'), 1, daysInMonth(dt.getFullYear(), dt.getMonth()));
        document.getElementById('pk-jour').value = dt.getDate();
        document.getElementById('pk-h-debut').value = startHour;
        document.getElementById('pk-m-debut').value = 0;
        document.getElementById('pk-h-fin').value = endHour;
        document.getElementById('pk-m-fin').value = 0;
    }

    function readPicker() {
        var y = +document.getElementById('pk-annee').value;
        var m = +document.getElementById('pk-mois').value;
        var d = +document.getElementById('pk-jour').value;
        var hd = +document.getElementById('pk-h-debut').value, md = +document.getElementById('pk-m-debut').value;
        var hf = +document.getElementById('pk-h-fin').value, mf = +document.getElementById('pk-m-fin').value;
        return {
            debut: new Date(y, m, d, hd, md),
            fin: new Date(y, m, d, hf, mf)
        };
    }

    // ─── Validation modale (anti-chevauchement) ───
    var lastOverlapId = null;
    function validateModal() {
        var alertBox = document.getElementById('planning-date-alert');
        var submitBtn = document.getElementById('planning-submit');
        var r = readPicker();
        var msg = '';
        var overlapId = null;

        if (r.fin <= r.debut) {
            msg = "L'heure de fin doit être après l'heure de début.";
        } else {
            for (var i = 0; i < EVENTS.length; i++) {
                var e = EVENTS[i];
                if (e.id === editId) continue; // on s'ignore soi-même en édition
                if (r.debut < e.fin && e.debut < r.fin) {
                    msg = 'Chevauchement avec « ' + e.titre + ' » (' +
                        pad(e.debut.getHours()) + ':' + pad(e.debut.getMinutes()) + '–' +
                        pad(e.fin.getHours()) + ':' + pad(e.fin.getMinutes()) + ').';
                    overlapId = e.id;
                    break;
                }
            }
        }

        if (msg) {
            alertBox.textContent = msg;
            alertBox.style.display = 'flex';
            submitBtn.disabled = true;
            // toast uniquement quand un NOUVEAU chevauchement apparaît
            if (overlapId !== null && overlapId !== lastOverlapId) {
                toast(msg);
            }
            lastOverlapId = overlapId;
            return false;
        }
        alertBox.style.display = 'none';
        submitBtn.disabled = false;
        lastOverlapId = null;
        return true;
    }

    function setPickerFull(debut, fin) {
        var y = debut.getFullYear(), m = debut.getMonth();
        document.getElementById('pk-annee').value = y;
        document.getElementById('pk-mois').value = m;
        fillSelect(document.getElementById('pk-jour'), 1, daysInMonth(y, m));
        document.getElementById('pk-jour').value = debut.getDate();
        function round5(n) { var r = Math.round(n / 5) * 5; return r > 55 ? 55 : r; }
        document.getElementById('pk-h-debut').value = debut.getHours();
        document.getElementById('pk-m-debut').value = round5(debut.getMinutes());
        document.getElementById('pk-h-fin').value = fin.getHours();
        document.getElementById('pk-m-fin').value = round5(fin.getMinutes());
    }

    // ─── Ouverture / fermeture modale création/édition ────────────
    function openModal(dt) {
        editId = null;
        document.getElementById('modal-title').textContent = 'Nouveau créneau';
        document.getElementById('planning-submit').textContent = 'Ajouter';
        document.getElementById('form-method').disabled = true;
        document.getElementById('planning-form').setAttribute('action', STORE_URL);
        setPickerDate(dt || selected, 9, 10);
        document.querySelector('#planning-form [name="titre_creneau"]').value = '';
        document.querySelector('#planning-form [name="description"]').value = '';
        document.querySelector('#planning-form [name="type_creneau"]').value = 'travail';
        validateModal();
        document.getElementById('modal-add').style.display = 'flex';
    }
    function openEdit(ev) {
        editId = ev.id;
        document.getElementById('modal-title').textContent = 'Modifier le créneau';
        document.getElementById('planning-submit').textContent = 'Enregistrer';
        var fm = document.getElementById('form-method');
        fm.disabled = false;
        fm.value = 'PUT';
        document.getElementById('planning-form').setAttribute('action', BASE_URL + '/' + ev.id);
        setPickerFull(ev.debut, ev.fin);
        document.querySelector('#planning-form [name="titre_creneau"]').value = ev.titre;
        document.querySelector('#planning-form [name="description"]').value = ev.desc;
        document.querySelector('#planning-form [name="type_creneau"]').value = ev.type;
        validateModal();
        document.getElementById('modal-add').style.display = 'flex';
    }
    function closeModal() { document.getElementById('modal-add').style.display = 'none'; }

    document.getElementById('btn-open-add').addEventListener('click', function () { openModal(selected); });
    document.getElementById('btn-add-day').addEventListener('click', function () { openModal(selected); });
    document.getElementById('btn-cancel-add').addEventListener('click', closeModal);
    document.getElementById('modal-add').addEventListener('mousedown', function (e) {
        if (e.target === this) closeModal();
    });

    // ─── Fiche détail ─────────────────────────────────────────────
    function eventById(id) {
        for (var i = 0; i < EVENTS.length; i++) if (EVENTS[i].id === id) return EVENTS[i];
        return null;
    }
    function openDetail(id) {
        var e = eventById(id);
        if (!e) return;
        detailId = id;
        var sameJour = sameDay(e.debut, e.fin);
        var quand = sameJour
            ? e.debut.getDate() + ' ' + MOIS[e.debut.getMonth()] + ' ' + e.debut.getFullYear() +
              ' · ' + pad(e.debut.getHours()) + ':' + pad(e.debut.getMinutes()) + ' – ' + pad(e.fin.getHours()) + ':' + pad(e.fin.getMinutes())
            : e.debut.getDate() + '/' + pad(e.debut.getMonth() + 1) + ' ' + pad(e.debut.getHours()) + ':' + pad(e.debut.getMinutes()) +
              '  →  ' + e.fin.getDate() + '/' + pad(e.fin.getMonth() + 1) + ' ' + pad(e.fin.getHours()) + ':' + pad(e.fin.getMinutes());
        var descHtml = e.desc
            ? '<p style="margin:0;white-space:pre-wrap;line-height:1.5;">' + esc(e.desc) + '</p>'
            : '<p style="margin:0;opacity:0.5;font-style:italic;">Aucune description.</p>';

        // Bloc infos enrichies (format, lieu, animateurs, places, prix) — événements uniquement.
        var FORMAT_LABELS = { presentiel: 'Présentiel', distanciel: 'Distanciel' };
        var infos = [];
        if (e.format) infos.push(['Format', FORMAT_LABELS[e.format] || e.format]);
        if (e.lieu) infos.push(['Lieu', e.lieu]);
        if (e.animateurs && e.animateurs.length) infos.push(['Animateur' + (e.animateurs.length > 1 ? 's' : ''), e.animateurs.join(', ')]);
        if (e.nbPlaces != null) infos.push(['Places', (e.nbDispo != null ? e.nbDispo + ' / ' + e.nbPlaces + ' dispo.' : String(e.nbPlaces))]);
        if (e.prix != null) infos.push(['Prix', e.prix > 0 ? (Number(e.prix).toFixed(2).replace('.', ',') + ' €') : 'Gratuit']);
        var infoHtml = infos.length
            ? '<div style="border-top:2px solid rgba(18,3,9,0.12);padding-top:16px;margin-bottom:16px;display:grid;grid-template-columns:auto 1fr;gap:9px 18px;align-items:baseline;">' +
              infos.map(function (r) {
                  return '<div style="font-family:\'DM Mono\',monospace;font-size:0.68rem;text-transform:uppercase;opacity:0.5;white-space:nowrap;">' + esc(r[0]) + '</div>' +
                         '<div style="font-size:0.94rem;line-height:1.35;">' + esc(r[1]) + '</div>';
              }).join('') +
              '</div>'
            : '';

        document.getElementById('detail-body').innerHTML =
            '<div style="margin-bottom:18px;"><span class="cal-event type-' + esc(e.type) + '" style="display:inline-block;padding:4px 10px;font-size:0.8rem;">' + esc(TYPE_LABELS[e.type] || e.type) + '</span>' +
            (e.manuel ? '' : '<span class="tl-badge-auto" style="background:rgba(18,3,9,0.12);color:var(--coffee);margin-left:8px;">automatique</span>') + '</div>' +
            '<h3 style="font-family:\'Bebas Neue\',sans-serif;font-size:1.6rem;margin:0 0 8px;letter-spacing:0.03em;">' + esc(e.titre) + '</h3>' +
            '<p style="font-family:\'DM Mono\',monospace;font-size:0.85rem;color:var(--coffee);opacity:0.7;margin:0 0 20px;">' + quand + '</p>' +
            infoHtml +
            '<div style="border-top:2px solid rgba(18,3,9,0.12);padding-top:16px;">' +
            '<div style="font-family:\'DM Mono\',monospace;font-size:0.7rem;text-transform:uppercase;opacity:0.5;margin-bottom:6px;">Description</div>' +
            descHtml + '</div>';
        // Boutons Modifier / Supprimer masqués pour les créneaux automatiques
        document.getElementById('detail-edit').style.display = e.manuel ? '' : 'none';
        document.getElementById('detail-delete').style.display = e.manuel ? '' : 'none';
        // Bouton Itinéraire : visible si un lieu (adresse) est renseigné.
        var itin = document.getElementById('detail-itineraire');
        if (e.lieu) {
            itin.style.display = '';
            itin.onclick = function () {
                window.open('https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(e.lieu), '_blank', 'noopener');
            };
        } else {
            itin.style.display = 'none';
            itin.onclick = null;
        }
        document.getElementById('modal-detail').style.display = 'flex';
    }
    function closeDetail() { document.getElementById('modal-detail').style.display = 'none'; }

    document.getElementById('detail-close').addEventListener('click', closeDetail);
    document.getElementById('detail-edit').addEventListener('click', function () {
        var e = eventById(detailId);
        closeDetail();
        if (e) openEdit(e);
    });
    document.getElementById('detail-delete').addEventListener('click', function () {
        var id = detailId;
        closeDetail();
        deleteCreneau(id);
    });
    document.getElementById('modal-detail').addEventListener('mousedown', function (e) {
        if (e.target === this) closeDetail();
    });

    // ─── Export iCalendar (.ics) ──────────────────────────────────
    function icsEscape(s) {
        return String(s == null ? '' : s)
            .replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\r?\n/g, '\\n');
    }
    function icsDate(dt) {
        return dt.getFullYear() + pad(dt.getMonth() + 1) + pad(dt.getDate()) + 'T' +
               pad(dt.getHours()) + pad(dt.getMinutes()) + pad(dt.getSeconds());
    }
    function buildICS() {
        var stamp = icsDate(new Date());
        var lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//UpcycleConnect//Planning//FR', 'CALSCALE:GREGORIAN'];
        EVENTS.forEach(function (e) {
            var desc = e.desc || '';
            if (e.animateurs && e.animateurs.length) desc += (desc ? '\n' : '') + 'Animateur(s) : ' + e.animateurs.join(', ');
            lines.push('BEGIN:VEVENT');
            lines.push('UID:planning-' + e.id + '@upcycleconnect');
            lines.push('DTSTAMP:' + stamp);
            lines.push('DTSTART:' + icsDate(e.debut));
            lines.push('DTEND:' + icsDate(e.fin));
            lines.push('SUMMARY:' + icsEscape(e.titre));
            if (e.lieu) lines.push('LOCATION:' + icsEscape(e.lieu));
            if (desc) lines.push('DESCRIPTION:' + icsEscape(desc));
            lines.push('END:VEVENT');
        });
        lines.push('END:VCALENDAR');
        return lines.join('\r\n');
    }
    var btnExport = document.getElementById('btn-export-ics');
    if (btnExport) {
        btnExport.addEventListener('click', function () {
            if (!EVENTS.length) { toast('Aucun créneau à exporter.'); return; }
            var blob = new Blob([buildICS()], { type: 'text/calendar;charset=utf-8' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'mon-planning-upcycleconnect.ics';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
        });
    }

    // Soumission : remplit les champs cachés au bon format + dernier garde-fou
    document.getElementById('planning-form').addEventListener('submit', function (e) {
        if (!validateModal()) { e.preventDefault(); return; }
        var r = readPicker();
        function fmt(dt) {
            return dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate()) +
                'T' + pad(dt.getHours()) + ':' + pad(dt.getMinutes());
        }
        document.getElementById('hid-debut').value = fmt(r.debut);
        document.getElementById('hid-fin').value = fmt(r.fin);
    });

    // ─── Navigation ───────────────────────────────────────────────
    document.getElementById('nav-prev').addEventListener('click', function () {
        view.m--; if (view.m < 0) { view.m = 11; view.y--; } renderCalendar();
    });
    document.getElementById('nav-next').addEventListener('click', function () {
        view.m++; if (view.m > 11) { view.m = 0; view.y++; } renderCalendar();
    });
    document.getElementById('nav-today').addEventListener('click', function () {
        view = { y: TODAY.y, m: TODAY.m };
        selected = new Date(TODAY.y, TODAY.m, TODAY.d);
        renderCalendar();
        renderDay();
    });

    // ─── Init ─────────────────────────────────────────────────────
    buildNavSelectors();
    buildPicker();
    renderCalendar();
    renderDay();
})();
</script>
@endsection

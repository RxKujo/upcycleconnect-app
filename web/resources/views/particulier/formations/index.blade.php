@extends('layouts.particulier')
@section('title', 'Mes formations & événements')

{{-- Mes formations et événements : statut paiement, billet PDF, itinéraire. --}}

@section('styles')
<style>
    .section-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 0.06em; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 3px solid var(--coffee); display:flex; justify-content:space-between; align-items:center; }
    .item-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; padding: 14px 0; border-bottom: 1px solid rgba(18,3,9,0.1); flex-wrap: wrap; }
    .item-row:last-child { border-bottom: none; }
    .item-main a { font-weight: 600; text-decoration: none; color: var(--coffee); font-size: 1rem; }
    .item-meta { font-family: 'DM Mono', monospace; font-size: 0.68rem; opacity: 0.55; margin-top: 3px; }
    .item-right { display: flex; gap: 12px; align-items: center; }
    .item-prix { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; color: var(--cherry); }
    .empty-mini { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; opacity: 0.45; padding: 14px 0; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title" data-i18n="part.trainings.title">Mes formations &amp; événements</h1>
    <a href="/evenements" class="btn-secondary" data-i18n="part.trainings.catalogue">Voir le catalogue</a>
</div>

{{-- === Inscriptions === --}}
<div class="card">
    <h3 class="section-title" data-i18n="part.trainings.myregistrations">Mes inscriptions</h3>
    <div id="events-container"><div class="empty-mini" data-i18n="common.loading">Chargement…</div></div>
</div>
@endsection

{{-- === Scripts === --}}
@section('scripts')
<script>
// Échappe le HTML (anti-injection).
function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// Charge les inscriptions et construit la liste.
async function loadEvents() {
    const container = document.getElementById('events-container');
    try {
        const resp = await apiFetch('/api/v1/utilisateurs/me/evenements-inscrits');
        if (!resp || !resp.ok) { container.innerHTML = '<div class="empty-mini">Indisponible.</div>'; return; }
        const events = await resp.json();
        if (!events || events.length === 0) {
            container.innerHTML = '<div class="empty-mini">Vous n\'êtes inscrit à aucun événement.</div>';
            return;
        }
        container.innerHTML = events.map(ev => {
            const date = new Date(ev.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
            const accessible = ev.statut === 'valide' || ev.statut === 'termine';

            const payeBadge = ev.statut_paiement === 'paye'
                ? '<span class="badge badge-valid">Payé</span>'
                : '<span class="badge badge-waiting">Gratuit</span>';

            const titreHtml = accessible
                ? `<a href="/evenements/${ev.id_evenement}">${escapeHtml(ev.titre)}</a>`
                : `<span style="font-weight:600;">${escapeHtml(ev.titre)}</span>`;

            let note = '';
            if (ev.statut === 'refuse' || ev.statut === 'annule') note = ' · <span style="color:var(--cherry);">Événement annulé</span>';
            else if (ev.statut === 'en_attente') note = ' · <span style="opacity:0.6;">En attente de validation</span>';

            const billetBtn = accessible
                ? `<button type="button" class="badge badge-waiting" style="cursor:pointer;" data-id="${ev.id_evenement}" data-titre="${escapeHtml(ev.titre)}" onclick="downloadTicket(this)">Billet PDF</button>`
                : '';

            const mapsLink = (accessible && ev.lieu)
                ? ` · <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(ev.lieu)}" target="_blank" rel="noopener" style="color:var(--teal);text-decoration:underline;">Itinéraire</a>`
                : '';

            return `<div class="item-row">
                <div class="item-main">
                    ${titreHtml}
                    <div class="item-meta">${date}${note}${mapsLink}</div>
                </div>
                <div class="item-right">${payeBadge}${billetBtn}</div>
            </div>`;
        }).join('');
    } catch (e) {
        container.innerHTML = '<div class="empty-mini">Erreur de chargement.</div>';
    }
}

function slugify(s) {
    return (s || 'billet').toString().toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')   // retire les accents
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 50) || 'billet';
}

// Télécharge le billet PDF (blob authentifié).
async function downloadTicket(btn) {
    const token = getToken();
    if (!token) return;
    const id = btn.dataset.id;
    const nom = slugify(btn.dataset.titre);
    const old = btn.textContent;
    btn.disabled = true; btn.textContent = '…';
    try {
        const res = await fetch(API_BASE + '/api/v1/evenements/' + id + '/ticket', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!res.ok) { showAlert('Billet indisponible pour le moment', 'error'); return; }
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = 'billet-' + nom + '.pdf'; a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        showAlert('Erreur lors du téléchargement', 'error');
    } finally {
        btn.disabled = false; btn.textContent = old;
    }
}

document.addEventListener('DOMContentLoaded', () => { loadEvents(); });
</script>
@endsection

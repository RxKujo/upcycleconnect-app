@extends('layouts.particulier')

@section('pub_slot')@include('partials.pub-slot')@endsection
@section('title', 'Tableau de bord')

{{-- Tableau de bord particulier : score, raccourcis et activité récente. --}}

@section('styles')
<style>
    .dash-hero { background: var(--coffee); color: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap; }
    .dash-hello { font-family: 'Bebas Neue', sans-serif; font-size: 2.4rem; letter-spacing: 0.04em; line-height: 1; }
    .dash-sub { font-family: 'DM Mono', monospace; font-size: 0.82rem; text-transform: uppercase; opacity: 0.7; margin-top: 8px; }
    .dash-score { text-align: center; border: 2px solid var(--wheat); padding: 12px 26px; }
    .dash-score .lvl { font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--wheat); }
    .dash-score .num { font-family: 'Bebas Neue', sans-serif; font-size: 2.8rem; line-height: 1; }
    .dash-score .pts { font-family: 'DM Mono', monospace; font-size: 0.65rem; text-transform: uppercase; opacity: 0.7; }

    .dash-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 36px; }
    .dash-card { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 24px; text-decoration: none; color: var(--coffee); display: flex; flex-direction: column; gap: 6px; transition: transform 0.12s, box-shadow 0.12s; }
    .dash-card:hover { transform: translate(-3px,-3px); box-shadow: var(--shadow); }
    .dash-card .label { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cherry); }
    .dash-card .big { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; line-height: 1; }
    .dash-card .go { font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; opacity: 0.55; margin-top: 4px; }

    .dash-section-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: 0.06em; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 3px solid var(--coffee); }
    .activity-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(18,3,9,0.1); gap: 12px; }
    .activity-row:last-child { border-bottom: none; }
    .activity-main a { font-weight: 600; text-decoration: none; color: var(--coffee); }
    .activity-meta { font-family: 'DM Mono', monospace; font-size: 0.68rem; opacity: 0.5; margin-top: 2px; }
    .empty-mini { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; opacity: 0.45; padding: 14px 0; }
</style>
@endsection

@section('content')
<div id="loading" class="loading" data-i18n="common.loading">Chargement de votre espace...</div>

<div id="dash" style="display:none;">
    {{-- === En-tête + score === --}}
    <div class="dash-hero">
        <div>
            <div class="dash-hello" id="hero-hello">Bonjour</div>
            <div class="dash-sub" id="hero-sub">Bienvenue dans votre espace</div>
        </div>
        <div class="dash-score">
            <div class="lvl" id="hero-lvl">&nbsp;</div>
            <div class="num" id="hero-score">0</div>
            <div class="pts" data-i18n="dash.points">points upcycling</div>
        </div>
    </div>

    {{-- === Raccourcis === --}}
    <div class="dash-cards">
        <a href="{{ route('particulier.annonces.index') }}" class="dash-card">
            <span class="label" data-i18n="nav.mylistings">Mes annonces</span>
            <span class="big" id="c-annonces">—</span>
            <span class="go" data-i18n="dash.managelistings">Gérer mes dépôts →</span>
        </a>
        <a href="{{ route('particulier.formations.index') }}" class="dash-card">
            <span class="label" data-i18n="nav.events">Formations &amp; événements</span>
            <span class="big" id="c-formations">—</span>
            <span class="go" data-i18n="dash.myregistrations">Voir mes inscriptions →</span>
        </a>
        <a href="{{ route('particulier.profile.show') }}" class="dash-card">
            <span class="label" data-i18n="dash.wasteavoided">Déchets évités</span>
            <span class="big" id="c-dechets">—</span>
            <span class="go" data-i18n="dash.kgprofile">kg · voir mon profil →</span>
        </a>
    </div>

    {{-- === Activité récente === --}}
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
            <h3 class="dash-section-title" style="margin-bottom:0; border:none; padding:0;" data-i18n="dash.recentactivity">Activité récente</h3>
            <a href="{{ route('particulier.annonces.index') }}" class="btn-secondary btn-sm" data-i18n="nav.mylistings">Mes annonces</a>
        </div>
        <div id="activity" style="margin-top:16px;">
            <div class="empty-mini" data-i18n="common.loading">Chargement…</div>
        </div>
    </div>
</div>

@endsection

{{-- === Scripts === --}}
@section('scripts')
<script>
// Échappe le HTML (anti-injection).
function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
}

// Charge le profil puis compteurs et activité.
async function loadDashboard() {
    let user = null;
    try {
        const r = await apiFetch('/api/v1/utilisateurs/me');
        if (!r) return;
        user = await r.json();
    } catch (e) {
        showAlert('Erreur de chargement', 'error');
        return;
    }

    document.getElementById('loading').style.display = 'none';
    document.getElementById('dash').style.display = 'block';

    document.getElementById('hero-hello').textContent = 'Bonjour ' + (user.prenom || '');
    document.getElementById('hero-sub').textContent = 'Membre depuis le ' + new Date(user.date_creation).toLocaleDateString('fr-FR');
    document.getElementById('hero-score').textContent = user.upcycling_score || 0;

    // Score détaillé (niveau + déchets)
    try {
        const rs = await apiFetch('/api/v1/utilisateurs/me/score');
        if (rs && rs.ok) {
            const s = await rs.json();
            document.getElementById('hero-score').textContent = s.score;
            if (s.niveau_actuel && s.niveau_actuel.nom) document.getElementById('hero-lvl').textContent = s.niveau_actuel.nom;
            const kg = (s.dechets_evites_kg || 0).toLocaleString('fr-FR', { maximumFractionDigits: 1 });
            document.getElementById('c-dechets').textContent = kg;
        }
    } catch (e) {}

    loadAnnoncesSummary();
    loadCounts();
}

async function loadCounts() {
    try {
        const re = await apiFetch('/api/v1/utilisateurs/me/evenements-inscrits');
        const evs = re && re.ok ? await re.json() : [];
        document.getElementById('c-formations').textContent = (evs || []).length;
    } catch (e) {}
}

const ANNONCE_STATUTS = {
    en_attente: { label: 'En attente', cls: 'badge-waiting' },
    validee:    { label: 'Validée',    cls: 'badge-valid' },
    refusee:    { label: 'Refusée',    cls: 'badge-cherry' },
    vendue:     { label: 'Vendue',     cls: 'badge-valid' },
    annulee:    { label: 'Annulée',    cls: 'badge-waiting' },
    retiree:    { label: 'Retirée',    cls: 'badge-waiting' }
};

// Annonces : compteur + activité récente.
async function loadAnnoncesSummary() {
    const container = document.getElementById('activity');
    try {
        const r = await apiFetch('/api/v1/annonces/me');
        if (!r) return;
        const annonces = await r.json();
        document.getElementById('c-annonces').textContent = (annonces || []).length;

        if (!annonces || annonces.length === 0) {
            container.innerHTML = '<div class="empty-mini">Vous n\'avez pas encore déposé d\'annonce.</div>';
            return;
        }
        const recent = annonces.slice(0, 5);
        container.innerHTML = recent.map(a => {
            const st = ANNONCE_STATUTS[a.statut] || { label: a.statut, cls: 'badge-waiting' };
            const date = new Date(a.date_creation).toLocaleDateString('fr-FR');
            const prix = a.type_annonce === 'don' ? 'Don' : (parseFloat(a.prix || 0).toFixed(2) + ' €');
            return `<div class="activity-row">
                <div class="activity-main">
                    <a href="/annonces/${a.id_annonce}">${escapeHtml(a.titre)}</a>
                    <div class="activity-meta">${prix} · déposée le ${date}</div>
                </div>
                <span class="badge ${st.cls}">${st.label}</span>
            </div>`;
        }).join('');
    } catch (e) {
        container.innerHTML = '<div class="empty-mini">Erreur de chargement.</div>';
    }
}

document.addEventListener('DOMContentLoaded', loadDashboard);
</script>
@endsection

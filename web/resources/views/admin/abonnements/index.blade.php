@extends('layouts.admin')
@section('title', 'Plans d\'abonnement')

@section('content')
<style>
    .plan-card { position: relative; border: var(--border); background: var(--cream); box-shadow: var(--shadow); padding: 0; overflow: hidden; display: flex; flex-direction: column; }
    .plan-accent { height: 10px; width: 100%; }
    .plan-body { padding: 24px; display: flex; flex-direction: column; flex: 1; }
    .plan-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 14px; }
    .plan-nom { font-family: 'Bebas Neue', sans-serif; font-size: 1.7rem; margin: 0; color: var(--coffee); line-height: 1; }
    .plan-prix { font-family: 'Bebas Neue', sans-serif; font-size: 2.4rem; margin: 0 0 4px; line-height: 1; }
    .plan-prix-an { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.55; margin: 0 0 12px; }
    .plan-desc { font-family: 'DM Mono', monospace; font-size: 0.78rem; opacity: 0.65; line-height: 1.55; margin: 0 0 16px; }
    .plan-privs { list-style: none; padding: 0; margin: 0 0 18px; display: flex; flex-direction: column; gap: 7px; }
    .plan-privs li { display: flex; align-items: center; gap: 9px; font-size: 0.85rem; }
    .plan-privs .pic { font-family: 'DM Mono', monospace; font-weight: 700; width: 16px; text-align: center; }
    .plan-privs .yes { color: var(--forest); }
    .plan-privs .no { color: rgba(18,3,9,0.3); }
    .plan-foot { margin-top: auto; padding-top: 16px; border-top: 2px solid rgba(18,3,9,0.1); display: flex; align-items: center; gap: 10px; }
    .plan-foot .idlabel { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.4; margin-right: auto; }

    /* Modale */
    .ab-overlay { display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6); z-index: 1000; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 40px 20px; }
    .ab-overlay.active { display: flex; }
    .ab-box { background: var(--cream); border: var(--border); box-shadow: var(--shadow); width: 100%; max-width: 620px; padding: 32px 36px; position: relative; }
    .ab-close { position: absolute; top: 14px; right: 18px; background: none; border: none; font-size: 1.7rem; cursor: pointer; color: var(--coffee); line-height: 1; }
    .ab-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .ab-grid .full { grid-column: 1 / -1; }
    .ab-check { display: flex; align-items: center; gap: 10px; font-family: 'Outfit', sans-serif; font-size: 0.95rem; cursor: pointer; padding: 8px 0; }
    .ab-check input { width: 20px; height: 20px; }
    .ab-color-row { display: flex; align-items: center; gap: 12px; }
    .ab-color-row input[type=color] { width: 54px; height: 44px; border: 3px solid var(--coffee); background: white; cursor: pointer; padding: 2px; }
    .ab-hint { font-family: 'DM Mono', monospace; font-size: 0.68rem; opacity: 0.5; margin-top: 4px; }
    #ab-error { display: none; background: #f8d7da; color: var(--cherry); border: 3px solid var(--cherry); padding: 12px 16px; margin-bottom: 18px; font-family: 'DM Mono', monospace; font-size: 0.82rem; }
</style>

<div class="page-header">
    <h1 class="page-title">Plans d'abonnement</h1>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
        <button type="button" onclick="syncStripe()" class="btn-secondary" id="btn-sync">↺ Sync Stripe</button>
        <button type="button" class="btn-primary" onclick="openPlanModal()">+ Nouveau plan</button>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:24px; margin-bottom:40px;" id="plans-grid">
    @forelse($abonnements as $plan)
    <div class="plan-card">
        <div class="plan-accent" style="background: {{ $plan['couleur'] ?? '#244F26' }};"></div>
        <div class="plan-body">
            <div class="plan-head">
                <h3 class="plan-nom">{{ $plan['nom'] }}</h3>
                <span class="badge badge-waiting">{{ $plan['type_cible'] }}</span>
            </div>
            <p class="plan-prix" style="color: {{ $plan['couleur'] ?? '#A4243B' }};">
                {{ $plan['prix_mensuel'] == 0 ? 'Gratuit' : number_format($plan['prix_mensuel'], 2, ',', ' ') . ' €/mois' }}
            </p>
            @if(!empty($plan['prix_annuel']))
            <p class="plan-prix-an">ou {{ number_format($plan['prix_annuel'], 2, ',', ' ') }} €/an</p>
            @endif
            @if(!empty($plan['description']))
            <p class="plan-desc">{{ $plan['description'] }}</p>
            @endif
            @php
                $priv = fn($on, $label) => '<li><span class="pic '.($on ? 'yes' : 'no').'">'.($on ? '✓' : '✕').'</span> '.$label.'</li>';
                $alDetail = $plan['alertes_actives']
                    ? ' ('.(is_null($plan['nb_alertes_max']) ? 'illimitées' : $plan['nb_alertes_max']).', '.(is_null($plan['rayon_alerte_max_km']) ? 'rayon libre' : $plan['rayon_alerte_max_km'].' km').')'
                    : '';
            @endphp
            <ul class="plan-privs">
                {!! $priv($plan['alertes_actives'], 'Alertes annonces'.$alDetail) !!}
                {!! $priv($plan['alertes_push'], 'Alertes push (OneSignal)') !!}
                {!! $priv($plan['dashboard_mensuel'], 'Tableau de bord 30 jours') !!}
                {!! $priv($plan['dashboard_annuel'], 'Tableau de bord annuel') !!}
                {!! $priv($plan['export_pdf'], 'Export PDF du rapport') !!}
                {!! $priv($plan['badges_actives'], 'Badges premium') !!}
                {!! $priv($plan['publicites_actives'], 'Publicités / sponsoring') !!}
            </ul>
            <div class="plan-foot">
                <span class="idlabel">#{{ $plan['id_abonnement'] }}</span>
                <button type="button" class="btn-secondary btn-sm" onclick='openPlanModal(@json($plan))'>Modifier</button>
                <button type="button" class="btn-danger btn-sm" onclick="deletePlan({{ $plan['id_abonnement'] }}, @js($plan['nom']))">Supprimer</button>
            </div>
        </div>
    </div>
    @empty
    <div class="card" style="grid-column:1/-1;">
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.5;text-align:center;padding:24px 0;">Aucun plan configuré. Cliquez sur « + Nouveau plan ».</p>
    </div>
    @endforelse
</div>

<div class="card">
    <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;margin:0 0 16px;border-bottom:3px solid var(--coffee);padding-bottom:10px;">
        Souscriptions actives
    </h3>
    <div id="souscriptions-container">
        <p style="font-family:'DM Mono',monospace;font-size:0.8rem;opacity:0.5;">Chargement…</p>
    </div>
</div>

{{-- Modale création / édition d'un plan --}}
<div class="ab-overlay" id="ab-modal">
    <div class="ab-box">
        <button type="button" class="ab-close" onclick="closePlanModal()">&times;</button>
        <h2 class="font-bebas" style="font-size:2rem; margin:0 0 22px;" id="ab-title">Nouveau plan</h2>
        <div id="ab-error"></div>
        <form id="ab-form" onsubmit="return savePlan(event)">
            <input type="hidden" id="f-id">
            <div class="ab-grid">
                <div class="form-group full">
                    <label class="form-label">Nom du plan</label>
                    <input type="text" id="f-nom" class="form-input" required maxlength="100" placeholder="ex. Expert Pro">
                </div>
                <div class="form-group">
                    <label class="form-label">Cible</label>
                    <select id="f-type" class="form-select">
                        <option value="professionnel">Professionnel</option>
                        <option value="particulier">Particulier</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Couleur d'accent</label>
                    <div class="ab-color-row">
                        <input type="color" id="f-couleur" value="#244F26">
                        <input type="text" id="f-couleur-hex" class="form-input" style="flex:1;" maxlength="7" placeholder="#244F26">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Prix mensuel (€)</label>
                    <input type="number" id="f-prix" class="form-input" min="0" step="0.01" value="0" required>
                    <div class="ab-hint">0 = plan gratuit</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Prix annuel (€) — optionnel</label>
                    <input type="number" id="f-prix-an" class="form-input" min="0" step="0.01" placeholder="—">
                </div>
                <div class="form-group full">
                    <label class="form-label">Description</label>
                    <textarea id="f-desc" class="form-textarea" style="min-height:80px;" placeholder="Ce que comprend le plan…"></textarea>
                </div>

                <div class="form-group full" style="margin-top:6px;">
                    <p style="font-family:'DM Mono',monospace; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.05em; opacity:0.6; margin:0 0 4px; border-top:2px solid rgba(18,3,9,0.1); padding-top:16px;">Privilèges — réellement appliqués côté serveur</p>
                </div>
                <label class="ab-check full"><input type="checkbox" id="f-alertes-act"> Alertes d'annonces par matériau</label>
                <div class="form-group">
                    <label class="form-label">Nb. d'alertes max</label>
                    <input type="number" id="f-alertes" class="form-input" min="0" placeholder="illimité si vide">
                    <div class="ab-hint">vide = illimité</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Rayon d'alerte max (km)</label>
                    <input type="number" id="f-rayon" class="form-input" min="0" placeholder="illimité si vide">
                    <div class="ab-hint">vide = illimité (modulable)</div>
                </div>
                <label class="ab-check"><input type="checkbox" id="f-alertes-push"> Alertes push (OneSignal)</label>
                <label class="ab-check"><input type="checkbox" id="f-dash-mens"> Tableau de bord 30 jours</label>
                <label class="ab-check"><input type="checkbox" id="f-dashboard"> Tableau de bord annuel</label>
                <label class="ab-check"><input type="checkbox" id="f-export"> Export PDF du rapport</label>
                <label class="ab-check"><input type="checkbox" id="f-badges"> Badges premium</label>
                <label class="ab-check"><input type="checkbox" id="f-pub"> Publicités / sponsoring</label>
            </div>
            <div style="display:flex; gap:12px; margin-top:24px;">
                <button type="submit" class="btn-primary" id="ab-submit">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="closePlanModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const API = '{{ config("services.api.public_url") }}';
const TOKEN = '{{ session("admin_token") }}';

// ─── Sync Stripe ──────────────────────────────────────────────
async function syncStripe() {
    const btn = document.getElementById('btn-sync');
    btn.disabled = true; btn.textContent = '…';
    try {
        const r = await fetch(API + '/api/v1/admin/stripe/sync-plans', { method: 'POST', headers: { 'Authorization': 'Bearer ' + TOKEN } });
        btn.textContent = r.ok ? '✓ Synchronisé' : '✕ Erreur';
    } catch { btn.textContent = '✕ Erreur'; }
    setTimeout(() => { btn.disabled = false; btn.textContent = '↺ Sync Stripe'; }, 3000);
}

// ─── Modale plan ──────────────────────────────────────────────
const $ = id => document.getElementById(id);
function openPlanModal(plan) {
    $('ab-error').style.display = 'none';
    if (plan && plan.id_abonnement) {
        $('ab-title').textContent = 'Modifier le plan';
        $('f-id').value = plan.id_abonnement;
        $('f-nom').value = plan.nom || '';
        $('f-type').value = plan.type_cible || 'professionnel';
        $('f-prix').value = plan.prix_mensuel != null ? plan.prix_mensuel : 0;
        $('f-prix-an').value = plan.prix_annuel != null ? plan.prix_annuel : '';
        $('f-desc').value = plan.description || '';
        $('f-alertes').value = plan.nb_alertes_max != null ? plan.nb_alertes_max : '';
        $('f-rayon').value = plan.rayon_alerte_max_km != null ? plan.rayon_alerte_max_km : '';
        $('f-alertes-act').checked = !!plan.alertes_actives;
        $('f-alertes-push').checked = !!plan.alertes_push;
        $('f-dash-mens').checked = !!plan.dashboard_mensuel;
        $('f-dashboard').checked = !!plan.dashboard_annuel;
        $('f-export').checked = !!plan.export_pdf;
        $('f-badges').checked = !!plan.badges_actives;
        $('f-pub').checked = !!plan.publicites_actives;
        setColor(plan.couleur || '#244F26');
    } else {
        $('ab-title').textContent = 'Nouveau plan';
        $('ab-form').reset();
        $('f-id').value = '';
        setColor('#244F26');
    }
    $('ab-modal').classList.add('active');
}
function closePlanModal() { $('ab-modal').classList.remove('active'); }
function setColor(hex) { $('f-couleur').value = hex; $('f-couleur-hex').value = hex; }
$('f-couleur').addEventListener('input', () => $('f-couleur-hex').value = $('f-couleur').value);
$('f-couleur-hex').addEventListener('input', () => { if (/^#[0-9a-fA-F]{6}$/.test($('f-couleur-hex').value)) $('f-couleur').value = $('f-couleur-hex').value; });
$('ab-modal').addEventListener('mousedown', e => { if (e.target.id === 'ab-modal') closePlanModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePlanModal(); });

function numOrNull(v) { v = String(v).trim(); return v === '' ? null : Number(v); }

async function savePlan(e) {
    e.preventDefault();
    const id = $('f-id').value;
    const payload = {
        nom: $('f-nom').value.trim(),
        type_cible: $('f-type').value,
        prix_mensuel: Number($('f-prix').value || 0),
        prix_annuel: numOrNull($('f-prix-an').value),
        description: $('f-desc').value.trim(),
        couleur: $('f-couleur-hex').value.trim() || '#244F26',
        nb_alertes_max: numOrNull($('f-alertes').value),
        rayon_alerte_max_km: numOrNull($('f-rayon').value),
        alertes_actives: $('f-alertes-act').checked,
        alertes_push: $('f-alertes-push').checked,
        dashboard_mensuel: $('f-dash-mens').checked,
        dashboard_annuel: $('f-dashboard').checked,
        export_pdf: $('f-export').checked,
        badges_actives: $('f-badges').checked,
        publicites_actives: $('f-pub').checked,
    };
    const submit = $('ab-submit');
    submit.disabled = true; submit.textContent = '…';
    try {
        const url = id ? API + '/api/v1/admin/abonnements/' + id : API + '/api/v1/admin/abonnements';
        const r = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { showError(d.erreur || 'Erreur lors de l\'enregistrement.'); return false; }
        location.reload();
    } catch { showError('Erreur réseau.'); }
    finally { submit.disabled = false; submit.textContent = 'Enregistrer'; }
    return false;
}

function showError(msg) { const el = $('ab-error'); el.textContent = msg; el.style.display = 'block'; }

async function deletePlan(id, nom) {
    if (!confirm('Supprimer le plan « ' + nom + ' » ? Cette action est définitive.')) return;
    try {
        const r = await fetch(API + '/api/v1/admin/abonnements/' + id, { method: 'DELETE', headers: { 'Authorization': 'Bearer ' + TOKEN } });
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { alert(d.erreur || 'Suppression impossible.'); return; }
        location.reload();
    } catch { alert('Erreur réseau.'); }
}

// ─── Souscriptions actives ────────────────────────────────────
(async function loadSouscriptions() {
    const container = document.getElementById('souscriptions-container');
    try {
        const r = await fetch(API + '/api/v1/admin/utilisateurs', { headers: { 'Authorization': 'Bearer ' + TOKEN } });
        if (!r.ok) { container.innerHTML = '<p style="color:var(--cherry);">Accès refusé.</p>'; return; }
        const users = await r.json();
        const pros = (users || []).filter(u => u.role === 'professionnel' || u.role === 'admin');
        if (pros.length === 0) { container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;opacity:0.5;">Aucun professionnel inscrit.</p>'; return; }
        const results = await Promise.all(pros.map(u =>
            fetch(API + '/api/v1/admin/utilisateurs/' + u.id_utilisateur + '/abonnement', { headers: { 'Authorization': 'Bearer ' + TOKEN } })
                .then(r => r.ok ? r.json() : null).then(sub => sub ? { user: u, sub } : null)
        ));
        const active = results.filter(Boolean).filter(r => r.sub && r.sub.est_active);
        if (active.length === 0) { container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;opacity:0.5;">Aucune souscription active pour le moment.</p>'; return; }
        let html = '<table><thead><tr><th>Utilisateur</th><th>Email</th><th>Plan</th><th>Depuis</th><th>Géré par admin</th></tr></thead><tbody>';
        active.forEach(({ user, sub }) => {
            const since = sub.date_debut ? new Date(sub.date_debut).toLocaleDateString('fr-FR') : '—';
            html += '<tr>'
                + '<td><a href="/admin/utilisateurs/' + user.id_utilisateur + '">' + (user.prenom || '') + ' ' + (user.nom || '') + '</a></td>'
                + '<td style="font-family:\'DM Mono\',monospace;font-size:0.78rem;">' + (user.email || '') + '</td>'
                + '<td><span class="badge badge-valid">' + (sub.nom || '—') + '</span></td>'
                + '<td style="font-family:\'DM Mono\',monospace;font-size:0.78rem;">' + since + '</td>'
                + '<td>' + (sub.gere_par_admin ? '<span class="badge badge-waiting">Admin</span>' : '—') + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (e) { container.innerHTML = '<p style="color:var(--cherry);">Erreur de chargement.</p>'; }
})();
</script>
@endpush

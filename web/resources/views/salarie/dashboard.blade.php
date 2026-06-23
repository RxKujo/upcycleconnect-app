@extends('layouts.salarie')

@section('title', 'Tableau de bord')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tableau de bord</h1>
    <span class="font-mono" style="font-size:0.75rem; opacity:0.5;">Salarié #{{ session('salarie_id') }}</span>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <p class="stat-label">Événements en attente</p>
        <p class="stat-value">{{ $stats['evenements_attente'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Événements validés</p>
        <p class="stat-value">{{ $stats['evenements_valides'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Articles brouillon</p>
        <p class="stat-value">{{ $stats['articles_brouillon'] ?? 0 }}</p>
    </div>
    <div class="stat-card">
        <p class="stat-label">Articles publiés</p>
        <p class="stat-value">{{ $stats['articles_publies'] ?? 0 }}</p>
    </div>
    <div class="stat-card" style="background:#fdf3e3; border-color:var(--cherry);">
        <p class="stat-label">Signalements à traiter</p>
        <p class="stat-value" style="color:var(--cherry);">{{ $stats['signalements'] ?? 0 }}</p>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:24px; margin-bottom:32px;">
    <a href="/salarie/evenements/create" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--forest); margin:0 0 8px;">+ Nouvel événement</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Formation, atelier ou conférence. Soumis à validation admin.</p>
    </a>
    <a href="/salarie/articles/create" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--forest); margin:0 0 8px;">+ Nouvel article</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Rédiger un article News & Conseils en brouillon ou publié.</p>
    </a>
    <a href="/salarie/forum/signalements" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--cherry); margin:0 0 8px;">⚑ Modération</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Traiter les messages signalés par la communauté.</p>
    </a>
    <a href="/salarie/idees" class="card" style="text-decoration:none; display:block;">
        <h3 class="font-bebas" style="font-size:1.6rem; color:var(--teal); margin:0 0 8px;">💡 Boîte à idées</h3>
        <p style="font-size:0.95rem; opacity:0.7;">Proposer et voter pour les idées de l'équipe.</p>
    </a>
</div>

{{-- Mini-planning : prochains créneaux --}}
<div class="card" id="mini-planning" style="margin-bottom:0;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 class="font-bebas" style="font-size:1.8rem;margin:0;">Mon planning — à venir</h2>
        <a href="/salarie/planning" class="btn-secondary btn-sm">Voir tout</a>
    </div>
    <div id="planning-items">
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;opacity:0.4;">Chargement…</p>
    </div>
</div>

@section('scripts')
<script>
(async function() {
    try {
        const r = await fetch('{{ config("services.api.public_url") }}/api/v1/salarie/planning', {
            headers: { 'Authorization': 'Bearer {{ session("salarie_token") }}' }
        });
        if (!r.ok) { document.getElementById('planning-items').innerHTML = '<p style="opacity:0.4;font-family:\'DM Mono\',monospace;font-size:0.85rem;text-transform:uppercase;">Impossible de charger le planning.</p>'; return; }
        const items = await r.json();
        const aujourd = new Date().toISOString().slice(0, 10);
        const prochains = (items || [])
            .filter(i => i.date_debut >= aujourd)
            .sort((a, b) => a.date_debut.localeCompare(b.date_debut))
            .slice(0, 5);
        if (!prochains.length) {
            document.getElementById('planning-items').innerHTML = '<p style="opacity:0.4;font-family:\'DM Mono\',monospace;font-size:0.85rem;text-transform:uppercase;">Aucun créneau à venir.</p>';
            return;
        }
        const colors = { evenement:'var(--teal)', reunion:'var(--cherry)', formation:'#6c5ce7', travail:'var(--coffee)', perso:'#b2bec3' };
        const html = prochains.map(i => {
            const d = new Date(i.date_debut);
            const label = d.toLocaleDateString('fr-FR', { weekday:'short', day:'2-digit', month:'short' }) + ' ' + d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
            const color = colors[i.type_creneau] || 'var(--coffee)';
            return `<div style="display:flex;align-items:center;gap:16px;padding:12px 0;border-bottom:2px solid rgba(18,3,9,0.06);">
                <div style="width:8px;height:40px;background:${color};flex-shrink:0;"></div>
                <div>
                    <div style="font-family:'Bebas Neue',sans-serif;font-size:1.2rem;line-height:1.2;">${i.titre_creneau || '—'}</div>
                    <div style="font-family:'DM Mono',monospace;font-size:0.75rem;text-transform:uppercase;opacity:0.5;">${label} · ${i.type_creneau || ''}</div>
                </div>
            </div>`;
        }).join('');
        document.getElementById('planning-items').innerHTML = html;
    } catch(e) {
        document.getElementById('planning-items').innerHTML = '';
    }
})();
</script>
@endsection
@endsection

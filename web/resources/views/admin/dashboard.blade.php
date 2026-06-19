@extends('layouts.admin')
@section('title', 'Tableau de bord')

@section('content')
<div class="page-header">
    <h1 class="page-title">Tableau de bord</h1>
    <span class="font-mono" style="font-size:0.75rem;opacity:0.5;">{{ now()->format('d/m/Y H:i') }}</span>
</div>

<div id="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:24px;margin-bottom:40px;">
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Membres</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--coffee);line-height:1;" id="stat-users">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Professionnels</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--teal);line-height:1;" id="stat-pros">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Abonnements actifs</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--forest);line-height:1;" id="stat-abonnements">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">CA Total</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--cherry);line-height:1;" id="stat-ca">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Commandes</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--coffee);line-height:1;" id="stat-commandes">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Annonces</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--coffee);line-height:1;" id="stat-annonces">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;border-color:{{ '' }};" id="card-annonces-attente">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Annonces en attente</p>
        <p class="font-bebas" style="font-size:3rem;line-height:1;" id="stat-annonces-attente">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;" id="card-events-attente">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Événements en attente</p>
        <p class="font-bebas" style="font-size:3rem;line-height:1;" id="stat-events-attente">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;" id="card-signalements">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Signalements</p>
        <p class="font-bebas" style="font-size:3rem;line-height:1;" id="stat-signalements">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Inscriptions événements</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--coffee);line-height:1;" id="stat-inscriptions">—</p>
    </div>
    <div class="card" style="cursor:default;transform:none;text-align:center;padding:28px 20px;">
        <p class="font-mono" style="font-size:0.72rem;text-transform:uppercase;opacity:0.55;margin-bottom:8px;">Formations disponibles</p>
        <p class="font-bebas" style="font-size:3rem;color:var(--teal);line-height:1;" id="stat-formations">—</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:24px;">
    <a href="{{ route('admin.annonces.index') }}" class="card" style="text-decoration:none;display:block;">
        <h3 class="font-bebas" style="font-size:1.5rem;color:var(--cherry);margin:0 0 6px;">→ Modérer les annonces</h3>
        <p style="font-family:'DM Mono',monospace;font-size:0.78rem;opacity:0.6;">Valider ou refuser les annonces en attente.</p>
    </a>
    <a href="{{ route('admin.evenements.index') }}" class="card" style="text-decoration:none;display:block;">
        <h3 class="font-bebas" style="font-size:1.5rem;color:var(--cherry);margin:0 0 6px;">→ Modérer les événements</h3>
        <p style="font-family:'DM Mono',monospace;font-size:0.78rem;opacity:0.6;">Valider ou refuser les événements soumis.</p>
    </a>
    <a href="{{ route('admin.utilisateurs.index') }}" class="card" style="text-decoration:none;display:block;">
        <h3 class="font-bebas" style="font-size:1.5rem;color:var(--forest);margin:0 0 6px;">→ Gérer les membres</h3>
        <p style="font-family:'DM Mono',monospace;font-size:0.78rem;opacity:0.6;">Bans, rôles, abonnements manuels.</p>
    </a>
    <a href="{{ route('admin.commandes.index') }}" class="card" style="text-decoration:none;display:block;">
        <h3 class="font-bebas" style="font-size:1.5rem;color:var(--forest);margin:0 0 6px;">→ Voir les commandes</h3>
        <p style="font-family:'DM Mono',monospace;font-size:0.78rem;opacity:0.6;">Suivi des achats et statuts de livraison.</p>
    </a>
</div>
@endsection

@push('scripts')
<script>
(async function() {
    const token = '{{ session("admin_token") }}';
    try {
        const r = await fetch('{{ config("services.api.url") }}/api/v1/admin/stats', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!r.ok) return;
        const d = await r.json();

        document.getElementById('stat-users').textContent = d.total_utilisateurs ?? '—';
        document.getElementById('stat-pros').textContent = d.total_pros ?? '—';
        document.getElementById('stat-abonnements').textContent = d.abonnements_actifs ?? '—';
        document.getElementById('stat-ca').textContent = d.ca_total != null
            ? parseFloat(d.ca_total).toLocaleString('fr-FR', { style:'currency', currency:'EUR', maximumFractionDigits:0 })
            : '—';
        document.getElementById('stat-commandes').textContent = d.total_commandes ?? '—';
        document.getElementById('stat-annonces').textContent = d.total_annonces ?? '—';
        document.getElementById('stat-inscriptions').textContent = d.total_inscriptions ?? '—';
        document.getElementById('stat-formations').textContent = d.total_formations ?? '—';

        const annAtEl = document.getElementById('stat-annonces-attente');
        annAtEl.textContent = d.annonces_en_attente ?? '—';
        if ((d.annonces_en_attente ?? 0) > 0) { annAtEl.style.color = 'var(--cherry)'; document.getElementById('card-annonces-attente').style.borderColor = 'var(--cherry)'; }

        const evAtEl = document.getElementById('stat-events-attente');
        evAtEl.textContent = d.evenements_en_attente ?? '—';
        if ((d.evenements_en_attente ?? 0) > 0) { evAtEl.style.color = 'var(--cherry)'; document.getElementById('card-events-attente').style.borderColor = 'var(--cherry)'; }

        const sigEl = document.getElementById('stat-signalements');
        sigEl.textContent = d.signalements ?? '—';
        if ((d.signalements ?? 0) > 0) { sigEl.style.color = 'var(--cherry)'; document.getElementById('card-signalements').style.borderColor = 'var(--cherry)'; }
    } catch(e) {}
})();
</script>
@endpush

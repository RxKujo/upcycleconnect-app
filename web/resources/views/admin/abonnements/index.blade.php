@extends('layouts.admin')
@section('title', 'Plans d\'abonnement')

@section('content')
<div class="page-header">
    <h1 class="page-title">Plans d'abonnement</h1>
    <form action="{{ route('admin.scores.index') }}" style="display:none"></form>
    <form method="POST" action="#" id="form-sync">
        @csrf
        <button type="button" onclick="syncStripe()" class="btn-secondary">↺ Sync Stripe</button>
    </form>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:24px;margin-bottom:40px;">
    @forelse($abonnements as $plan)
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;">
            <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;margin:0;color:var(--coffee);">{{ $plan['nom'] }}</h3>
            <span class="badge badge-waiting">{{ $plan['type_cible'] }}</span>
        </div>
        <p style="font-family:'Bebas Neue',sans-serif;font-size:2.5rem;color:var(--cherry);margin:0 0 8px;">
            {{ $plan['prix_mensuel'] == 0 ? 'Gratuit' : number_format($plan['prix_mensuel'], 2, ',', ' ') . ' €/mois' }}
        </p>
        @if(!empty($plan['description']))
        <p style="font-family:'DM Mono',monospace;font-size:0.8rem;opacity:0.6;line-height:1.6;">{{ $plan['description'] }}</p>
        @endif
        <div style="margin-top:16px;padding-top:16px;border-top:2px solid rgba(18,3,9,0.1);">
            <p style="font-family:'DM Mono',monospace;font-size:0.72rem;text-transform:uppercase;opacity:0.5;">ID Abonnement</p>
            <p style="font-family:'DM Mono',monospace;font-size:0.85rem;">#{{ $plan['id_abonnement'] }}</p>
        </div>
    </div>
    @empty
    <div class="card" style="grid-column:1/-1;">
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;opacity:0.5;text-align:center;padding:24px 0;">Aucun plan configuré.</p>
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
@endsection

@push('scripts')
<script>
async function syncStripe() {
    const token = '{{ session("admin_token") }}';
    const btn = document.querySelector('#form-sync button');
    btn.disabled = true;
    btn.textContent = '…';
    try {
        const r = await fetch('{{ config("services.api.public_url") }}/api/v1/admin/stripe/sync-plans', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const d = await r.json();
        btn.textContent = r.ok ? '✓ Synchronisé' : '✕ Erreur';
    } catch { btn.textContent = '✕ Erreur'; }
    setTimeout(() => { btn.disabled = false; btn.textContent = '↺ Sync Stripe'; }, 3000);
}

(async function loadSouscriptions() {
    const container = document.getElementById('souscriptions-container');
    const token = '{{ session("admin_token") }}';
    try {
        // List all users and filter those with active subs via utilisateurs list
        const r = await fetch('{{ config("services.api.public_url") }}/api/v1/admin/utilisateurs', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!r.ok) { container.innerHTML = '<p style="color:var(--cherry);">Accès refusé.</p>'; return; }
        const users = await r.json();
        const pros = (users || []).filter(u => u.role === 'professionnel' || u.role === 'admin');
        if (pros.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;opacity:0.5;">Aucun professionnel inscrit.</p>';
            return;
        }
        // Fetch souscription for each pro in parallel
        const results = await Promise.all(pros.map(u =>
            fetch('{{ config("services.api.public_url") }}/api/v1/admin/utilisateurs/' + u.id_utilisateur + '/abonnement', {
                headers: { 'Authorization': 'Bearer ' + token }
            }).then(r => r.ok ? r.json() : null).then(sub => sub ? { user: u, sub } : null)
        ));
        const active = results.filter(Boolean).filter(r => r.sub && r.sub.est_active);
        if (active.length === 0) {
            container.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.8rem;opacity:0.5;">Aucune souscription active pour le moment.</p>';
            return;
        }
        let html = '<table><thead><tr><th>Utilisateur</th><th>Email</th><th>Plan</th><th>Depuis</th><th>Géré par admin</th></tr></thead><tbody>';
        active.forEach(({ user, sub }) => {
            const since = sub.date_debut ? new Date(sub.date_debut).toLocaleDateString('fr-FR') : '—';
            html += '<tr>'
                + '<td><a href="/admin/utilisateurs/' + user.id_utilisateur + '">' + (user.prenom||'') + ' ' + (user.nom||'') + '</a></td>'
                + '<td style="font-family:\'DM Mono\',monospace;font-size:0.78rem;">' + (user.email||'') + '</td>'
                + '<td><span class="badge badge-valid">' + (sub.nom_abonnement||'—') + '</span></td>'
                + '<td style="font-family:\'DM Mono\',monospace;font-size:0.78rem;">' + since + '</td>'
                + '<td>' + (sub.gere_par_admin ? '<span class="badge badge-waiting">Admin</span>' : '—') + '</td>'
                + '</tr>';
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch(e) {
        container.innerHTML = '<p style="color:var(--cherry);">Erreur de chargement.</p>';
    }
})();
</script>
@endpush

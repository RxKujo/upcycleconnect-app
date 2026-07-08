@extends('layouts.admin')
@section('title', 'Utilisateur #' . $utilisateur['id_utilisateur'])

{{-- Vue admin : fiche détaillée d'un compte utilisateur. Bandeau d'identité et cartes
     d'actions (changement de rôle, affectation de site, abonnement, modération/ban). --}}

@php
    $roleColors = ['particulier' => 'var(--teal)', 'professionnel' => 'var(--cherry)', 'salarie' => 'var(--forest)', 'admin' => 'var(--coffee)'];
    $roleColor  = $roleColors[$utilisateur['role']] ?? 'var(--coffee)';
    $roleLabels = ['particulier' => 'Particulier', 'professionnel' => 'Artisan / Pro', 'salarie' => 'Salarié', 'admin' => 'Admin'];
@endphp

@section('content')
{{-- === Styles de la fiche === --}}
<style>
    .u-hero { padding: 32px 36px; }
    .u-hero-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border-bottom: 3px solid var(--coffee); padding-bottom: 22px; margin-bottom: 24px; }
    .u-name { font-family: 'Bebas Neue', sans-serif; font-size: 2.6rem; line-height: 0.95; letter-spacing: 0.03em; }
    .u-badges { display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap; align-items: center; }
    .u-role-badge { color: #fff; border-color: var(--coffee); }
    .u-id { font-family: 'DM Mono', monospace; font-size: 1.5rem; font-weight: 700; opacity: 0.3; white-space: nowrap; }
    .u-facts { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 22px; }
    .u-fact .info-label { display: block; margin-bottom: 6px; }
    .u-fact .info-value { font-size: 1.1rem; word-break: break-word; }

    .u-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; align-items: start; margin-top: 24px; }
    .u-actions-grid .card { margin: 0; }
    .u-card-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; letter-spacing: 0.04em; margin: 0 0 20px; border-bottom: 3px solid var(--coffee); padding-bottom: 10px; display: flex; align-items: center; gap: 10px; }
    .u-card-title .dot { width: 12px; height: 12px; border: 2px solid var(--coffee); flex-shrink: 0; }
    .action-row { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
    .action-row .form-select { flex: 1 1 180px; min-width: 160px; margin: 0; }
    .u-current { font-size: 0.95rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
</style>

{{-- === En-tête de page (retour + suppression du compte) === --}}
<div class="page-header">
    <h1 class="page-title">Fiche compte</h1>
    <div class="action-cell">
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn-secondary btn-sm">← Retour</a>
        <form action="{{ route('admin.utilisateurs.delete', $utilisateur['id_utilisateur']) }}" method="POST"
              data-confirm="Supprimer définitivement ce compte ? Cette action est irréversible.">
            @csrf @method('DELETE')
            <button type="submit" class="btn-danger btn-sm">Supprimer le compte</button>
        </form>
    </div>
</div>

{{-- ── Bandeau identité ────────────────────────────────────────────────── --}}
<div class="card u-hero">
    <div class="u-hero-head">
        <div>
            <div class="u-name">{{ trim(($utilisateur['prenom'] ?? '') . ' ' . ($utilisateur['nom'] ?? '')) ?: 'Compte' }}</div>
            <div class="u-badges">
                <span class="badge u-role-badge" style="background:{{ $roleColor }};">{{ $roleLabels[$utilisateur['role']] ?? $utilisateur['role'] }}</span>
                @if($utilisateur['est_banni'])
                    @php $banFin = $utilisateur['date_fin_ban'] ? ' · jusqu\'au ' . \Carbon\Carbon::parse($utilisateur['date_fin_ban'])->format('d/m/Y') : ''; @endphp
                    <span class="badge badge-refused">Banni{{ $banFin }}</span>
                @else
                    <span class="badge badge-valid">Actif</span>
                @endif
                @if(!empty($utilisateur['est_certifie']))
                    <span class="badge badge-valid">Certifié</span>
                @endif
            </div>
        </div>
        <div class="u-id">#{{ $utilisateur['id_utilisateur'] }}</div>
    </div>

    <div class="u-facts">
        <div class="u-fact">
            <span class="info-label">Email</span>
            <p class="info-value" style="font-family:'DM Mono',monospace; font-size:0.95rem;">{{ $utilisateur['email'] }}</p>
        </div>
        <div class="u-fact">
            <span class="info-label">Téléphone</span>
            <p class="info-value">{{ $utilisateur['telephone'] ?? '—' }}</p>
        </div>
        <div class="u-fact">
            <span class="info-label">Ville</span>
            <p class="info-value">{{ $utilisateur['ville'] ?? '—' }}</p>
        </div>
        @if(!empty($utilisateur['nom_entreprise']))
        <div class="u-fact">
            <span class="info-label">Entreprise</span>
            <p class="info-value">{{ $utilisateur['nom_entreprise'] }}</p>
        </div>
        @endif
        <div class="u-fact">
            <span class="info-label">Inscription</span>
            <p class="info-value">{{ \Carbon\Carbon::parse($utilisateur['date_creation'])->format('d/m/Y') }}</p>
        </div>
        <div class="u-fact">
            <span class="info-label">Score Upcycling</span>
            <p class="info-value"><strong style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; color:var(--forest);">{{ $utilisateur['upcycling_score'] ?? 0 }}</strong> pts</p>
        </div>
    </div>
</div>

{{-- ── Actions de gestion ──────────────────────────────────────────────── --}}
<div class="u-actions-grid">

    {{-- Carte : changement de rôle --}}
    <div class="card">
        <h3 class="u-card-title"><span class="dot" style="background:{{ $roleColor }};"></span> Rôle du compte</h3>
        <div class="u-current">Rôle actuel : <span class="badge u-role-badge" style="background:{{ $roleColor }};">{{ $roleLabels[$utilisateur['role']] ?? $utilisateur['role'] }}</span></div>
        <form action="{{ route('admin.utilisateurs.role', $utilisateur['id_utilisateur']) }}" method="POST">
            @csrf @method('PUT')
            <div class="action-row">
                <select name="role" class="form-select">
                    @foreach(['particulier','professionnel','salarie','admin'] as $r)
                        <option value="{{ $r }}" {{ $utilisateur['role'] === $r ? 'selected' : '' }}>{{ $roleLabels[$r] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary btn-sm" style="white-space:nowrap;">Changer</button>
            </div>
        </form>
    </div>

    {{-- Carte : affectation à un site (salariés uniquement) --}}
    @if($utilisateur['role'] === 'salarie')
    <div class="card">
        <h3 class="u-card-title"><span class="dot" style="background:var(--forest);"></span> Site (antenne)</h3>
        <div class="u-current">
            Site actuel :
            @if(!empty($utilisateur['nom_site']))
                <span class="badge badge-valid">{{ $utilisateur['nom_site'] }}</span>
            @else
                <span class="badge badge-waiting">Aucun (voit tout l'inventaire)</span>
            @endif
        </div>
        <form action="{{ route('admin.utilisateurs.site', $utilisateur['id_utilisateur']) }}" method="POST">
            @csrf @method('PUT')
            <div class="action-row">
                <select name="id_site" class="form-select">
                    <option value="">— Aucun site —</option>
                    @foreach($sites as $s)
                        <option value="{{ $s['id_site'] }}" {{ ($utilisateur['id_site_uc'] ?? null) == $s['id_site'] ? 'selected' : '' }}>
                            {{ $s['nom_site'] }}@if(!empty($s['ville'])) — {{ $s['ville'] }}@endif
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary btn-sm" style="white-space:nowrap;">Affecter</button>
            </div>
        </form>
        @if(empty($sites))
            <p style="font-size:0.85rem;opacity:0.6;margin-top:12px;">Aucun site défini. <a href="{{ route('admin.sites.index') }}" style="color:var(--cherry);">Créer un site →</a></p>
        @endif
    </div>
    @endif

    {{-- Carte : gestion de l'abonnement (professionnels uniquement) --}}
    @if($utilisateur['role'] === 'professionnel')
    <div class="card">
        <h3 class="u-card-title"><span class="dot" style="background:var(--wheat);"></span> Abonnement</h3>
        @if($souscription)
            <div style="margin-bottom:18px;">
                <p class="info-value" style="margin:0 0 4px;"><strong>{{ $souscription['nom'] }}</strong></p>
                <p style="font-size:0.9rem;color:rgba(18,3,9,0.6);margin:0;">
                    Depuis le {{ \Carbon\Carbon::parse($souscription['date_debut'])->format('d/m/Y') }}
                    @if($souscription['date_fin'])
                        — jusqu'au {{ \Carbon\Carbon::parse($souscription['date_fin'])->format('d/m/Y') }}
                    @else
                        — sans date de fin
                    @endif
                </p>
                @if($souscription['gere_par_admin'])
                    <span class="badge badge-waiting" style="margin-top:8px;">Géré manuellement</span>
                @endif
            </div>
            <form action="{{ route('admin.utilisateurs.abonnement.revoke', $utilisateur['id_utilisateur']) }}" method="POST"
                  data-confirm="Révoquer cet abonnement ?">
                @csrf @method('DELETE')
                <button type="submit" class="btn-danger btn-sm">Révoquer</button>
            </form>
        @else
            <p class="info-value" style="margin-bottom:18px;color:rgba(18,3,9,0.6);">Aucun abonnement actif.</p>
        @endif

        @if(count($abonnements) > 0)
        <form action="{{ route('admin.utilisateurs.abonnement.assign', $utilisateur['id_utilisateur']) }}" method="POST" style="margin-top:18px;padding-top:18px;border-top:2px solid rgba(18,3,9,0.1);">
            @csrf
            <label class="form-label">Assigner un abonnement</label>
            <select name="id_abonnement" class="form-select" style="margin-bottom:12px;">
                @foreach($abonnements as $ab)
                    <option value="{{ $ab['id_abonnement'] }}">{{ $ab['nom'] }} — {{ number_format($ab['prix_mensuel'], 2, ',', ' ') }} €/mois ({{ $ab['type_cible'] }})</option>
                @endforeach
            </select>
            <div style="margin-bottom:16px;">
                <label class="form-label" style="font-size:0.8rem;">Date de fin (optionnel)</label>
                <input type="date" name="date_fin" class="form-input">
            </div>
            <button type="submit" class="btn-primary btn-sm">Assigner</button>
        </form>
        @endif
    </div>
    @endif

    {{-- Carte : modération (bannir / débannir) --}}
    <div class="card">
        <h3 class="u-card-title"><span class="dot" style="background:var(--cherry);"></span> Modération</h3>
        @if($utilisateur['est_banni'])
            <div class="u-current">Ce compte est <span class="badge badge-refused">banni</span></div>
            <form action="{{ route('admin.utilisateurs.unban', $utilisateur['id_utilisateur']) }}" method="POST">
                @csrf
                <button type="submit" class="btn-success">Débannir le compte</button>
            </form>
        @else
            <form action="{{ route('admin.utilisateurs.ban', $utilisateur['id_utilisateur']) }}" method="POST">
                @csrf
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Type de bannissement</label>
                    <div style="display:flex;gap:20px;margin-bottom:16px;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="ban_type" value="temporaire" checked onchange="toggleBanDate(false)"> Temporaire
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                            <input type="radio" name="ban_type" value="permanent" onchange="toggleBanDate(true)"> Permanent
                        </label>
                    </div>
                    <input type="hidden" name="permanent" id="permanent-input" value="">
                    <div id="ban-date-group" style="margin-bottom:16px;">
                        <label class="form-label">Date de fin</label>
                        <input type="date" name="date_fin_ban" class="form-input" min="{{ now()->addDay()->format('Y-m-d') }}" value="{{ now()->addMonth()->format('Y-m-d') }}">
                    </div>
                    <button type="submit" class="btn-danger">Bannir</button>
                </div>
            </form>
        @endif
    </div>

</div>

{{-- === Script : bascule du champ date selon le type de bannissement === --}}
<script>
function toggleBanDate(permanent) {
    document.getElementById('ban-date-group').style.display = permanent ? 'none' : 'block';
    document.getElementById('permanent-input').value = permanent ? '1' : '';
}
</script>
@endsection

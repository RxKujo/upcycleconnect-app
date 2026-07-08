@extends('layouts.admin')
@section('title', 'Utilisateurs')

{{-- Vue admin : liste des utilisateurs organisée en onglets par rôle, avec recherche et
     filtre de statut appliqués côté client (JavaScript). --}}

@php
    // Regroupement par type de compte : chaque onglet a ses colonnes propres.
    $groups = [
        'particulier'   => ['label' => 'Particuliers', 'items' => []],
        'professionnel' => ['label' => 'Artisans / Pros', 'items' => []],
        'salarie'       => ['label' => 'Salariés', 'items' => []],
        'admin'         => ['label' => 'Admins', 'items' => []],
    ];
    foreach ($utilisateurs as $u) {
        $r = $u['role'];
        if (!isset($groups[$r])) { $r = 'particulier'; }
        $groups[$r]['items'][] = $u;
    }
    // Onglet actif par défaut : le premier groupe non vide (sinon particuliers).
    $defaultRole = 'particulier';
    foreach ($groups as $k => $g) { if (count($g['items'])) { $defaultRole = $k; break; } }
    $roleColors = ['particulier' => 'var(--teal)', 'professionnel' => 'var(--cherry)', 'salarie' => 'var(--forest)', 'admin' => 'var(--coffee)'];
@endphp

@section('content')
{{-- === En-tête de page === --}}
<div class="page-header">
    <h1 class="page-title">Utilisateurs</h1>
    <span class="font-mono" style="font-size:0.8rem;opacity:0.6;">{{ count($utilisateurs) }} compte(s) au total</span>
</div>

<style>
    .user-tabs { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px; }
    .user-tab {
        display:flex; align-items:center; gap:10px; cursor:pointer;
        font-family:'DM Mono',monospace; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em; font-weight:700;
        padding:14px 20px; border:var(--border); background:var(--cream); color:var(--coffee);
        box-shadow:var(--shadow-sm); transition:all 0.12s ease;
    }
    .user-tab:hover { transform:translate(-1px,-1px); }
    .user-tab .tab-count {
        min-width:26px; text-align:center; padding:2px 8px; font-size:0.82rem;
        background:rgba(18,3,9,0.1); border:2px solid var(--coffee);
    }
    .user-tab.active { background:var(--coffee); color:var(--cream); box-shadow:none; transform:translate(3px,3px); }
    .user-tab.active .tab-count { background:var(--cherry); color:var(--cream); border-color:var(--cream); }
    .user-tab .tab-dot { width:12px; height:12px; border:2px solid currentColor; display:inline-block; }
    .user-toolbar { display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
    .user-toolbar input, .user-toolbar select { margin:0; }
    .user-section { display:none; }
    .user-section.active { display:block; }
    .role-empty { text-align:center; padding:48px; font-family:'DM Mono',monospace; text-transform:uppercase; opacity:0.5; border:2px dashed rgba(18,3,9,0.25); }
    .u-hide { display:none !important; }
</style>

{{-- === Onglets par rôle === --}}
<div class="user-tabs">
    @foreach($groups as $role => $g)
    <div class="user-tab {{ $role === $defaultRole ? 'active' : '' }}" data-tab="{{ $role }}" onclick="showRole('{{ $role }}')">
        <span class="tab-dot" style="background:{{ $roleColors[$role] }};"></span>
        {{ $g['label'] }}
        <span class="tab-count">{{ count($g['items']) }}</span>
    </div>
    @endforeach
</div>

{{-- === Barre d'outils : recherche + filtre de statut === --}}
<div class="user-toolbar card" style="padding:18px 22px; margin-bottom:24px;">
    <div style="flex:1; min-width:220px;">
        <input type="text" id="user-search" class="form-input" placeholder="Rechercher (nom, email, entreprise…)" oninput="applyFilter()" style="width:100%;">
    </div>
    <select id="filter-statut" class="form-select" style="width:auto; min-width:170px;" onchange="applyFilter()">
        <option value="">Tous les statuts</option>
        <option value="actif">Actifs</option>
        <option value="banni">Bannis</option>
    </select>
</div>

{{-- === Sections/tableaux par rôle (colonnes spécifiques selon le rôle) === --}}
@foreach($groups as $role => $g)
<div class="user-section {{ $role === $defaultRole ? 'active' : '' }}" id="sec-{{ $role }}">
    @if(count($g['items']) === 0)
        <div class="role-empty">Aucun compte « {{ $g['label'] }} »</div>
    @else
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    @if($role === 'professionnel')
                        <th>Entreprise</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th style="text-align:center;">Certifié</th>
                    @elseif($role === 'salarie')
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Site (antenne)</th>
                    @elseif($role === 'particulier')
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Ville</th>
                        <th style="text-align:center;">Score</th>
                    @else
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Inscrit le</th>
                    @endif
                    <th style="text-align:center;">Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($g['items'] as $u)
                @php $search = strtolower(trim(($u['prenom'] ?? '').' '.($u['nom'] ?? '').' '.($u['email'] ?? '').' '.($u['nom_entreprise'] ?? ''))); @endphp
                <tr data-statut="{{ $u['est_banni'] ? 'banni' : 'actif' }}" data-search="{{ $search }}">
                    <td style="font-family:'DM Mono',monospace; opacity:0.7;">#{{ $u['id_utilisateur'] }}</td>

                    @if($role === 'professionnel')
                        <td style="font-weight:700;">{{ $u['nom_entreprise'] ?: '—' }}</td>
                        <td>{{ trim(($u['prenom'] ?? '').' '.($u['nom'] ?? '')) ?: '—' }}</td>
                        <td style="font-family:'DM Mono',monospace; font-size:0.9rem;">{{ $u['email'] }}</td>
                        <td>{{ $u['ville'] ?: '—' }}</td>
                        <td style="text-align:center;">
                            @if(!empty($u['est_certifie']))<span class="badge badge-valid">Oui</span>@else<span style="opacity:0.4;">—</span>@endif
                        </td>
                    @elseif($role === 'salarie')
                        <td style="font-weight:700;">{{ trim(($u['prenom'] ?? '').' '.($u['nom'] ?? '')) }}</td>
                        <td style="font-family:'DM Mono',monospace; font-size:0.9rem;">{{ $u['email'] }}</td>
                        <td>
                            @if(!empty($u['nom_site']))
                                <span class="badge badge-valid">{{ $u['nom_site'] }}</span>
                            @else
                                <span class="badge badge-waiting">Aucun site</span>
                            @endif
                        </td>
                    @elseif($role === 'particulier')
                        <td style="font-weight:700;">{{ trim(($u['prenom'] ?? '').' '.($u['nom'] ?? '')) }}</td>
                        <td style="font-family:'DM Mono',monospace; font-size:0.9rem;">{{ $u['email'] }}</td>
                        <td>{{ $u['ville'] ?: '—' }}</td>
                        <td style="text-align:center; font-family:'Bebas Neue',sans-serif; font-size:1.2rem; color:var(--forest);">{{ $u['upcycling_score'] ?? 0 }}</td>
                    @else
                        <td style="font-weight:700;">{{ trim(($u['prenom'] ?? '').' '.($u['nom'] ?? '')) }}</td>
                        <td style="font-family:'DM Mono',monospace; font-size:0.9rem;">{{ $u['email'] }}</td>
                        <td>{{ !empty($u['date_creation']) ? \Carbon\Carbon::parse($u['date_creation'])->format('d/m/Y') : '—' }}</td>
                    @endif

                    <td style="text-align:center;">
                        @if($u['est_banni'])
                            <span class="badge badge-refused">Banni</span>
                        @else
                            <span class="badge badge-valid">Actif</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-cell">
                            <a href="{{ route('admin.utilisateurs.show', $u['id_utilisateur']) }}" class="btn-secondary btn-sm">Gérer</a>
                            @if($u['est_banni'])
                            <form method="POST" action="{{ route('admin.utilisateurs.unban', $u['id_utilisateur']) }}" style="margin:0;">
                                @csrf
                                <button type="submit" class="btn-success btn-sm">Débannir</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="role-empty u-hide" data-noresult="{{ $role }}">Aucun résultat pour cette recherche.</p>
    @endif
</div>
@endforeach

{{-- === Scripts : bascule d'onglet et filtrage de la section active === --}}
<script>
let currentRole = @json($defaultRole);

function showRole(role) {
    currentRole = role;
    document.querySelectorAll('.user-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === role));
    document.querySelectorAll('.user-section').forEach(s => s.classList.toggle('active', s.id === 'sec-' + role));
    applyFilter();
}

function applyFilter() {
    const q = document.getElementById('user-search').value.toLowerCase().trim();
    const statut = document.getElementById('filter-statut').value;
    const section = document.getElementById('sec-' + currentRole);
    if (!section) return;
    let visible = 0;
    section.querySelectorAll('tbody tr').forEach(row => {
        const matchText = !q || (row.dataset.search || '').includes(q);
        const matchStatut = !statut || row.dataset.statut === statut;
        const show = matchText && matchStatut;
        row.classList.toggle('u-hide', !show);
        if (show) visible++;
    });
    const noResult = section.querySelector('[data-noresult]');
    const table = section.querySelector('.table-container');
    if (noResult && table) {
        noResult.classList.toggle('u-hide', visible !== 0);
        table.classList.toggle('u-hide', visible === 0);
    }
}
</script>
@endsection

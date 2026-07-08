@extends('layouts.admin')
@section('title', 'Sites & antennes')

{{-- Vue admin : gestion des sites / antennes physiques (CRUD via modale). Les salariés et
     leur matériel sont rattachés à un site ; supprimer un site les détache. --}}

@section('content')
{{-- === En-tête de page === --}}
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Sites & antennes</h1>
    <button type="button" class="btn-primary" onclick="openSiteModal()">+ Nouveau site</button>
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.78rem; opacity:0.6; margin-bottom:24px; max-width:720px; line-height:1.6;">
    Antennes physiques UpcycleConnect. Chaque salarié peut être rattaché à un site (fiche utilisateur → « Site »),
    et le matériel qu'il crée hérite de ce site. Un salarié voit l'inventaire de son site + le matériel sans site.
    Supprimer un site détache ses salariés et son matériel (ils redeviennent « sans site »).
</p>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif
@if(session('success'))
    <div class="badge badge-valid" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ session('error') }}</div>
@endif

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:520px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 20px; font-size:1.3rem; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
</style>

{{-- === Modale : création / édition d'un site === --}}
<div class="modal-overlay" id="siteModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeSiteModal()">&times;</button>
        <h3 id="siteModalTitle">Nouveau site</h3>
        <form method="POST" id="siteForm" action="{{ route('admin.sites.store') }}">
            @csrf
            <div id="siteMethod"></div>
            <div class="form-group">
                <label class="form-label" for="s_nom">Nom du site</label>
                <input type="text" name="nom_site" id="s_nom" class="form-input" placeholder="ex. UC Paris 11" required maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label" for="s_adresse">Adresse</label>
                <input type="text" name="adresse" id="s_adresse" class="form-input" placeholder="12 rue de la Roquette" maxlength="500">
            </div>
            <div style="display:flex; gap:16px;">
                <div class="form-group" style="flex:2;">
                    <label class="form-label" for="s_ville">Ville</label>
                    <input type="text" name="ville" id="s_ville" class="form-input" placeholder="Paris" maxlength="100">
                </div>
                <div class="form-group" style="flex:1;">
                    <label class="form-label" for="s_cp">Code postal</label>
                    <input type="text" name="code_postal" id="s_cp" class="form-input" placeholder="75011" maxlength="10">
                </div>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:8px;">Enregistrer</button>
        </form>
    </div>
</div>

{{-- === Tableau : liste des sites === --}}
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Ville</th>
                <th>Adresse</th>
                <th style="text-align:center;">Salariés</th>
                <th style="text-align:center;">Matériel</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sites as $s)
            <tr>
                <td style="font-weight:600;">{{ $s['nom_site'] }}</td>
                <td>{{ $s['ville'] ?: '—' }}@if(!empty($s['code_postal'])) <span style="opacity:0.5;">({{ $s['code_postal'] }})</span>@endif</td>
                <td style="opacity:0.75;">{{ $s['adresse'] ?: '—' }}</td>
                <td style="text-align:center;"><span class="badge badge-valid">{{ $s['nb_salaries'] ?? 0 }}</span></td>
                <td style="text-align:center;"><span class="badge badge-waiting">{{ $s['nb_materiels'] ?? 0 }}</span></td>
                <td>
                    <div class="action-cell">
                        <button type="button" class="btn-secondary btn-sm" onclick='openSiteEdit(@json($s))'>Modifier</button>
                        <form action="{{ route('admin.sites.destroy', $s['id_site']) }}" method="POST" style="margin:0;" data-confirm="Supprimer le site « {{ $s['nom_site'] }} » ? Ses {{ $s['nb_salaries'] ?? 0 }} salarié(s) et {{ $s['nb_materiels'] ?? 0 }} matériel(s) seront détachés.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; padding:24px;">Aucun site. Créez la première antenne.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- === Scripts : modale (nouveau/édition) et autocomplétion d'adresse === --}}
<script>
    const siteForm   = document.getElementById('siteForm');
    const siteMethod = document.getElementById('siteMethod');

    function openSiteModal() {
        document.getElementById('siteModalTitle').textContent = 'Nouveau site';
        siteForm.action = "{{ route('admin.sites.store') }}";
        siteMethod.innerHTML = '';
        document.getElementById('s_nom').value = '';
        document.getElementById('s_adresse').value = '';
        document.getElementById('s_ville').value = '';
        document.getElementById('s_cp').value = '';
        document.getElementById('siteModal').classList.add('active');
    }
    function openSiteEdit(s) {
        document.getElementById('siteModalTitle').textContent = 'Modifier : ' + s.nom_site;
        siteForm.action = "{{ url('admin/sites') }}/" + s.id_site;
        siteMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('s_nom').value = s.nom_site || '';
        document.getElementById('s_adresse').value = s.adresse || '';
        document.getElementById('s_ville').value = s.ville || '';
        document.getElementById('s_cp').value = s.code_postal || '';
        document.getElementById('siteModal').classList.add('active');
    }
    function closeSiteModal() { document.getElementById('siteModal').classList.remove('active'); }
    document.getElementById('siteModal').addEventListener('mousedown', (e) => { if (e.target.id === 'siteModal') closeSiteModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSiteModal(); });
    @if($errors->any()) openSiteModal(); @endif

    // Autocomplétion d'adresse (BAN) : remplit aussi ville + code postal, comme
    // partout ailleurs sur la plateforme.
    document.addEventListener('DOMContentLoaded', function () {
        window.initAddressAutocomplete(
            document.getElementById('s_adresse'),
            { city: document.getElementById('s_ville'), postcode: document.getElementById('s_cp') }
        );
    });
</script>

@include('partials.address-autocomplete')
@endsection

@extends('layouts.admin')
@section('title', "Catégories d'objets")

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Catégories d'objets</h1>
    <button type="button" class="btn-primary" onclick="openCatModal()">+ Nouvelle catégorie</button>
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.78rem; opacity:0.6; margin-bottom:24px; max-width:680px;">
    Ces catégories alimentent le menu déroulant du dépôt d'annonce (liste fermée). Une catégorie désactivée
    n'apparaît plus dans le formulaire, mais reste sur les annonces existantes.
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
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:480px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 20px; font-size:1.3rem; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .cat-row-off { opacity:0.5; }
</style>

<div class="modal-overlay" id="catModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeCatModal()">&times;</button>
        <h3 id="catModalTitle">Nouvelle catégorie</h3>
        <form method="POST" id="catForm" action="{{ route('admin.categories-objets.store') }}">
            @csrf
            <div id="catMethod"></div>
            <div class="form-group">
                <label class="form-label" for="c_nom">Nom de la catégorie</label>
                <input type="text" name="nom" id="c_nom" class="form-input" placeholder="ex. Mobilier de jardin" required maxlength="100">
            </div>
            <div class="form-group" id="c_actif_group" style="display:none;">
                <label class="form-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="actif" id="c_actif" value="1" style="width:auto;"> Active
                </label>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:8px;">Enregistrer</button>
        </form>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $c)
            <tr class="{{ $c['actif'] ? '' : 'cat-row-off' }}">
                <td style="font-weight:600;">{{ $c['nom'] }}</td>
                <td>
                    @if($c['actif'])
                        <span class="badge badge-valid">Active</span>
                    @else
                        <span class="badge badge-waiting">Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="action-cell">
                        <button type="button" class="btn-secondary btn-sm" onclick='openCatEdit(@json($c))'>Modifier</button>
                        <form action="{{ route('admin.categories-objets.destroy', $c['id_categorie_objet']) }}" method="POST" style="margin:0;" data-confirm="Supprimer la catégorie « {{ $c['nom'] }} » ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center; padding:24px;">Aucune catégorie.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    const catForm    = document.getElementById('catForm');
    const catMethod  = document.getElementById('catMethod');
    const catActifGr = document.getElementById('c_actif_group');

    function openCatModal() {
        document.getElementById('catModalTitle').textContent = 'Nouvelle catégorie';
        catForm.action = "{{ route('admin.categories-objets.store') }}";
        catMethod.innerHTML = '';
        document.getElementById('c_nom').value = '';
        catActifGr.style.display = 'none';
        document.getElementById('catModal').classList.add('active');
    }
    function openCatEdit(c) {
        document.getElementById('catModalTitle').textContent = 'Modifier : ' + c.nom;
        catForm.action = "{{ url('admin/categories-objets') }}/" + c.id_categorie_objet;
        catMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('c_nom').value = c.nom;
        catActifGr.style.display = 'block';
        document.getElementById('c_actif').checked = !!c.actif;
        document.getElementById('catModal').classList.add('active');
    }
    function closeCatModal() { document.getElementById('catModal').classList.remove('active'); }
    document.getElementById('catModal').addEventListener('mousedown', (e) => { if (e.target.id === 'catModal') closeCatModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCatModal(); });
    @if($errors->any()) openCatModal(); @endif
</script>
@endsection

@extends('layouts.admin')
@section('title', 'Catégories de prestations')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Catégories de prestations</h1>
    <button type="button" class="btn-primary" onclick="openCatModal()">+ Nouvelle catégorie</button>
</div>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif

<p style="font-family:'DM Mono',monospace; font-size:0.8rem; opacity:0.6; margin:-24px 0 28px;">
    Ces catégories classent les <strong>prestations</strong> proposées par les professionnels (services, ateliers, réparations…).
</p>

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:520px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 20px; font-size:1.3rem; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-box .form-label { font-size:0.72rem; margin-bottom:5px; }
    .modal-box .form-input, .modal-box .form-textarea { font-size:0.95rem; padding:9px 12px; box-shadow:2px 2px 0px rgba(18,3,9,0.1); }
    .modal-box .form-textarea { min-height:90px; }
</style>

<div class="modal-overlay" id="catModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeCatModal()">&times;</button>
        <h3 id="catModalTitle">Nouvelle catégorie</h3>
        <form method="POST" id="catForm" action="{{ route('admin.categories.store') }}">
            @csrf
            <div id="catMethod"></div>
            <div class="form-group">
                <label class="form-label" for="c_nom">Nom</label>
                <input type="text" name="nom" id="c_nom" class="form-input" placeholder="ex. Menuiserie & ébénisterie" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="c_desc">Description (optionnel)</label>
                <textarea name="description" id="c_desc" class="form-input form-textarea" placeholder="À quoi correspond cette catégorie de prestations ?"></textarea>
            </div>
            <button type="submit" class="btn-primary" style="margin-top:4px;">Enregistrer</button>
        </form>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $cat)
            <tr>
                <td>{{ $cat['id_categorie'] }}</td>
                <td style="font-weight:600;">{{ $cat['nom'] }}</td>
                <td>{{ $cat['description'] ?: '—' }}</td>
                <td>
                    <div class="action-cell">
                        <button type="button" class="btn-secondary btn-sm" onclick='openCatEdit(@json($cat))'>Modifier</button>
                        <form action="{{ route('admin.categories.destroy', $cat['id_categorie']) }}" method="POST" style="margin:0;" data-confirm="Supprimer cette catégorie ?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; padding:24px;">Aucune catégorie.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    const catForm   = document.getElementById('catForm');
    const catMethod = document.getElementById('catMethod');

    function openCatModal() {
        document.getElementById('catModalTitle').textContent = 'Nouvelle catégorie';
        catForm.action = "{{ route('admin.categories.store') }}";
        catMethod.innerHTML = '';
        document.getElementById('c_nom').value = '';
        document.getElementById('c_desc').value = '';
        document.getElementById('catModal').classList.add('active');
    }
    function openCatEdit(cat) {
        document.getElementById('catModalTitle').textContent = 'Modifier : ' + cat.nom;
        catForm.action = "{{ url('admin/categories') }}/" + cat.id_categorie;
        catMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('c_nom').value = cat.nom;
        document.getElementById('c_desc').value = cat.description || '';
        document.getElementById('catModal').classList.add('active');
    }
    function closeCatModal() { document.getElementById('catModal').classList.remove('active'); }
    document.getElementById('catModal').addEventListener('mousedown', (e) => { if (e.target.id === 'catModal') closeCatModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeCatModal(); });
    @if($errors->any()) openCatModal(); @endif
</script>
@endsection

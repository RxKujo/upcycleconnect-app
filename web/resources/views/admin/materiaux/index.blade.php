@extends('layouts.admin')
@section('title', 'Matériaux')

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Matériaux</h1>
    <button type="button" class="btn-primary" onclick="openMatModal()">+ Nouveau matériau</button>
</div>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:520px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 20px; font-size:1.3rem; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-box .form-label { font-size:0.72rem; margin-bottom:5px; }
    .modal-box .form-input { font-size:0.95rem; padding:9px 12px; box-shadow:2px 2px 0px rgba(18,3,9,0.1); }
    .modal-box .form-input:focus { box-shadow:3px 3px 0px rgba(164,36,59,0.25); }
    .mat-icon { width:36px; height:36px; object-fit:contain; border:2px solid var(--coffee); background:white; display:block; }
    .mat-row-off { opacity:0.5; }
</style>

<div class="modal-overlay" id="matModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeMatModal()">&times;</button>
        <h3 id="matModalTitle">Nouveau matériau</h3>
        <form method="POST" id="matForm" action="{{ route('admin.materiaux.store') }}">
            @csrf
            <div id="matMethod"></div>

            <div class="form-group">
                <label class="form-label" for="m_code">Code interne (minuscules, sans espaces)</label>
                <input type="text" name="code" id="m_code" class="form-input" placeholder="ex. aluminium" pattern="[a-z0-9_]+" required>
                <small id="m_code_hint" style="display:none; color:#888; font-family:'DM Mono',monospace; font-size:0.7rem;">Le code est immuable (référencé par les annonces/alertes).</small>
            </div>
            <div class="form-group">
                <label class="form-label" for="m_libelle">Libellé affiché</label>
                <input type="text" name="libelle" id="m_libelle" class="form-input" placeholder="ex. Aluminium" required>
            </div>
            <div class="form-group" id="m_actif_group" style="display:none;">
                <label class="form-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="actif" id="m_actif" value="1" style="width:auto;"> Actif
                </label>
            </div>
            <div class="form-group">
                <label class="form-label" for="m_iconeInput">Icône (jpg, png, webp, svg — max 2 Mo)</label>
                <input type="file" id="m_iconeInput" class="form-input" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                <input type="hidden" name="icone_base64" id="m_iconeBase64">
                <img id="m_iconePreview" alt="Aperçu" style="display:none; margin-top:10px; width:60px; height:60px; object-fit:contain; border:2px solid var(--coffee); background:white;">
            </div>

            <button type="submit" class="btn-primary" style="margin-top:8px;">Enregistrer</button>
        </form>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Icône</th>
                <th>Code</th>
                <th>Libellé</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($materiaux as $m)
            <tr class="{{ $m['actif'] ? '' : 'mat-row-off' }}">
                <td>
                    @if(!empty($m['icone']))
                        <img src="{{ media_url($m['icone']) }}" alt="{{ $m['libelle'] }}" class="mat-icon">
                    @else
                        <span style="color:#bbb;">—</span>
                    @endif
                </td>
                <td><code style="font-family:'DM Mono',monospace;">{{ $m['code'] }}</code></td>
                <td>{{ $m['libelle'] }}</td>
                <td>
                    @if($m['actif'])
                        <span class="badge badge-valid">Actif</span>
                    @else
                        <span class="badge badge-waiting">Inactif</span>
                    @endif
                </td>
                <td>
                    <div class="action-cell">
                        <button type="button" class="btn-secondary btn-sm"
                            onclick='openMatEdit(@json($m))'>Modifier</button>
                        <form action="{{ route('admin.materiaux.toggle', $m['id_materiau']) }}" method="POST" style="margin:0;">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="{{ $m['actif'] ? 'btn-danger' : 'btn-success' }} btn-sm">
                                {{ $m['actif'] ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; padding:24px;">Aucun matériau.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    const matForm   = document.getElementById('matForm');
    const matMethod = document.getElementById('matMethod');
    const codeInput = document.getElementById('m_code');
    const codeHint  = document.getElementById('m_code_hint');
    const actifGroup= document.getElementById('m_actif_group');

    function openMatModal() {
        document.getElementById('matModalTitle').textContent = 'Nouveau matériau';
        matForm.action = "{{ route('admin.materiaux.store') }}";
        matMethod.innerHTML = '';
        codeInput.value = ''; codeInput.readOnly = false; codeHint.style.display = 'none';
        document.getElementById('m_libelle').value = '';
        document.getElementById('m_iconeBase64').value = '';
        document.getElementById('m_iconePreview').style.display = 'none';
        actifGroup.style.display = 'none';
        document.getElementById('matModal').classList.add('active');
    }
    function openMatEdit(m) {
        document.getElementById('matModalTitle').textContent = 'Modifier : ' + m.libelle;
        matForm.action = "{{ url('admin/materiaux') }}/" + m.id_materiau;
        matMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        codeInput.value = m.code; codeInput.readOnly = true; codeHint.style.display = 'block';
        document.getElementById('m_libelle').value = m.libelle;
        document.getElementById('m_iconeBase64').value = '';
        const prev = document.getElementById('m_iconePreview');
        if (m.icone) { prev.src = window.MEDIA_BASE + '/' + m.icone; prev.style.display = 'block'; }
        else { prev.style.display = 'none'; }
        actifGroup.style.display = 'block';
        document.getElementById('m_actif').checked = !!m.actif;
        document.getElementById('matModal').classList.add('active');
    }
    function closeMatModal() { document.getElementById('matModal').classList.remove('active'); }
    document.getElementById('matModal').addEventListener('mousedown', (e) => { if (e.target.id === 'matModal') closeMatModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMatModal(); });
    @if($errors->any()) openMatModal(); @endif

    // Icône → base64 + aperçu
    const iconeInput = document.getElementById('m_iconeInput');
    iconeInput.addEventListener('change', () => {
        const file = iconeInput.files[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) { alert('Icône trop lourde (max 2 Mo).'); iconeInput.value = ''; return; }
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('m_iconeBase64').value = e.target.result;
            const prev = document.getElementById('m_iconePreview');
            prev.src = e.target.result; prev.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
</script>
@endsection

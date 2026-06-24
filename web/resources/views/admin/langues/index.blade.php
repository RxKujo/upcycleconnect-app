@extends('layouts.admin')

@section('title', 'Langues & Traductions')

@section('content')
<div class="page-header">
    <h1 class="page-title">Langues & Traductions</h1>
    <button class="btn-primary" onclick="document.getElementById('modal-langue').style.display='flex'">
        + Ajouter une langue
    </button>
</div>

{{-- Section langues --}}
<div class="card" style="margin-bottom:40px;">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Langues configurées</h2>
    @if(empty($langues))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Aucune langue configurée.</p>
    @else
        <div class="table-container" style="box-shadow:none;border:none;">
            <table style="min-width:600px;">
                <thead>
                    <tr>
                        <th>Code ISO</th>
                        <th>Libellé</th>
                        <th>RTL</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($langues as $langue)
                    <tr>
                        <td><span class="badge" style="background:var(--teal);color:var(--cream);">{{ $langue['code_iso'] ?? '—' }}</span></td>
                        <td>{{ $langue['libelle'] ?? '—' }}</td>
                        <td>
                            @if(!empty($langue['rtl']))
                                <span class="badge badge-waiting">RTL</span>
                            @else
                                <span style="opacity:0.3;font-size:0.85rem;">LTR</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($langue['est_active']))
                                <span class="badge badge-valid">Active</span>
                            @else
                                <span class="badge badge-refused">Inactive</span>
                            @endif
                        </td>
                        <td class="action-cell">
                            <button class="btn-secondary btn-sm"
                                onclick="openEditLangue({{ $langue['id_langue'] ?? 0 }}, '{{ addslashes($langue['libelle'] ?? '') }}', {{ !empty($langue['est_active']) ? 'true' : 'false' }}, {{ !empty($langue['rtl']) ? 'true' : 'false' }})">
                                Modifier
                            </button>
                            <form action="{{ route('admin.langues.destroy', $langue['id_langue'] ?? 0) }}" method="POST"
                                  data-confirm="Supprimer cette langue ? Toutes ses traductions seront perdues.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Section traductions --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 24px;padding-bottom:20px;border-bottom:3px solid var(--coffee);">
    <h2 class="font-bebas" style="font-size:2rem;color:var(--coffee);margin:0;letter-spacing:0.05em;line-height:1;">Traductions UI</h2>
    <button class="btn-primary" onclick="document.getElementById('modal-trad').style.display='flex'">
        + Ajouter / modifier une traduction
    </button>
</div>

<div class="card">
    @if(empty($translations))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Aucune traduction enregistrée.</p>
    @else
        {{-- Filtre par clé --}}
        <div style="display:flex;gap:16px;margin-bottom:24px;align-items:center;">
            <input type="text" id="filtre-cle" class="form-input" style="max-width:340px;"
                   placeholder="Filtrer par clé…" oninput="filtrerTrads(this.value)">
            <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">
                {{ count($translations) }} traduction(s)
            </span>
        </div>
        <div class="table-container" style="box-shadow:none;border:none;">
            <table id="trad-table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th>Clé</th>
                        <th>Langue</th>
                        <th>Valeur</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($translations as $trad)
                    <tr data-cle="{{ strtolower($trad['cle'] ?? '') }}">
                        <td><code style="font-family:'DM Mono',monospace;font-size:0.85rem;background:rgba(18,3,9,0.06);padding:2px 6px;">{{ $trad['cle'] ?? '—' }}</code></td>
                        <td><span class="badge" style="background:var(--teal);color:var(--cream);">{{ $trad['code_iso'] ?? $trad['id_langue'] ?? '—' }}</span></td>
                        <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $trad['valeur'] ?? '—' }}</td>
                        <td>
                            <form action="{{ route('admin.langues.translations.destroy', $trad['id_translation'] ?? 0) }}" method="POST"
                                  data-confirm="Supprimer cette traduction ?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Modal ajout langue --}}
<div id="modal-langue" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:480px;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Ajouter une langue</h2>
        <form action="{{ route('admin.langues.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Code ISO <span style="color:var(--cherry)">*</span> <span style="opacity:0.5;font-size:0.8rem;">(ex: fr, en, ar)</span></label>
                <input type="text" name="code_iso" class="form-input" required minlength="2" maxlength="5" placeholder="fr">
            </div>
            <div class="form-group">
                <label class="form-label">Libellé <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="libelle" class="form-input" required maxlength="100" placeholder="Français">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                <label class="form-label" style="margin:0;">Écriture droite → gauche (RTL)</label>
                <input type="checkbox" name="rtl" value="1" style="width:20px;height:20px;cursor:pointer;">
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn-primary">Ajouter</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-langue').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal édition langue --}}
<div id="modal-edit-langue" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:480px;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Modifier la langue</h2>
        <form id="form-edit-langue" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Libellé</label>
                <input type="text" id="edit-libelle" name="libelle" class="form-input" maxlength="100">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                <label class="form-label" style="margin:0;">Active</label>
                <input type="checkbox" id="edit-active" name="est_active" value="1" style="width:20px;height:20px;cursor:pointer;">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                <label class="form-label" style="margin:0;">RTL</label>
                <input type="checkbox" id="edit-rtl" name="rtl" value="1" style="width:20px;height:20px;cursor:pointer;">
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-edit-langue').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal ajout traduction --}}
<div id="modal-trad" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:520px;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Ajouter / modifier une traduction</h2>
        <form action="{{ route('admin.langues.translations.upsert') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Clé de traduction <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="cle" class="form-input" required maxlength="200" placeholder="Ex: nav.accueil">
            </div>
            <div class="form-group">
                <label class="form-label">Langue <span style="color:var(--cherry)">*</span></label>
                <select name="id_langue" class="form-select" required>
                    @foreach($langues as $langue)
                        <option value="{{ $langue['id_langue'] ?? 0 }}">{{ $langue['libelle'] ?? '' }} ({{ $langue['code_iso'] ?? '' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Valeur <span style="color:var(--cherry)">*</span></label>
                <textarea name="valeur" class="form-textarea" required style="min-height:80px;" placeholder="Traduction dans cette langue…"></textarea>
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary">Enregistrer</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-trad').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditLangue(id, libelle, active, rtl) {
    document.getElementById('form-edit-langue').action = '/admin/langues/' + id;
    document.getElementById('edit-libelle').value = libelle;
    document.getElementById('edit-active').checked = active;
    document.getElementById('edit-rtl').checked = rtl;
    document.getElementById('modal-edit-langue').style.display = 'flex';
}
function filtrerTrads(val) {
    const rows = document.querySelectorAll('#trad-table tbody tr');
    rows.forEach(tr => {
        tr.style.display = tr.dataset.cle.includes(val.toLowerCase()) ? '' : 'none';
    });
}
</script>
@endpush

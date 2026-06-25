@extends('layouts.admin')

@section('title', 'Langues & Traductions')

@section('content')
<style>
.cell-empty { background: #fbeaec; border-color: var(--cherry) !important; }
#trad-table td, #trad-table th { vertical-align: middle; }
#trad-table input.form-input { margin: 0; }
</style>
<div class="page-header">
    <h1 class="page-title">Langues & Traductions</h1>
    <button class="btn-primary" onclick="document.getElementById('modal-langue').style.display='flex'">
        + Ajouter une langue
    </button>
</div>

{{-- ─── Section langues ─────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:40px;">
    <h2 class="font-bebas" style="font-size:1.8rem;margin:0 0 24px;">Langues configurées</h2>
    @if(empty($langues))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Aucune langue configurée.</p>
    @else
        <div class="table-container" style="box-shadow:none;border:none;">
            <table style="min-width:600px;">
                <thead>
                    <tr><th>Code ISO</th><th>Libellé</th><th>RTL</th><th>Statut</th><th>Actions</th></tr>
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
                                  data-confirm="Supprimer la langue « {{ $langue['libelle'] ?? '' }} » ET toutes ses traductions ? Action irréversible.">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="margin:18px 0 0;font-size:0.82rem;opacity:0.55;line-height:1.5;">
            Désactiver une langue la masque du site public sans perdre ses traductions.
            La suppression, elle, efface définitivement la langue <em>et</em> toutes ses traductions.
        </p>
    @endif
</div>

{{-- ─── Section traductions : grille clés × langues ─────────────────── --}}
@php
    // Construction de la matrice : 1 ligne = 1 clé, 1 colonne = 1 langue.
    $cles = collect($translations)->pluck('cle')->unique()->sort()->values();
    $matrix = [];
    foreach ($translations as $t) {
        $matrix[$t['cle']][$t['id_langue']] = $t['valeur'] ?? '';
    }
    $nbNewRows = 3; // lignes vierges pour ajouter de nouvelles clés
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin:0 0 8px;padding-bottom:20px;border-bottom:3px solid var(--coffee);">
    <h2 class="font-bebas" style="font-size:2rem;color:var(--coffee);margin:0;letter-spacing:0.05em;line-height:1;">Traductions de l'interface</h2>
    <div style="display:flex;gap:12px;align-items:center;">
        <span id="missing-count" style="font-family:'DM Mono',monospace;font-size:0.78rem;text-transform:uppercase;"></span>
        <button type="submit" form="grid-form" class="btn-primary">Enregistrer tout</button>
    </div>
</div>
<p style="margin:0 0 24px;font-size:0.85rem;opacity:0.6;line-height:1.5;">
    Chaque ligne est une clé d'interface (définie dans le code). Remplissez la valeur dans chaque langue.
    Les cases <span style="background:#fbeaec;border:1px solid var(--cherry);padding:1px 8px;">vides</span> ne sont pas encore traduites.
    Videz une case pour effacer cette traduction.
</p>

<div class="card">
    @if($cles->isEmpty() && empty($langues))
        <p style="opacity:0.4;font-family:'DM Mono',monospace;text-transform:uppercase;font-size:0.9rem;">Ajoutez d'abord une langue.</p>
    @else
    <div style="display:flex;gap:16px;margin-bottom:24px;align-items:center;flex-wrap:wrap;">
        <input type="text" id="filtre-cle" class="form-input" style="max-width:340px;"
               placeholder="Filtrer par clé…" oninput="filtrerTrads(this.value)">
        <span style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;opacity:0.5;">
            {{ $cles->count() }} clé(s) · {{ count($langues) }} langue(s)
        </span>
    </div>

    <form id="grid-form" action="{{ route('admin.langues.translations.bulk') }}" method="POST">
        @csrf
        <div class="table-container" style="box-shadow:none;border:none;">
            <table id="trad-table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th style="position:sticky;left:0;background:var(--wheat);">Clé</th>
                        @foreach($langues as $langue)
                        <th>
                            <span class="badge" style="background:var(--teal);color:var(--cream);">{{ $langue['code_iso'] ?? '?' }}</span>
                            @if(!empty($langue['rtl']))<span style="font-size:0.6rem;opacity:0.6;">RTL</span>@endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($cles as $i => $cle)
                    <tr data-cle="{{ strtolower($cle) }}">
                        <td style="position:sticky;left:0;background:var(--cream);">
                            <code style="font-family:'DM Mono',monospace;font-size:0.82rem;background:rgba(18,3,9,0.06);padding:3px 7px;white-space:nowrap;">{{ $cle }}</code>
                            <input type="hidden" name="cle[{{ $i }}]" value="{{ $cle }}">
                        </td>
                        @foreach($langues as $langue)
                        @php $val = $matrix[$cle][$langue['id_langue']] ?? ''; @endphp
                        <td>
                            <input type="text" class="form-input cell {{ $val === '' ? 'cell-empty' : '' }}"
                                   name="val[{{ $i }}][{{ $langue['id_langue'] }}]"
                                   value="{{ $val }}"
                                   @if(!empty($langue['rtl'])) dir="rtl" @endif
                                   style="min-width:160px;padding:8px 10px;font-size:0.9rem;"
                                   oninput="markCell(this)">
                        </td>
                        @endforeach
                    </tr>
                    @endforeach

                    {{-- Lignes vierges : ajouter de nouvelles clés --}}
                    @for($n = 0; $n < $nbNewRows; $n++)
                    @php $ri = $cles->count() + $n; @endphp
                    <tr data-cle="" class="new-row">
                        <td style="position:sticky;left:0;background:var(--cream);">
                            <input type="text" name="cle[{{ $ri }}]" class="form-input"
                                   placeholder="nouvelle.cle"
                                   style="min-width:170px;padding:8px 10px;font-size:0.85rem;font-family:'DM Mono',monospace;">
                        </td>
                        @foreach($langues as $langue)
                        <td>
                            <input type="text" class="form-input"
                                   name="val[{{ $ri }}][{{ $langue['id_langue'] }}]"
                                   @if(!empty($langue['rtl'])) dir="rtl" @endif
                                   style="min-width:160px;padding:8px 10px;font-size:0.9rem;"
                                   placeholder="—">
                        </td>
                        @endforeach
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div style="margin-top:24px;display:flex;gap:12px;align-items:center;">
            <button type="submit" class="btn-primary">Enregistrer tout</button>
            <span style="font-size:0.8rem;opacity:0.5;">Les 3 dernières lignes permettent d'ajouter de nouvelles clés.</span>
        </div>
    </form>
    @endif
</div>

{{-- ─── Modal ajout langue ──────────────────────────────────────────── --}}
<div id="modal-langue" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:480px;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;">Ajouter une langue</h2>
        <form action="{{ route('admin.langues.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Code ISO <span style="color:var(--cherry)">*</span> <span style="opacity:0.5;font-size:0.8rem;">(ex: fr, en, ar)</span></label>
                <input type="text" name="code_iso" class="form-input" required minlength="2" maxlength="5" placeholder="es">
            </div>
            <div class="form-group">
                <label class="form-label">Libellé <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="libelle" class="form-input" required maxlength="100" placeholder="Español">
            </div>
            <div class="form-group" style="display:flex;align-items:center;gap:16px;">
                <label class="form-label" style="margin:0;">Écriture droite → gauche (RTL)</label>
                <input type="checkbox" name="rtl" value="1" style="width:20px;height:20px;cursor:pointer;">
            </div>
            <p style="font-size:0.8rem;opacity:0.55;margin:0 0 16px;">Toutes les clés existantes apparaîtront automatiquement avec des cases vides à remplir.</p>
            <div style="display:flex;gap:12px;margin-top:8px;">
                <button type="submit" class="btn-primary">Ajouter</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-langue').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Modal édition langue ────────────────────────────────────────── --}}
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
    val = val.toLowerCase();
    document.querySelectorAll('#trad-table tbody tr').forEach(tr => {
        if (tr.classList.contains('new-row')) return; // garder les lignes d'ajout visibles
        tr.style.display = tr.dataset.cle.includes(val) ? '' : 'none';
    });
}
function markCell(input) {
    input.classList.toggle('cell-empty', input.value.trim() === '');
    updateMissing();
}
function updateMissing() {
    const empties = document.querySelectorAll('#trad-table .cell.cell-empty').length;
    const el = document.getElementById('missing-count');
    if (!el) return;
    el.textContent = empties > 0 ? (empties + ' à traduire') : 'Tout est traduit';
    el.style.color = empties > 0 ? 'var(--cherry)' : 'var(--forest)';
}
// Confirmation des suppressions (le data-confirm n'était pas câblé auparavant)
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', e => {
        if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    });
});
updateMissing();
</script>
@endpush

@extends('layouts.admin')
@section('title', "Modèles d'événements")

@section('content')
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title">Modèles d'événements</h1>
    <button type="button" class="btn-primary" onclick="openTplModal()">+ Nouveau modèle</button>
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.8rem; color:#666; margin-bottom:24px; max-width:760px; line-height:1.6;">
    Les modèles pré-remplissent le formulaire de création d'événement côté salarié : il choisit un modèle et les champs (type, format, description, places, prix…) se remplissent automatiquement. Désactivez un modèle pour le retirer de la liste sans le supprimer.
</p>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif

<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:600px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 4px; font-size:1.3rem; }
    .modal-box .modal-sub { font-family:'DM Mono',monospace; font-size:0.7rem; color:#888; text-transform:uppercase; margin:0 0 20px; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-box .form-label { font-size:0.72rem; margin-bottom:5px; }
    .modal-box .form-input, .modal-box .form-select, .modal-box .form-textarea { font-size:0.95rem; padding:9px 12px; box-shadow:2px 2px 0px rgba(18,3,9,0.1); }
    .modal-box .form-input:focus { box-shadow:3px 3px 0px rgba(164,36,59,0.25); }
    .modal-section { border-top:2px dashed rgba(18,3,9,0.15); margin:22px 0 16px; padding-top:16px; }
    .modal-section-title { font-family:'DM Mono',monospace; font-size:0.7rem; text-transform:uppercase; color:var(--cherry); font-weight:700; margin-bottom:14px; letter-spacing:0.05em; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .tpl-row-off { opacity:0.5; }
    .tpl-tag { display:inline-block; font-family:'DM Mono',monospace; font-size:0.68rem; padding:2px 8px; border:2px solid var(--coffee); margin-right:6px; }
</style>

<div class="modal-overlay" id="tplModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeTplModal()">&times;</button>
        <h3 id="tplModalTitle">Nouveau modèle</h3>
        <p class="modal-sub">Modèle réutilisable pour créer des événements</p>
        <form method="POST" id="tplForm" action="{{ route('admin.templates.store') }}">
            @csrf
            <div id="tplMethod"></div>

            <div class="form-group">
                <label class="form-label" for="t_nom">Nom du modèle (interne)</label>
                <input type="text" name="nom_template" id="t_nom" class="form-input" placeholder="ex. Atelier réparation" required maxlength="150">
            </div>
            <div class="form-group">
                <label class="form-label" for="t_desc">Description du modèle (aide-mémoire)</label>
                <input type="text" name="description" id="t_desc" class="form-input" placeholder="ex. Atelier pratique, présentiel, gratuit" maxlength="255">
            </div>
            <div class="form-group" id="t_actif_group" style="display:none;">
                <label class="form-label" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="actif" id="t_actif" value="1" style="width:auto;"> Actif
                </label>
            </div>

            <div class="modal-section">
                <div class="modal-section-title">Valeurs pré-remplies dans le formulaire</div>
                <div class="form-group">
                    <label class="form-label" for="t_titre">Titre proposé</label>
                    <input type="text" name="m_titre" id="t_titre" class="form-input" maxlength="200">
                </div>
                <div class="form-group">
                    <label class="form-label" for="t_mdesc">Description proposée</label>
                    <textarea name="m_description" id="t_mdesc" class="form-textarea" rows="4"></textarea>
                </div>
                <div class="grid2">
                    <div class="form-group">
                        <label class="form-label" for="t_type">Type</label>
                        <select name="type_evenement" id="t_type" class="form-select" required>
                            <option value="formation">Formation</option>
                            <option value="atelier">Atelier</option>
                            <option value="conference">Conférence</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="t_format">Format</label>
                        <select name="format" id="t_format" class="form-select" required>
                            <option value="presentiel">Présentiel</option>
                            <option value="distanciel">Distanciel</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="t_lieu">Lieu (vide si distanciel)</label>
                    <input type="text" name="m_lieu" id="t_lieu" class="form-input" maxlength="300">
                </div>
                <div class="grid2">
                    <div class="form-group">
                        <label class="form-label" for="t_places">Nombre de places</label>
                        <input type="number" name="nb_places_total" id="t_places" class="form-input" min="1" value="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="t_prix">Prix (€)</label>
                        <input type="number" name="prix" id="t_prix" class="form-input" min="0" step="0.01" value="0" required>
                    </div>
                </div>
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
                <th>Type / Format</th>
                <th>Places · Prix</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templates as $t)
            @php $m = $t['modele'] ?? []; if (!is_array($m)) $m = []; @endphp
            <tr class="{{ $t['actif'] ? '' : 'tpl-row-off' }}">
                <td>
                    <strong>{{ $t['nom_template'] }}</strong>
                    @if(!empty($t['description']))
                        <div style="font-size:0.8rem; color:#888;">{{ $t['description'] }}</div>
                    @endif
                </td>
                <td>
                    <span class="tpl-tag">{{ ucfirst($m['type_evenement'] ?? '—') }}</span>
                    <span class="tpl-tag">{{ ucfirst($m['format'] ?? '—') }}</span>
                </td>
                <td>
                    {{ $m['nb_places_total'] ?? '—' }} places ·
                    {{ isset($m['prix']) && $m['prix'] > 0 ? number_format($m['prix'], 2) . ' €' : 'Gratuit' }}
                </td>
                <td>
                    @if($t['actif'])
                        <span class="badge badge-valid">Actif</span>
                    @else
                        <span class="badge badge-waiting">Inactif</span>
                    @endif
                </td>
                <td>
                    <div class="action-cell">
                        <button type="button" class="btn-secondary btn-sm" onclick='openTplEdit(@json($t))'>Modifier</button>
                        <form action="{{ route('admin.templates.toggle', $t['id_template']) }}" method="POST" style="margin:0;">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="{{ $t['actif'] ? 'btn-secondary' : 'btn-success' }} btn-sm">
                                {{ $t['actif'] ? 'Désactiver' : 'Activer' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.templates.destroy', $t['id_template']) }}" method="POST" style="margin:0;"
                              data-confirm="Supprimer définitivement le modèle « {{ $t['nom_template'] }} » ? Cette action est irréversible.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; padding:24px;">Aucun modèle.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
    const tplForm    = document.getElementById('tplForm');
    const tplMethod  = document.getElementById('tplMethod');
    const tplActifGr = document.getElementById('t_actif_group');

    function setVal(id, v) { document.getElementById(id).value = (v === undefined || v === null) ? '' : v; }

    function openTplModal() {
        document.getElementById('tplModalTitle').textContent = 'Nouveau modèle';
        tplForm.action = "{{ route('admin.templates.store') }}";
        tplMethod.innerHTML = '';
        setVal('t_nom', ''); setVal('t_desc', '');
        setVal('t_titre', ''); setVal('t_mdesc', ''); setVal('t_lieu', '');
        document.getElementById('t_type').value = 'atelier';
        document.getElementById('t_format').value = 'presentiel';
        setVal('t_places', 10); setVal('t_prix', 0);
        tplActifGr.style.display = 'none';
        document.getElementById('tplModal').classList.add('active');
    }
    function openTplEdit(t) {
        const m = t.modele || {};
        document.getElementById('tplModalTitle').textContent = 'Modifier : ' + t.nom_template;
        tplForm.action = "{{ url('admin/templates') }}/" + t.id_template;
        tplMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        setVal('t_nom', t.nom_template); setVal('t_desc', t.description);
        setVal('t_titre', m.titre); setVal('t_mdesc', m.description); setVal('t_lieu', m.lieu);
        document.getElementById('t_type').value = m.type_evenement || 'atelier';
        document.getElementById('t_format').value = m.format || 'presentiel';
        setVal('t_places', m.nb_places_total || 10);
        setVal('t_prix', (m.prix !== undefined ? m.prix : 0));
        tplActifGr.style.display = 'block';
        document.getElementById('t_actif').checked = !!t.actif;
        document.getElementById('tplModal').classList.add('active');
    }
    function closeTplModal() { document.getElementById('tplModal').classList.remove('active'); }
    document.getElementById('tplModal').addEventListener('mousedown', (e) => { if (e.target.id === 'tplModal') closeTplModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTplModal(); });
    @if($errors->any()) openTplModal(); @endif
</script>
@endsection

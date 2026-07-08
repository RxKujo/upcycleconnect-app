@extends('layouts.salarie')
@section('title', 'Mes modèles')
@section('content')
{{-- === En-tête + bouton nouveau modèle === --}}
<div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
    <h1 class="page-title"><span data-i18n="sal.tpl.title">Modèles d'événements</span></h1>
    <div style="display:flex; gap:12px;">
        <a href="{{ route('salarie.evenements.index') }}" class="btn-secondary">← Mes événements</a>
        <button type="button" class="btn-primary" onclick="openTplModal()"><span data-i18n="sal.tpl.new">+ Nouveau modèle</span></button>
    </div>
</div>

<p style="font-family:'DM Mono',monospace; font-size:0.8rem; color:#666; margin-bottom:24px; max-width:760px; line-height:1.6;">
    Les modèles pré-remplissent le formulaire de création d'événement : choisissez un modèle et les champs (type, format, description, places, prix…) se remplissent automatiquement.
</p>

@if($errors->any())
    <div class="badge badge-cherry" style="display:block; margin-bottom:20px; padding:12px 20px;">{{ $errors->first() }}</div>
@endif

{{-- === Styles : modale de création/édition de modèle === --}}
<style>
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(18,3,9,0.6); z-index:1000; align-items:flex-start; justify-content:center; overflow-y:auto; padding:40px 20px; }
    .modal-overlay.active { display:flex; }
    .modal-box { background:var(--cream); border:var(--border); box-shadow:var(--shadow); width:100%; max-width:600px; padding:28px 32px; position:relative; }
    .modal-box h3 { margin:0 0 4px; font-size:1.3rem; }
    .modal-box .modal-sub { font-family:'DM Mono',monospace; font-size:0.7rem; color:#888; text-transform:uppercase; margin:0 0 20px; }
    .modal-close { position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; cursor:pointer; color:var(--coffee); line-height:1; }
    .modal-box .form-group { margin-bottom:16px; }
    .modal-section { border-top:2px dashed rgba(18,3,9,0.15); margin:22px 0 16px; padding-top:16px; }
    .modal-section-title { font-family:'DM Mono',monospace; font-size:0.7rem; text-transform:uppercase; color:var(--forest); font-weight:700; margin-bottom:14px; letter-spacing:0.05em; }
    .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .tpl-tag { display:inline-block; font-family:'DM Mono',monospace; font-size:0.68rem; padding:2px 8px; border:2px solid var(--coffee); margin-right:6px; }
</style>

{{-- === Modale : création / édition d'un modèle (formulaire partagé, action ajustée en JS) === --}}
<div class="modal-overlay" id="tplModal">
    <div class="modal-box">
        <button type="button" class="modal-close" onclick="closeTplModal()">&times;</button>
        <h3 id="tplModalTitle"><span data-i18n="sal.tpl.new2">Nouveau modèle</span></h3>
        <p class="modal-sub">Modèle réutilisable pour créer des événements</p>
        <form method="POST" id="tplForm" action="{{ route('salarie.templates.store') }}">
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

            <button type="submit" class="btn-primary" style="margin-top:8px;"><span data-i18n="btn.save">Enregistrer</span></button>
        </form>
    </div>
</div>

{{-- === Tableau des modèles existants === --}}
<div class="card" style="padding:0; overflow-x:auto;">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="text-align:left; border-bottom:3px solid var(--coffee);">
                <th style="padding:14px 18px;">Nom</th>
                <th style="padding:14px 18px;">Type / Format</th>
                <th style="padding:14px 18px;">Places · Prix</th>
                <th style="padding:14px 18px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templates as $t)
            @php $m = $t['modele'] ?? []; if (!is_array($m)) $m = []; @endphp
            <tr style="border-bottom:1px solid rgba(18,3,9,0.1);">
                <td style="padding:14px 18px;">
                    <strong>{{ $t['nom_template'] }}</strong>
                    @if(!empty($t['description']))
                        <div style="font-size:0.8rem; color:#888;">{{ $t['description'] }}</div>
                    @endif
                </td>
                <td style="padding:14px 18px;">
                    <span class="tpl-tag">{{ ucfirst($m['type_evenement'] ?? '—') }}</span>
                    <span class="tpl-tag">{{ ucfirst($m['format'] ?? '—') }}</span>
                </td>
                <td style="padding:14px 18px;">
                    {{ $m['nb_places_total'] ?? '—' }} places ·
                    {{ isset($m['prix']) && $m['prix'] > 0 ? number_format($m['prix'], 2) . ' €' : 'Gratuit' }}
                </td>
                <td style="padding:14px 18px;">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="btn-secondary btn-sm" onclick='openTplEdit(@json($t))'>Modifier</button>
                        <form action="{{ route('salarie.templates.destroy', $t['id_template']) }}" method="POST" style="margin:0;"
                              data-confirm="Supprimer définitivement le modèle « {{ $t['nom_template'] }} » ? Cette action est irréversible.">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center; padding:28px; color:#888;"><span data-i18n="sal.tpl.empty">Aucun modèle. Créez-en un avec « + Nouveau modèle ».</span></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- === Scripts : ouverture/pré-remplissage de la modale (création vs édition) === --}}
<script>
    const tplForm   = document.getElementById('tplForm');
    const tplMethod = document.getElementById('tplMethod');

    function setVal(id, v) { document.getElementById(id).value = (v === undefined || v === null) ? '' : v; }

    function openTplModal() {
        document.getElementById('tplModalTitle').textContent = 'Nouveau modèle';
        tplForm.action = "{{ route('salarie.templates.store') }}";
        tplMethod.innerHTML = '';
        setVal('t_nom', ''); setVal('t_desc', '');
        setVal('t_titre', ''); setVal('t_mdesc', ''); setVal('t_lieu', '');
        document.getElementById('t_type').value = 'atelier';
        document.getElementById('t_format').value = 'presentiel';
        setVal('t_places', 10); setVal('t_prix', 0);
        document.getElementById('tplModal').classList.add('active');
    }
    function openTplEdit(t) {
        const m = t.modele || {};
        document.getElementById('tplModalTitle').textContent = 'Modifier : ' + t.nom_template;
        tplForm.action = "{{ url('salarie/templates') }}/" + t.id_template;
        tplMethod.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        setVal('t_nom', t.nom_template); setVal('t_desc', t.description);
        setVal('t_titre', m.titre); setVal('t_mdesc', m.description); setVal('t_lieu', m.lieu);
        document.getElementById('t_type').value = m.type_evenement || 'atelier';
        document.getElementById('t_format').value = m.format || 'presentiel';
        setVal('t_places', m.nb_places_total || 10);
        setVal('t_prix', (m.prix !== undefined ? m.prix : 0));
        document.getElementById('tplModal').classList.add('active');
    }
    function closeTplModal() { document.getElementById('tplModal').classList.remove('active'); }
    document.getElementById('tplModal').addEventListener('mousedown', (e) => { if (e.target.id === 'tplModal') closeTplModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTplModal(); });
    @if($errors->any()) openTplModal(); @endif
</script>
@endsection

@extends('layouts.admin')

@section('title', 'Étapes du tutoriel')

@section('content')
<div class="page-header">
    <h1 class="page-title">Étapes du tutoriel</h1>
</div>

<div id="alert" style="display:none;" class="alert alert-success"></div>

<div id="etapes-list"></div>
@endsection

@section('scripts')
<script>
const API = '{{ config("services.api.public_url") }}';
const TOKEN = '{{ session("admin_token") }}';
let etapes = [];

async function load() {
    const r = await fetch(API + '/api/v1/admin/tutoriel/etapes', { headers: { 'Authorization': 'Bearer ' + TOKEN } });
    if (!r.ok) return;
    etapes = await r.json();
    render();
}

function render() {
    document.getElementById('etapes-list').innerHTML = etapes.map((e, i) => `
        <div class="card" style="margin-bottom:20px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
                <div>
                    <div class="form-group">
                        <label class="form-label">Titre</label>
                        <input class="form-input" id="titre-${e.id_etape}" value="${escHtml(e.titre)}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ordre</label>
                        <input class="form-input" type="number" id="ordre-${e.id_etape}" value="${e.ordre}">
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label class="form-label">Contenu</label>
                        <textarea class="form-textarea" id="contenu-${e.id_etape}" style="min-height:100px;">${escHtml(e.contenu)}</textarea>
                    </div>
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;">
                <label style="font-family:'DM Mono',monospace;font-size:0.82rem;text-transform:uppercase;display:flex;align-items:center;gap:8px;">
                    <input type="checkbox" id="actif-${e.id_etape}" ${e.est_actif ? 'checked' : ''}> Étape active
                </label>
                <button class="btn-primary" style="padding:8px 20px;" onclick="save(${e.id_etape})">Enregistrer</button>
            </div>
        </div>
    `).join('');
}

async function save(id) {
    const body = {
        titre: document.getElementById('titre-' + id).value,
        contenu: document.getElementById('contenu-' + id).value,
        ordre: parseInt(document.getElementById('ordre-' + id).value),
        cible_element: '',
        position: 'center',
        icone: '',
        est_actif: document.getElementById('actif-' + id).checked
    };
    const r = await fetch(API + '/api/v1/admin/tutoriel/etapes/' + id, {
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + TOKEN, 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
    });
    const el = document.getElementById('alert');
    if (r.ok) {
        el.textContent = 'Étape #' + id + ' mise à jour.';
        el.className = 'alert alert-success';
        el.style.display = 'flex';
        setTimeout(() => el.style.display = 'none', 3000);
    }
}

function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

load();
</script>
@endsection

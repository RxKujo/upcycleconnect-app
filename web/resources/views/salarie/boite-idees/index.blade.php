@extends($layout)

@section('title', 'Boîte à idées')

@section('content')
<style>
/* ─── Liste classée (rangées à hauteur fixe) ─────────────────────── */
.idee-list { display: flex; flex-direction: column; gap: 14px; margin-bottom: 32px; }
.idee-row { display: flex; align-items: center; gap: 20px; background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 16px 22px; transition: transform 0.12s, box-shadow 0.12s; }
.idee-row:hover { transform: translate(-2px,-2px); box-shadow: var(--shadow); }
.idee-row-vote { flex-shrink: 0; }
.idee-row-main { flex: 1 1 auto; min-width: 0; }
.idee-row-side { flex-shrink: 0; display: flex; flex-direction: column; align-items: stretch; gap: 10px; width: 260px; }
.idee-side-top { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }

.idee-statut { font-family: 'DM Mono', monospace; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 5px 12px; border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.25); white-space: nowrap; }

/* Widget de vote up / down (style Reddit) */
.idee-votebox { flex-shrink: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; width: 58px; border: 3px solid var(--coffee); box-shadow: 2px 2px 0 var(--coffee); background: var(--cream); padding: 6px 0; }
.vote-arrow { background: none; border: none; cursor: pointer; font-size: 1.05rem; line-height: 1; padding: 4px 0; width: 100%; color: var(--coffee); opacity: 0.5; transition: all 0.1s ease; }
.vote-arrow:hover:not(:disabled) { opacity: 1; }
.vote-arrow.up.active { color: var(--forest); opacity: 1; }
.vote-arrow.down.active { color: var(--cherry); opacity: 1; }
.vote-arrow:disabled { cursor: default; opacity: 0.3; }
.vote-score { font-family: 'DM Mono', monospace; font-size: 1.15rem; font-weight: 700; line-height: 1; color: var(--coffee); }
.vote-score.pos { color: var(--forest); }
.vote-score.neg { color: var(--cherry); }

.idee-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.45rem; margin: 0 0 3px; line-height: 1.1; letter-spacing: 0.02em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.idee-contenu { font-size: 0.92rem; line-height: 1.4; color: rgba(18,3,9,0.72); margin: 0 0 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.idee-meta { display: flex; align-items: center; gap: 14px; font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; opacity: 0.5; flex-wrap: wrap; }
.idee-meta-tags { display: inline-flex; gap: 5px; flex-wrap: wrap; }
.idee-tag { background: var(--wheat); border: 2px solid var(--coffee); font-family: 'DM Mono', monospace; font-size: 0.62rem; text-transform: uppercase; padding: 1px 7px; letter-spacing: 0.04em; opacity: 0.95; }

.idee-owner-tag { display: inline-block; font-family: 'DM Mono', monospace; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; background: var(--teal); color: var(--cream); padding: 2px 7px; letter-spacing: 0.04em; }
.idee-statut-select { width: 100%; border: 2px solid var(--coffee); background: white; font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; padding: 8px 10px; outline: none; cursor: pointer; box-shadow: 2px 2px 0 rgba(18,3,9,0.1); box-sizing: border-box; }
.idee-statut-select:focus { border-color: var(--forest); }
.idee-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.idee-act { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.03em; padding: 9px 10px; cursor: pointer; background: white; color: var(--coffee); border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.15); transition: all 0.12s ease; white-space: nowrap; }
.idee-act:hover { background: var(--wheat); transform: translate(-1px,-1px); box-shadow: 3px 3px 0 rgba(18,3,9,0.2); }
.idee-act.danger { color: var(--cherry); border-color: var(--cherry); grid-column: 1 / -1; }
.idee-act.danger:hover { background: var(--cherry); color: var(--cream); }

/* Responsive : la colonne droite passe dessous sur petit écran */
@media (max-width: 760px) {
    .idee-row { flex-wrap: wrap; }
    .idee-row-main { flex-basis: 70%; }
    .idee-row-side { width: 100%; }
    .idee-side-top { justify-content: flex-start; }
}

/* Onglets */
.idee-tabs { display: flex; gap: 4px; margin-bottom: 28px; border-bottom: 4px solid var(--coffee); flex-wrap: wrap; }
.idee-tab { font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; letter-spacing: 0.06em; text-transform: uppercase; padding: 12px 26px; cursor: pointer; background: transparent; border: 3px solid transparent; border-bottom: none; color: var(--coffee); opacity: 0.5; margin-bottom: -4px; transition: opacity 0.12s; }
.idee-tab:hover { opacity: 0.85; }
.idee-tab.active { opacity: 1; background: var(--cream); border-color: var(--coffee); box-shadow: 3px -2px 0 rgba(18,3,9,0.15); }
.idee-tab .tab-count { font-family: 'DM Mono', monospace; font-size: 0.75rem; opacity: 0.6; margin-left: 6px; }

.idee-empty { background: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); text-align: center; padding: 60px 40px; }
.idee-empty .big { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; opacity: 0.3; margin: 0; }
.idee-empty .sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; text-transform: uppercase; opacity: 0.4; margin: 12px 0 0; }

/* Fallback stats (assure un rendu correct aussi sous le layout admin) */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 48px; }
.stat-card { border: var(--border); background: white; padding: 24px; box-shadow: var(--shadow-sm); }
.stat-label { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; opacity: 0.55; margin-bottom: 8px; }
.stat-value { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--forest); line-height: 1; }
</style>

@php
    $mesIdees = count(array_filter($idees, fn($i) => ($i['id_auteur'] ?? 0) == session('salarie_id')))
              + count(array_filter($archivees, fn($i) => ($i['id_auteur'] ?? 0) == session('salarie_id')));
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title"><span data-i18n="sal.ideas">Boîte à idées</span></h1>
        <p style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;opacity:0.5;margin:8px 0 0;">
            Partagez vos idées avec l'équipe — réservé aux salariés
        </p>
    </div>
    <button class="btn-primary" onclick="document.getElementById('modal-add').style.display='flex'">
        + Proposer une idée
    </button>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Idées partagées</div>
        <div class="stat-value">{{ count($idees) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mes idées</div>
        <div class="stat-value">{{ $mesIdees }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Idées votées</div>
        <div class="stat-value" id="count-votes">{{ count(array_filter($idees, fn($i) => (int)($i['mon_vote'] ?? 0) !== 0)) }}</div>
    </div>
</div>

{{-- Onglets --}}
<div class="idee-tabs">
    <button type="button" class="idee-tab active" data-tab="populaire">Populaire</button>
    <button type="button" class="idee-tab" data-tab="recent">Récent</button>
    <button type="button" class="idee-tab" data-tab="archives">Archives<span class="tab-count">({{ count($archivees) }})</span></button>
</div>

{{-- Flux principal (Populaire / Récent) --}}
<div id="flux-section">
    @if(empty($idees))
        <div class="idee-empty">
            <p class="big"><span data-i18n="sal.ideas.empty">Aucune idée pour le moment</span></p>
            <p class="sub">Soyez le premier à proposer quelque chose !</p>
        </div>
    @else
        <div class="idee-list" id="flux-grid">
            @foreach($idees as $idee)
                @include('salarie.boite-idees._carte', ['idee' => $idee, 'isAdmin' => $isAdmin, 'isArchive' => false])
            @endforeach
        </div>
    @endif
</div>

{{-- Archives --}}
<div id="archives-section" style="display:none;">
    @if(empty($archivees))
        <div class="idee-empty">
            <p class="big"><span data-i18n="sal.ideas.emptyarchived">Aucune idée archivée</span></p>
            <p class="sub">Les idées que vous archivez apparaîtront ici.</p>
        </div>
    @else
        <div class="idee-list" id="archives-grid">
            @foreach($archivees as $idee)
                @include('salarie.boite-idees._carte', ['idee' => $idee, 'isAdmin' => $isAdmin, 'isArchive' => true])
            @endforeach
        </div>
    @endif
</div>

{{-- Modal ajout --}}
<div id="modal-add" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:540px;position:relative;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;"><span data-i18n="sal.ideas.propose">Proposer une idée</span></h2>
        <form action="{{ route('salarie.idees.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" name="titre" class="form-input" required maxlength="200" placeholder="Ex: Organiser un atelier zéro déchet">
            </div>
            <div class="form-group">
                <label class="form-label">Description <span style="color:var(--cherry)">*</span></label>
                <textarea name="contenu" class="form-textarea" required placeholder="Décrivez votre idée en détail…"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags <span style="opacity:0.5;font-size:0.8rem;">(séparés par des virgules)</span></label>
                <input type="text" name="tags" class="form-input" maxlength="300" placeholder="Ex: recyclage, événement, formation">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary"><span data-i18n="sal.ideas.proposebtn">Proposer</span></button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-add').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal édition --}}
<div id="modal-edit" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.6);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:var(--border);box-shadow:var(--shadow);padding:40px;width:100%;max-width:540px;position:relative;">
        <h2 class="font-bebas" style="font-size:2rem;margin:0 0 28px;"><span data-i18n="sal.ideas.edit">Modifier l'idée</span></h2>
        <form id="form-edit" method="POST">
            @csrf @method('PUT')
            <div class="form-group">
                <label class="form-label">Titre <span style="color:var(--cherry)">*</span></label>
                <input type="text" id="edit-titre" name="titre" class="form-input" required maxlength="200">
            </div>
            <div class="form-group">
                <label class="form-label">Description <span style="color:var(--cherry)">*</span></label>
                <textarea id="edit-contenu" name="contenu" class="form-textarea" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Tags</label>
                <input type="text" id="edit-tags" name="tags" class="form-input" maxlength="300">
            </div>
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-primary"><span data-i18n="btn.save">Enregistrer</span></button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-edit').style.display='none'">Annuler</button>
            </div>
        </form>
    </div>
</div>
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';

function openEditModal(id, titre, contenu, tags) {
    document.getElementById('form-edit').action = '/salarie/idees/' + id;
    document.getElementById('edit-titre').value = titre;
    document.getElementById('edit-contenu').value = contenu;
    document.getElementById('edit-tags').value = tags;
    document.getElementById('modal-edit').style.display = 'flex';
}

// ─── Onglets + tri (état partagé avec le vote) ───────────────────────────────
const tabs = document.querySelectorAll('.idee-tab');
const fluxSection = document.getElementById('flux-section');
const archivesSection = document.getElementById('archives-section');
const fluxGrid = document.getElementById('flux-grid');
let currentSort = 'populaire';

function compareCards(a, b, mode) {
    if (mode === 'recent') {
        return (parseInt(b.dataset.date) || 0) - (parseInt(a.dataset.date) || 0);
    }
    const dv = (parseInt(b.dataset.votes) || 0) - (parseInt(a.dataset.votes) || 0);
    return dv !== 0 ? dv : (parseInt(b.dataset.date) || 0) - (parseInt(a.dataset.date) || 0);
}

function sortFlux(mode) {
    if (!fluxGrid) return;
    Array.from(fluxGrid.children)
        .sort((a, b) => compareCards(a, b, mode))
        .forEach(c => fluxGrid.appendChild(c));
}

// Réordonne avec une animation FLIP : les rangées glissent vers leur place.
function sortFluxAnimated() {
    if (!fluxGrid) return;
    const cards = Array.from(fluxGrid.children);
    const before = new Map();
    cards.forEach(c => before.set(c, c.getBoundingClientRect().top));

    sortFlux(currentSort);

    cards.forEach(c => {
        const dy = before.get(c) - c.getBoundingClientRect().top;
        if (!dy) return;
        c.style.transition = 'none';
        c.style.transform = 'translateY(' + dy + 'px)';
        requestAnimationFrame(() => {
            c.style.transition = 'transform 0.4s cubic-bezier(0.22, 1, 0.36, 1)';
            c.style.transform = '';
        });
    });
}

function showTab(tab) {
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
    if (tab === 'archives') {
        fluxSection.style.display = 'none';
        archivesSection.style.display = '';
    } else {
        archivesSection.style.display = 'none';
        fluxSection.style.display = '';
        currentSort = tab;
        sortFlux(tab);
    }
}

tabs.forEach(t => t.addEventListener('click', () => showTab(t.dataset.tab)));

// ─── Vote up / down (style Reddit, sans rechargement) ────────────────────────
const countVotesEl = document.getElementById('count-votes');
const VOTE_BASE = '{{ url('/salarie/idees') }}';

document.querySelectorAll('.idee-votebox').forEach(box => {
    const id = box.dataset.id;
    const up = box.querySelector('.vote-arrow.up');
    const down = box.querySelector('.vote-arrow.down');
    const scoreEl = box.querySelector('.vote-score');

    box.querySelectorAll('.vote-arrow').forEach(btn => {
        if (btn.disabled) return; // archives : lecture seule
        btn.addEventListener('click', async () => {
            up.disabled = down.disabled = true;
            try {
                const res = await fetch(VOTE_BASE + '/' + id + '/voter', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ valeur: parseInt(btn.dataset.val) }),
                });
                if (!res.ok) return;
                const data = await res.json().catch(() => ({}));

                const prevVoted = up.classList.contains('active') || down.classList.contains('active');
                const mv = data.mon_vote || 0;
                up.classList.toggle('active', mv === 1);
                down.classList.toggle('active', mv === -1);

                const score = (typeof data.score === 'number') ? data.score : (parseInt(scoreEl.textContent) || 0);
                scoreEl.textContent = score;
                scoreEl.className = 'vote-score ' + (score > 0 ? 'pos' : (score < 0 ? 'neg' : ''));

                const row = box.closest('.idee-row');
                if (row) row.dataset.votes = score;

                // Compteur global « Idées votées »
                if (countVotesEl) {
                    const c = parseInt(countVotesEl.textContent) || 0;
                    countVotesEl.textContent = Math.max(0, c + ((mv !== 0 ? 1 : 0) - (prevVoted ? 1 : 0)));
                }

                // Reclasse en direct (uniquement en tri « Populaire »).
                if (currentSort === 'populaire') sortFluxAnimated();
            } finally {
                up.disabled = down.disabled = false;
            }
        });
    });
});
</script>
@endsection

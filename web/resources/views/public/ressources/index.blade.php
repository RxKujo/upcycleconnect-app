@extends('layouts.public')

@section('pub_slot')@include('partials.pub-slot')@endsection

@section('title', 'Ressources Pédagogiques')

@section('styles')
.ressources-wrap { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); margin-bottom: 8px; }
.page-sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; color: #666; text-transform: uppercase; margin-bottom: 40px; max-width: 640px; line-height: 1.6; }

.filters { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 36px; }
.filter-chip { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 18px; border: 2px solid var(--coffee); background: var(--cream); color: var(--coffee); cursor: pointer; }
.filter-chip.active { background: var(--coffee); color: var(--cream); }

.articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
.article-card { background: white; border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); overflow: hidden; cursor: pointer; display: flex; flex-direction: column; transition: transform 0.15s ease, box-shadow 0.15s ease; }
.article-card:hover { transform: translate(-3px,-3px); box-shadow: 6px 6px 0 var(--coffee); }
.article-top { height: 8px; }
.article-body { padding: 22px; display: flex; flex-direction: column; flex: 1; }
.cat-badge { align-self: flex-start; font-family: 'DM Mono', monospace; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 4px 10px; border: 2px solid var(--coffee); margin-bottom: 12px; }
.article-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; line-height: 1.1; margin: 0 0 8px; color: var(--coffee); }
.article-meta { font-family: 'DM Mono', monospace; font-size: 0.72rem; text-transform: uppercase; color: #999; margin-bottom: 14px; }
.article-excerpt { font-size: 0.95rem; color: #555; line-height: 1.55; flex: 1; }
.article-more { font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--cherry); margin-top: 16px; font-weight: bold; }

.empty-state { text-align: center; padding: 72px 24px; border: 3px solid var(--coffee); background: white; box-shadow: var(--shadow-sm); }

/* Modal lecture article */
.art-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6); z-index: 9999; align-items: flex-start; justify-content: center; overflow-y: auto; padding: 48px 20px; }
.art-modal-overlay.active { display: flex; }
.art-modal { background: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow); width: 100%; max-width: 720px; padding: 40px; position: relative; }
.art-modal-close { position: absolute; top: 16px; right: 18px; background: none; border: none; font-size: 1.8rem; cursor: pointer; color: var(--coffee); line-height: 1; }
.art-modal h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2.2rem; line-height: 1.05; margin: 0 0 10px; }
.art-modal-meta { font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; color: #888; margin-bottom: 24px; }
.art-modal-content { font-size: 1.02rem; line-height: 1.7; color: #333; white-space: pre-wrap; }
@endsection

@section('content')
<div class="ressources-wrap">
    <h1 class="page-title">Ressources pédagogiques</h1>
    <p class="page-sub">Actualités, conseils et astuces rédigés par l'équipe UpcycleConnect pour vous accompagner dans le réemploi et la réparation.</p>

    <div class="filters" id="filters"></div>

    <div id="articles-grid" class="articles-grid">
        <div style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;color:#999;">Chargement…</div>
    </div>

    <div id="empty-state" class="empty-state" style="display:none;">
        <h3 style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;margin-bottom:8px;">Aucune ressource pour le moment</h3>
        <p style="opacity:0.6;">L'équipe UpcycleConnect publiera bientôt des actualités et des conseils ici.</p>
    </div>
</div>

@endsection

@section('scripts')
<script>
const CAT_LABELS = {
    actualites: { label: 'Actualités',         color: 'var(--teal)',   text: '#fff' },
    conseils:   { label: 'Conseils & astuces', color: 'var(--forest)', text: '#fff' },
    tutoriel:   { label: 'Tutoriel',           color: '#6c5ce7',       text: '#fff' },
    materiaux:  { label: 'Matériaux',          color: 'var(--cherry)', text: '#fff' },
    evenement:  { label: 'Événement',          color: 'var(--coffee)', text: '#fff' },
};
// Liste FIXE des rubriques (alignée sur config/articles.php) — affichée même vide.
const FIXED_CATS = @json(config('articles.categories'));
function catInfo(cat) {
    return CAT_LABELS[cat] || { label: cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : 'Ressource', color: 'var(--wheat)', text: 'var(--coffee)' };
}
function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function stripHtml(s) {
    const tmp = document.createElement('div');
    tmp.innerHTML = String(s || '');
    return tmp.textContent || tmp.innerText || '';
}
function formatDate(s) {
    if (!s) return '';
    const d = new Date(s);
    if (isNaN(d)) return '';
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

let ALL_ARTICLES = [];
let currentFilter = 'all';

function countFor(cat) {
    if (cat === 'all') return ALL_ARTICLES.length;
    if (cat === '__none__') return ALL_ARTICLES.filter(a => !a.categorie).length;
    return ALL_ARTICLES.filter(a => a.categorie === cat).length;
}

function renderFilters() {
    const wrap = document.getElementById('filters');
    // « Tout » + toutes les rubriques fixes (même à 0) + « Non classé » si présent.
    const entries = [['all', 'Tout']].concat(Object.entries(FIXED_CATS));
    if (countFor('__none__') > 0) entries.push(['__none__', 'Non classé']);

    wrap.innerHTML = entries.map(([key, label]) => {
        const n = countFor(key);
        const active = key === currentFilter ? ' active' : '';
        const dimmed = n === 0 ? ' style="opacity:0.45;"' : '';
        return `<button class="filter-chip${active}" data-cat="${escapeHtml(key)}"${dimmed}>${escapeHtml(label)} <span style="opacity:0.6;">(${n})</span></button>`;
    }).join('');

    wrap.querySelectorAll('.filter-chip').forEach(btn => {
        btn.addEventListener('click', () => {
            currentFilter = btn.dataset.cat;
            wrap.querySelectorAll('.filter-chip').forEach(b => b.classList.toggle('active', b === btn));
            renderArticles();
        });
    });
}

function renderArticles() {
    const grid = document.getElementById('articles-grid');
    const empty = document.getElementById('empty-state');
    let list;
    if (currentFilter === 'all') list = ALL_ARTICLES;
    else if (currentFilter === '__none__') list = ALL_ARTICLES.filter(a => !a.categorie);
    else list = ALL_ARTICLES.filter(a => a.categorie === currentFilter);

    if (!list.length) {
        grid.style.display = 'none';
        empty.style.display = 'block';
        return;
    }
    grid.style.display = 'grid';
    empty.style.display = 'none';

    grid.innerHTML = list.map((a) => {
        const ci = catInfo(a.categorie);
        const auteur = [a.auteur_prenom, a.auteur_nom_initiale].filter(Boolean).join(' ');
        const meta = [formatDate(a.date_publication), auteur ? 'par ' + escapeHtml(auteur) : ''].filter(Boolean).join(' · ');
        const plain = stripHtml(a.contenu || '').replace(/\s+/g, ' ').trim();
        const excerpt = escapeHtml(plain.substring(0, 140)) + (plain.length > 140 ? '…' : '');
        return `<a href="/ressources/${a.id_article}" class="article-card">
            <div class="article-top" style="background:${ci.color};"></div>
            <div class="article-body">
                <span class="cat-badge" style="background:${ci.color};color:${ci.text};">${escapeHtml(ci.label)}</span>
                <h3 class="article-title">${escapeHtml(a.titre)}</h3>
                <p class="article-meta">${meta}</p>
                <p class="article-excerpt">${excerpt}</p>
                <span class="article-more">Lire l'article →</span>
            </div>
        </a>`;
    }).join('');
}

async function init() {
    const grid = document.getElementById('articles-grid');
    try {
        const r = await fetch(API_BASE + '/api/v1/public/articles');
        if (!r.ok) throw new Error('http ' + r.status);
        ALL_ARTICLES = await r.json() || [];
    } catch (e) {
        ALL_ARTICLES = [];
    }
    renderFilters();
    renderArticles();
}

if (document.readyState !== 'loading') init();
else document.addEventListener('DOMContentLoaded', init);
</script>
@endsection

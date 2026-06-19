@extends('layouts.public')

@section('title', 'Ressources Pédagogiques')

@section('styles')
<style>
.ressources-wrap { max-width: 1200px; margin: 0 auto; padding: 60px 24px; }
.page-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); margin-bottom: 8px; }
.page-sub { font-family: 'DM Mono', monospace; font-size: 0.85rem; color: #666; text-transform: uppercase; margin-bottom: 48px; }
.tabs { display: flex; border-bottom: 4px solid var(--coffee); margin-bottom: 40px; }
.tab-btn { font-family: 'Bebas Neue', sans-serif; font-size: 1.3rem; letter-spacing: 0.1em; padding: 12px 28px; cursor: pointer; border: none; background: none; color: var(--coffee); opacity: 0.5; position: relative; }
.tab-btn.active { opacity: 1; }
.tab-btn.active::after { content: ''; position: absolute; bottom: -4px; left: 0; right: 0; height: 4px; background: var(--forest); }
.tab-content { display: none; }
.tab-content.active { display: block; }
.articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
.article-card { background: white; border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); overflow: hidden; }
.article-img { height: 180px; background: var(--wheat); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
.article-body { padding: 20px; }
.article-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; margin: 0 0 8px; }
.article-meta { font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; color: #888; margin-bottom: 12px; }
.article-excerpt { font-size: 0.95rem; color: #555; line-height: 1.5; }

/* Forum section */
.forum-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.sujet-row { border: 3px solid var(--coffee); background: white; padding: 20px 24px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; transition: all 0.2s; }
.sujet-row:hover { transform: translate(-2px,-2px); box-shadow: var(--shadow-sm); }
.sujet-titre { font-family: 'Bebas Neue', sans-serif; font-size: 1.2rem; }
.sujet-meta { font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; color: #888; margin-top: 4px; }
.sujet-link { text-decoration: none; color: inherit; flex: 1; }
.signaler-btn { font-family: 'DM Mono', monospace; font-size: 0.75rem; text-transform: uppercase; background: none; border: 2px solid var(--cherry); color: var(--cherry); padding: 6px 12px; cursor: pointer; }
.signaler-btn:hover { background: var(--cherry); color: white; }
.btn { display: inline-flex; align-items: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; border: 3px solid var(--coffee); padding: 10px 24px; font-size: 1.1rem; box-shadow: var(--shadow-sm); transition: all 0.2s; text-decoration: none; }
.btn-primary { background: var(--forest); color: var(--cream); }
.btn:hover { transform: translate(2px,2px); box-shadow: var(--shadow-hover); }

/* Modal signalement */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(18,3,9,0.6); z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal-box { background: var(--cream); border: 3px solid var(--coffee); box-shadow: var(--shadow); padding: 40px; width: 100%; max-width: 480px; }
.modal-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; margin: 0 0 20px; }
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-family: 'DM Mono', monospace; font-size: 0.82rem; text-transform: uppercase; font-weight: bold; margin-bottom: 8px; }
.form-textarea { width: 100%; border: 3px solid var(--coffee); padding: 10px 14px; font-family: 'Outfit', sans-serif; font-size: 1rem; min-height: 100px; resize: vertical; outline: none; box-sizing: border-box; }
</style>
@endsection

@section('content')
<div class="ressources-wrap">
    <h1 class="page-title">Ressources Pédagogiques</h1>
    <p class="page-sub">Articles, conseils et forum de la communauté UpcycleConnect</p>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('articles')">Articles & Conseils</button>
        <button class="tab-btn" onclick="switchTab('forum')">Forum</button>
    </div>

    <!-- Tab Articles -->
    <div id="tab-articles" class="tab-content active">
        <div id="articles-grid" class="articles-grid">
            <div style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;color:#999;">Chargement...</div>
        </div>
    </div>

    <!-- Tab Forum -->
    <div id="tab-forum" class="tab-content">
        <div class="forum-header">
            <span style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;" id="forum-count"></span>
            <a href="/forum" class="btn btn-primary">Voir le forum complet</a>
        </div>
        <div id="forum-list">
            <div style="font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;color:#999;">Chargement...</div>
        </div>
    </div>
</div>

<!-- Modal Signalement -->
<div class="modal-overlay" id="modal-signaler">
    <div class="modal-box">
        <h2 class="modal-title">Signaler un sujet</h2>
        <input type="hidden" id="signal-sujet-id">
        <div class="form-group">
            <label class="form-label">Raison du signalement *</label>
            <textarea id="signal-raison" class="form-textarea" placeholder="Décrivez pourquoi ce contenu est inapproprié..."></textarea>
        </div>
        <div style="display:flex;gap:12px;margin-top:20px;">
            <button class="btn btn-primary" onclick="submitSignalement()" style="font-size:1rem;padding:10px 20px;">Envoyer</button>
            <button class="btn" onclick="closeSignaler()" style="font-size:1rem;padding:10px 20px;background:var(--cream);">Annuler</button>
        </div>
        <div id="signal-success" style="display:none;font-family:'DM Mono',monospace;font-size:0.85rem;text-transform:uppercase;color:var(--forest);margin-top:12px;"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const API = '{{ config("services.api.url") }}';
const token = localStorage.getItem('uc_token');

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach((b, i) => {
        b.classList.toggle('active', (i === 0 && tab === 'articles') || (i === 1 && tab === 'forum'));
    });
    document.getElementById('tab-articles').classList.toggle('active', tab === 'articles');
    document.getElementById('tab-forum').classList.toggle('active', tab === 'forum');
    if (tab === 'forum' && !document.getElementById('forum-list').dataset.loaded) {
        loadForum();
    }
}

async function loadArticles() {
    const r = await fetch(API + '/api/v1/public/articles');
    if (!r.ok) return [];
    return await r.json();
}

async function loadForum() {
    const r = await fetch(API + '/api/v1/public/forum');
    if (!r.ok) return;
    const data = await r.json();
    const sujets = data.sujets || data;
    document.getElementById('forum-count').textContent = (sujets.length || 0) + ' sujets';
    document.getElementById('forum-list').dataset.loaded = '1';
    document.getElementById('forum-list').innerHTML = sujets.slice(0, 10).map(s => `
        <div class="sujet-row">
            <a href="/forum/${s.id_sujet}" class="sujet-link">
                <div class="sujet-titre">${s.titre}</div>
                <div class="sujet-meta">${s.nb_messages || 0} réponses • ${new Date(s.date_creation).toLocaleDateString('fr-FR')}</div>
            </a>
            <button class="signaler-btn" onclick="openSignaler(${s.id_sujet}, event)">Signaler</button>
        </div>
    `).join('');
}

async function init() {
    const articles = await loadArticles();
    const grid = document.getElementById('articles-grid');
    if (!articles.length) {
        grid.innerHTML = '<p style="font-family:\'DM Mono\',monospace;font-size:0.85rem;text-transform:uppercase;color:#999;">Aucun article disponible.</p>';
        return;
    }
    grid.innerHTML = articles.slice(0, 6).map(a => `
        <div class="article-card">
            <div class="article-img">📰</div>
            <div class="article-body">
                <h3 class="article-title">${a.titre || a.title}</h3>
                <p class="article-meta">${new Date(a.date_creation || a.created_at || Date.now()).toLocaleDateString('fr-FR')}</p>
                <p class="article-excerpt">${(a.contenu || a.content || '').substring(0, 120)}...</p>
            </div>
        </div>
    `).join('');
}

function openSignaler(sujetId, e) {
    e.preventDefault();
    if (!token) { window.location.href = '/login?return=/ressources'; return; }
    document.getElementById('signal-sujet-id').value = sujetId;
    document.getElementById('signal-raison').value = '';
    document.getElementById('signal-success').style.display = 'none';
    document.getElementById('modal-signaler').classList.add('active');
}

function closeSignaler() {
    document.getElementById('modal-signaler').classList.remove('active');
}

async function submitSignalement() {
    const sujetId = document.getElementById('signal-sujet-id').value;
    const raison = document.getElementById('signal-raison').value.trim();
    if (!raison) { alert('Veuillez indiquer une raison.'); return; }

    const r = await fetch(API + '/api/v1/forum/signaler', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token, 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_sujet: parseInt(sujetId), raison })
    });

    const el = document.getElementById('signal-success');
    if (r.ok) {
        el.textContent = 'Signalement envoyé. Merci !';
        el.style.display = 'block';
        setTimeout(closeSignaler, 2000);
    } else {
        el.textContent = 'Erreur lors de l\'envoi.';
        el.style.color = 'var(--cherry)';
        el.style.display = 'block';
    }
}

document.getElementById('modal-signaler').addEventListener('click', function(e) {
    if (e.target === this) closeSignaler();
});

init();
</script>
@endsection

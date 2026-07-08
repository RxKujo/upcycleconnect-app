{{-- Widget messagerie acheteur ↔ vendeur (bulle flottante bas-droite).
     Auto-actif si un jeton est présent (localStorage). Polling léger.
     API globale : window.ucOpenConversation(idAnnonce) — ouvre/crée la conversation
     avec le vendeur de l'annonce et affiche le fil. --}}
<style>
    #ucmsg-root { position: fixed; bottom: 22px; right: 22px; z-index: 900; font-family: 'Outfit', sans-serif; }
    #ucmsg-bubble {
        width: 60px; height: 60px; border: 3px solid var(--coffee, #120309); background: var(--cherry, #A4243B);
        color: var(--cream, #F5F0E1); cursor: pointer; box-shadow: 4px 4px 0 var(--coffee, #120309);
        display: flex; align-items: center; justify-content: center; position: relative; transition: transform .12s;
    }
    #ucmsg-bubble:hover { transform: translate(-1px,-1px); box-shadow: 5px 5px 0 var(--coffee,#120309); }
    #ucmsg-bubble:active { transform: translate(2px,2px); box-shadow: 2px 2px 0 var(--coffee,#120309); }
    #ucmsg-badge {
        position: absolute; top: -8px; right: -8px; min-width: 22px; height: 22px; padding: 0 5px;
        background: var(--forest, #244F26); color: #fff; border: 2px solid var(--coffee,#120309);
        border-radius: 11px; font-family: 'DM Mono', monospace; font-size: .72rem; font-weight: 700;
        display: none; align-items: center; justify-content: center;
    }
    #ucmsg-panel {
        display: none; position: absolute; bottom: 74px; right: 0; width: 360px; max-width: calc(100vw - 44px);
        height: 480px; max-height: calc(100vh - 120px); background: var(--cream, #F5F0E1);
        border: 3px solid var(--coffee,#120309); box-shadow: 6px 6px 0 var(--coffee,#120309);
        flex-direction: column; overflow: hidden;
    }
    #ucmsg-root.open #ucmsg-panel { display: flex; }
    #ucmsg-header {
        display: flex; align-items: center; gap: 8px; padding: 12px 14px; background: var(--coffee,#120309);
        color: var(--cream,#F5F0E1); border-bottom: 3px solid var(--coffee,#120309); flex-shrink: 0;
    }
    #ucmsg-title { flex: 1; font-family: 'Bebas Neue', sans-serif; font-size: 1.25rem; letter-spacing: .06em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #ucmsg-header button { background: none; border: none; color: var(--cream,#F5F0E1); cursor: pointer; font-size: 1.3rem; line-height: 1; padding: 2px 6px; }
    #ucmsg-header button:hover { color: var(--wheat,#D8C99B); }
    #ucmsg-body { flex: 1; overflow-y: auto; min-height: 0; }

    .ucmsg-conv { display: block; width: 100%; text-align: left; background: none; border: none; border-bottom: 2px solid rgba(18,3,9,.12); padding: 12px 14px; cursor: pointer; }
    .ucmsg-conv:hover { background: rgba(18,3,9,.05); }
    .ucmsg-conv-top { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
    .ucmsg-conv-name { font-weight: 600; color: var(--coffee,#120309); }
    .ucmsg-conv-annonce { font-family: 'DM Mono', monospace; font-size: .7rem; text-transform: uppercase; color: var(--cherry,#A4243B); margin: 2px 0 4px; }
    .ucmsg-conv-last { font-size: .85rem; color: #5b5045; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ucmsg-conv-dot { min-width: 18px; height: 18px; padding: 0 4px; background: var(--forest,#244F26); color: #fff; border-radius: 9px; font-family: 'DM Mono', monospace; font-size: .68rem; display: inline-flex; align-items: center; justify-content: center; }

    #ucmsg-messages { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
    .ucmsg-msg { max-width: 78%; padding: 8px 12px; border: 2px solid var(--coffee,#120309); font-size: .9rem; line-height: 1.35; word-wrap: break-word; }
    .ucmsg-msg.mine { align-self: flex-end; background: var(--wheat,#D8C99B); }
    .ucmsg-msg.theirs { align-self: flex-start; background: #fff; }
    .ucmsg-msg time { display: block; font-family: 'DM Mono', monospace; font-size: .62rem; opacity: .55; margin-top: 3px; }

    #ucmsg-form { display: flex; gap: 6px; padding: 10px; border-top: 3px solid var(--coffee,#120309); background: var(--cream,#F5F0E1); flex-shrink: 0; }
    #ucmsg-input { flex: 1; border: 2px solid var(--coffee,#120309); background: #fff; padding: 9px 12px; font-family: 'Outfit', sans-serif; font-size: .9rem; outline: none; }
    #ucmsg-form button { border: 2px solid var(--coffee,#120309); background: var(--forest,#244F26); color: #fff; font-family: 'Bebas Neue', sans-serif; letter-spacing: .06em; font-size: 1rem; padding: 0 16px; cursor: pointer; }
    #ucmsg-form button:active { transform: translate(1px,1px); }
    .ucmsg-empty { padding: 30px 20px; text-align: center; font-family: 'DM Mono', monospace; font-size: .8rem; color: #8a7d6e; text-transform: uppercase; }

    /* Mini-carte de l'annonce en haut du fil */
    .ucmsg-anncard { display: flex; gap: 10px; padding: 10px; border-bottom: 3px solid var(--coffee,#120309); background: var(--wheat,#D8C99B); align-items: center; }
    .ucmsg-anncard img { width: 52px; height: 52px; object-fit: cover; border: 2px solid var(--coffee,#120309); flex-shrink: 0; }
    .ucmsg-ann-nophoto { width: 52px; height: 52px; border: 2px solid var(--coffee,#120309); background: #fff; flex-shrink: 0; display: flex; align-items: center; justify-content: center; color: #b3a892; }
    .ucmsg-anninfo { min-width: 0; flex: 1; }
    .ucmsg-anntitre { font-weight: 700; font-size: .9rem; line-height: 1.2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ucmsg-annmeta { font-family: 'DM Mono', monospace; font-size: .68rem; color: #5b5045; margin: 3px 0 6px; }
    .ucmsg-annactions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .ucmsg-annlink { font-family: 'DM Mono', monospace; font-size: .7rem; text-transform: uppercase; color: var(--coffee,#120309); text-decoration: underline; }
    .ucmsg-sell { border: 2px solid var(--coffee,#120309); background: var(--forest,#244F26); color: #fff; font-family: 'DM Mono', monospace; font-size: .66rem; text-transform: uppercase; font-weight: 700; padding: 4px 9px; cursor: pointer; }
    .ucmsg-sell:active { transform: translate(1px,1px); }
    .ucmsg-ann-vendu { display: inline-block; background: var(--forest,#244F26); color: #fff; font-family: 'DM Mono', monospace; font-size: .6rem; text-transform: uppercase; padding: 1px 6px; border: 1px solid var(--coffee,#120309); vertical-align: middle; }
</style>

<div id="ucmsg-root">
    <button id="ucmsg-bubble" aria-label="Messagerie" title="Messagerie">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
        </svg>
        <span id="ucmsg-badge">0</span>
    </button>
    <div id="ucmsg-panel" role="dialog" aria-label="Messagerie">
        <div id="ucmsg-header">
            <button id="ucmsg-back" style="display:none;" aria-label="Retour">←</button>
            <span id="ucmsg-title">Messages</span>
            <button id="ucmsg-close" aria-label="Fermer">×</button>
        </div>
        <div id="ucmsg-body">
            <div id="ucmsg-list"></div>
            <div id="ucmsg-thread" style="display:none;">
                <div id="ucmsg-annonce"></div>
                <div id="ucmsg-messages"></div>
            </div>
        </div>
        <form id="ucmsg-form" style="display:none;">
            <input id="ucmsg-input" placeholder="Votre message…" autocomplete="off" maxlength="5000">
            <button type="submit" data-i18n="msg.send">Envoyer</button>
        </form>
    </div>
</div>

<script>
(function () {
    const API = '{{ config('services.api.public_url') }}';
    const MEDIA = (window.MEDIA_BASE || '{{ media_base() }}').replace(/\/$/, '');
    function token() { return localStorage.getItem('auth_token') || localStorage.getItem('uc_token'); }
    const root = document.getElementById('ucmsg-root');
    if (!root) return;

    // Widget réservé aux utilisateurs connectés. Un visiteur qui clique
    // « Contacter le vendeur » est renvoyé vers la page de connexion.
    if (!token()) {
        root.style.display = 'none';
        window.ucOpenConversation = function () {
            window.location.href = '/login?return=' + encodeURIComponent(location.pathname);
        };
        return;
    }

    const badge   = document.getElementById('ucmsg-badge');
    const panel   = document.getElementById('ucmsg-panel');
    const listEl  = document.getElementById('ucmsg-list');
    const threadEl= document.getElementById('ucmsg-thread');
    const msgsEl  = document.getElementById('ucmsg-messages');
    const titleEl = document.getElementById('ucmsg-title');
    const backBtn = document.getElementById('ucmsg-back');
    const form    = document.getElementById('ucmsg-form');
    const input   = document.getElementById('ucmsg-input');

    let currentConv = null;   // id de la conversation ouverte
    let lastMsgId   = 0;      // dernier message affiché (pour le polling)
    let pollTimer   = null;

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function heure(iso) {
        try { return new Date(iso).toLocaleString('fr-FR', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' }); }
        catch (e) { return ''; }
    }
    async function api(path, opts) {
        opts = opts || {};
        opts.headers = Object.assign({ 'Authorization': 'Bearer ' + token(), 'Content-Type': 'application/json' }, opts.headers || {});
        const r = await fetch(API + path, opts);
        if (!r.ok) throw new Error('http ' + r.status);
        return r.json();
    }

    // ── Pastille non-lus ──
    async function refreshUnread() {
        try {
            const d = await api('/api/v1/messages/unread-count');
            const n = d.non_lus || 0;
            badge.textContent = n > 99 ? '99+' : n;
            badge.style.display = n > 0 ? 'flex' : 'none';
        } catch (e) { /* silencieux */ }
    }

    // ── Liste des conversations ──
    async function openList() {
        currentConv = null;
        threadEl.style.display = 'none';
        form.style.display = 'none';
        backBtn.style.display = 'none';
        listEl.style.display = 'block';
        titleEl.textContent = 'Messages';
        try {
            const convs = await api('/api/v1/conversations');
            if (!convs.length) {
                listEl.innerHTML = '<div class="ucmsg-empty">Aucune conversation pour le moment.</div>';
                return;
            }
            listEl.innerHTML = convs.map(c => `
                <button class="ucmsg-conv" data-id="${c.id_conversation}">
                    <div class="ucmsg-conv-top">
                        <span class="ucmsg-conv-name">${esc(c.autre_nom)}</span>
                        ${c.non_lus > 0 ? `<span class="ucmsg-conv-dot">${c.non_lus}</span>` : ''}
                    </div>
                    <div class="ucmsg-conv-annonce">${esc(c.titre_annonce)}</div>
                    <div class="ucmsg-conv-last">${esc(c.dernier_message)}</div>
                </button>`).join('');
            listEl.querySelectorAll('.ucmsg-conv').forEach(b =>
                b.addEventListener('click', () => openThread(parseInt(b.dataset.id, 10))));
        } catch (e) {
            listEl.innerHTML = '<div class="ucmsg-empty">Erreur de chargement.</div>';
        }
    }

    // ── Fil d'une conversation ──
    function renderMessages(data) {
        const maxId = data.messages.reduce((m, x) => Math.max(m, x.id_message), 0);
        if (maxId === lastMsgId && msgsEl.childElementCount) return; // rien de neuf
        lastMsgId = maxId;
        msgsEl.innerHTML = data.messages.map(m => `
            <div class="ucmsg-msg ${m.est_moi ? 'mine' : 'theirs'}">
                ${esc(m.contenu)}<time>${heure(m.date_envoi)}</time>
            </div>`).join('');
        msgsEl.parentElement.scrollTop = msgsEl.parentElement.scrollHeight;
    }
    let lastAnnSig = '';
    function renderAnnonce(a) {
        const box = document.getElementById('ucmsg-annonce');
        if (!a) { box.innerHTML = ''; lastAnnSig = ''; return; }
        const sig = JSON.stringify(a);
        if (sig === lastAnnSig) return; // évite le re-render à chaque poll
        lastAnnSig = sig;
        const photo = a.photo
            ? `<img src="${MEDIA}/${esc(a.photo)}" alt="">`
            : '<div class="ucmsg-ann-nophoto">—</div>';
        const prix = a.type_annonce === 'don' ? 'Gratuit (don)'
            : (a.prix != null ? Number(a.prix).toFixed(2) + ' €' : '');
        const remise = a.mode_remise === 'main_propre' ? 'Espèces à la remise' : 'Conteneur';
        const vendu = a.statut === 'vendue';
        const badge = vendu ? '<span class="ucmsg-ann-vendu">Vendue</span>' : '';
        const sellBtn = (a.est_vendeur && a.statut === 'validee')
            ? '<button type="button" class="ucmsg-sell">Déclarer vendu</button>' : '';
        box.innerHTML = `
            <div class="ucmsg-anncard">
                ${photo}
                <div class="ucmsg-anninfo">
                    <div class="ucmsg-anntitre">${esc(a.titre)} ${badge}</div>
                    <div class="ucmsg-annmeta">${esc(prix)} · ${esc(remise)}</div>
                    <div class="ucmsg-annactions">
                        <a class="ucmsg-annlink" href="/annonces/${a.id_annonce}">Voir l'annonce →</a>
                        ${sellBtn}
                    </div>
                </div>
            </div>`;
        const sb = box.querySelector('.ucmsg-sell');
        if (sb) sb.addEventListener('click', declarerVendu);
    }

    async function declarerVendu() {
        if (!currentConv) return;
        if (!window.confirm('Déclarer cette annonce vendue à cet acheteur ? (paiement en espèces à la remise)')) return;
        try {
            const r = await fetch(API + '/api/v1/conversations/' + currentConv + '/vendu', {
                method: 'PUT', headers: { 'Authorization': 'Bearer ' + token() }
            });
            const d = await r.json();
            if (!r.ok) { alert(d.erreur || 'Erreur.'); return; }
            lastAnnSig = ''; // force le re-render avec le nouveau statut
            loadThread();
        } catch (e) { alert('Erreur réseau.'); }
    }

    async function loadThread() {
        if (!currentConv) return;
        try {
            const data = await api('/api/v1/conversations/' + currentConv + '/messages');
            titleEl.textContent = data.autre_nom + ' — ' + data.titre_annonce;
            renderAnnonce(data.annonce);
            renderMessages(data);
        } catch (e) { /* silencieux */ }
    }
    async function openThread(convId) {
        currentConv = convId;
        lastMsgId = 0;
        lastAnnSig = '';
        document.getElementById('ucmsg-annonce').innerHTML = '';
        listEl.style.display = 'none';
        threadEl.style.display = 'block';
        form.style.display = 'flex';
        backBtn.style.display = 'inline';
        msgsEl.innerHTML = '<div class="ucmsg-empty">Chargement…</div>';
        await loadThread();
        refreshUnread();
        input.focus();
    }

    // ── Envoi ──
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const contenu = input.value.trim();
        if (!contenu || !currentConv) return;
        input.value = '';
        try {
            await api('/api/v1/conversations/' + currentConv + '/messages', { method: 'POST', body: JSON.stringify({ contenu }) });
            await loadThread();
        } catch (e) { input.value = contenu; }
    });

    // ── Ouverture / fermeture du panneau ──
    function openPanel() { root.classList.add('open'); if (!currentConv) openList(); }
    function closePanel() { root.classList.remove('open'); }
    document.getElementById('ucmsg-bubble').addEventListener('click', () => root.classList.contains('open') ? closePanel() : openPanel());
    document.getElementById('ucmsg-close').addEventListener('click', closePanel);
    backBtn.addEventListener('click', openList);

    // ── Point d'entrée public : « Contacter le vendeur » ──
    window.ucOpenConversation = async function (idAnnonce) {
        if (!token()) { window.location.href = '/login?return=' + encodeURIComponent(location.pathname); return; }
        try {
            const r = await fetch(API + '/api/v1/conversations', {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + token(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_annonce: idAnnonce }),
            });
            const d = await r.json();
            if (!r.ok) { alert(d.erreur || "Impossible d'ouvrir la conversation."); return; }
            openPanel();
            openThread(d.id_conversation);
        } catch (e) {
            alert("Impossible d'ouvrir la conversation.");
        }
    };

    // ── Polling léger ──
    refreshUnread();
    pollTimer = setInterval(() => {
        refreshUnread();
        if (root.classList.contains('open')) {
            if (currentConv) loadThread();
            else openList();
        }
    }, 5000);
})();
</script>

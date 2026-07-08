{{-- Tutoriel guidé multi-pages (non-passable) : spotlight sur cible_element, navigation page à page,
     état en localStorage. Layouts public / particulier / pro. --}}
{{-- Overlay = capteur de clics + fond sombre ; carte au-dessus (z-index) pour rester lisible. --}}
<div id="tuto-overlay" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.82);z-index:99999;"></div>
<div id="tuto-card" style="display:none;background:var(--cream);border:3px solid var(--coffee);box-shadow:8px 8px 0 var(--coffee);padding:36px;width:460px;max-width:92vw;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:100001;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
        <span id="tuto-step-num" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:var(--forest);font-weight:700;"></span>
    </div>
    <div id="tuto-icone" style="font-size:3rem;margin-bottom:8px;"></div>
    <h2 id="tuto-titre" style="font-family:'Bebas Neue',sans-serif;font-size:2.1rem;color:var(--coffee);margin:0 0 14px;"></h2>
    <p id="tuto-contenu" style="font-size:1.05rem;color:#444;line-height:1.65;margin-bottom:28px;"></p>
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div id="tuto-dots" style="display:flex;gap:8px;"></div>
        <div style="display:flex;gap:12px;">
            <button id="tuto-prev" type="button" style="display:none;font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:0.08em;background:var(--cream);color:var(--coffee);border:3px solid var(--coffee);padding:9px 20px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">← Précédent</button>
            <button id="tuto-next" type="button" style="font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:0.08em;background:var(--forest);color:var(--cream);border:3px solid var(--coffee);padding:9px 22px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">Suivant →</button>
        </div>
    </div>
</div>

{{-- === Script === --}}
<script>
(function () {
    const API = '{{ config('services.api.public_url') }}';
    const token = localStorage.getItem('uc_token') || localStorage.getItem('auth_token');
    if (!token) return;
    const LS_ACTIVE = 'uc_tuto_active', LS_STEP = 'uc_tuto_step';

    const overlay = document.getElementById('tuto-overlay');
    const card    = document.getElementById('tuto-card');
    const numEl   = document.getElementById('tuto-step-num');
    const iconeEl = document.getElementById('tuto-icone');
    const titreEl = document.getElementById('tuto-titre');
    const contenuEl = document.getElementById('tuto-contenu');
    const dotsEl  = document.getElementById('tuto-dots');
    const prevBtn = document.getElementById('tuto-prev');
    const nextBtn = document.getElementById('tuto-next');

    let _etapes = [], _idx = 0, _spotEl = null, _spotPrev = { pos: '', pe: '' };

    async function fetchJSON(path, opts) {
        const r = await fetch(API + path, Object.assign({ headers: { 'Authorization': 'Bearer ' + token } }, opts || {}));
        if (!r.ok) throw new Error('http ' + r.status);
        return r.json();
    }

    async function boot() {
        try {
            const etapes = await fetchJSON('/api/v1/tutoriel/etapes');
            if (!etapes || !etapes.length) { clearActive(); return; }
            if (localStorage.getItem(LS_ACTIVE) === '1') { resume(etapes); return; }
            // Démarrage : seulement si pas encore vu / terminé / passé.
            const st = await fetchJSON('/api/v1/tutoriel/statut');
            if (st && (st.tuto_vu || st.termine || st.passe)) return;
            localStorage.setItem(LS_ACTIVE, '1');
            localStorage.setItem(LS_STEP, '0');
            resume(etapes);
        } catch (e) { /* silencieux */ }
    }

    function resume(etapes) {
        _etapes = etapes;
        let idx = parseInt(localStorage.getItem(LS_STEP) || '0', 10);
        if (isNaN(idx) || idx < 0) idx = 0;
        if (idx >= etapes.length) idx = etapes.length - 1;
        const wanted = etapes[idx].page || '';
        if (wanted && wanted !== location.pathname) {
            // L'étape vit sur une autre page : on y va, le tuto reprendra au chargement.
            location.href = wanted;
            return;
        }
        launch(idx);
    }

    function launch(idx) {
        overlay.style.display = 'block';
        card.style.display = 'block';
        lock();
        afficher(idx);
    }

    // ── Blocage total : scroll, Échap, Tab (le tuto n'est pas passable) ──
    function lock() {
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', keyTrap, true);
        window.addEventListener('resize', reposition);
        window.addEventListener('scroll', reposition, true);
    }
    function unlock() {
        document.body.style.overflow = '';
        document.removeEventListener('keydown', keyTrap, true);
        window.removeEventListener('resize', reposition);
        window.removeEventListener('scroll', reposition, true);
        clearSpot();
    }
    function keyTrap(ev) {
        if (ev.key === 'Escape') { ev.preventDefault(); ev.stopPropagation(); return; }
        if (ev.key === 'Tab') {
            const f = document.querySelectorAll('#tuto-card button');
            if (!f.length) return;
            const first = f[0], last = f[f.length - 1];
            if (ev.shiftKey && document.activeElement === first) { ev.preventDefault(); last.focus(); }
            else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
        }
    }

    // ── Spotlight : trou lumineux autour de l'élément, non cliquable ──
    function clearSpot() {
        if (_spotEl) {
            _spotEl.style.boxShadow = '';
            _spotEl.style.zIndex = '';
            _spotEl.style.position = _spotPrev.pos || '';
            _spotEl.style.pointerEvents = _spotPrev.pe || '';
            _spotEl = null;
        }
    }
    function setSpot(el) {
        clearSpot();
        _spotPrev = { pos: el.style.position || '', pe: el.style.pointerEvents || '' };
        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
        el.style.zIndex = '100000';
        el.style.pointerEvents = 'none'; // visible mais non cliquable : on avance via « Suivant »
        el.style.boxShadow = '0 0 0 4px var(--forest), 0 0 0 9999px rgba(18,3,9,0.82)';
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        _spotEl = el;
    }

    function placeCard(targetEl, position) {
        card.style.position = 'fixed';
        if (!targetEl) {
            card.style.top = '50%'; card.style.left = '50%'; card.style.transform = 'translate(-50%,-50%)';
            return;
        }
        card.style.transform = 'none';
        const r = targetEl.getBoundingClientRect();
        const cw = card.offsetWidth, ch = card.offsetHeight, gap = 18;
        let top, left;
        switch (position) {
            case 'top':   top = r.top - ch - gap;              left = r.left + r.width / 2 - cw / 2; break;
            case 'left':  top = r.top + r.height / 2 - ch / 2; left = r.left - cw - gap;             break;
            case 'right': top = r.top + r.height / 2 - ch / 2; left = r.right + gap;                 break;
            default:      top = r.bottom + gap;                left = r.left + r.width / 2 - cw / 2; break;
        }
        top  = Math.max(12, Math.min(top,  window.innerHeight - ch - 12));
        left = Math.max(12, Math.min(left, window.innerWidth  - cw - 12));
        card.style.top = top + 'px'; card.style.left = left + 'px';
    }

    function reposition() {
        const e = _etapes[_idx]; if (!e) return;
        const el = e.cible_element ? document.querySelector(e.cible_element) : null;
        placeCard(el, e.position);
    }

    // Cherche la cible avec quelques réessais (éléments injectés après le chargement).
    function withTarget(selector, cb, tries) {
        tries = (tries == null) ? 15 : tries;
        if (!selector) { cb(null); return; }
        const el = document.querySelector(selector);
        if (el) { cb(el); return; }
        if (tries <= 0) { cb(null); return; } // repli : carte centrée
        setTimeout(function () { withTarget(selector, cb, tries - 1); }, 200);
    }

    function afficher(idx) {
        _idx = idx;
        localStorage.setItem(LS_STEP, String(idx));
        const e = _etapes[idx];
        numEl.textContent = 'Étape ' + (idx + 1) + ' / ' + _etapes.length;
        iconeEl.textContent = e.icone || '';
        titreEl.textContent = e.titre;
        contenuEl.textContent = e.contenu;
        prevBtn.style.display = idx > 0 ? 'inline-block' : 'none';
        nextBtn.textContent = (idx === _etapes.length - 1) ? 'Terminer ✓' : 'Suivant →';
        dotsEl.innerHTML = _etapes.map(function (_, i) {
            return '<div style="width:10px;height:10px;border-radius:50%;background:' + (i === idx ? 'var(--forest)' : 'rgba(18,3,9,0.2)') + ';"></div>';
        }).join('');
        withTarget(e.cible_element, function (el) {
            if (el) { overlay.style.background = 'transparent'; setSpot(el); }
            else { clearSpot(); overlay.style.background = 'rgba(18,3,9,0.82)'; }
            requestAnimationFrame(function () { placeCard(el, e.position); });
        });
    }

    function goTo(idx) {
        const e = _etapes[idx];
        const wanted = (e && e.page) || '';
        localStorage.setItem(LS_STEP, String(idx));
        if (wanted && wanted !== location.pathname) {
            clearSpot();
            location.href = wanted; // reprise au chargement de la nouvelle page
            return;
        }
        afficher(idx);
    }

    async function terminer() {
        unlock();
        overlay.style.display = 'none';
        card.style.display = 'none';
        clearActive();
        try { await fetchJSON('/api/v1/tutoriel/termine', { method: 'POST' }); } catch (e) {}
    }
    function clearActive() {
        localStorage.removeItem(LS_ACTIVE);
        localStorage.removeItem(LS_STEP);
    }

    nextBtn.addEventListener('click', function () {
        if (_idx >= _etapes.length - 1) terminer();
        else goTo(_idx + 1);
    });
    prevBtn.addEventListener('click', function () {
        if (_idx > 0) goTo(_idx - 1);
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>

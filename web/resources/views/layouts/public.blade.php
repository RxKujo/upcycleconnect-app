<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'UpcycleConnect — Marketplace écologique. Vendez, donnez et trouvez des matériaux recyclés.')">
    @hasSection('og_title')
    <meta property="og:title" content="@yield('og_title')">
    <meta property="og:description" content="@yield('og_description')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    @endif
    <title>@yield('title', 'UpcycleConnect') — UpcycleConnect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --cherry: #A4243B;
            --wheat:  #D8C99B;
            --coffee: #120309;
            --forest: #244F26;
            --teal:   #18607D;
            --cream:  #F5F0E1;
            --shadow:       5px 5px 0px #120309;
            --shadow-sm:    3px 3px 0px #120309;
            --shadow-hover: 2px 2px 0px #120309;
            --border:       3px solid #120309;
            --nav-height: 72px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background-color: var(--cream);
            font-family: 'Outfit', sans-serif;
            color: var(--coffee);
            line-height: 1.6;
            padding-top: var(--nav-height);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        ul { list-style: none; }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        .font-bebas { font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.08em; text-transform: uppercase; }
        .font-mono { font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.06em; }

        /* =============================================
           SYSTÈME DE BOUTONS UNIFIÉ
           Usage : .btn + .btn-primary / .btn-secondary / .btn-ghost / .btn-success
                   .btn-sm (petit) / .btn-lg (grand) / .btn-block (pleine largeur)
        ============================================= */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 14px 28px;
            border: 3px solid var(--coffee);
            background: var(--cream);
            color: var(--coffee);
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            box-shadow: 4px 4px 0 var(--coffee);
            transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease, color 0.12s ease;
            user-select: none;
            line-height: 1;
        }
        .btn:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 var(--coffee);
        }
        .btn:active {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0 var(--coffee);
        }
        .btn:disabled,
        .btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: 4px 4px 0 var(--coffee) !important;
        }

        /* Variantes de couleur */
        .btn-primary {
            background: var(--cherry);
            color: var(--cream);
            border-color: var(--coffee);
        }
        .btn-primary:hover { background: var(--cherry); color: var(--cream); }

        .btn-secondary {
            background: var(--cream);
            color: var(--coffee);
            border-color: var(--coffee);
        }
        .btn-secondary:hover { background: var(--coffee); color: var(--cream); }

        .btn-success {
            background: var(--forest);
            color: var(--cream);
            border-color: var(--coffee);
        }
        .btn-success:hover { background: var(--forest); color: var(--cream); }

        .btn-ghost {
            background: transparent;
            color: inherit;
            border-color: currentColor;
            box-shadow: none;
        }
        .btn-ghost:hover {
            background: currentColor;
            box-shadow: 4px 4px 0 currentColor;
        }
        .btn-ghost:hover > * { color: var(--cream); }

        /* Tailles */
        .btn-sm {
            padding: 9px 18px;
            font-size: 0.85rem;
            box-shadow: 3px 3px 0 var(--coffee);
        }
        .btn-sm:hover { box-shadow: 5px 5px 0 var(--coffee); }
        .btn-sm:active { box-shadow: 1px 1px 0 var(--coffee); }

        .btn-lg {
            padding: 18px 36px;
            font-size: 1.2rem;
            box-shadow: 5px 5px 0 var(--coffee);
        }
        .btn-lg:hover { box-shadow: 7px 7px 0 var(--coffee); }
        .btn-lg:active { box-shadow: 2px 2px 0 var(--coffee); }

        /* Pleine largeur */
        .btn-block {
            display: flex;
            width: 100%;
        }

        /* Icon uniquement */
        .btn-icon {
            padding: 12px;
            width: auto;
        }

        .card {
            background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .card:hover { transform: translate(-2px, -2px); box-shadow: 7px 7px 0px var(--coffee); }

        .badge { display: inline-flex; align-items: center; padding: 5px 14px; font-family: 'DM Mono', monospace; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; border: 2px solid var(--coffee); }
        .badge-valid   { background: var(--forest); color: var(--cream); }
        .badge-cherry  { background: var(--cherry); color: var(--cream); }
        .badge-waiting { background: var(--wheat);  color: var(--coffee); }
        .badge-teal    { background: var(--teal);   color: var(--cream); }

        .section { border-bottom: var(--border); }
        .section-light { background: var(--cream); }
        .section-wheat { background: var(--wheat); }
        .section-dark  { background: var(--coffee); color: var(--cream); }
        .section-teal  { background: var(--teal); color: var(--cream); }

        .section-inner { max-width: 1200px; margin: 0 auto; padding: 80px 32px; }
        .section-label { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--teal); margin-bottom: 12px; }
        .section-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(2.8rem, 5vw, 4rem); letter-spacing: 0.06em; line-height: 1; margin-bottom: 16px; }
        .section-subtitle { font-size: 1.1rem; color: var(--teal); margin-bottom: 48px; max-width: 540px; }

        .page-container { max-width: 1200px; margin: 0 auto; padding: 48px 32px; }
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: clamp(2.5rem, 5vw, 3.5rem); letter-spacing: 0.06em; line-height: 1; margin-bottom: 8px; }
        .page-subtitle { font-size: 1.05rem; color: var(--teal); margin-bottom: 40px; }

        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 28px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px; }

        @media (max-width: 1024px) {
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
            .grid-3 { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .section-inner { padding: 56px 20px; }
            .page-container { padding: 32px 20px; }
        }

        @yield('styles')
    </style>
</head>
<body>
    @include('partials.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    <script>
        const API_BASE = '{{ config("services.api.url") }}';
        window.APP_API_BASE = API_BASE;
        window.APP = {
            user: localStorage.getItem('auth_token') ? true : null,
            token: localStorage.getItem('auth_token'),
        };
        async function apiFetch(url, options = {}) {
            const token = localStorage.getItem('auth_token');
            options.headers = Object.assign({}, options.headers || {}, {
                'Content-Type': 'application/json',
                ...(token ? { 'Authorization': 'Bearer ' + token } : {}),
            });
            const resp = await fetch(API_BASE + url, options);
            if (resp.status === 401) { localStorage.removeItem('auth_token'); }
            return resp;
        }
    </script>
    <script src="/js/panier.js"></script>
    @yield('scripts')

<!-- Overlay tutoriel -->
<div id="tuto-overlay" style="display:none;position:fixed;inset:0;background:rgba(18,3,9,0.82);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:var(--cream);border:3px solid var(--coffee);box-shadow:8px 8px 0 var(--coffee);padding:48px;max-width:580px;width:90%;position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
            <span id="tuto-step-num" style="font-family:'DM Mono',monospace;font-size:0.8rem;text-transform:uppercase;color:var(--forest);font-weight:700;"></span>
            <button onclick="passerTutoriel()" style="font-family:'DM Mono',monospace;font-size:0.75rem;text-transform:uppercase;background:none;border:none;cursor:pointer;color:#888;text-decoration:underline;">Passer le tutoriel</button>
        </div>
        <div id="tuto-icone" style="font-size:3rem;margin-bottom:16px;"></div>
        <h2 id="tuto-titre" style="font-family:'Bebas Neue',sans-serif;font-size:2.2rem;color:var(--coffee);margin:0 0 16px;"></h2>
        <p id="tuto-contenu" style="font-size:1.05rem;color:#444;line-height:1.7;margin-bottom:32px;"></p>
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div id="tuto-dots" style="display:flex;gap:8px;"></div>
            <div style="display:flex;gap:12px;">
                <button id="tuto-prev" onclick="tutoNav(-1)" style="display:none;font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:0.1em;background:var(--cream);color:var(--coffee);border:3px solid var(--coffee);padding:10px 24px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">← Précédent</button>
                <button id="tuto-next" onclick="tutoNav(1)" style="font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:0.1em;background:var(--forest);color:var(--cream);border:3px solid var(--coffee);padding:10px 24px;cursor:pointer;box-shadow:3px 3px 0 var(--coffee);">Suivant →</button>
            </div>
        </div>
    </div>
</div>

<script>
(async function() {
    const token = localStorage.getItem('uc_token') || localStorage.getItem('auth_token');
    if (!token) return;
    const force = sessionStorage.getItem('force_tutoriel');
    try {
        const r = await fetch('{{ config("services.api.url") }}/api/v1/tutoriel/statut', { headers: { 'Authorization': 'Bearer ' + token } });
        if (!r.ok) return;
        const statut = await r.json();
        if (!force && (statut.tuto_vu || statut.termine || statut.passe)) return;
        sessionStorage.removeItem('force_tutoriel');
        const re = await fetch('{{ config("services.api.url") }}/api/v1/tutoriel/etapes', { headers: { 'Authorization': 'Bearer ' + token } });
        if (!re.ok) return;
        const etapes = await re.json();
        if (!etapes || !etapes.length) return;
        lancerTutoriel(etapes, token);
    } catch(e) {}
})();

let _tutoEtapes = [], _tutoIdx = 0, _tutoToken = '';

function lancerTutoriel(etapes, token) {
    _tutoEtapes = etapes; _tutoIdx = 0; _tutoToken = token;
    afficherEtape(0);
    document.getElementById('tuto-overlay').style.display = 'flex';
}

function afficherEtape(idx) {
    const e = _tutoEtapes[idx];
    if (!e) return;
    document.getElementById('tuto-step-num').textContent = 'Étape ' + e.ordre + ' / ' + _tutoEtapes.length;
    document.getElementById('tuto-icone').textContent = e.icone || '';
    document.getElementById('tuto-titre').textContent = e.titre;
    document.getElementById('tuto-contenu').textContent = e.contenu;
    document.getElementById('tuto-prev').style.display = idx > 0 ? 'inline-block' : 'none';
    const nextBtn = document.getElementById('tuto-next');
    if (idx === _tutoEtapes.length - 1) {
        nextBtn.textContent = 'Terminer ✓';
        nextBtn.onclick = terminerTutoriel;
    } else {
        nextBtn.textContent = 'Suivant →';
        nextBtn.onclick = () => tutoNav(1);
    }
    const dots = document.getElementById('tuto-dots');
    dots.innerHTML = _tutoEtapes.map((_, i) => `<div style="width:10px;height:10px;border-radius:50%;background:${i===idx?'var(--forest)':'rgba(18,3,9,0.2)'};"></div>`).join('');
}

function tutoNav(dir) {
    _tutoIdx = Math.max(0, Math.min(_tutoEtapes.length - 1, _tutoIdx + dir));
    afficherEtape(_tutoIdx);
}

async function terminerTutoriel() {
    await fetch('{{ config("services.api.url") }}/api/v1/tutoriel/termine', { method: 'POST', headers: { 'Authorization': 'Bearer ' + _tutoToken } });
    document.getElementById('tuto-overlay').style.display = 'none';
}

async function passerTutoriel() {
    await fetch('{{ config("services.api.url") }}/api/v1/tutoriel/passer', { method: 'POST', headers: { 'Authorization': 'Bearer ' + _tutoToken } });
    document.getElementById('tuto-overlay').style.display = 'none';
}

function setLang(lang) {
    localStorage.setItem('uc_lang', lang);
    document.documentElement.lang = lang;
    const fr = document.getElementById('lang-fr');
    const en = document.getElementById('lang-en');
    if (fr) fr.style.background = lang === 'fr' ? 'var(--forest)' : '';
    if (fr) fr.style.color = lang === 'fr' ? 'white' : '';
    if (en) en.style.background = lang === 'en' ? 'var(--forest)' : '';
    if (en) en.style.color = lang === 'en' ? 'white' : '';
}
// Initialise lang switcher state
(function() {
    const lang = localStorage.getItem('uc_lang') || 'fr';
    setLang(lang);
})();
</script>
</body>
</html>

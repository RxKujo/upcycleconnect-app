<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mon Espace') — UpcycleConnect</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --cherry: #A4243B;
            --wheat: #D8C99B;
            --coffee: #120309;
            --forest: #244F26;
            --teal: #18607D;
            --cream: #F5F0E1;
            --shadow: 5px 5px 0px #120309;
            --shadow-sm: 3px 3px 0px #120309;
            --shadow-hover: 2px 2px 0px #120309;
            --border: 3px solid #120309;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background-color: var(--cream); font-family: 'Outfit', sans-serif; color: var(--coffee); min-height: 100vh; }
        .font-bebas { font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; }
        .font-mono { font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.05em; }

        /* ===== Top-bar : le site public ===== */
        .topbar { background: var(--coffee); color: var(--cream); padding: 14px 32px; display: flex; justify-content: space-between; align-items: center; border-bottom: var(--border); gap: 24px; flex-wrap: wrap; }
        .topbar-brand { font-family: 'Bebas Neue', sans-serif; font-size: 1.7rem; letter-spacing: 0.12em; color: var(--wheat); text-decoration: none; white-space: nowrap; }
        .topbar-brand span { color: var(--cream); }
        .topbar-nav { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .topbar-nav a { color: var(--cream); text-decoration: none; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.05em; padding: 7px 13px; border: 2px solid transparent; opacity: 0.85; }
        .topbar-nav a:hover { opacity: 1; border-color: rgba(245,240,225,0.3); }
        .topbar-right { display: flex; gap: 14px; align-items: center; }
        .topbar-cart { color: var(--cream); text-decoration: none; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.05em; padding: 7px 13px; border: 2px solid rgba(245,240,225,0.3); position: relative; }
        .topbar-cart:hover { border-color: var(--wheat); }
        .topbar-user { font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; color: var(--wheat); }
        .btn-logout { background: var(--cherry); color: var(--cream); border: 2px solid var(--cream); padding: 7px 14px; cursor: pointer; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.78rem; }
        .btn-logout:active { transform: translate(2px, 2px); }

        /* ===== Shell : sidebar « Mon espace » + contenu ===== */
        .espace-shell { display: grid; grid-template-columns: 248px 1fr; align-items: start; max-width: 1320px; margin: 0 auto; }
        .espace-sidebar { position: sticky; top: 0; align-self: start; padding: 32px 0 32px 24px; }
        .side-head { font-family: 'Bebas Neue', sans-serif; font-size: 1.05rem; letter-spacing: 0.12em; color: var(--cherry); padding: 0 16px 14px; border-bottom: 3px solid var(--coffee); margin-bottom: 14px; }
        .side-nav { display: flex; flex-direction: column; gap: 4px; }
        .side-link { display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--coffee); font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.82rem; letter-spacing: 0.04em; padding: 12px 16px; border: 2px solid transparent; }
        .side-link:hover { background: rgba(18,3,9,0.05); }
        .side-link.active { background: var(--coffee); color: var(--cream); box-shadow: var(--shadow-sm); }
        .side-link .ic { width: 9px; height: 9px; border: 2px solid currentColor; flex-shrink: 0; }
        .side-link.active .ic { background: var(--cherry); border-color: var(--cherry); }
        .side-cta { margin: 18px 16px 0; }
        .side-cta a { display: block; text-align: center; background: var(--cherry); color: var(--cream); border: var(--border); box-shadow: var(--shadow-sm); padding: 12px; font-family: 'Bebas Neue', sans-serif; font-size: 1.1rem; letter-spacing: 0.08em; text-decoration: none; }
        .side-cta a:active { transform: translate(3px,3px); box-shadow: var(--shadow-hover); }

        .espace-main { padding: 40px 32px; min-width: 0; }

        /* ===== Composants partagés ===== */
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; text-decoration: none; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--cream); color: var(--coffee); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; text-decoration: none; }
        .btn-success { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--forest); color: var(--cream); border: 3px solid var(--coffee); padding: 10px 24px; font-size: 1.1rem; text-decoration: none; }
        .btn-danger { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); padding: 10px 24px; font-size: 1.1rem; text-decoration: none; }
        .btn-primary:active, .btn-secondary:active, .btn-success:active, .btn-danger:active { transform: translate(3px, 3px); box-shadow: var(--shadow-hover); }
        .btn-sm { padding: 6px 16px; font-size: 1rem; }
        .btn-disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

        .card { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px; margin-bottom: 32px; }

        .form-group { margin-bottom: 24px; }
        .form-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; font-weight: bold; letter-spacing: 0.05em; color: var(--coffee); margin-bottom: 8px; display: block; }
        .form-input, .form-textarea, .form-select { width: 100%; border: 3px solid var(--coffee); background: white; font-family: 'Outfit', sans-serif; font-size: 1rem; padding: 12px 16px; outline: none; box-shadow: 3px 3px 0px rgba(18,3,9,0.1); border-radius: 0; }
        .form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--cherry); box-shadow: 5px 5px 0px rgba(164,36,59,0.2); }
        .form-textarea { resize: vertical; min-height: 120px; }

        .alert { padding: 16px 20px; border: var(--border); margin-bottom: 24px; font-size: 1rem; font-weight: 500; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm); }
        .alert-success { background-color: var(--wheat); color: var(--forest); border-color: var(--forest); }
        .alert-error { background-color: var(--cream); color: var(--cherry); border-color: var(--cherry); }

        .badge { display: inline-flex; align-items: center; padding: 4px 12px; font-family: 'DM Mono', monospace; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; border: 2px solid var(--coffee); border-radius: 0; }
        .badge-valid { background-color: var(--forest); color: var(--cream); }
        .badge-cherry { background-color: var(--cherry); color: var(--cream); }
        .badge-waiting { background-color: var(--wheat); color: var(--coffee); }

        .table-container { width: 100%; overflow-x: auto; border: var(--border); box-shadow: var(--shadow); background: var(--cream); margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: var(--wheat); border-bottom: var(--border); }
        th { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; padding: 12px 16px; text-align: left; }
        td { padding: 10px 16px; border-bottom: 2px solid rgba(18,3,9,0.1); font-size: 0.95rem; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 36px; padding-bottom: 18px; border-bottom: 4px solid var(--coffee); flex-wrap: wrap; gap: 16px; }
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--coffee); letter-spacing: 0.05em; line-height: 1; }

        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--coffee); border: 2px solid var(--coffee); transition: 0.3s; }
        .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 2px; bottom: 2px; background-color: var(--cream); transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background-color: var(--cherry); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }

        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(18,3,9,0.6); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px; max-width: 500px; width: 90%; }
        .modal h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; margin-bottom: 16px; }

        .field-error { color: var(--cherry); font-size: 0.8rem; margin-top: 4px; font-family: 'DM Mono', monospace; }
        .field-valid { color: var(--forest); font-size: 0.8rem; margin-top: 4px; font-family: 'DM Mono', monospace; }
        .loading { text-align: center; padding: 40px; font-family: 'DM Mono', monospace; text-transform: uppercase; color: var(--coffee); }

        /* Responsive : sidebar passe en barre horizontale scrollable */
        @media (max-width: 860px) {
            .espace-shell { grid-template-columns: 1fr; }
            .espace-sidebar { position: static; padding: 20px 16px 0; }
            .side-head { display: none; }
            .side-nav { flex-direction: row; overflow-x: auto; gap: 6px; border-bottom: 3px solid var(--coffee); padding-bottom: 12px; }
            .side-link { white-space: nowrap; border: 2px solid var(--coffee); }
            .side-cta { margin: 14px 0 0; }
            .espace-main { padding: 24px 16px; }
        }
        @media (max-width: 768px) {
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            .page-title { font-size: 2rem; }
        }

    </style>
    @yield('styles')
</head>
<body>
    @include('partials._toast')

    @php
        $isActive = fn($pattern) => request()->is($pattern) ? 'active' : '';
    @endphp

    <nav class="topbar" aria-label="Navigation du site">
        <a href="/" class="topbar-brand">Upcycle<span>Connect</span></a>
        <div class="topbar-nav">
            <a href="/annonces">Marché</a>
            <a href="/evenements">Formations &amp; événements</a>
            <a href="/forum">Forum</a>
            <a href="/ressources">Ressources</a>
        </div>
        <div class="topbar-right">
            <span class="topbar-user" id="topbar-user"></span>
            <button class="btn-logout" onclick="logout()">Déconnexion</button>
        </div>
    </nav>

    <div class="espace-shell">
        <aside class="espace-sidebar">
            <div class="side-head">Mon espace</div>
            <nav class="side-nav" aria-label="Mon espace particulier">
                <a href="{{ route('particulier.dashboard') }}" class="side-link {{ request()->routeIs('particulier.dashboard') ? 'active' : '' }}"><span class="ic"></span> Tableau de bord</a>
                <a href="{{ route('particulier.annonces.index') }}" class="side-link {{ $isActive('particulier/annonces*') }}"><span class="ic"></span> Mes annonces</a>
                <a href="{{ route('particulier.formations.index') }}" class="side-link {{ $isActive('particulier/formations*') }}"><span class="ic"></span> Mes formations</a>
                <a href="{{ route('particulier.profile.show') }}" class="side-link {{ $isActive('particulier/profile*') }}"><span class="ic"></span> Profil &amp; paramètres</a>
            </nav>
            <div class="side-cta">
                <a href="{{ route('particulier.annonces.create') }}">+ Déposer une annonce</a>
            </div>
        </aside>

        <main class="espace-main">
            <div id="alert-container"></div>
            @yield('content')
        </main>
    </div>

    <script>
        const API_BASE = '{{ config("services.api.public_url") }}';

        function getToken() {
            return localStorage.getItem('auth_token');
        }

        function logout() {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }

        function showAlert(message, type = 'success') {
            const container = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = 'alert alert-' + type;
            alert.innerHTML = (type === 'success' ? '<span style="font-size:1.2rem;">OK</span> ' : '<span style="font-size:1.2rem;">!</span> ') + message;
            container.prepend(alert);
            setTimeout(() => alert.remove(), 5000);
        }

        async function apiFetch(url, options = {}) {
            const token = getToken();
            if (!token) {
                window.location.href = '/login';
                return null;
            }
            const defaultHeaders = {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            };
            options.headers = { ...defaultHeaders, ...options.headers };
            const response = await fetch(API_BASE + url, options);
            if (response.status === 401) {
                localStorage.removeItem('auth_token');
                window.location.href = '/login';
                return null;
            }
            return response;
        }

        // Prénom dans la top-bar
        (async function () {
            try {
                const r = await apiFetch('/api/v1/utilisateurs/me');
                if (r && r.ok) {
                    const u = await r.json();
                    const el = document.getElementById('topbar-user');
                    if (el) el.textContent = (u.prenom || '') + (u.prenom ? ' ' : '') + (u.nom ? u.nom[0] + '.' : '');
                }
            } catch (e) { /* silencieux */ }
        })();
    </script>
    @yield('scripts')
    @include('partials.datepicker')
</body>
</html>

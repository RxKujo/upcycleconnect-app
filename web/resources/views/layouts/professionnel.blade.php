<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Espace Pro') — UpcycleConnect</title>
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

        /* Navbar */
        .navbar { background: var(--coffee); color: var(--cream); padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; border-bottom: var(--border); }
        .navbar-brand { font-family: 'Bebas Neue', sans-serif; font-size: 1.8rem; letter-spacing: 0.12em; color: var(--wheat); text-decoration: none; }
        .navbar-brand span { color: var(--cream); }
        .navbar-badge { display: inline-flex; align-items: center; padding: 2px 10px; background: var(--cherry); color: var(--cream); font-family: 'DM Mono', monospace; font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.05em; border: 2px solid var(--cream); margin-left: 8px; vertical-align: middle; }
        .navbar-links { display: flex; gap: 24px; align-items: center; }
        .navbar-links a { color: var(--cream); text-decoration: none; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.05em; padding: 8px 16px; border: 2px solid transparent; }
        .navbar-links a:hover, .navbar-links a.active { border-color: var(--cherry); color: var(--wheat); }
        .navbar-links .btn-logout { background: var(--cherry); color: var(--cream); border: 2px solid var(--cream); padding: 8px 16px; cursor: pointer; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; }
        .navbar-links .btn-logout:active { transform: translate(2px, 2px); }

        /* Main */
        .main-content { max-width: 1200px; margin: 0 auto; padding: 48px 24px; }

        /* Buttons */
        .btn-primary { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; text-decoration: none; }
        .btn-secondary { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); border-radius: 0; background-color: var(--cream); color: var(--coffee); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; text-decoration: none; }
        .btn-primary:active, .btn-secondary:active { transform: translate(3px, 3px); box-shadow: var(--shadow-hover); }
        .btn-sm { padding: 6px 16px; font-size: 1rem; }
        .btn-disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

        /* Cards */
        .card { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 32px; margin-bottom: 32px; }

        /* Forms */
        .form-group { margin-bottom: 24px; }
        .form-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.85rem; font-weight: bold; letter-spacing: 0.05em; color: var(--coffee); margin-bottom: 8px; display: block; }
        .form-input, .form-textarea, .form-select { width: 100%; border: 3px solid var(--coffee); background: white; font-family: 'Outfit', sans-serif; font-size: 1rem; padding: 12px 16px; outline: none; box-shadow: 3px 3px 0px rgba(18,3,9,0.1); border-radius: 0; }
        .form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--cherry); box-shadow: 5px 5px 0px rgba(164,36,59,0.2); }

        /* Alerts */
        .alert { padding: 16px 20px; border: var(--border); margin-bottom: 24px; font-size: 1rem; font-weight: 500; display: flex; align-items: center; gap: 12px; box-shadow: var(--shadow-sm); }
        .alert-success { background-color: var(--wheat); color: var(--forest); border-color: var(--forest); }
        .alert-error { background-color: var(--cream); color: var(--cherry); border-color: var(--cherry); }

        /* Badge */
        .badge { display: inline-flex; align-items: center; padding: 4px 12px; font-family: 'DM Mono', monospace; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; border: 2px solid var(--coffee); border-radius: 0; }
        .badge-valid { background-color: var(--forest); color: var(--cream); }
        .badge-cherry { background-color: var(--cherry); color: var(--cream); }
        .badge-waiting { background-color: var(--wheat); color: var(--coffee); }

        /* Table */
        .table-container { width: 100%; overflow-x: auto; border: var(--border); box-shadow: var(--shadow); background: var(--cream); margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: var(--wheat); border-bottom: var(--border); }
        th { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; padding: 12px 16px; text-align: left; }
        td { padding: 10px 16px; border-bottom: 2px solid rgba(18,3,9,0.1); font-size: 0.95rem; }

        /* Page header */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 4px solid var(--coffee); }
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 2.5rem; color: var(--coffee); letter-spacing: 0.05em; line-height: 1; }

        /* Toggle switch */
        .toggle-switch { position: relative; display: inline-block; width: 52px; height: 28px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--coffee); border: 2px solid var(--coffee); transition: 0.3s; }
        .toggle-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 2px; bottom: 2px; background-color: var(--cream); transition: 0.3s; }
        .toggle-switch input:checked + .toggle-slider { background-color: var(--cherry); }
        .toggle-switch input:checked + .toggle-slider:before { transform: translateX(24px); }

        /* Loading */
        .loading { text-align: center; padding: 40px; font-family: 'DM Mono', monospace; text-transform: uppercase; color: var(--coffee); }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 12px; }
            .main-content { padding: 24px 16px; }
            .page-title { font-size: 2rem; }
        }

        @yield('styles')
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="/" class="navbar-brand">Upcycle<span>Connect</span> <span class="navbar-badge">Pro</span></a>
        <div class="navbar-links">
            <a href="/annonces" class="{{ request()->is('annonces*') ? 'active' : '' }}">Marche</a>
            <a href="/evenements" class="{{ request()->is('evenements*') ? 'active' : '' }}">Evenements</a>
            <a href="/forum" class="{{ request()->is('forum*') ? 'active' : '' }}">Forum</a>
            <a href="/mes-commandes" class="{{ request()->is('mes-commandes*') ? 'active' : '' }}">Mes commandes</a>
            <a href="/panier" class="{{ request()->is('panier*') ? 'active' : '' }}" style="position:relative;">
                Panier
                <span id="nav-cart-count" style="display:none; position:absolute; top:-6px; right:-10px; background:var(--cherry); color:var(--cream); min-width:18px; height:18px; padding:0 5px; border-radius:9px; font-family:'DM Mono',monospace; font-size:0.65rem; align-items:center; justify-content:center; border:1px solid var(--cream);">0</span>
            </a>
            <a href="/professionnel/profile" class="{{ request()->is('professionnel/profile*') ? 'active' : '' }}">Mon profil</a>
            <button class="btn-logout" onclick="logout()">Deconnexion</button>
        </div>
    </nav>

    <div class="main-content">
        <div id="alert-container"></div>
        @yield('content')
    </div>

    <script>
        const API_BASE = 'http://localhost:8888';

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
    </script>
    <script src="/js/panier.js"></script>
    @yield('scripts')
</body>
</html>

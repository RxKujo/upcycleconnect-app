<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — UpcycleConnect</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        body { background-color: var(--cream); font-family: 'Outfit', sans-serif; margin: 0; color: var(--coffee); }
        .font-bebas { font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; }
        .font-mono { font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.05em; }
        .font-playfair { font-family: 'Playfair Display', serif; }

        .admin-wrapper { display: flex; min-height: 100vh; }
        
        /* Sidebar styling */
        .sidebar { width: 280px; height: 100vh; overflow-y: auto; overflow-x: hidden; background-color: var(--coffee); color: var(--cream); position: fixed; left: 0; top: 0; display: flex; flex-direction: column; z-index: 100; border-right: var(--border);}
        .sidebar::-webkit-scrollbar { width: 8px; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--cherry); }
        .sidebar::-webkit-scrollbar-track { background: rgba(245,240,225,0.08); }
        .sidebar-header { padding: 32px 24px 24px; border-bottom: 3px solid var(--cherry); background-color: rgba(0,0,0,0.2); }
        .sidebar-nav { flex-grow: 1; padding-top: 24px; }
        .sidebar a { display: flex; align-items: center; padding: 14px 24px 14px 20px; color: var(--cream); text-decoration: none; font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.9rem; font-weight: 500; letter-spacing: 0.05em; border-left: 6px solid transparent; transition: all 0.2s ease; }
        .sidebar a:hover, .sidebar a.active { border-left-color: var(--cherry); background-color: rgba(164, 36, 59, 0.15); color: var(--wheat); padding-left: 26px; }
        .sidebar-footer { padding: 24px; border-top: 3px solid rgba(245,240,225,0.1); }
        .sidebar-section-label { font-family: 'DM Mono', monospace; font-size: 0.62rem; color: rgba(245,240,225,0.4); padding: 22px 24px 8px; letter-spacing: 0.12em; text-transform: uppercase; margin: 0; }
        .sidebar-badge { background: var(--cherry); color: var(--cream); font-size: 0.7rem; padding: 2px 7px; border-radius: 10px; font-family: 'DM Mono', monospace; font-weight: 700; }
        
        /* Main Layout */
        .main-content { margin-left: 280px; padding: 48px; width: calc(100% - 280px); box-sizing: border-box; }
        .content-container { max-width: 1400px; margin: 0 auto; }

        /* Buttons */
        .btn-primary, .btn-secondary, .btn-danger, .btn-success { display: inline-flex; align-items: center; justify-content: center; font-family: 'Bebas Neue', sans-serif; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; box-shadow: var(--shadow-sm); transition: all 0.2s ease; text-decoration: none; box-sizing: border-box; border-radius: 0; }
        .btn-primary { background-color: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; }
        .btn-secondary { background-color: var(--cream); color: var(--coffee); border: 3px solid var(--coffee); padding: 12px 28px; font-size: 1.2rem; }
        .btn-danger { background-color: var(--cherry); color: var(--cream); border: 3px solid var(--coffee); padding: 8px 20px; font-size: 1rem; }
        .btn-success { background-color: var(--forest); color: var(--cream); border: 3px solid var(--coffee); padding: 8px 20px; font-size: 1rem; }
        .btn-sm { padding: 6px 16px; font-size: 1rem; }
        .btn-primary:active, .btn-secondary:active, .btn-danger:active, .btn-success:active { transform: translate(3px, 3px); box-shadow: var(--shadow-hover); }

        /* Tables */
        .table-container { width: 100%; overflow-x: auto; border: var(--border); box-shadow: var(--shadow); background: var(--cream); margin-bottom: 32px; }
        table { width: 100%; border-collapse: collapse; min-width: 900px; }
        thead { background-color: var(--wheat); border-bottom: var(--border); }
        th { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.9rem; letter-spacing: 0.05em; padding: 18px 24px; text-align: left; color: var(--coffee); font-weight: 700; }
        td { padding: 16px 24px; border-bottom: 2px solid rgba(18, 3, 9, 0.1); font-size: 1.05rem; vertical-align: middle; color: var(--coffee); }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: rgba(216, 201, 155, 0.15); }
        .action-cell { display: flex; gap: 12px; align-items: center; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; font-family: 'DM Mono', monospace; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; border: 2px solid var(--coffee); border-radius: 0; box-shadow: 2px 2px 0px rgba(18,3,9,0.3); }
        .badge-waiting { background-color: var(--wheat); color: var(--coffee); }
        .badge-valid { background-color: var(--forest); color: var(--cream); }
        .badge-refused { background-color: var(--cherry); color: var(--cream); }

        /* Cards & Forms — pas d'effet d'enfoncement (non cliquable) */
        .card { background: var(--cream); border: var(--border); box-shadow: var(--shadow); padding: 40px; margin-bottom: 32px; }

        .form-group { margin-bottom: 28px; }
        .form-label { font-family: 'DM Mono', monospace; text-transform: uppercase; font-size: 0.9rem; font-weight: bold; letter-spacing: 0.05em; color: var(--coffee); margin-bottom: 10px; display: block; }
        .form-input, .form-textarea, .form-select { width: 100%; border: 3px solid var(--coffee); background: white; font-family: 'Outfit', sans-serif; font-size: 1.1rem; padding: 14px 18px; outline: none; transition: border-color 0.15s ease, box-shadow 0.15s ease; box-shadow: 3px 3px 0px rgba(18,3,9,0.1); box-sizing: border-box; border-radius: 0; }
        .form-input:focus, .form-textarea:focus, .form-select:focus { border-color: var(--cherry); box-shadow: 4px 4px 0px rgba(164,36,59,0.25); }
        .form-textarea { resize: vertical; min-height: 150px; }

        /* Alerts */
        .alert { padding: 18px 24px; border: var(--border); margin-bottom: 32px; font-size: 1.1rem; font-weight: 500; display: flex; align-items: center; gap: 16px; box-shadow: var(--shadow-sm); font-family: 'DM Mono', monospace; text-transform: uppercase; letter-spacing: 0.02em;}
        .alert-success { background-color: var(--wheat); color: var(--forest); border-color: var(--forest); }
        .alert-error { background-color: #f8d7da; color: var(--cherry); border-color: var(--cherry); }

        /* Page Layout Utilities */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 48px; padding-bottom: 24px; border-bottom: 4px solid var(--coffee); }
        .page-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; color: var(--coffee); margin: 0; letter-spacing: 0.05em; line-height: 1; text-shadow: 2px 2px 0px rgba(216, 201, 155, 0.5); }
        
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 40px; }
        .info-item { margin-bottom: 0; }
        .info-item.full-width { grid-column: 1 / -1; }
        .info-label { font-family: 'DM Mono', monospace; font-size: 0.85rem; font-weight: bold; color: var(--cherry); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 10px; border-bottom: 2px solid rgba(164,36,59,0.2); padding-bottom: 4px; display: inline-block;}
        .info-value { font-size: 1.2rem; color: var(--coffee); margin: 0; font-weight: 500; line-height: 1.5; }
        .info-value-large { font-size: 1.8rem; color: var(--coffee); margin: 0; font-weight: 700; font-family: 'Playfair Display', serif; }
    </style>
</head>
<body>
    @include('partials._toast')
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1 class="font-bebas" style="font-size: 2rem; margin: 0; color: var(--wheat); letter-spacing: 0.06em; line-height: 0.95;">Upcycle<span style="color: var(--cream); display: block;">Connect</span></h1>
                <span class="font-mono" style="font-size: 0.75rem; color: var(--cherry); font-weight: bold; margin-top: 8px; display: block;">Panel Administrateur</span>
            </div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->is('admin') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Tableau de bord
                </a>

                <p class="sidebar-section-label">À valider</p>
                <a href="{{ route('admin.annonces.index') }}" class="{{ request()->is('admin/annonces*') ? 'active' : '' }}" style="justify-content:space-between;">
                    <span><span style="margin-right: 12px; font-size: 1.2em;">◆</span> Annonces</span>
                    <span id="badge-annonces" class="sidebar-badge" style="display:none;"></span>
                </a>
                <a href="{{ route('admin.evenements.index') }}" class="{{ request()->is('admin/evenements*') ? 'active' : '' }}" style="justify-content:space-between;">
                    <span><span style="margin-right: 12px; font-size: 1.2em;">◆</span> Événements</span>
                    <span id="badge-evenements" class="sidebar-badge" style="display:none;"></span>
                </a>
                <a href="{{ route('admin.publicites.index') }}" class="{{ request()->is('admin/publicites*') ? 'active' : '' }}" style="justify-content:space-between;">
                    <span><span style="margin-right: 12px; font-size: 1.2em;">◆</span> Publicités</span>
                    <span id="badge-publicites" class="sidebar-badge" style="display:none;"></span>
                </a>

                <p class="sidebar-section-label">Catalogue & contenu</p>
                <a href="{{ route('admin.categories.index') }}" class="{{ request()->is('admin/categories') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Catégories prestations
                </a>
                <a href="{{ route('admin.categories-objets.index') }}" class="{{ request()->is('admin/categories-objets*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Catégories d'objets
                </a>
                <a href="{{ route('admin.materiaux.index') }}" class="{{ request()->is('admin/materiaux*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Matériaux
                </a>
                <a href="{{ route('admin.templates.index') }}" class="{{ request()->is('admin/templates*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Modèles d'événements
                </a>
                <a href="{{ route('admin.tutoriel.index') }}" class="{{ request()->is('admin/tutoriel*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Tutoriel
                </a>
                <a href="{{ route('salarie.idees.index') }}" class="{{ request()->is('salarie/idees*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Boîte à idées
                </a>

                <p class="sidebar-section-label">Logistique</p>
                <a href="{{ route('admin.commandes.index') }}" class="{{ request()->is('admin/commandes*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Commandes
                </a>
                <a href="{{ route('admin.conteneurs.index') }}" class="{{ request()->is('admin/conteneurs*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Conteneurs
                </a>
                <a href="{{ route('admin.depot.index') }}" class="{{ request()->is('admin/depot*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Dépôts conteneur
                </a>

                <p class="sidebar-section-label">Communauté</p>
                <a href="{{ route('admin.utilisateurs.index') }}" class="{{ request()->is('admin/utilisateurs*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Utilisateurs
                </a>
                <a href="{{ route('admin.abonnements.index') }}" class="{{ request()->is('admin/abonnements*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Abonnements
                </a>
                <a href="{{ route('admin.scores.index') }}" class="{{ request()->is('admin/scores*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Upcycling Score
                </a>

                <p class="sidebar-section-label">Système & finances</p>
                <a href="{{ route('admin.finances.index') }}" class="{{ request()->is('admin/finances*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Pilotage financier
                </a>
                <a href="{{ route('admin.notifications.index') }}" class="{{ request()->is('admin/notifications*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Notifications
                </a>
                <a href="{{ route('admin.langues.index') }}" class="{{ request()->is('admin/langues*') ? 'active' : '' }}">
                    <span style="margin-right: 12px; font-size: 1.2em;">◆</span> Langues & Trad.
                </a>
            </nav>
            <div class="sidebar-footer">
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary" style="width: 100%; text-align: center; justify-content: center; padding: 10px; font-size: 1.1rem; border-color: var(--wheat);">
                        Déconnexion
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-content">
            <div class="content-container">
                @if(session('success'))
                    <div class="alert alert-success">
                        <span style="font-size: 1.4rem;">✓</span> {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">
                        <span style="font-size: 1.4rem;">⚠</span> {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
<script>
(async function() {
    try {
        const r = await fetch('{{ config("services.api.public_url") }}/api/v1/admin/stats', {
            headers: { 'Authorization': 'Bearer {{ session("admin_token") }}' }
        });
        if (!r.ok) return;
        const d = await r.json();
        if (d.annonces_en_attente > 0) {
            const b = document.getElementById('badge-annonces');
            if (b) { b.textContent = d.annonces_en_attente; b.style.display = 'inline'; }
        }
        if (d.evenements_en_attente > 0) {
            const b = document.getElementById('badge-evenements');
            if (b) { b.textContent = d.evenements_en_attente; b.style.display = 'inline'; }
        }
        if (d.publicites_en_attente > 0) {
            const b = document.getElementById('badge-publicites');
            if (b) { b.textContent = d.publicites_en_attente; b.style.display = 'inline'; }
        }
    } catch(e) {}
})();
</script>
</body>
</html>

<nav class="navbar-public" id="navbar" role="navigation" aria-label="Navigation principale">
    <div class="nav-inner">
        
        <a href="{{ route('home') }}" class="nav-brand" aria-label="UpcycleConnect — Accueil">
            <span class="nav-brand-logo" aria-hidden="true"></span>
            <span class="nav-brand-text">UpcycleConnect</span>
        </a>

        <ul class="nav-links-public">
            <li><a href="{{ route('marche.index') }}" class="{{ request()->routeIs('marche.*') ? 'active' : '' }}">Marché</a></li>
            <li><a href="{{ route('evenements.index') }}" class="{{ request()->routeIs('evenements.*') ? 'active' : '' }}">Événements</a></li>
            <li><a href="{{ route('conseils.index') }}" class="{{ request()->routeIs('conseils.*') ? 'active' : '' }}">Conseils</a></li>
            <li><a href="{{ route('forum.index') }}" class="{{ request()->routeIs('forum.*') ? 'active' : '' }}">Forum</a></li>
        </ul>

        <div class="auth-wrapper" id="auth-wrapper">
            <a href="{{ route('particulier.register') }}" class="nav-btn nav-btn-ghost" id="nav-register-btn">Inscription</a>
            <a href="{{ route('particulier.login') }}" class="nav-btn nav-btn-primary" id="nav-login-btn">Connexion</a>

            <div id="nav-user-menu" class="nav-user-menu">
                <button id="nav-user-btn" class="nav-user-btn" aria-expanded="false" aria-label="Menu utilisateur">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                    </svg>
                    <span id="nav-user-name">Compte</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
                <div id="nav-user-dropdown" class="nav-user-dropdown">
                    <a href="/particulier/profile">Mon espace</a>
                    <a href="/particulier/annonces/create">Déposer une annonce</a>
                    <a href="/particulier/profile#score">Mon Upcycling Score</a>
                    <button id="nav-logout-btn">Déconnexion</button>
                </div>
            </div>
        </div>

        <button class="nav-burger" id="nav-burger" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>

    <div class="nav-mobile" id="nav-mobile">
        <a href="{{ route('marche.index') }}">Marché</a>
        <a href="{{ route('evenements.index') }}">Événements</a>
        <a href="{{ route('conseils.index') }}">Conseils</a>
        <a href="{{ route('forum.index') }}">Forum</a>
        <a href="{{ route('services-pro') }}">Services Pro</a>
        <a href="{{ route('a-propos') }}">À propos</a>
        <div class="nav-mobile-auth" id="nav-mobile-auth">
            <a href="{{ route('particulier.register') }}" class="nav-btn nav-btn-ghost">Inscription</a>
            <a href="{{ route('particulier.login') }}" class="nav-btn nav-btn-primary">Connexion</a>
        </div>
    </div>
</nav>

<style>
    .navbar-public {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 200;
        height: var(--nav-height);
        background: var(--wheat);
        border-bottom: var(--border);
        box-shadow: 0 2px 0 var(--coffee);
        transition: box-shadow 0.2s;
    }
    .navbar-public.scrolled {
        box-shadow: 0 4px 0 var(--cherry);
    }
    .nav-inner {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 32px;
        height: 100%;
        display: flex;
        align-items: center;
        gap: 32px;
    }

    /* Brand */
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--coffee);
        flex-shrink: 0;
        transition: transform 0.15s;
    }
    .nav-brand:hover { transform: translateY(-1px); }
    .nav-brand-logo {
        width: 34px;
        height: 34px;
        background: var(--cherry);
        border: 2px solid var(--coffee);
        flex-shrink: 0;
        position: relative;
    }
    .nav-brand-logo::after {
        content: '';
        position: absolute;
        inset: 6px;
        border: 2px solid var(--cream);
    }
    .nav-brand-text {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 1.55rem;
        letter-spacing: 0.08em;
        color: var(--coffee);
    }

    /* Liens centraux */
    .nav-links-public {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 1;
        justify-content: center;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .nav-links-public a {
        font-family: 'DM Mono', monospace;
        font-size: 0.78rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--coffee);
        padding: 8px 18px;
        border: 2px solid transparent;
        transition: all 0.15s;
        position: relative;
    }
    .nav-links-public a:hover {
        color: var(--cherry);
    }
    .nav-links-public a.active {
        color: var(--cherry);
    }
    .nav-links-public a.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 18px;
        right: 18px;
        height: 3px;
        background: var(--cherry);
    }

    /* Zone auth */
    .auth-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
        visibility: hidden;
        flex-shrink: 0;
    }
    /* Boutons auth : même mécanique que .btn global, deux variantes */
    .nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-family: 'DM Mono', monospace;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 10px 22px;
        border: 2px solid var(--coffee);
        cursor: pointer;
        white-space: nowrap;
        box-shadow: 3px 3px 0 var(--coffee);
        transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease, color 0.12s ease;
        line-height: 1;
        text-decoration: none;
    }
    .nav-btn:hover {
        transform: translate(-1px, -1px);
        box-shadow: 4px 4px 0 var(--coffee);
    }
    .nav-btn:active {
        transform: translate(2px, 2px);
        box-shadow: 1px 1px 0 var(--coffee);
    }
    /* Variante Inscription : cream (secondaire) */
    .nav-btn-ghost {
        background: var(--cream);
        color: var(--coffee);
    }
    .nav-btn-ghost:hover {
        background: var(--coffee);
        color: var(--cream);
    }
    /* Variante Connexion : cherry (primaire) */
    .nav-btn-primary {
        background: var(--cherry);
        color: var(--cream);
    }

    /* Menu utilisateur */
    .nav-user-menu {
        display: none;
        position: relative;
    }
    .nav-user-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--cream);
        border: 2px solid var(--coffee);
        color: var(--coffee);
        padding: 9px 14px;
        font-family: 'DM Mono', monospace;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 3px 3px 0 var(--coffee);
        transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease, color 0.12s ease;
        line-height: 1;
    }
    .nav-user-btn:hover {
        transform: translate(-1px, -1px);
        box-shadow: 4px 4px 0 var(--coffee);
    }
    .nav-user-btn:active,
    .nav-user-btn[aria-expanded="true"] {
        transform: translate(2px, 2px);
        box-shadow: 1px 1px 0 var(--coffee);
        background: var(--coffee);
        color: var(--cream);
    }
    .nav-user-dropdown {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        background: var(--cream);
        border: 3px solid var(--coffee);
        box-shadow: 5px 5px 0 var(--coffee);
        display: none;
        flex-direction: column;
        min-width: 220px;
        z-index: 300;
    }
    .nav-user-dropdown.open { display: flex; }
    .nav-user-dropdown a,
    .nav-user-dropdown button {
        font-family: 'DM Mono', monospace;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--coffee);
        padding: 14px 18px;
        border: none;
        border-bottom: 2px solid var(--coffee);
        background: var(--cream);
        cursor: pointer;
        text-align: left;
        transition: background 0.15s;
    }
    .nav-user-dropdown a:hover { background: var(--wheat); }
    .nav-user-dropdown button {
        background: var(--cherry);
        color: var(--cream);
        font-weight: 700;
        border-bottom: none;
    }
    .nav-user-dropdown button:hover { background: var(--coffee); }

    /* Burger mobile */
    .nav-burger {
        display: none;
        background: transparent;
        border: 2px solid var(--coffee);
        padding: 10px;
        cursor: pointer;
        flex-direction: column;
        gap: 4px;
    }
    .nav-burger span {
        display: block;
        width: 22px;
        height: 2.5px;
        background: var(--coffee);
        transition: all 0.2s;
    }
    .nav-burger[aria-expanded="true"] span:nth-child(1) { transform: translateY(6.5px) rotate(45deg); }
    .nav-burger[aria-expanded="true"] span:nth-child(2) { opacity: 0; }
    .nav-burger[aria-expanded="true"] span:nth-child(3) { transform: translateY(-6.5px) rotate(-45deg); }

    /* Menu mobile */
    .nav-mobile {
        display: none;
        position: fixed;
        top: var(--nav-height);
        left: 0;
        right: 0;
        background: var(--cream);
        border-bottom: var(--border);
        flex-direction: column;
        padding: 16px 24px 24px;
        gap: 4px;
    }
    .nav-mobile.open { display: flex; }
    .nav-mobile a {
        font-family: 'DM Mono', monospace;
        font-size: 0.85rem;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--coffee);
        padding: 14px 8px;
        border-bottom: 2px solid rgba(18,3,9,0.1);
    }
    .nav-mobile-auth {
        display: flex;
        gap: 10px;
        margin-top: 16px;
    }
    .nav-mobile-auth .nav-btn { flex: 1; }

    /* Responsive */
    @media (max-width: 900px) {
        .nav-links-public, .auth-wrapper { display: none !important; }
        .nav-burger { display: flex; }
        .nav-inner { padding: 0 20px; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('auth_token');
    const wrapper = document.getElementById('auth-wrapper');
    const registerBtn = document.getElementById('nav-register-btn');
    const loginBtn = document.getElementById('nav-login-btn');
    const userMenu = document.getElementById('nav-user-menu');
    const userBtn = document.getElementById('nav-user-btn');
    const dropdown = document.getElementById('nav-user-dropdown');
    const logoutBtn = document.getElementById('nav-logout-btn');
    const userName = document.getElementById('nav-user-name');

    if (token) {
        registerBtn.style.display = 'none';
        loginBtn.style.display = 'none';
        userMenu.style.display = 'block';

        try {
            const payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
            userName.textContent = payload.prenom || 'Compte';
        } catch(e) {
            userName.textContent = 'Compte';
        }

        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = dropdown.classList.toggle('open');
            userBtn.setAttribute('aria-expanded', open);
        });
        document.addEventListener('click', (e) => {
            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('open');
                userBtn.setAttribute('aria-expanded', 'false');
            }
        });
        logoutBtn.addEventListener('click', () => {
            localStorage.removeItem('auth_token');
            window.location.href = '/';
        });
    }

    wrapper.style.visibility = 'visible';

    // Shadow on scroll
    const nav = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        nav.classList.toggle('scrolled', window.scrollY > 30);
    }, { passive: true });

    // Burger mobile
    const burger = document.getElementById('nav-burger');
    const mobile = document.getElementById('nav-mobile');
    const mobileAuth = document.getElementById('nav-mobile-auth');

    if (token && mobileAuth) mobileAuth.style.display = 'none';

    burger.addEventListener('click', () => {
        const open = mobile.classList.toggle('open');
        burger.setAttribute('aria-expanded', open);
    });
});
</script>

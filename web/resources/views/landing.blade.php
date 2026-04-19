<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UpcycleConnect — Réduisez les déchets. Vendez vos objets. Trouvez des matériaux. Rejoignez la communauté écologique.">
    <title>UpcycleConnect — Réduisez les déchets. Valorisez les matériaux.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@400;500&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* =============================================
           DESIGN TOKENS
        ============================================= */
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

        /* =============================================
           RESET
        ============================================= */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            background-color: var(--cream);
            font-family: 'Outfit', sans-serif;
            color: var(--coffee);
            line-height: 1.6;
        }
        ul { list-style: none; }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }

        /* =============================================
           TYPOGRAPHY HELPERS
        ============================================= */
        .font-bebas {
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .font-mono {
            font-family: 'DM Mono', monospace;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* =============================================
           COMPONENT: BUTTONS  (x-btn)
        ============================================= */
        .btn-primary,
        .btn-secondary,
        .btn-success {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            border-radius: 0;
            border: var(--border);
            box-shadow: var(--shadow-sm);
            padding: 13px 32px;
            font-size: 1.15rem;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
            white-space: nowrap;
        }
        .btn-primary  { background: var(--cherry); color: var(--cream); }
        .btn-secondary{ background: var(--cream);  color: var(--coffee); }
        .btn-success  { background: var(--forest); color: var(--cream); }
        .btn-sm       { padding: 8px 20px; font-size: 1rem; }

        .btn-primary:hover,
        .btn-secondary:hover,
        .btn-success:hover {
            transform: translate(-2px, -2px);
            box-shadow: var(--shadow);
        }
        .btn-primary:active,
        .btn-secondary:active,
        .btn-success:active {
            transform: translate(3px, 3px);
            box-shadow: var(--shadow-hover);
        }

        /* =============================================
           COMPONENT: CARD  (x-card)
        ============================================= */
        .card {
            background: var(--cream);
            border: var(--border);
            box-shadow: var(--shadow);
            padding: 32px;
            margin-bottom: 0;
        }

        /* =============================================
           COMPONENT: BADGE  (x-badge)
        ============================================= */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 5px 14px;
            font-family: 'DM Mono', monospace;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 2px solid var(--coffee);
            border-radius: 0;
        }
        .badge-valid   { background: var(--forest); color: var(--cream); }
        .badge-cherry  { background: var(--cherry); color: var(--cream); }
        .badge-waiting { background: var(--wheat);  color: var(--coffee); }

        /* =============================================
           LAYOUT: SECTIONS
        ============================================= */
        .section { border-bottom: var(--border); }
        .section-light { background: var(--cream); }
        .section-wheat { background: var(--wheat); }

        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 96px 32px;
        }

        .section-label {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--teal);
            margin-bottom: 12px;
        }
        .section-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.8rem, 5vw, 4rem);
            letter-spacing: 0.06em;
            line-height: 1;
            color: var(--coffee);
            margin-bottom: 16px;
        }
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--teal);
            margin-bottom: 56px;
            max-width: 540px;
        }

        /* =============================================
           NAVBAR
        ============================================= */
        .landing-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 200;
            height: var(--nav-height);
            background: var(--coffee);
            border-bottom: var(--border);
            transition: box-shadow 0.2s;
        }
        .landing-nav.scrolled {
            box-shadow: 0 4px 0 var(--cherry);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 32px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.7rem;
            letter-spacing: 0.1em;
            color: var(--cream);
        }
        .nav-brand-logo {
            width: 32px;
            height: 32px;
            background: var(--cherry);
            border: 2px solid var(--cream);
            flex-shrink: 0;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            justify-content: center;
        }
        .nav-links a {
            font-family: 'DM Mono', monospace;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--cream);
            padding: 8px 16px;
            border: 2px solid transparent;
            transition: border-color 0.15s, color 0.15s;
        }
        .nav-links a:hover {
            border-color: var(--cherry);
            color: var(--wheat);
        }

        /* =============================================
           USER DROPDOWN
        ============================================= */
        .auth-wrapper {
            position: relative;
        }
        #auth-login-btn:hover,
        #auth-register-btn:hover {
            transform: none;
            box-shadow: var(--shadow-sm);
        }
        .user-menu-btn {
            background: transparent;
            border: 2px solid transparent;
            color: var(--cream);
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .user-menu-btn:hover,
        .user-menu-btn[aria-expanded="true"] {
            background: var(--cherry);
            border-color: var(--cherry);
            color: var(--cream);
        }
        .user-dropdown {
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            background: var(--cream);
            border: 3px solid var(--coffee);
            box-shadow: var(--shadow);
            display: none;
            flex-direction: column;
            min-width: 180px;
            z-index: 300;
        }
        .user-dropdown.active {
            display: flex;
        }
        .user-dropdown a, 
        .user-dropdown button {
            font-family: 'DM Mono', monospace;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--coffee);
            padding: 12px 16px;
            text-align: left;
            background: var(--cream);
            border: none;
            border-bottom: 2px solid var(--coffee);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .user-dropdown a:last-child,
        .user-dropdown button:last-child {
            border-bottom: none;
        }
        .user-dropdown a:hover {
            background: var(--wheat);
            color: var(--coffee);
        }
        .user-dropdown button#logout-btn {
            background: var(--cherry);
            color: var(--cream);
            font-weight: bold;
        }
        .user-dropdown button#logout-btn:hover {
            background: var(--coffee);
            color: var(--wheat);
        }

        /* =============================================
           HERO
        ============================================= */
        .hero {
            background: var(--cream);
            border-bottom: var(--border);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: var(--nav-height);
        }
        .hero-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 80px 32px;
            text-align: center;
        }
        .hero-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(4rem, 10vw, 8rem);
            letter-spacing: 0.06em;
            line-height: 1;
            color: var(--coffee);
            margin-bottom: 28px;
        }
        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.25rem);
            color: var(--teal);
            font-weight: 500;
            line-height: 1.7;
            margin-bottom: 48px;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }
        .hero-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* =============================================
           SERVICES
        ============================================= */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            margin-top: 56px;
        }
        .service-icon {
            width: 44px;
            height: 44px;
            border: var(--border);
            margin-bottom: 20px;
            flex-shrink: 0;
        }
        .service-type {
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--coffee);
            opacity: 0.6;
            margin-bottom: 8px;
        }
        .service-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            letter-spacing: 0.06em;
            color: var(--coffee);
            margin-bottom: 14px;
        }
        .service-desc {
            font-size: 0.95rem;
            color: var(--coffee);
            opacity: 0.75;
            line-height: 1.6;
        }
        .services-grid .card {
            display: flex;
            flex-direction: column;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .services-grid .card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 8px 8px 0px var(--coffee);
        }

        /* =============================================
           STATS
        ============================================= */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            margin-top: 48px;
        }
        .stat-box {
            border: var(--border);
            box-shadow: var(--shadow);
            background: var(--cream);
            padding: 36px 28px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .stat-number {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            letter-spacing: 0.04em;
            line-height: 1;
            color: var(--coffee);
        }
        .stat-label {
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--teal);
        }

        /* =============================================
           HOW IT WORKS
        ============================================= */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
            margin-top: 56px;
            border: var(--border);
            box-shadow: var(--shadow);
        }
        .step {
            padding: 36px 28px;
            border-right: var(--border);
            background: var(--cream);
        }
        .step:last-child { border-right: none; }
        .step-number {
            display: block;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3rem;
            color: var(--cherry);
            letter-spacing: 0.04em;
            line-height: 1;
            margin-bottom: 16px;
        }
        .step-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4rem;
            letter-spacing: 0.06em;
            color: var(--coffee);
            margin-bottom: 10px;
        }
        .step-desc {
            font-size: 0.9rem;
            color: var(--coffee);
            opacity: 0.7;
            line-height: 1.6;
        }

        /* =============================================
           PRICING
        ============================================= */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 28px;
            margin-top: 0;
            align-items: start;
        }
        .pricing-card-wrapper {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        .pricing-badge-row {
            display: flex;
            justify-content: center;
            margin-bottom: -2px;
            position: relative;
            z-index: 1;
        }
        .pricing-card-wrapper .card {
            display: flex;
            flex-direction: column;
        }
        .pricing-name {
            font-family: 'DM Mono', monospace;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--coffee);
            margin-bottom: 16px;
        }
        .pricing-price {
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-bottom: 28px;
        }
        .pricing-amount {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3.5rem;
            letter-spacing: 0.04em;
            line-height: 1;
            color: var(--coffee);
        }
        .pricing-period {
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--coffee);
            opacity: 0.6;
        }
        .pricing-features {
            list-style: none;
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            flex: 1;
        }
        .pricing-features li {
            font-size: 0.95rem;
            padding-left: 24px;
            position: relative;
            color: var(--coffee);
        }
        .pricing-features li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: var(--forest);
            font-weight: 700;
        }
        .pricing-features li.featured::before { color: var(--cherry); }
        .pricing-cta { width: 100%; }

        /* =============================================
           CTA BANNER
        ============================================= */
        .cta-banner {
            background: var(--teal);
            border-bottom: var(--border);
        }
        .cta-inner {
            text-align: center;
            padding-top: 80px;
            padding-bottom: 80px;
        }
        .cta-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: clamp(3rem, 6vw, 5.5rem);
            letter-spacing: 0.06em;
            line-height: 1;
            color: var(--cream);
            margin-bottom: 20px;
        }
        .cta-subtitle {
            font-size: 1.1rem;
            color: rgba(245, 240, 225, 0.85);
            margin-bottom: 40px;
        }

        /* =============================================
           FOOTER
        ============================================= */
        .footer {
            background: var(--coffee);
            color: var(--cream);
        }
        .footer-inner {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            padding-bottom: 64px;
        }
        .footer-logo {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.8rem;
            letter-spacing: 0.1em;
            color: var(--wheat);
            display: block;
            margin-bottom: 14px;
        }
        .footer-tagline {
            font-size: 0.9rem;
            color: rgba(245, 240, 225, 0.6);
            line-height: 1.6;
            max-width: 260px;
        }
        .footer-col-title {
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--wheat);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(245, 240, 225, 0.15);
        }
        .footer-col ul {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .footer-col ul li a {
            font-size: 0.9rem;
            color: rgba(245, 240, 225, 0.6);
            transition: color 0.15s;
        }
        .footer-col ul li a:hover {
            color: var(--cream);
        }
        .footer-bottom {
            border-top: 2px solid rgba(245, 240, 225, 0.1);
            padding: 20px 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-bottom p {
            font-family: 'DM Mono', monospace;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(245, 240, 225, 0.35);
        }

        /* =============================================
           RESPONSIVE
        ============================================= */
        @media (max-width: 1024px) {
            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .step:nth-child(2) { border-right: none; }
            .step:nth-child(1),
            .step:nth-child(2) { border-bottom: var(--border); }
            .footer-inner {
                grid-template-columns: 1fr 1fr;
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .nav-links  { display: none; }
            .section-inner { padding: 64px 20px; }
            .hero-inner { padding: 60px 20px; }

            .services-grid,
            .stats-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
            }
            .steps-grid {
                grid-template-columns: 1fr;
            }
            .step { border-right: none; border-bottom: var(--border); }
            .step:last-child { border-bottom: none; }

            .footer-inner {
                grid-template-columns: 1fr;
                gap: 32px;
            }
            .footer-bottom { padding: 20px; }
        }
    </style>
</head>
<body>

    <nav class="landing-nav" id="landing-nav" role="navigation" aria-label="Navigation principale">
        <div class="nav-inner">
            <a href="/" class="nav-brand" aria-label="UpcycleConnect — Accueil">
                <span class="nav-brand-logo" aria-hidden="true"></span>
                UpcycleConnect
            </a>

            <div class="nav-links">
                <a href="#services">Services</a>
                <a href="#abonnements">Abonnements</a>
                <a href="#about">À Propos</a>
            </div>

            <div class="auth-wrapper" id="auth-wrapper" style="visibility: hidden; display: flex; gap: 12px; align-items: center;">
                
                <x-btn id="auth-register-btn" variant="secondary" size="sm" href="{{ route('particulier.register') }}">
                    Inscription
                </x-btn>
                
                <x-btn id="auth-login-btn" variant="primary" size="sm" href="{{ route('particulier.login') }}">
                    Connexion
                </x-btn>

                <div id="auth-user-menu" style="display: none;">
                    <button class="user-menu-btn" id="user-menu-btn" aria-label="Menu utilisateur" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                        </svg>
                    </button>
                    <div class="user-dropdown" id="user-dropdown">
                        <a href="/particulier/profile">Mon profil</a>
                        <a href="/particulier/profile#annonces">Mes annonces</a>
                        <button id="logout-btn">Déconnexion</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero section-light" id="hero" aria-labelledby="hero-title">
        <div class="hero-inner">
            <h1 class="hero-title" id="hero-title">Upcyclez Ensemble</h1>

            <p class="hero-subtitle">
                Réduisez les déchets. Vendez vos objets. Trouvez des matériaux.<br>
                Rejoignez la communauté écologique.
            </p>

            <div class="hero-actions">
                <x-btn variant="primary" href="{{ route('particulier.register') }}">
                    Commencer
                </x-btn>
                <x-btn variant="secondary" href="#services">
                    En savoir plus
                </x-btn>
            </div>
        </div>
    </section>

    <section class="section section-light" id="services" aria-labelledby="services-title">
        <div class="section-inner">
            <p class="section-label">Ce que nous offrons</p>
            <h2 class="section-title" id="services-title">Nos Services</h2>
            <p class="section-subtitle">Trois espaces pensés pour chaque acteur de l'upcycling</p>

            <div class="services-grid">

                <x-card>
                    <div class="service-icon" style="background: var(--cherry);" aria-hidden="true"></div>
                    <p class="service-type">Particulier</p>
                    <h3 class="service-name">Donnez &amp; Vendez</h3>
                    <p class="service-desc">
                        Donnez une seconde vie à vos objets et matériaux en les proposant à la communauté.
                    </p>
                </x-card>

                <x-card>
                    <div class="service-icon" style="background: var(--forest);" aria-hidden="true"></div>
                    <p class="service-type">Professionnel</p>
                    <h3 class="service-name">Trouvez des Matériaux</h3>
                    <p class="service-desc">
                        Accédez à un flux constant de matériaux recyclables pour alimenter vos projets de création.
                    </p>
                </x-card>

                <x-card>
                    <div class="service-icon" style="background: var(--teal);" aria-hidden="true"></div>
                    <p class="service-type">Communauté</p>
                    <h3 class="service-name">Upcycling Score</h3>
                    <p class="service-desc">
                        Gagnez des points à chaque échange, montez en niveau et débloquez des badges exclusifs.
                    </p>
                </x-card>

            </div>
        </div>
    </section>

    <section class="section section-wheat" id="impact" aria-labelledby="impact-title">
        <div class="section-inner">
            <p class="section-label">Notre impact</p>
            <h2 class="section-title" id="impact-title">En Chiffres</h2>

            <div class="stats-grid" role="list">

                <div class="stat-box" role="listitem">
                    <span class="stat-number" aria-label="10 000 plus">10 000+</span>
                    <span class="stat-label">Objets sauvés des déchets</span>
                </div>

                <div class="stat-box" role="listitem">
                    <span class="stat-number" aria-label="2 500 plus">2 500+</span>
                    <span class="stat-label">Artisans &amp; particuliers actifs</span>
                </div>

                <div class="stat-box" role="listitem">
                    <span class="stat-number">48T</span>
                    <span class="stat-label">De CO₂ évité cette année</span>
                </div>

            </div>
        </div>
    </section>

    <section class="section section-light" id="how-it-works" aria-labelledby="how-title">
        <div class="section-inner">
            <p class="section-label">Simple &amp; Efficace</p>
            <h2 class="section-title" id="how-title">Comment ça marche</h2>

            <ol class="steps-grid" aria-label="Étapes pour rejoindre UpcycleConnect">

                <li class="step">
                    <span class="step-number" aria-hidden="true">01</span>
                    <h3 class="step-title">Créez votre compte</h3>
                    <p class="step-desc">Particulier ou Pro, inscrivez-vous en quelques clics.</p>
                </li>

                <li class="step">
                    <span class="step-number" aria-hidden="true">02</span>
                    <h3 class="step-title">Déposez ou cherchez</h3>
                    <p class="step-desc">Publiez vos objets ou trouvez des matériaux dans le catalogue.</p>
                </li>

                <li class="step">
                    <span class="step-number" aria-hidden="true">03</span>
                    <h3 class="step-title">Échangez</h3>
                    <p class="step-desc">Conteneur ou main propre, choisissez votre mode de livraison.</p>
                </li>

                <li class="step">
                    <span class="step-number" aria-hidden="true">04</span>
                    <h3 class="step-title">Gagnez des points</h3>
                    <p class="step-desc">Votre Upcycling Score augmente. Débloquez badges et récompenses.</p>
                </li>

            </ol>
        </div>
    </section>

    <section class="section section-light" id="abonnements" aria-labelledby="pricing-title">
        <div class="section-inner">
            <p class="section-label">Tarifs</p>
            <h2 class="section-title" id="pricing-title">Abonnements</h2>
            <p class="section-subtitle" style="margin-bottom: 48px;">Une offre adaptée à chaque profil</p>

            <div class="pricing-grid">

                <div class="pricing-card-wrapper">
                    <div class="pricing-badge-row" aria-hidden="true" style="visibility:hidden;">
                        <x-badge variant="cherry">Populaire</x-badge>
                    </div>
                    <x-card>
                        <p class="pricing-name">Gratuit</p>
                        <div class="pricing-price">
                            <span class="pricing-amount">0€</span>
                            <span class="pricing-period">/mois</span>
                        </div>
                        <ul class="pricing-features" aria-label="Fonctionnalités incluses">
                            <li>Dépôt d'annonces illimité</li>
                            <li>Accès au catalogue</li>
                            <li>Espace conseil &amp; news</li>
                            <li>Upcycling Score</li>
                        </ul>
                        <x-btn variant="secondary" href="{{ route('particulier.register') }}" class="pricing-cta">
                            Commencer
                        </x-btn>
                    </x-card>
                </div>

                <div class="pricing-card-wrapper">
                    <div class="pricing-badge-row" aria-label="Plan populaire">
                        <x-badge variant="cherry">Populaire</x-badge>
                    </div>
                    <x-card :danger="true">
                        <p class="pricing-name">Eco-Citizen</p>
                        <div class="pricing-price">
                            <span class="pricing-amount">10,99€</span>
                            <span class="pricing-period">/mois</span>
                        </div>
                        <ul class="pricing-features" aria-label="Fonctionnalités incluses">
                            <li class="featured">Dépôt d'annonces illimité</li>
                            <li class="featured">Accès au catalogue</li>
                            <li class="featured">Espace conseil &amp; news</li>
                            <li class="featured">Upcycling Score</li>
                        </ul>
                        <x-btn variant="primary" href="{{ route('particulier.register') }}" class="pricing-cta">
                            S'abonner
                        </x-btn>
                    </x-card>
                </div>

                <div class="pricing-card-wrapper">
                    <div class="pricing-badge-row" aria-hidden="true" style="visibility:hidden;">
                        <x-badge variant="cherry">Populaire</x-badge>
                    </div>
                    <x-card style="border-color: var(--forest);">
                        <p class="pricing-name">Essential Pro</p>
                        <div class="pricing-price">
                            <span class="pricing-amount">15,99€</span>
                            <span class="pricing-period">/mois</span>
                        </div>
                        <ul class="pricing-features" aria-label="Fonctionnalités incluses">
                            <li>Dépôt d'annonces illimité</li>
                            <li>Accès au catalogue</li>
                            <li>Espace conseil &amp; news</li>
                            <li>Upcycling Score</li>
                        </ul>
                        <x-btn variant="success" href="{{ route('particulier.register') }}" class="pricing-cta">
                            S'abonner
                        </x-btn>
                    </x-card>
                </div>

            </div>
        </div>
    </section>

    <section class="cta-banner" id="about" aria-labelledby="cta-title">
        <div class="section-inner cta-inner">
            <h2 class="cta-title" id="cta-title">Prêt à transformer ?</h2>
            <p class="cta-subtitle">
                Rejoignez la communauté UpcycleConnect et participez à la révolution.
            </p>
            <x-btn variant="secondary" href="{{ route('particulier.register') }}">
                Créer un compte
            </x-btn>
        </div>
    </section>

    <footer class="footer" role="contentinfo">
        <div class="section-inner footer-inner">

            <div class="footer-brand">
                <a href="/" class="footer-logo">UpcycleConnect</a>
                <p class="footer-tagline">
                    Réduire les déchets, valoriser les matériaux,<br>connecter les communautés.
                </p>
            </div>

            <nav class="footer-col" aria-label="Plateforme">
                <p class="footer-col-title">Plateforme</p>
                <ul>
                    <li><a href="#">Catalogue</a></li>
                    <li><a href="#">Événements</a></li>
                    <li><a href="#">Formations</a></li>
                    <li><a href="#">Conteneurs</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Entreprise">
                <p class="footer-col-title">Entreprise</p>
                <ul>
                    <li><a href="#">À Propos</a></li>
                    <li><a href="#">Contact</a></li>
                    <li><a href="#">Carrières</a></li>
                    <li><a href="#">Presse</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Légal">
                <p class="footer-col-title">Légal</p>
                <ul>
                    <li><a href="#">Mentions Légales</a></li>
                    <li><a href="#">Confidentialité</a></li>
                    <li><a href="#">CGU</a></li>
                    <li><a href="#">RGPD</a></li>
                </ul>
            </nav>

        </div>
        <div class="footer-bottom">
            <p>© 2026 UpcycleConnect — Digital Worm Mission 1 | Design System v1.0 | Neo Brutalism Vintage</p>
        </div>
    </footer>

    <script>
        // Navbar: ombre au scroll
        (function () {
            const nav = document.getElementById('landing-nav');
            const onScroll = () => nav.classList.toggle('scrolled', window.scrollY > 40);
            window.addEventListener('scroll', onScroll, { passive: true });
        })();

        // Auth state check
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('auth_token');
            const authWrapper = document.getElementById('auth-wrapper');
            const loginBtn = document.getElementById('auth-login-btn');
            const registerBtn = document.getElementById('auth-register-btn');
            const userMenu = document.getElementById('auth-user-menu');
            const userMenuBtn = document.getElementById('user-menu-btn');
            const userDropdown = document.getElementById('user-dropdown');
            const logoutBtn = document.getElementById('logout-btn');

            if (token) {
                if (loginBtn) loginBtn.style.display = 'none';
                if (registerBtn) registerBtn.style.display = 'none';
                if (userMenu) userMenu.style.display = 'block';

                // Dropdown handler
                if (userMenuBtn && userDropdown) {
                    userMenuBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isExpanded = userMenuBtn.getAttribute('aria-expanded') === 'true';
                        userMenuBtn.setAttribute('aria-expanded', !isExpanded);
                        userDropdown.classList.toggle('active');
                    });

                    // Close when clicking outside
                    document.addEventListener('click', (e) => {
                        if (!userMenu.contains(e.target)) {
                            userMenuBtn.setAttribute('aria-expanded', 'false');
                            userDropdown.classList.remove('active');
                        }
                    });
                }

                // Logout
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', () => {
                        localStorage.removeItem('auth_token');
                        window.location.reload();
                    });
                }
            } else {
                if (loginBtn) loginBtn.style.display = 'inline-flex';
                if (userMenu) userMenu.style.display = 'none';
            }

            // Reveal component to prevent flicker
            if (authWrapper) {
                authWrapper.style.visibility = 'visible';
            }
        });
    </script>

</body>
</html>

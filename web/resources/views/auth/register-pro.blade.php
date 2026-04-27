<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Professionnel — UpcycleConnect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@400;500&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--cream);
            font-family: 'Outfit', sans-serif;
            color: var(--coffee);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--coffee);
            text-decoration: none;
            margin-bottom: 12px;
            opacity: 0.6;
            transition: opacity 0.15s;
            align-self: flex-start;
        }
        .back-link:hover { opacity: 1; }
        .back-link svg { flex-shrink: 0; }

        .auth-container {
            width: 100%;
            max-width: 720px;
            margin: 0 auto;
        }

        .auth-card {
            background: var(--cream);
            border: var(--border);
            box-shadow: var(--shadow);
            padding: 40px 44px;
        }

        .auth-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.8rem;
            color: var(--coffee);
            margin-bottom: 8px;
            letter-spacing: 0.1em;
            line-height: 1;
            text-transform: uppercase;
        }

        .auth-subtitle {
            font-family: 'DM Mono', monospace;
            font-size: 0.85rem;
            color: var(--cherry);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 28px;
        }

        .alert {
            padding: 16px 20px;
            border: var(--border);
            margin-bottom: 28px;
            font-size: 1rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--cherry);
            border-color: var(--cherry);
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--forest);
            border-color: var(--forest);
        }

        .alert-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-content {
            flex: 1;
        }

        .alert-error-item {
            margin-bottom: 8px;
        }

        .alert-error-item:last-child {
            margin-bottom: 0;
        }

        .section-divider {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--cherry);
            margin: 28px 0 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--coffee);
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group.required .form-label::after {
            content: ' *';
            color: var(--cherry);
            font-weight: bold;
        }

        .form-label {
            font-family: 'DM Mono', monospace;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            color: var(--coffee);
            margin-bottom: 10px;
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
                margin-bottom: 0;
            }

            .form-row .form-group {
                margin-bottom: 20px;
            }
        }

        .form-input,
        .form-textarea {
            width: 100%;
            border: 3px solid var(--coffee);
            background: white;
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            padding: 10px 14px;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 3px 3px 0px rgba(18, 3, 9, 0.1);
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--cherry);
            box-shadow: 0 0 0 2px var(--cherry), 5px 5px 0px rgba(164, 36, 59, 0.2);
            transform: translate(-2px, -2px);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #999;
            opacity: 0.8;
        }

        .form-input.error,
        .form-textarea.error {
            border-color: var(--cherry);
            box-shadow: 3px 3px 0px rgba(164, 36, 59, 0.2);
        }

        .error-message {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
            color: var(--cherry);
            margin-top: 6px;
            display: none;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .form-input.error ~ .error-message,
        .form-textarea.error ~ .error-message {
            display: block;
        }

        .siret-status {
            font-family: 'DM Mono', monospace;
            font-size: 0.8rem;
            margin-top: 6px;
            display: none;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .siret-status.checking {
            display: block;
            color: var(--teal);
        }

        .siret-status.valid {
            display: block;
            color: var(--forest);
        }

        .siret-status.invalid {
            display: block;
            color: var(--cherry);
        }

        .btn-submit {
            width: 100%;
            background-color: var(--cherry);
            color: var(--cream);
            border: var(--border);
            padding: 16px 28px;
            font-size: 1.3rem;
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
            transition: all 0.15s ease;
            font-weight: 600;
        }

        .btn-submit:active:not(:disabled) {
            transform: translate(3px, 3px);
            box-shadow: var(--shadow-hover);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-submit.loading {
            position: relative;
            color: transparent;
        }

        .spinner {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--cream);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .auth-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 1rem;
            color: var(--coffee);
        }

        .auth-footer-text {
            margin-bottom: 8px;
        }

        .auth-link {
            color: var(--teal);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
        }

        .auth-link:hover {
            border-bottom-color: var(--teal);
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--coffee);
            font-size: 1.2rem;
            padding: 8px;
            display: none;
        }

        .password-group:has(input:focus) .password-toggle,
        .password-group:has(input:not(:placeholder-shown)) .password-toggle {
            display: block;
        }

        .recaptcha-container {
            margin: 24px 0;
            display: flex;
            justify-content: center;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(245, 240, 225, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        @media (max-width: 600px) {
            .auth-card {
                padding: 32px 20px;
            }

            .auth-title {
                font-size: 2.2rem;
                margin-bottom: 24px;
            }
        }

        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-dropdown {
            display: none;
            position: absolute;
            top: calc(100% - 3px);
            left: 0;
            right: 0;
            background: white;
            border: 3px solid var(--coffee);
            border-top: none;
            box-shadow: 5px 5px 0px rgba(18, 3, 9, 0.15);
            z-index: 100;
            max-height: 220px;
            overflow-y: auto;
        }

        .autocomplete-item {
            padding: 10px 14px;
            cursor: pointer;
            font-size: 0.95rem;
            border-bottom: 1px solid rgba(18, 3, 9, 0.1);
            transition: background 0.1s;
        }

        .autocomplete-item:last-child { border-bottom: none; }

        .autocomplete-item:hover {
            background: var(--wheat);
        }

        #adresseAutoGroup.has-error #adresseSearch {
            border-color: var(--cherry);
            box-shadow: 3px 3px 0px rgba(164, 36, 59, 0.2);
        }

        #adresseAutoGroup.has-error .error-message {
            display: block;
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="auth-container">
        <a href="{{ route('home') }}" class="back-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
            </svg>
            Retour
        </a>

        <div class="auth-card">
            <h1 class="auth-title">Inscription Professionnel</h1>
            <div class="auth-subtitle">Artisans & Entreprises</div>

            <div id="alertContainer"></div>

            <form id="registerProForm" method="POST" action="http://localhost:8888/api/v1/auth/register" novalidate>
                <input type="hidden" name="role" value="professionnel">

                <div class="section-divider">Informations personnelles</div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="prenom" class="form-label">Prenom</label>
                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            class="form-input"
                            placeholder="Votre prenom"
                            required
                        >
                        <div class="error-message">Le prenom est obligatoire</div>
                    </div>

                    <div class="form-group required">
                        <label for="nom" class="form-label">Nom</label>
                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            class="form-input"
                            placeholder="Votre nom"
                            required
                        >
                        <div class="error-message">Le nom est obligatoire</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="contact@entreprise.com"
                            required
                        >
                        <div class="error-message">Veuillez entrer une adresse email valide</div>
                    </div>

                    <div class="form-group">
                        <label for="telephone" class="form-label">Telephone (optionnel)</label>
                        <input
                            type="tel"
                            id="telephone"
                            name="telephone"
                            class="form-input"
                            placeholder="+33 6 12 34 56 78"
                        >
                        <div class="error-message">Veuillez entrer un numero de telephone valide</div>
                    </div>
                </div>

                <div class="section-divider">Informations entreprise</div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="nom_entreprise" class="form-label">Nom de l'entreprise</label>
                        <input
                            type="text"
                            id="nom_entreprise"
                            name="nom_entreprise"
                            class="form-input"
                            placeholder="Nom de votre entreprise"
                            required
                        >
                        <div class="error-message">Le nom de l'entreprise est obligatoire</div>
                    </div>

                    <div class="form-group required">
                        <label for="numero_siret" class="form-label">Numero SIRET</label>
                        <input
                            type="text"
                            id="numero_siret"
                            name="numero_siret"
                            class="form-input"
                            placeholder="123 456 789 00012"
                            maxlength="17"
                            required
                        >
                        <div class="error-message">Numero SIRET invalide (14 chiffres requis)</div>
                        <div class="siret-status" id="siretStatus"></div>
                    </div>
                </div>

                <div class="form-group required" id="adresseAutoGroup">
                    <label for="adresseSearch" class="form-label">Adresse entreprise</label>
                    <div class="autocomplete-wrapper">
                        <input
                            type="text"
                            id="adresseSearch"
                            class="form-input"
                            placeholder="Ex: 10 Rue de la Paix, Paris..."
                            autocomplete="off"
                        >
                        <div class="autocomplete-dropdown" id="adresseSuggestions"></div>
                    </div>
                    <div class="error-message">Veuillez sélectionner une adresse dans la liste</div>
                    <input type="hidden" name="adresse_complete" id="adresse_complete">
                    <input type="hidden" name="ville" id="ville">
                    <input type="hidden" name="code_postal" id="code_postal">
                </div>

                <div id="adresseFallback" style="display:none">
                    <div class="form-row">
                        <div class="form-group required">
                            <label for="ville_manual" class="form-label">Ville</label>
                            <input type="text" id="ville_manual" name="ville_manual" class="form-input" placeholder="Ville de l'entreprise">
                            <div class="error-message">La ville est obligatoire</div>
                        </div>
                        <div class="form-group">
                            <label for="adresse_manual" class="form-label">Adresse (optionnel)</label>
                            <input type="text" id="adresse_manual" name="adresse_manual" class="form-input" placeholder="123 Rue de...">
                            <div class="error-message">L'adresse est trop longue (max 255 caracteres)</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="code_postal_manual" class="form-label">Code postal (optionnel)</label>
                        <input type="text" id="code_postal_manual" name="code_postal_manual" class="form-input" placeholder="75001">
                    </div>
                </div>

                <div class="section-divider">Securite</div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <div class="password-group">
                            <input
                                type="password"
                                id="mot_de_passe"
                                name="mot_de_passe"
                                class="form-input"
                                placeholder="Minimum 8 caracteres"
                                required
                            >
                            <button type="button" class="password-toggle" data-target="mot_de_passe" aria-label="Afficher/masquer le mot de passe">
                                <span class="toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg></span>
                            </button>
                        </div>
                        <div class="error-message">Le mot de passe doit contenir au moins 8 caracteres</div>
                    </div>

                    <div class="form-group required">
                        <label for="password_confirmation" class="form-label">Confirmation</label>
                        <div class="password-group">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-input"
                                placeholder="Confirmez votre mot de passe"
                                required
                            >
                            <button type="button" class="password-toggle" data-target="password_confirmation" aria-label="Afficher/masquer le mot de passe">
                                <span class="toggle-icon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg></span>
                            </button>
                        </div>
                        <div class="error-message">Les mots de passe ne correspondent pas</div>
                    </div>
                </div>

                <div class="recaptcha-container">
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key', '') }}"></div>
                </div>

                <button type="submit" class="btn-submit">Creer mon compte professionnel</button>
            </form>

            <div class="auth-footer">
                <div class="auth-footer-text">
                    Vous etes un particulier? <a href="/register" class="auth-link">Inscription particulier</a>
                </div>
                <div class="auth-footer-text">
                    Deja inscrit? <a href="/login" class="auth-link">Se connecter</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('registerProForm');
        const alertContainer = document.getElementById('alertContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const passwordToggles = document.querySelectorAll('.password-toggle');
        const siretStatus = document.getElementById('siretStatus');

        // Validation patterns
        const patterns = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^[\d\s+()-]+$|^$/,
            password: /.{8,}/,
            siret: /^\d{14}$/,
        };

        // Password toggle functionality
        passwordToggles.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const eyeIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>`;
                const eyeSlashIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/><path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"/></svg>`;
                button.innerHTML = `<span class="toggle-icon">${isPassword ? eyeSlashIcon : eyeIcon}</span>`;
            });
        });

        // SIRET field: format as user types and validate on blur
        let siretCheckTimeout = null;
        const siretInput = document.getElementById('numero_siret');

        siretInput.addEventListener('input', () => {
            siretInput.value = siretInput.value.replace(/[^\d\s]/g, '');
        });

        siretInput.addEventListener('keypress', (e) => {
            if (!/[\d\s]/.test(e.key)) {
                e.preventDefault();
            }
        });

        siretInput.addEventListener('blur', () => {
            const raw = siretInput.value.replace(/\s/g, '');
            if (raw.length === 0) {
                siretStatus.className = 'siret-status';
                siretInput.classList.remove('error');
                return;
            }

            if (!patterns.siret.test(raw)) {
                siretInput.classList.add('error');
                siretStatus.className = 'siret-status invalid';
                siretStatus.textContent = 'Le SIRET doit contenir exactement 14 chiffres';
                return;
            }

            siretInput.classList.remove('error');
            siretStatus.className = 'siret-status checking';
            siretStatus.textContent = 'Verification en cours...';

            clearTimeout(siretCheckTimeout);
            siretCheckTimeout = setTimeout(async () => {
                try {
                    const resp = await fetch(`https://recherche-entreprises.api.gouv.fr/search?q=${raw}`);
                    const data = await resp.json();
                    if (data.total_results > 0) {
                        siretStatus.className = 'siret-status valid';
                        const name = data.results[0]?.nom_complet || '';
                        siretStatus.textContent = name ? `SIRET verifie — ${name}` : 'SIRET verifie';
                    } else {
                        siretStatus.className = 'siret-status invalid';
                        siretStatus.textContent = 'SIRET non trouve dans la base INSEE';
                    }
                } catch {
                    siretStatus.className = 'siret-status';
                    siretStatus.textContent = '';
                }
            }, 300);
        });

        let fallbackActive = false;

        // Live validation
        const validateField = (field) => {
            const value = field.value.trim();
            let isValid = true;

            switch (field.name) {
                case 'nom':
                case 'prenom':
                case 'nom_entreprise':
                    isValid = value.length > 0;
                    break;
                case 'email':
                    isValid = value.length === 0 || patterns.email.test(value);
                    break;
                case 'telephone':
                    isValid = patterns.phone.test(value);
                    break;
                case 'ville_manual':
                    isValid = !fallbackActive || value.length > 0;
                    break;
                case 'adresse_manual':
                    isValid = value.length <= 255;
                    break;
                case 'numero_siret':
                    const raw = value.replace(/\s/g, '');
                    isValid = raw.length === 0 || patterns.siret.test(raw);
                    break;
                case 'mot_de_passe':
                    isValid = value.length >= 8 || value.length === 0;
                    break;
                case 'password_confirmation':
                    const pwd = document.getElementById('mot_de_passe').value;
                    isValid = value === pwd;
                    break;
            }

            if (!isValid) {
                field.classList.add('error');
            } else {
                field.classList.remove('error');
            }

            return isValid;
        };

        // Add live validation to inputs
        form.querySelectorAll('.form-input').forEach(field => {
            if (field.name !== 'numero_siret') {
                field.addEventListener('blur', () => validateField(field));
                field.addEventListener('change', () => validateField(field));
            }
        });

        // Cross-field validation for password confirmation
        document.getElementById('mot_de_passe').addEventListener('change', () => {
            validateField(document.getElementById('password_confirmation'));
        });

        // Display alert messages
        const showAlert = (message, type = 'error', details = null) => {
            alertContainer.innerHTML = '';

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;

            const icon = document.createElement('div');
            icon.className = 'alert-icon';
            const icons = {
                error: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767z"/><path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/></svg>`,
                success: `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425z"/></svg>`,
            };
            icon.innerHTML = icons[type] || icons.error;

            const content = document.createElement('div');
            content.className = 'alert-content';

            const messagePara = document.createElement('div');
            messagePara.textContent = message;
            content.appendChild(messagePara);

            if (details && Array.isArray(details)) {
                details.forEach(detail => {
                    const detailDiv = document.createElement('div');
                    detailDiv.className = 'alert-error-item';
                    detailDiv.textContent = detail;
                    content.appendChild(detailDiv);
                });
            }

            alert.appendChild(icon);
            alert.appendChild(content);
            alertContainer.appendChild(alert);

            setTimeout(() => alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
        };

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate all fields
            const fields = form.querySelectorAll('.form-input');
            let formIsValid = true;
            const errors = [];

            if (!fallbackActive) {
                if (!adresseSelected || !document.getElementById('ville').value) {
                    setAdresseError(true);
                    formIsValid = false;
                    errors.push('Veuillez sélectionner une adresse dans la liste');
                }
            } else {
                document.getElementById('adresse_complete').value = document.getElementById('adresse_manual').value;
                document.getElementById('ville').value = document.getElementById('ville_manual').value;
                document.getElementById('code_postal').value = document.getElementById('code_postal_manual').value;
            }

            fields.forEach(field => {
                if (!validateField(field)) {
                    formIsValid = false;
                    if (field.name === 'nom') errors.push('Le nom est obligatoire');
                    else if (field.name === 'prenom') errors.push('Le prenom est obligatoire');
                    else if (field.name === 'email') errors.push('Email invalide');
                    else if (field.name === 'telephone') errors.push('Telephone invalide');
                    else if (field.name === 'nom_entreprise') errors.push("Le nom de l'entreprise est obligatoire");
                    else if (field.name === 'numero_siret') errors.push('Numero SIRET invalide');
                    else if (field.name === 'ville_manual') errors.push('La ville est obligatoire');
                    else if (field.name === 'adresse_manual') errors.push('Adresse trop longue');
                    else if (field.name === 'mot_de_passe') errors.push('Mot de passe minimum 8 caracteres');
                    else if (field.name === 'password_confirmation') errors.push('Les mots de passe ne correspondent pas');
                }
            });

            // Check required fields are not empty
            const requiredFields = ['nom', 'prenom', 'email', 'nom_entreprise', 'numero_siret', 'mot_de_passe', 'password_confirmation'];
            requiredFields.forEach(name => {
                const field = document.getElementById(name);
                if (field && field.value.trim() === '') {
                    formIsValid = false;
                    field.classList.add('error');
                }
            });

            // Check reCAPTCHA
            const captchaResponse = grecaptcha.getResponse();
            if (!captchaResponse) {
                formIsValid = false;
                errors.push('Veuillez valider le captcha');
            }

            if (!formIsValid) {
                showAlert('Veuillez corriger les erreurs', 'error', errors);
                return;
            }

            // Prepare form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

            // Clean SIRET: remove spaces
            data.numero_siret = data.numero_siret.replace(/\s/g, '');

            // Add captcha token
            data.captcha_token = captchaResponse;

            // Remove password_confirmation (not sent to API)
            delete data.password_confirmation;

            // Show loading state
            const submitBtn = form.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            loadingOverlay.classList.add('active');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const responseData = await response.json();

                if (response.ok && response.status === 201) {
                    showAlert('Inscription reussie! Redirection...', 'success');

                    if (responseData.token) {
                        localStorage.setItem('auth_token', responseData.token);
                    }

                    setTimeout(() => {
                        window.location.href = '/professionnel/profile';
                    }, 2000);
                } else if (response.status === 400) {
                    const errorMessage = responseData.erreur || responseData.message || 'Erreur de validation';
                    showAlert(errorMessage, 'error');
                    grecaptcha.reset();
                } else {
                    showAlert('Erreur serveur. Veuillez reessayer plus tard.', 'error');
                    grecaptcha.reset();
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur reseau. Veuillez verifier votre connexion.', 'error');
                grecaptcha.reset();
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                loadingOverlay.classList.remove('active');
            }
        });

        // === Autocomplete adresse ===
        const adresseAutoGroup  = document.getElementById('adresseAutoGroup');
        const adresseSearch     = document.getElementById('adresseSearch');
        const adresseSuggestions= document.getElementById('adresseSuggestions');
        const adresseFallback   = document.getElementById('adresseFallback');
        let adresseSelected = false;
        let debounceTimer   = null;

        const activateFallback = () => {
            fallbackActive = true;
            adresseAutoGroup.style.display = 'none';
            adresseFallback.style.display  = 'block';
        };

        const setAdresseError = (hasError) => {
            adresseAutoGroup.classList.toggle('has-error', hasError);
        };

        const closeSuggestions = () => {
            adresseSuggestions.style.display = 'none';
            adresseSuggestions.innerHTML = '';
        };

        fetch('https://data.geopf.fr/geocodage/search/?q=Paris&limit=1').catch(() => activateFallback());

        adresseSearch.addEventListener('input', () => {
            const value = adresseSearch.value.trim();
            adresseSelected = false;
            document.getElementById('adresse_complete').value = '';
            document.getElementById('ville').value = '';
            document.getElementById('code_postal').value = '';
            setAdresseError(false);
            clearTimeout(debounceTimer);

            if (value.length < 3) { closeSuggestions(); return; }

            debounceTimer = setTimeout(async () => {
                try {
                    const res  = await fetch(`https://data.geopf.fr/geocodage/search/?q=${encodeURIComponent(value)}&limit=5`);
                    if (!res.ok) throw new Error();
                    const data = await res.json();
                    const features = data.features || [];
                    if (features.length === 0) { closeSuggestions(); return; }

                    adresseSuggestions.innerHTML = '';
                    features.forEach(feature => {
                        const props = feature.properties;
                        const item  = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = props.label;
                        item.addEventListener('mousedown', (e) => {
                            e.preventDefault();
                            adresseSearch.value = props.label;
                            document.getElementById('adresse_complete').value = props.name     || '';
                            document.getElementById('ville').value            = props.city     || '';
                            document.getElementById('code_postal').value      = props.postcode || '';
                            adresseSelected = true;
                            setAdresseError(false);
                            closeSuggestions();
                        });
                        adresseSuggestions.appendChild(item);
                    });
                    adresseSuggestions.style.display = 'block';
                } catch {
                    activateFallback();
                }
            }, 300);
        });

        adresseSearch.addEventListener('blur', () => setTimeout(closeSuggestions, 150));
        document.addEventListener('click', (e) => {
            if (!adresseAutoGroup.contains(e.target)) closeSuggestions();
        });
    </script>
</body>
</html>

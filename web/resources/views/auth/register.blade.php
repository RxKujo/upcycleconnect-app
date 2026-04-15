<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — UpcycleConnect</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Mono:wght@400;500&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        html, body {
            height: 100%;
        }

        body {
            background-color: var(--cream);
            font-family: 'Outfit', sans-serif;
            color: var(--coffee);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 500px;
        }

        .auth-card {
            background: var(--cream);
            border: var(--border);
            box-shadow: var(--shadow);
            padding: 48px 40px;
            transition: all 0.2s ease;
        }

        .auth-card:hover {
            transform: translate(3px, 3px);
            box-shadow: var(--shadow-hover);
        }

        .auth-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3.5rem;
            color: var(--coffee);
            margin-bottom: 40px;
            letter-spacing: 0.1em;
            line-height: 1;
            text-transform: uppercase;
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

        .form-group {
            margin-bottom: 28px;
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
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-row .form-group {
                margin-bottom: 28px;
            }
        }

        .form-input,
        .form-textarea {
            width: 100%;
            border: 3px solid var(--coffee);
            background: white;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            padding: 14px 16px;
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

        .form-textarea {
            resize: vertical;
            min-height: 120px;
            font-family: 'Outfit', sans-serif;
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

        .btn-submit:hover:not(:disabled) {
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
            margin-top: 32px;
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
                padding: 32px 24px;
            }

            .auth-title {
                font-size: 2.5rem;
                margin-bottom: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Créer un compte</h1>

            <div id="alertContainer"></div>

            <form id="registerForm" method="POST" action="http://localhost:8080/api/v1/auth/register-particulier" novalidate>
                <div class="form-row">
                    <div class="form-group">
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

                    <div class="form-group">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            class="form-input"
                            placeholder="Votre prénom"
                            required
                        >
                        <div class="error-message">Le prénom est obligatoire</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="votre@email.com"
                        required
                    >
                    <div class="error-message">Veuillez entrer une adresse email valide</div>
                </div>

                <div class="form-group">
                    <label for="telephone" class="form-label">Téléphone (optionnel)</label>
                    <input
                        type="tel"
                        id="telephone"
                        name="telephone"
                        class="form-input"
                        placeholder="+33 6 12 34 56 78"
                    >
                    <div class="error-message">Veuillez entrer un numéro de téléphone valide</div>
                </div>

                <div class="form-group required">
                    <label for="ville" class="form-label">Ville</label>
                    <input
                        type="text"
                        id="ville"
                        name="ville"
                        class="form-input"
                        placeholder="Ville"
                        required
                    >
                    <div class="error-message">La ville est obligatoire</div>
                </div>

                <div class="form-group">
                    <label for="adresse_complete" class="form-label">Adresse complète (optionnel)</label>
                    <textarea
                        id="adresse_complete"
                        name="adresse_complete"
                        class="form-textarea"
                        placeholder="123 Rue de..."
                    ></textarea>
                    <div class="error-message">L'adresse est trop longue (max 255 caractères)</div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <div class="password-group">
                            <input
                                type="password"
                                id="mot_de_passe"
                                name="mot_de_passe"
                                class="form-input"
                                placeholder="Minimum 8 caractères"
                                required
                            >
                            <button type="button" class="password-toggle" data-target="mot_de_passe" aria-label="Afficher/masquer le mot de passe">
                                <span class="toggle-icon">👁</span>
                            </button>
                        </div>
                        <div class="error-message">Le mot de passe doit contenir au moins 8 caractères</div>
                    </div>

                    <div class="form-group">
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
                                <span class="toggle-icon">👁</span>
                            </button>
                        </div>
                        <div class="error-message">Les mots de passe ne correspondent pas</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">S'inscrire</button>
            </form>

            <div class="auth-footer">
                <div class="auth-footer-text">
                    Déjà inscrit? <a href="/login" class="auth-link">Se connecter</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('registerForm');
        const alertContainer = document.getElementById('alertContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const passwordToggles = document.querySelectorAll('.password-toggle');

        // Validation patterns
        const patterns = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            phone: /^[\d\s+()-]+$|^$/,
            password: /.{8,}/,
        };

        // Password toggle functionality
        passwordToggles.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = button.dataset.target;
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                button.innerHTML = `<span class="toggle-icon">${isPassword ? '🙈' : '👁'}</span>`;
            });
        });

        // Live validation
        const validateField = (field) => {
            const value = field.value.trim();
            let isValid = true;
            let errorMsg = '';

            switch (field.name) {
                case 'nom':
                case 'prenom':
                    isValid = value.length > 0;
                    break;
                case 'email':
                    isValid = value.length === 0 || patterns.email.test(value);
                    break;
                case 'telephone':
                    isValid = patterns.phone.test(value);
                    break;
                case 'ville':
                    isValid = value.length > 0;
                    break;
                case 'adresse_complete':
                    isValid = value.length <= 255;
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
        form.querySelectorAll('.form-input, .form-textarea').forEach(field => {
            field.addEventListener('blur', () => validateField(field));
            field.addEventListener('change', () => validateField(field));
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
            icon.textContent = type === 'error' ? '⚠' : '✓';

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

            // Scroll to alert
            setTimeout(() => alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
        };

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate all fields
            const fields = form.querySelectorAll('.form-input, .form-textarea');
            let formIsValid = true;
            const errors = [];

            fields.forEach(field => {
                if (!validateField(field)) {
                    formIsValid = false;
                    if (field.name === 'nom') errors.push('Le nom est obligatoire');
                    else if (field.name === 'prenom') errors.push('Le prénom est obligatoire');
                    else if (field.name === 'email') errors.push('Email invalide');
                    else if (field.name === 'telephone') errors.push('Téléphone invalide');
                    else if (field.name === 'ville') errors.push('La ville est obligatoire');
                    else if (field.name === 'adresse_complete') errors.push('Adresse trop longue');
                    else if (field.name === 'mot_de_passe') errors.push('Mot de passe minimum 8 caractères');
                    else if (field.name === 'password_confirmation') errors.push('Les mots de passe ne correspondent pas');
                }
            });

            if (!formIsValid) {
                showAlert('Veuillez corriger les erreurs', 'error', errors);
                return;
            }

            // Prepare form data
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);

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
                    // Success
                    showAlert('Inscription réussie! Redirection...', 'success');

                    // Store token if provided
                    if (responseData.token) {
                        localStorage.setItem('auth_token', responseData.token);
                    }

                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = '/particulier/profile';
                    }, 2000);
                } else if (response.status === 400) {
                    // Validation errors from API
                    const errorDetails = [];
                    if (responseData.errors) {
                        Object.entries(responseData.errors).forEach(([field, messages]) => {
                            if (Array.isArray(messages)) {
                                errorDetails.push(...messages);
                            } else {
                                errorDetails.push(messages);
                            }
                        });
                    }
                    showAlert(responseData.message || 'Erreur de validation', 'error', errorDetails.length ? errorDetails : null);
                } else if (response.status === 409) {
                    showAlert('Cette adresse email est déjà utilisée', 'error');
                } else {
                    showAlert('Erreur serveur. Veuillez réessayer plus tard.', 'error');
                }
            } catch (error) {
                console.error('Erreur:', error);
                showAlert('Erreur réseau. Veuillez vérifier votre connexion.', 'error');
            } finally {
                // Reset loading state
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                loadingOverlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>

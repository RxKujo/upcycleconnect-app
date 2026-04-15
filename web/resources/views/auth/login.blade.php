<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — UpcycleConnect</title>
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

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffc107;
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

        .form-input {
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

        .form-input:focus {
            border-color: var(--cherry);
            box-shadow: 0 0 0 2px var(--cherry), 5px 5px 0px rgba(164, 36, 59, 0.2);
            transform: translate(-2px, -2px);
        }

        .form-input::placeholder {
            color: #999;
            opacity: 0.8;
        }

        .form-input.error {
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

        .form-input.error ~ .error-message {
            display: block;
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

        .forgot-password {
            text-align: right;
            margin-top: 12px;
            margin-bottom: 28px;
        }

        .forgot-password a {
            color: var(--teal);
            text-decoration: none;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .forgot-password a:hover {
            border-bottom: 2px solid var(--teal);
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

        .ban-notice {
            background-color: #fff3cd;
            border: var(--border);
            color: #856404;
            padding: 20px;
            border-radius: 0;
            box-shadow: var(--shadow-sm);
            margin-bottom: 28px;
        }

        .ban-notice-title {
            font-family: 'DM Mono', monospace;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.95rem;
            margin-bottom: 8px;
            letter-spacing: 0.05em;
        }

        .ban-notice-content {
            font-size: 1rem;
            line-height: 1.5;
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
            <h1 class="auth-title">Se connecter</h1>

            <div id="alertContainer"></div>

            <form id="loginForm" method="POST" action="http://localhost:8080/api/v1/auth/login" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="votre@email.com"
                        required
                        autocomplete="email"
                    >
                    <div class="error-message">Veuillez entrer une adresse email valide</div>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <div class="password-group">
                        <input
                            type="password"
                            id="mot_de_passe"
                            name="mot_de_passe"
                            class="form-input"
                            placeholder="Votre mot de passe"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" data-target="mot_de_passe" aria-label="Afficher/masquer le mot de passe">
                            <span class="toggle-icon">👁</span>
                        </button>
                    </div>
                    <div class="error-message">Le mot de passe est obligatoire</div>
                </div>

                <div class="forgot-password">
                    <a href="/forgot-password">Mot de passe oublié?</a>
                </div>

                <button type="submit" class="btn-submit">Se connecter</button>
            </form>

            <div class="auth-footer">
                <div class="auth-footer-text">
                    Pas de compte? <a href="/register" class="auth-link">S'inscrire</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const alertContainer = document.getElementById('alertContainer');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const passwordToggle = document.querySelector('.password-toggle');

        // Validation patterns
        const patterns = {
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        };

        // Password toggle functionality
        if (passwordToggle) {
            passwordToggle.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = passwordToggle.dataset.target;
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                passwordToggle.innerHTML = `<span class="toggle-icon">${isPassword ? '🙈' : '👁'}</span>`;
            });
        }

        // Validate email field
        const validateEmail = () => {
            const email = document.getElementById('email');
            const value = email.value.trim();
            const isValid = value.length === 0 || patterns.email.test(value);

            if (!isValid) {
                email.classList.add('error');
            } else {
                email.classList.remove('error');
            }

            return isValid;
        };

        // Validate password field
        const validatePassword = () => {
            const password = document.getElementById('mot_de_passe');
            const isValid = password.value.length > 0;

            if (!isValid) {
                password.classList.add('error');
            } else {
                password.classList.remove('error');
            }

            return isValid;
        };

        // Live validation
        document.getElementById('email').addEventListener('blur', validateEmail);
        document.getElementById('mot_de_passe').addEventListener('blur', validatePassword);

        // Display alert messages
        const showAlert = (message, type = 'error', details = null) => {
            alertContainer.innerHTML = '';

            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;

            const icon = document.createElement('div');
            icon.className = 'alert-icon';
            icon.textContent = type === 'error' ? '⚠' : (type === 'warning' ? '⚡' : '✓');

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

        // Display ban notice
        const showBanNotice = (unbannedAt) => {
            alertContainer.innerHTML = '';

            const notice = document.createElement('div');
            notice.className = 'ban-notice';

            const title = document.createElement('div');
            title.className = 'ban-notice-title';
            title.textContent = 'Compte suspendu';

            const content = document.createElement('div');
            content.className = 'ban-notice-content';

            try {
                const date = new Date(unbannedAt);
                const formattedDate = date.toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                content.textContent = `Votre compte est suspendu jusqu'au ${formattedDate}. Veuillez réessayer ultérieurement.`;
            } catch {
                content.textContent = `Votre compte est actuellement suspendu. Veuillez réessayer ultérieurement.`;
            }

            notice.appendChild(title);
            notice.appendChild(content);
            alertContainer.appendChild(notice);

            setTimeout(() => notice.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 100);
        };

        // Form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Validate fields
            const emailValid = validateEmail();
            const passwordValid = validatePassword();

            if (!emailValid || !passwordValid) {
                const errors = [];
                if (!emailValid) errors.push('Email invalide');
                if (!passwordValid) errors.push('Le mot de passe est obligatoire');
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

                if (response.ok && (response.status === 200 || response.status === 201)) {
                    // Success - decode JWT to get role
                    const token = responseData.token;
                    let userRole = 'particulier';

                    try {
                        // Decode JWT payload (format: header.payload.signature)
                        const parts = token.split('.');
                        if (parts.length === 3) {
                            // Decode base64url payload
                            const decoded = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                            userRole = decoded.role || 'particulier';
                        }
                    } catch (e) {
                        console.error('Erreur lors du décodage du JWT:', e);
                        userRole = 'particulier';
                    }

                    // Route based on role
                    if (userRole === 'admin') {
                        // Admin: need to set up server-side session
                        showAlert('Connexion en tant qu\'admin... Redirection...', 'success');

                        // Get CSRF token from meta tag
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        // POST token to Laravel to set admin session
                        fetch('/auth/set-admin-session', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ token: token })
                        })
                        .then(sessionResponse => {
                            if (sessionResponse.ok) {
                                return sessionResponse.json();
                            }
                            throw new Error('Erreur lors de la création de la session');
                        })
                        .then(sessionData => {
                            if (sessionData.success) {
                                // Session created successfully, redirect to admin panel
                                setTimeout(() => {
                                    window.location.href = sessionData.redirect || '/admin/utilisateurs';
                                }, 1000);
                            } else {
                                showAlert(sessionData.message || 'Erreur: impossible de créer la session admin', 'error');
                                submitBtn.disabled = false;
                                submitBtn.classList.remove('loading');
                                loadingOverlay.classList.remove('active');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showAlert('Erreur réseau lors de la création de la session', 'error');
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('loading');
                            loadingOverlay.classList.remove('active');
                        });
                    } else {
                        // Regular user: store token in localStorage
                        showAlert('Connexion réussie! Redirection...', 'success');
                        localStorage.setItem('auth_token', token);

                        setTimeout(() => {
                            window.location.href = '/particulier/profile';
                        }, 1500);
                    }
                } else if (response.status === 401) {
                    // Authentication failed
                    showAlert('Email ou mot de passe incorrect', 'error');
                } else if (response.status === 403) {
                    // Account banned
                    if (responseData.data && responseData.data.unbanned_at) {
                        showBanNotice(responseData.data.unbanned_at);
                    } else {
                        showAlert('Votre compte est suspendu. Veuillez réessayer ultérieurement.', 'warning');
                    }
                } else if (response.status === 400) {
                    // Validation error
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
                } else if (response.status === 500) {
                    showAlert('Erreur serveur. Veuillez réessayer plus tard.', 'error');
                } else {
                    showAlert('Une erreur est survenue. Veuillez réessayer.', 'error');
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

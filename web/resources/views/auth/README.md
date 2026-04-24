# Pages d'authentification UpcycleConnect

## Vues créées

### 1. register.blade.php
Formulaire d'inscription complète pour les particuliers

**Champs:**
- Nom (text, obligatoire)
- Prénom (text, obligatoire)
- Email (email, obligatoire, validation format)
- Téléphone (tel, optionnel, validation si rempli)
- Ville (text, obligatoire)
- Adresse (textarea, optionnel, max 255 chars)
- Mot de passe (password, min 8 caractères)
- Confirmation mot de passe (password, doit matcher)

**Features:**
- Validation live (blur/change events)
- Toggle affichage/masquage password
- Loading spinner pendant API call
- Gestion erreurs API (201, 400, 409, 500)
- Stockage JWT en localStorage
- Redirection automatique vers /particulier/dashboard après succès

### 2. login.blade.php
Formulaire de connexion

**Champs:**
- Email (email, obligatoire)
- Mot de passe (password, obligatoire)

**Features:**
- Validation live
- Toggle affichage/masquage password
- Gestion spéciale compte banni (affiche date déblocage)
- Loading spinner
- Gestion erreurs API (200, 401, 403, 400, 500)
- Redirection automatique vers /particulier/dashboard après succès
- Lien "Mot de passe oublié?" (placeholder)

### 3. test.html (optionnel)
Page de test pour visualiser les vues et voir les fonctionnalités

## Design System

### Couleurs (Neo-Brutalism)
- Cherry Rose: #A4243B (CTA, erreurs)
- Wheat: #D8C99B (accents secondaires)
- Coffee Bean: #120309 (texte, bordures, ombres)
- Forest Moss: #244F26 (succès)
- Deep Teal: #18607D (liens)
- Cream: #F5F0E1 (fond)

### Typographie
- Bebas Neue: Titres, boutons (uppercase)
- Outfit: Body text, inputs
- DM Mono: Labels, badges (monospace)

### Éléments de style
- Bordures: 3px solid #120309
- Ombres: 5px 5px 0px #120309
- Pas de border-radius
- Hover: translate(3px, 3px) + box-shadow transition
- Focus: outline 2px cherry offset 2px

## Utilisation

### Routes (routes/web.php)
```php
Route::middleware('guest')->group(function () {
    Route::get('/register', fn() => view('auth.register'));
    Route::get('/login', fn() => view('auth.login'));
});
```

### URL de test
- http://localhost:8000/register
- http://localhost:8000/login

## API Integration

### Register
**Endpoint:** POST `/api/v1/auth/register-particulier`

**Request body:**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean@example.com",
  "telephone": "+33 6 12 34 56 78",
  "ville": "Paris",
  "adresse": "123 Rue",
  "password": "SecurePassword123",
  "password_confirmation": "SecurePassword123"
}
```

**Success (201):** Redirection vers /particulier/dashboard après 2 secondes

### Login
**Endpoint:** POST `/api/v1/auth/login`

**Request body:**
```json
{
  "email": "jean@example.com",
  "password": "SecurePassword123"
}
```

**Success (200):** Redirection vers /particulier/dashboard après 1.5 secondes

## Validation côté client

- Email: Format valide (regex)
- Téléphone: Optionnel, format si rempli
- Mot de passe: Min 8 caractères
- Confirmation: Doit matcher password
- Champs obligatoires: Nom, Prénom, Email, Ville, Password

## Gestion des erreurs API

**Register:**
- 201: Succès → redirection
- 400: Validation errors → liste chaque erreur
- 409: Email existe → message spécifique
- 500: Erreur serveur → message générique

**Login:**
- 200: Succès → redirection
- 401: Identifiants incorrects → message d'erreur
- 403: Compte banni → affiche date déblocage
- 400: Validation → liste erreurs
- 500: Erreur serveur → message générique

## Stockage JWT

Token stocké en localStorage:
```javascript
localStorage.setItem('auth_token', token);
```

À utiliser dans les requêtes ultérieures:
```javascript
headers: {
  'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
}
```

## Accessibilité

- Labels liés aux inputs (for/id)
- Contraste WCAG AA (3.1:1 minimum)
- HTML sémantique
- ARIA labels sur buttons
- Textes en français avec accents

## Responsive

- Mobile-first (320px+)
- Tablet (600px+)
- Desktop (1024px+)
- Padding: 48/40px → 32/24px
- Titre: 3.5rem → 2.5rem
- Form rows: 2 col → 1 col (< 600px)

## Prochaines étapes

1. Configuration des routes Laravel
2. Tests des formulaires
3. Vérification des réponses API
4. Intégration avec dashboard
5. Page "Mot de passe oublié"
6. Vérification email
7. 2FA

## Support

- Voir IMPLEMENTATION_AUTH.md pour documentation complète
- Voir QUICK_START_AUTH.md pour démarrage rapide
- Voir API_EXAMPLES.md pour exemples de réponses

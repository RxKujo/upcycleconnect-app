# UpcycleConnect

UpcycleConnect est une application web de marketplace de réemploi et de services écologiques (style Vinted/Leboncoin). Les visiteurs consultent librement le marché, les événements, les conseils et le forum ; l'inscription n'est requise que pour agir (publier une annonce, s'inscrire à un événement, poster sur le forum). Les administrateurs et salariés valident et modèrent les contenus.

## Ce que c'est

UpcycleConnect est composé de trois parties principales :

1. Une base de données **MySQL** qui stocke utilisateurs, annonces, objets, commandes, événements, articles, forum et catégories.
2. Une **API en Go** (bibliothèque standard `net/http`, sans framework) qui gère toute la logique métier, l'accès à la base de données et l'authentification JWT.
3. Une application **Laravel** (vues Blade + assets compilés par Vite) qui sert le front public et les espaces admin / salarié. Laravel ne se connecte jamais directement à MySQL : il consomme l'API Go via HTTP.

## Structure du code

```
api/                       Serveur API en Go
  cmd/server/main.go       Point de démarrage
  internal/handlers/       Logique métier (auth, annonces, public, salarié, admin…)
  internal/middleware/     Authentification JWT et configuration CORS
  internal/router/         Routage des requêtes
  internal/models/         Structures de données
  internal/services/       PDF (tickets), email
  pkg/database/            Connexion à MySQL

web/                       Application Laravel
  app/Http/Controllers/    Contrôleurs publics, Admin/ et Salarie/
  app/Http/Middleware/     AdminAuth, SalarieAuth
  resources/views/         Vues Blade (public, admin, salarié, auth…)
  routes/web.php           Routes web

database/migrations/       Scripts SQL de création des tables et seeds
```

## Comment lancer l'application

### Option recommandée : Docker

À la racine du projet :

```
docker compose -f docker-compose.dev.yml up -d --build
```

Cela démarre :

- MySQL sur le port `3306` (migrations/seeds appliqués automatiquement au premier démarrage)
- PHPMyAdmin sur `http://localhost:8081`
- l'API Go sur `http://localhost:8888`
- Laravel sur `http://localhost:8000`
- Vite (assets) sur le port `5173`

Les variables sont lues depuis le fichier `.env` à la racine (voir section Configuration).

### Option manuelle (sans Docker)

Quatre terminaux sont nécessaires.

1. **Base de données** : `docker compose -f docker-compose.dev.yml up -d db`
2. **API Go** : depuis `api/`, `go run cmd/server/main.go` — affiche « Serveur en écoute sur le port 8888 ».
3. **Assets** : depuis `web/`, `npm install` puis `npm run dev` (port 5173).
4. **Laravel** : depuis `web/`, `php artisan serve` (port 8000).

## Accéder à l'application

- Front public : http://localhost:8000
- Connexion (admin, salarié, particulier, professionnel) : http://localhost:8000/login

Les identifiants des comptes de démonstration sont décrits dans `CREDENTIALS.md`.

## Endpoints de l'API

L'API écoute par défaut sur `http://localhost:8888`. Aperçu (liste non exhaustive) :

Authentification :
- `POST /api/v1/auth/register` — créer un compte
- `POST /api/v1/auth/login` — se connecter (renvoie un token JWT)

Zone publique (sans authentification) :
- `GET /api/v1/public/annonces` — lister les annonces validées
- `GET /api/v1/public/annonces/{id}` — détail d'une annonce
- `GET /api/v1/public/articles` — articles / conseils
- `GET /api/v1/public/forum` — sujets du forum
- `GET /api/v1/public/catalogue` — catalogue
- `GET /api/v1/public/stats` — statistiques publiques
- `GET /api/v1/evenements/catalogue` — événements à venir

Profil utilisateur (authentifié) :
- `GET /api/v1/utilisateurs/me` — voir son profil
- `PUT /api/v1/utilisateurs/me` — modifier son profil
- `GET /api/v1/utilisateurs/me/export-pdf` — export RGPD des données personnelles

Annonces et commandes (authentifié) :
- `POST /api/v1/annonces` — publier une annonce
- `GET /api/v1/annonces/me` — ses annonces
- `POST /api/v1/commandes/checkout` — valider le panier
- `GET /api/v1/commandes/me` — ses commandes

Administration (`role = admin`) :
- `GET /api/v1/admin/utilisateurs` — lister les utilisateurs
- `PUT /api/v1/admin/utilisateurs/{id}/ban` — bannir un utilisateur
- `GET|POST /api/v1/admin/categories` — gérer les catégories
- `GET /api/v1/admin/annonces`, `PUT /api/v1/admin/annonces/{id}/valider` — modération des annonces
- `GET /api/v1/admin/evenements`, `PUT /api/v1/admin/evenements/{id}/valider` — modération des événements

## Configuration

Le fichier `.env` à la racine fournit les variables consommées par `docker-compose.dev.yml`.

En exécution **hors Docker**, l'API lit directement les variables suivantes (noms attendus par `pkg/database`) :

```
DB_HOST=localhost
DB_PORT=3306
DB_USER=uc_user
DB_PASSWORD=uc_password
DB_NAME=upcycleconnect
API_PORT=8888
JWT_SECRET=<secret>
# Origines autorisées par le CORS (séparées par des virgules).
# localhost:8000 et localhost:5173 sont déjà autorisés par défaut.
CORS_ALLOWED_ORIGINS=https://mon-domaine.fr
```

Note : sous Docker, `docker-compose.dev.yml` traduit automatiquement `DB_USERNAME`/`DB_DATABASE` (utilisés par Laravel) en `DB_USER`/`DB_NAME` pour le conteneur de l'API.

## Arrêter l'application

- Docker : `docker compose -f docker-compose.dev.yml down`
- Mode manuel : `CTRL+C` dans chaque terminal serveur.

## Problèmes courants

- **L'API ne démarre pas** : vérifiez que MySQL est lancé et que les variables `DB_*` sont correctes.
- **La connexion admin échoue** : assurez-vous que l'API Go répond sur le port `8888`.
- **Erreur CORS dans le navigateur** : ajoutez l'origine de votre front à `CORS_ALLOWED_ORIGINS`.
- **Les assets ne se chargent pas** : vérifiez que Vite (`npm run dev`) tourne sur le port `5173`.

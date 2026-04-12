# UpcycleConnect

UpcycleConnect est une application web pour gérer un marché de réutilisation et de services écologiques. Les utilisateurs peuvent créer des comptes, proposer des services, organiser des événements et les administrateurs valident tout.

## Ce que c'est

UpcycleConnect est composé de trois parties principales:

1. Une base de données MySQL qui stocke tous les utilisateurs, les services, les événements et les catégories
2. Une API en Go qui gère toutes les opérations de base de données et l'authentification
3. Un panneau administrateur en Laravel et Vue.js pour gérer le contenu

## Structure du code

Le projet est organisé comme ceci:

api/: Le serveur API écrit en Go
- cmd/server/main.go: Point de démarrage
- internal/handlers/: Logique pour les utilisateurs, services, événements
- internal/middleware/: Authentification JWT et configuration CORS
- internal/router/: Routage des requêtes
- pkg/database/: Connexion à MySQL

web/: L'application Laravel
- app/Http/Controllers/Admin/: Contrôleurs pour le panneau administrateur
- app/Models/: Modèles de données
- database/: Migrations et seeders
- resources/views/: Pages HTML

database/: Les fichiers de migration pour créer les tables

## Comment lancer l'application

Vous avez besoin de quatre terminaux différents pour lancer l'application.

### Terminal 1: Base de données

Allez à la racine du projet et démarrez Docker:

```
cd d:\Code\upcycleconnect-app
docker-compose up -d
```

Cela lance MySQL sur le port 3306 et PHPMyAdmin sur le port 8081.

### Terminal 2: API Go

Allez dans le dossier api et lancez le serveur:

```
cd d:\Code\upcycleconnect-app\api
go run cmd/server/main.go
```

L'API doit afficher "Serveur en écoute sur le port 8080".

### Terminal 3: Assets

Allez dans le dossier web et lancez Vite pour compiler les assets:

```
cd d:\Code\upcycleconnect-app\web
npm run dev
```

Cela compile le JavaScript et le CSS et démarre un serveur sur le port 5173.

### Terminal 4: Application Laravel

Toujours dans le dossier web, lancez Laravel:

```
cd d:\Code\upcycleconnect-app\web
php artisan serve
```

L'application démarre sur le port 8000.

## Accéder à l'application

Quand tout est lancé, ouvrez votre navigateur et allez à:

http://localhost:8000/admin/login

Connectez-vous avec:
- Email: admin@upcycleconnect.com
- Mot de passe: Admin123!

## Endpoints de l'API

L'API écoute sur http://localhost:8080 et propose:

Connexion et inscription:
- POST /api/v1/auth/register: Créer un compte
- POST /api/v1/auth/login: Se connecter

Profil utilisateur:
- GET /api/v1/utilisateurs/me: Voir son profil
- PUT /api/v1/utilisateurs/me: Modifier son profil

Administration:
- GET /api/v1/admin/utilisateurs: Lister les utilisateurs
- GET /api/v1/admin/utilisateurs/{id}/ban: Bannir un utilisateur
- GET /api/v1/admin/categories: Lister les catégories
- POST /api/v1/admin/categories: Créer une catégorie
- GET /api/v1/admin/prestations: Lister les services
- PUT /api/v1/admin/prestations/{id}/valider: Valider un service
- GET /api/v1/admin/evenements: Lister les événements
- PUT /api/v1/admin/evenements/{id}/valider: Valider un événement

## Configuration

Le fichier .env à la racine de api/ configure la connexion à la base de données:

```
DB_HOST=localhost
DB_PORT=3306
DB_USER=uc_user
DB_PASSWORD=uc_password
DB_NAME=upcycleconnect
API_PORT=8080
JWT_SECRET=upcycleconnect_secret_key_2026
```

## Arrêter l'application

Pour arrêter tout proprement:

1. Dans chaque terminal lancé en mode serveur, appuyez sur CTRL+C
2. Pour arrêter Docker: docker-compose down

## Problèmes courants

L'API ne démarre pas: Vérifiez que MySQL fonctionne avec docker-compose up -d

La connexion au panneau admin échoue: Assurez-vous que l'API Go fonctionne sur le port 8080

Les assets ne se chargent pas: Vérifiez que npm run dev fonctionne sur le port 5173


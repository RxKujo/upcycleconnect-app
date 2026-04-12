# UpcycleConnect API - Go Natif

API REST pour UpcycleConnect, écrite en Go natif (sans framework externe).

## Démarrage rapide

### Prérequis
- Go 1.25.3+
- MySQL 8.0+
- Variables d'environnement configurées (voir `.env`)

### Lancer l'API

```bash
cd api
go run cmd/server/main.go
```

**Port par défaut :** `8080` (configurable via `API_PORT` dans `.env`)

## Structure du projet

```
api/
├── cmd/server/main.go           # Point d'entrée
├── internal/
│   ├── handlers/                # Logique métier
│   │   ├── auth.go              # Register, Login
│   │   ├── utilisateurs.go      # Gestion des utilisateurs
│   │   ├── categories.go        # Catégories de services
│   │   ├── prestations.go       # Services
│   │   └── evenements.go        # Événements
│   ├── middleware/              # Authentification & CORS
│   │   ├── auth.go              # JWT validation
│   │   └── cors.go              # Headers CORS
│   ├── models/                  # Structures de données
│   └── router/                  # Routeur custom (net/http)
├── pkg/database/                # Connexion MySQL
└── go.mod                       # Dépendances
```

## Configuration

Créez un fichier `.env` à la racine de `api/` :

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=uc_user
DB_PASSWORD=uc_password
DB_NAME=upcycleconnect
API_PORT=8080
JWT_SECRET=votre_secret_jwt_ici
```

## Liste des endpoints

### Authentication (Public)
```
POST   /api/v1/auth/register    Créer un compte
POST   /api/v1/auth/login       Se connecter
```

### Utilisateurs (Authentifié)
```
GET    /api/v1/utilisateurs/me   Profil actuel
PUT    /api/v1/utilisateurs/me   Mettre à jour profil
```

### Admin (Authentifié + role admin)
```
GET    /api/v1/admin/utilisateurs              Lister les utilisateurs
GET    /api/v1/admin/utilisateurs/{id}        Détails d'un utilisateur
PUT    /api/v1/admin/utilisateurs/{id}/ban    Bannir un utilisateur
PUT    /api/v1/admin/utilisateurs/{id}/unban  Débannir un utilisateur

GET    /api/v1/admin/categories               Lister les catégories
POST   /api/v1/admin/categories               Créer une catégorie
PUT    /api/v1/admin/categories/{id}          Modifier une catégorie
DELETE /api/v1/admin/categories/{id}          Supprimer une catégorie

GET    /api/v1/admin/prestations              Lister les services
GET    /api/v1/admin/prestations/{id}         Détails d'un service
POST   /api/v1/admin/prestations              Créer un service
PUT    /api/v1/admin/prestations/{id}/valider Valider un service
PUT    /api/v1/admin/prestations/{id}/refuser Refuser un service

GET    /api/v1/admin/evenements              Lister les événements
GET    /api/v1/admin/evenements/{id}         Détails d'un événement
POST   /api/v1/admin/evenements              Créer un événement
PUT    /api/v1/admin/evenements/{id}/valider Valider un événement
PUT    /api/v1/admin/evenements/{id}/refuser Refuser un événement
```

## Authentification

Tous les endpoints (sauf `/auth/*`) requièrent un header :

```
Authorization: Bearer {jwt_token}
```

Le token est obtenu via `/api/v1/auth/login` et valide pour **72 heures**.

## Exemples d'utilisation

### Inscription
```bash
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean@example.com",
    "mot_de_passe": "SecurePass123",
    "role": "particulier",
    "telephone": "+33612345678",
    "ville": "Paris"
  }'
```

### Connexion
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jean@example.com",
    "mot_de_passe": "SecurePass123"
  }'
```

**Réponse :**
```json
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

### Utiliser le token
```bash
curl -X GET http://localhost:8080/api/v1/utilisateurs/me \
  -H "Authorization: Bearer {votre_token}"
```

## Dépendances

- `github.com/go-sql-driver/mysql` - Driver MySQL
- `github.com/golang-jwt/jwt/v5` - JWT encoding/verification
- `golang.org/x/crypto` - Password hashing (bcrypt)
- `github.com/joho/godotenv` - Gestion des .env

## Développement

### Compiler une version exécutable
```bash
go build -o server cmd/server/main.go
./server  # Windows: .\server.exe
```

### Exécuter les tests (si implémentés)
```bash
go test ./...
```

## Notes

- L'API utilise `net/http` (stdlib) sans dépendance framework external
- Routage manuel pour flexibilité maximale
- JWT signé en HS256 avec secret depuis `.env`
- Mots de passe hashés avec bcrypt (cost 10)
- Validation des timestamps automatique

## Support

Pour toute question, consultez :
- [Go Documentation](https://golang.org/doc/)
- [JWT.io](https://jwt.io/)

# UpcycleConnect - Lancement Rapide

Ce guide explique comment lancer le projet de A à Z.

---

## 1. Démarrer la base de données
À la racine du projet, lancez Docker :
```bash
docker-compose up -d
```
*Note : Importez ensuite le fichier `database/migrations/001_initial_schema.sql` dans votre MySQL.*

---

## 2. Lancer l'API (Go natif)
Ouvrez un terminal dans le dossier `api/` :
```bash
go run cmd/server/main.go
```
*L'API tourne sur http://localhost:8080 (voir `.env` pour changer le port)*

**Note :** L'API est écrite en Go natif (`net/http` stdlib) sans framework externe.

---

## 3. Lancer le Front (Laravel)
Ouvrez **deux** terminaux dans le dossier `web/` :

**Terminal A (Assets) :**
```bash
npm run dev
```

**Terminal B (Serveur) :**
```bash
php artisan serve
```
*Le site tourne sur http://localhost:8000*

---

## 4. Accès Admin
Rendez-vous sur : **[http://localhost:8000/admin/login](http://localhost:8000/admin/login)**

**Identifiants :**
- **Email :** admin@upcycleconnect.com
- **Mot de passe :** Admin123!

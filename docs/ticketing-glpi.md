# Ticketing incidents — GLPI (miroir)

Stratégie : **l'app reste la source de vérité** des tickets d'incident
(`tickets_incidents`) ; GLPI en reçoit une **copie** pour le suivi support.
La colonne `tickets_incidents.glpi_ticket_id` relie les deux.

## Flux implémenté (côté API Go)

- **Création** : quand le worker crée un ticket (objet non récupéré →
  `rappel_worker.go`), il appelle `glpi.CreateTicket()` et stocke l'id GLPI
  dans `glpi_ticket_id`.
- **Résolution** : `ResolveTicket` (validation admin) passe le ticket GLPI
  correspondant au statut *résolu* (5) via `glpi.UpdateTicketStatus()`.
- Client : `api/pkg/glpi/glpi.go` (initSession → opération → killSession).
  Endpoint et jetons configurables par env ; **no-op** si non configuré.

Pour miroiter un autre type de ticket, appeler `glpi.CreateTicket(sujet, desc)`
puis stocker l'id retourné.

## Déployer l'instance GLPI (dev)

Les services GLPI sont **optionnels** (profil `glpi`), ils ne démarrent pas avec
un `up` normal :

```bash
docker compose -f docker-compose.dev.yml --profile glpi up -d glpi glpi_db
```

- GLPI : http://localhost:8082 — MariaDB dédiée `uc_glpi_db` (séparée de la base
  applicative `uc_mysql`).

> ⚠️ **Version épinglée à GLPI 10.0.15** (image `elestio/glpi:10.0.15` +
> `VERSION_GLPI=10.0.15`). Le client Go (`api/pkg/glpi`) utilise l'**API REST
> legacy** `apirest.php`, qui est **cassée/dépréciée dans GLPI 11** (la HL API
> `api.php` OAuth la remplace). Ne pas laisser l'image en `latest`.

Installation du schéma en une commande (au lieu de l'assistant web) :

```bash
docker exec uc_glpi sh -lc 'cd /var/www/html/glpi && php bin/console database:install \
  --no-interaction --db-host=glpi_db --db-name=glpi --db-user=glpi --db-password=glpi --default-language=fr_FR'
```

En dev, l'API REST et les tokens sont **déjà configurés** (App-Token sur le
client « full access » avec plage IP élargie au réseau Docker, user_token sur le
compte `glpi`, chiffrement des app-tokens désactivé) et renseignés dans le `.env`
racine (`GLPI_URL`/`GLPI_APP_TOKEN`/`GLPI_USER_TOKEN`).

## Configuration initiale de GLPI (une fois)

1. Ouvrir http://localhost:8082 et suivre l'assistant d'installation.
   - Base de données : hôte `glpi_db`, utilisateur `glpi`, mot de passe `glpi`
     (cf. variables `GLPI_DB_*`), base `glpi`.
   - Se connecter avec le compte par défaut `glpi` / `glpi` (à changer).
2. **Activer l'API REST** : *Configuration > Générale > API* → activer
   « Activer l'API REST » et « Activer la connexion avec les identifiants ».
3. **App-Token** : dans la même page, créer un client API et copier son
   *App-Token*.
4. **User-Token** : *Préférences* (icône profil) > onglet *Jetons d'API* >
   générer un *jeton d'API* pour un compte technique.
5. Renseigner les variables d'environnement de l'API :
   - `GLPI_URL=http://glpi/apirest.php` (nom de service Docker `glpi`), ou
     l'URL publique en prod (`.../apirest.php`).
   - `GLPI_APP_TOKEN=<App-Token>`
   - `GLPI_USER_TOKEN=<jeton d'API>`
   En dev : dans le `.env` racine ; en prod : `.env.prod` (cf. `example.env.prod`).
6. Redémarrer l'API (`docker compose ... up -d api`).

## Vérification

- Client GLPI (sans instance réelle) : `go test ./pkg/glpi/`.
- Avec instance + tokens : déclencher une résolution de ticket depuis l'admin
  et vérifier la mise à jour du ticket dans GLPI (le champ `glpi_ticket_id` doit
  être renseigné en base).

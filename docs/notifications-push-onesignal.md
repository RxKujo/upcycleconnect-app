# Notifications push — OneSignal

Architecture : **envoi centralisé côté API Go**, réception **web** (Laravel) et
**mobile Android natif (Kotlin)**. Ciblage par **External ID = id utilisateur**
(pas de table de player_id à maintenir).

## Activation (prod ou dev)

1. Créer une app sur https://onesignal.com avec **deux plateformes** :
   - **Web Push** (renseigner l'URL du site + l'icône) ;
   - **Google Android (FCM)** pour l'app Kotlin.
2. Récupérer l'**App ID** (public) et la **REST API Key** (secrète).
3. Renseigner les variables d'environnement :
   - API Go **et** web : `ONESIGNAL_APP_ID`
   - API Go uniquement : `ONESIGNAL_API_KEY` (REST API Key)
   - En dev : les mettre dans le `.env` racine ; en prod : dans `.env.prod`
     (cf. `example.env.prod`). Laisser vide = push désactivé (no-op propre).

## Côté serveur (déjà en place)

- `api/internal/services/onesignal_service.go`
  - `SendPushToExternalIDs(ids, titre, corps, data)` — ciblage External ID
    (recommandé, cf. décision d'archi).
  - `SendPushToPlayers(...)` — legacy par player_id (utilisé par l'envoi groupé admin).
  - Endpoint surchargeable via `ONESIGNAL_API_BASE` (tests / proxy).
- Déclencheur métier branché : **arrivée d'un objet en conteneur** →
  `notifyAcheteurDepot` envoie email **+ push** à l'acheteur.
- Pour ajouter un déclencheur : appeler
  `services.SendPushToExternalIDs([]string{strconv.Itoa(idUser)}, titre, corps, data)`.

## Côté web (déjà en place)

- Service worker : `web/public/OneSignalSDKWorker.js`.
- Init : `web/resources/views/partials/onesignal.blade.php`, inclus dans les
  layouts `particulier` et `professionnel`. Récupère l'id utilisateur depuis le
  JWT (`uc_token`/`auth_token`) et appelle `OneSignal.login(id)`.
- Ne s'affiche que si `ONESIGNAL_APP_ID` est configuré.

## Côté app mobile Android (Kotlin) — à faire dans le repo mobile

Le repo mobile n'est pas ici ; étapes d'intégration :

1. Dépendance (module app `build.gradle`) :
   ```gradle
   implementation("com.onesignal:OneSignal:[5.1.6, 5.99.99]")
   ```
2. Initialisation (dans `Application.onCreate()`) :
   ```kotlin
   OneSignal.initWithContext(this, "VOTRE_ONESIGNAL_APP_ID")
   CoroutineScope(Dispatchers.IO).launch {
       OneSignal.Notifications.requestPermission(true)
   }
   ```
3. Associer l'utilisateur **après connexion** (même External ID que le web) :
   ```kotlin
   OneSignal.login(idUtilisateur.toString())
   ```
   Et à la déconnexion : `OneSignal.logout()`.
4. Configurer **FCM** (google-services.json) et coller la clé serveur FCM dans
   la plateforme Android de OneSignal.

Aucun changement serveur nécessaire : l'API Go cible déjà par External ID, donc
web et mobile reçoivent les mêmes push dès que `OneSignal.login(id)` est appelé.

## Vérification

- Test unitaire du payload d'envoi (sans compte réel) :
  `go test ./internal/services/ -run TestSendPush`.
- Web : service worker servi à `/OneSignalSDKWorker.js`, script d'init présent
  dans les pages authentifiées quand `ONESIGNAL_APP_ID` est renseigné.

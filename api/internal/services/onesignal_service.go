package services

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
)

// ─── Notifications push OneSignal ─────────────────────────────────────────────
//
// Variables .env requises :
//   ONESIGNAL_APP_ID    — identifiant de l'app OneSignal
//   ONESIGNAL_API_KEY   — clé REST API OneSignal
// ─────────────────────────────────────────────────────────────────────────────

const onesignalDefaultEndpoint = "https://onesignal.com/api/v1/notifications"

// onesignalEndpointURL renvoie l'URL de l'API OneSignal. Surchargeable via
// ONESIGNAL_API_BASE (utile pour les tests / un proxy interne).
func onesignalEndpointURL() string {
	if base := os.Getenv("ONESIGNAL_API_BASE"); base != "" {
		return base
	}
	return onesignalDefaultEndpoint
}

type onesignalPayload struct {
	AppID            string            `json:"app_id"`
	IncludePlayerIDs []string          `json:"include_player_ids"`
	Headings         map[string]string `json:"headings"`
	Contents         map[string]string `json:"contents"`
	Data             map[string]string `json:"data,omitempty"`
}

// SendPushToPlayer envoie une notification push à un joueur OneSignal identifié
// par son player_id (champ onesignal_player_id dans utilisateurs).
func SendPushToPlayer(playerID, title, body string) error {
	return SendPushToPlayers([]string{playerID}, title, body, nil)
}

// SendPushToPlayers envoie une notification à plusieurs joueurs (par player_id).
// data est facultatif (métadonnées pour le client mobile/web).
func SendPushToPlayers(playerIDs []string, title, body string, data map[string]string) error {
	appID := os.Getenv("ONESIGNAL_APP_ID")
	if appID == "" || os.Getenv("ONESIGNAL_API_KEY") == "" {
		log.Println("[ONESIGNAL] ONESIGNAL_APP_ID ou ONESIGNAL_API_KEY non configurés — push ignoré")
		return nil
	}
	if len(playerIDs) == 0 {
		return nil
	}

	payload := onesignalPayload{
		AppID:            appID,
		IncludePlayerIDs: playerIDs,
		Headings:         map[string]string{"fr": title, "en": title},
		Contents:         map[string]string{"fr": body, "en": body},
		Data:             data,
	}
	if err := postOneSignal(payload); err != nil {
		return err
	}
	log.Printf("[ONESIGNAL] Push envoyé à %d joueur(s) — titre: %s", len(playerIDs), title)
	return nil
}

// onesignalExternalPayload cible les utilisateurs par External ID (= id
// utilisateur), défini côté client via OneSignal.login(). Évite de stocker les
// player_id (cf. décision d'archi : External ID = user_id).
type onesignalExternalPayload struct {
	AppID                     string            `json:"app_id"`
	IncludeExternalUserIDs    []string          `json:"include_external_user_ids"`
	ChannelForExternalUserIDs string            `json:"channel_for_external_user_ids"`
	Headings                  map[string]string `json:"headings"`
	Contents                  map[string]string `json:"contents"`
	Data                      map[string]string `json:"data,omitempty"`
}

// SendPushToExternalIDs envoie une notification en ciblant par External ID.
// externalIDs = identifiants utilisateurs (chaînes).
func SendPushToExternalIDs(externalIDs []string, title, body string, data map[string]string) error {
	appID := os.Getenv("ONESIGNAL_APP_ID")
	if appID == "" || os.Getenv("ONESIGNAL_API_KEY") == "" {
		log.Println("[ONESIGNAL] ONESIGNAL_APP_ID ou ONESIGNAL_API_KEY non configurés — push ignoré")
		return nil
	}
	if len(externalIDs) == 0 {
		return nil
	}

	payload := onesignalExternalPayload{
		AppID:                     appID,
		IncludeExternalUserIDs:    externalIDs,
		ChannelForExternalUserIDs: "push",
		Headings:                  map[string]string{"fr": title, "en": title},
		Contents:                  map[string]string{"fr": body, "en": body},
		Data:                      data,
	}
	if err := postOneSignal(payload); err != nil {
		return err
	}
	log.Printf("[ONESIGNAL] Push envoyé à %d destinataire(s) (external_id) — titre: %s", len(externalIDs), title)
	return nil
}

// postOneSignal effectue l'appel HTTP POST authentifié vers l'API OneSignal.
func postOneSignal(payload any) error {
	raw, err := json.Marshal(payload)
	if err != nil {
		return err
	}

	req, err := http.NewRequest("POST", onesignalEndpointURL(), bytes.NewReader(raw))
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Basic "+os.Getenv("ONESIGNAL_API_KEY"))

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		log.Printf("[ONESIGNAL] Erreur HTTP : %v", err)
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		bodyBytes, _ := io.ReadAll(resp.Body)
		e := fmt.Errorf("onesignal HTTP %d : %s", resp.StatusCode, string(bodyBytes))
		log.Printf("[ONESIGNAL] %v", e)
		return e
	}
	return nil
}

// NotifierObjetsEnConteneur envoie une notification "prêt à récupérer" au pro.
func NotifierObjetsEnConteneur(playerID string, commandeID int, conteneurRef string) error {
	title := "Vos objets sont prêts !"
	body := fmt.Sprintf("La commande #%d est disponible dans le conteneur %s. Vous avez 7 jours pour la récupérer.", commandeID, conteneurRef)
	return SendPushToPlayer(playerID, title, body)
}

// NotifierRappelEvenement envoie un rappel d'événement au pro.
func NotifierRappelEvenement(playerID string, titreEv string, dateDebut string) error {
	title := "Rappel événement"
	body := fmt.Sprintf("L'événement « %s » commence le %s. N'oubliez pas !", titreEv, dateDebut)
	return SendPushToPlayer(playerID, title, body)
}

// NotifierAlerteMateriauPush envoie une alerte matériau en canal push (Expert Pro).
func NotifierAlerteMateriauPush(playerID, materiau string, nbAnnonces int, ville string) error {
	title := "Nouvelle alerte matériau"
	body := fmt.Sprintf("%d annonce(s) de %s disponible(s) près de %s.", nbAnnonces, materiau, ville)
	return SendPushToPlayer(playerID, title, body)
}

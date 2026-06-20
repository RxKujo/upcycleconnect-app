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

const onesignalEndpoint = "https://onesignal.com/api/v1/notifications"

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

// SendPushToPlayers envoie une notification à plusieurs joueurs.
// data est facultatif (métadonnées pour le client mobile/web).
func SendPushToPlayers(playerIDs []string, title, body string, data map[string]string) error {
	appID := os.Getenv("ONESIGNAL_APP_ID")
	apiKey := os.Getenv("ONESIGNAL_API_KEY")
	if appID == "" || apiKey == "" {
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
	}
	if data != nil {
		payload.Data = data
	}

	raw, err := json.Marshal(payload)
	if err != nil {
		return err
	}

	req, err := http.NewRequest("POST", onesignalEndpoint, bytes.NewReader(raw))
	if err != nil {
		return err
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("Authorization", "Basic "+apiKey)

	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		log.Printf("[ONESIGNAL] Erreur HTTP : %v", err)
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode >= 400 {
		bodyBytes, _ := io.ReadAll(resp.Body)
		err := fmt.Errorf("onesignal HTTP %d : %s", resp.StatusCode, string(bodyBytes))
		log.Printf("[ONESIGNAL] %v", err)
		return err
	}

	log.Printf("[ONESIGNAL] Push envoyé à %d joueur(s) — titre: %s", len(playerIDs), title)
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

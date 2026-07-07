// Package glpi est un client minimal de l'API REST GLPI, utilisé pour miroiter
// les tickets d'incident de l'app vers GLPI (l'app reste la source de vérité).
//
// Configuration (variables d'environnement) :
//
//	GLPI_URL        — base de l'API REST, ex : http://glpi/apirest.php
//	GLPI_APP_TOKEN  — App-Token (généré dans Configuration > Générale > API)
//	GLPI_USER_TOKEN — jeton API d'un compte technique (Préférences > jetons API)
//
// Si l'une des variables manque, les appels sont ignorés silencieusement
// (no-op) pour ne pas bloquer le flux métier quand GLPI n'est pas déployé.
package glpi

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"
)

// Statuts GLPI (champ status d'un Ticket).
const (
	StatusNew    = 1
	StatusSolved = 5
	StatusClosed = 6
)

var httpClient = &http.Client{Timeout: 10 * time.Second}

func cfg() (url, appToken, userToken string, ok bool) {
	url = os.Getenv("GLPI_URL")
	appToken = os.Getenv("GLPI_APP_TOKEN")
	userToken = os.Getenv("GLPI_USER_TOKEN")
	ok = url != "" && appToken != "" && userToken != ""
	return
}

// Configured indique si GLPI est configuré (sinon les appels sont des no-op).
func Configured() bool {
	_, _, _, ok := cfg()
	return ok
}

// initSession ouvre une session GLPI et renvoie le Session-Token.
func initSession(url, appToken, userToken string) (string, error) {
	req, err := http.NewRequest(http.MethodGet, url+"/initSession", nil)
	if err != nil {
		return "", err
	}
	req.Header.Set("App-Token", appToken)
	req.Header.Set("Authorization", "user_token "+userToken)

	resp, err := httpClient.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 400 {
		b, _ := io.ReadAll(resp.Body)
		return "", fmt.Errorf("glpi initSession HTTP %d : %s", resp.StatusCode, string(b))
	}
	var out struct {
		SessionToken string `json:"session_token"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&out); err != nil {
		return "", err
	}
	if out.SessionToken == "" {
		return "", fmt.Errorf("glpi initSession : session_token vide")
	}
	return out.SessionToken, nil
}

func killSession(url, appToken, sessionToken string) {
	req, err := http.NewRequest(http.MethodGet, url+"/killSession", nil)
	if err != nil {
		return
	}
	req.Header.Set("App-Token", appToken)
	req.Header.Set("Session-Token", sessionToken)
	if resp, err := httpClient.Do(req); err == nil {
		resp.Body.Close()
	}
}

// CreateTicket crée un ticket dans GLPI et renvoie son id (chaîne). Renvoie
// ("", nil) si GLPI n'est pas configuré.
func CreateTicket(title, content string) (string, error) {
	url, appToken, userToken, ok := cfg()
	if !ok {
		return "", nil
	}
	session, err := initSession(url, appToken, userToken)
	if err != nil {
		return "", err
	}
	defer killSession(url, appToken, session)

	payload := map[string]any{"input": map[string]any{"name": title, "content": content}}
	raw, _ := json.Marshal(payload)

	req, err := http.NewRequest(http.MethodPost, url+"/Ticket", bytes.NewReader(raw))
	if err != nil {
		return "", err
	}
	req.Header.Set("App-Token", appToken)
	req.Header.Set("Session-Token", session)
	req.Header.Set("Content-Type", "application/json")

	resp, err := httpClient.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 400 {
		b, _ := io.ReadAll(resp.Body)
		return "", fmt.Errorf("glpi createTicket HTTP %d : %s", resp.StatusCode, string(b))
	}
	var out struct {
		ID json.Number `json:"id"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&out); err != nil {
		return "", err
	}
	return out.ID.String(), nil
}

// UpdateTicketStatus met à jour le statut d'un ticket GLPI existant. No-op si
// GLPI n'est pas configuré ou si glpiID est vide.
func UpdateTicketStatus(glpiID string, status int) error {
	if glpiID == "" {
		return nil
	}
	url, appToken, userToken, ok := cfg()
	if !ok {
		return nil
	}
	session, err := initSession(url, appToken, userToken)
	if err != nil {
		return err
	}
	defer killSession(url, appToken, session)

	payload := map[string]any{"input": map[string]any{"id": glpiID, "status": status}}
	raw, _ := json.Marshal(payload)

	req, err := http.NewRequest(http.MethodPut, url+"/Ticket/"+glpiID, bytes.NewReader(raw))
	if err != nil {
		return err
	}
	req.Header.Set("App-Token", appToken)
	req.Header.Set("Session-Token", session)
	req.Header.Set("Content-Type", "application/json")

	resp, err := httpClient.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()
	if resp.StatusCode >= 400 {
		b, _ := io.ReadAll(resp.Body)
		return fmt.Errorf("glpi updateTicket HTTP %d : %s", resp.StatusCode, string(b))
	}
	return nil
}

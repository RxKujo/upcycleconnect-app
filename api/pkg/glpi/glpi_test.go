package glpi

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

// TestCreateTicket vérifie le cycle initSession → POST Ticket → killSession
// contre un GLPI mocké (sans instance réelle).
func TestCreateTicket(t *testing.T) {
	var calls []string
	var ticketBody map[string]any
	var appTokenSeen, sessionSeen string

	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		calls = append(calls, r.Method+" "+r.URL.Path)
		switch {
		case strings.HasSuffix(r.URL.Path, "/initSession"):
			if r.Header.Get("Authorization") != "user_token utok" {
				t.Errorf("Authorization = %q", r.Header.Get("Authorization"))
			}
			appTokenSeen = r.Header.Get("App-Token")
			_, _ = w.Write([]byte(`{"session_token":"sess-42"}`))
		case strings.HasSuffix(r.URL.Path, "/Ticket"):
			sessionSeen = r.Header.Get("Session-Token")
			raw, _ := io.ReadAll(r.Body)
			_ = json.Unmarshal(raw, &ticketBody)
			w.WriteHeader(http.StatusCreated)
			_, _ = w.Write([]byte(`{"id":1007,"message":""}`))
		case strings.HasSuffix(r.URL.Path, "/killSession"):
			_, _ = w.Write([]byte(`{}`))
		}
	}))
	defer srv.Close()

	t.Setenv("GLPI_URL", srv.URL)
	t.Setenv("GLPI_APP_TOKEN", "atok")
	t.Setenv("GLPI_USER_TOKEN", "utok")

	id, err := CreateTicket("Sujet incident", "Description détaillée")
	if err != nil {
		t.Fatalf("CreateTicket erreur : %v", err)
	}
	if id != "1007" {
		t.Errorf("id GLPI = %q, attendu 1007", id)
	}
	if appTokenSeen != "atok" {
		t.Errorf("App-Token = %q, attendu atok", appTokenSeen)
	}
	if sessionSeen != "sess-42" {
		t.Errorf("Session-Token = %q, attendu sess-42", sessionSeen)
	}
	input, _ := ticketBody["input"].(map[string]any)
	if input == nil || input["name"] != "Sujet incident" || input["content"] != "Description détaillée" {
		t.Errorf("input du ticket incorrect : %v", ticketBody)
	}
	// initSession, Ticket, killSession doivent tous être appelés.
	joined := strings.Join(calls, " | ")
	for _, want := range []string{"/initSession", "/Ticket", "/killSession"} {
		if !strings.Contains(joined, want) {
			t.Errorf("appel manquant %s (appels: %s)", want, joined)
		}
	}
}

// TestNonConfigure : sans variables GLPI, CreateTicket est un no-op sans erreur.
func TestNonConfigure(t *testing.T) {
	t.Setenv("GLPI_URL", "")
	t.Setenv("GLPI_APP_TOKEN", "")
	t.Setenv("GLPI_USER_TOKEN", "")
	if Configured() {
		t.Fatal("Configured() devrait être false")
	}
	id, err := CreateTicket("x", "y")
	if err != nil || id != "" {
		t.Errorf("attendu ('', nil) non configuré, obtenu (%q, %v)", id, err)
	}
}

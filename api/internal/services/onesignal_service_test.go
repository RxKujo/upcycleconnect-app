package services

import (
	"encoding/json"
	"io"
	"net/http"
	"net/http/httptest"
	"testing"
)

// TestSendPushToExternalIDs vérifie que la requête émise vers OneSignal est
// correcte (payload External ID + auth), sans compte réel, via un serveur mock.
func TestSendPushToExternalIDs(t *testing.T) {
	var gotAuth, gotContentType string
	var gotBody map[string]any

	srv := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		gotAuth = r.Header.Get("Authorization")
		gotContentType = r.Header.Get("Content-Type")
		raw, _ := io.ReadAll(r.Body)
		_ = json.Unmarshal(raw, &gotBody)
		w.WriteHeader(http.StatusOK)
		_, _ = w.Write([]byte(`{"id":"mock-notif"}`))
	}))
	defer srv.Close()

	t.Setenv("ONESIGNAL_APP_ID", "app-123")
	t.Setenv("ONESIGNAL_API_KEY", "rest-key-abc")
	t.Setenv("ONESIGNAL_API_BASE", srv.URL)

	err := SendPushToExternalIDs([]string{"42", "43"}, "Titre", "Corps", map[string]string{"type": "depot_arrive"})
	if err != nil {
		t.Fatalf("SendPushToExternalIDs a renvoyé une erreur : %v", err)
	}

	if gotContentType != "application/json" {
		t.Errorf("Content-Type = %q, attendu application/json", gotContentType)
	}
	if gotAuth != "Basic rest-key-abc" {
		t.Errorf("Authorization = %q, attendu Basic rest-key-abc", gotAuth)
	}
	if gotBody["app_id"] != "app-123" {
		t.Errorf("app_id = %v, attendu app-123", gotBody["app_id"])
	}
	if gotBody["channel_for_external_user_ids"] != "push" {
		t.Errorf("channel_for_external_user_ids = %v, attendu push", gotBody["channel_for_external_user_ids"])
	}
	ids, ok := gotBody["include_external_user_ids"].([]any)
	if !ok || len(ids) != 2 || ids[0] != "42" || ids[1] != "43" {
		t.Errorf("include_external_user_ids = %v, attendu [42 43]", gotBody["include_external_user_ids"])
	}
	if contents, ok := gotBody["contents"].(map[string]any); !ok || contents["fr"] != "Corps" {
		t.Errorf("contents.fr = %v, attendu Corps", gotBody["contents"])
	}
}

// TestSendPushSansConfig : sans clés configurées, l'envoi est ignoré sans erreur.
func TestSendPushSansConfig(t *testing.T) {
	t.Setenv("ONESIGNAL_APP_ID", "")
	t.Setenv("ONESIGNAL_API_KEY", "")
	if err := SendPushToExternalIDs([]string{"1"}, "t", "b", nil); err != nil {
		t.Errorf("attendu nil sans config, obtenu %v", err)
	}
}

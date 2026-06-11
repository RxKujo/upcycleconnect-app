package middleware

import (
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestCORSAllowedOrigin(t *testing.T) {
	rec := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/api/v1/public/annonces", nil)
	req.Header.Set("Origin", "http://localhost:8000")

	CORS(rec, req)

	if got := rec.Header().Get("Access-Control-Allow-Origin"); got != "http://localhost:8000" {
		t.Errorf("origine autorisée: ACAO = %q, attendu %q", got, "http://localhost:8000")
	}
	if got := rec.Header().Get("Access-Control-Allow-Credentials"); got != "true" {
		t.Errorf("origine autorisée: credentials = %q, attendu \"true\"", got)
	}
}

func TestCORSDisallowedOrigin(t *testing.T) {
	rec := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodGet, "/api/v1/public/annonces", nil)
	req.Header.Set("Origin", "https://site-malveillant.example")

	CORS(rec, req)

	if got := rec.Header().Get("Access-Control-Allow-Origin"); got != "" {
		t.Errorf("origine non autorisée: ACAO = %q, attendu vide", got)
	}
	if got := rec.Header().Get("Access-Control-Allow-Credentials"); got != "" {
		t.Errorf("origine non autorisée: credentials = %q, attendu vide", got)
	}
}

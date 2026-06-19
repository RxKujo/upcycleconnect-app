package handlers

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"regexp"
	"strings"
	"time"
)

// ─── Réponses JSON ────────────────────────────────────────────────────────────

func jsonOK(w http.ResponseWriter, data interface{}, status int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(data)
}

func jsonErr(w http.ResponseWriter, msg string, status int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(map[string]string{"erreur": msg})
}

// ─── Scan helpers — évitent le pattern sql.NullX répété partout ──────────────

func scanNullFloat64(n sql.NullFloat64) *float64 {
	if !n.Valid {
		return nil
	}
	v := n.Float64
	return &v
}

func scanNullString(n sql.NullString) *string {
	if !n.Valid {
		return nil
	}
	v := n.String
	return &v
}

func scanNullInt(n sql.NullInt64) *int {
	if !n.Valid {
		return nil
	}
	v := int(n.Int64)
	return &v
}

func scanNullTime(n sql.NullTime) string {
	if !n.Valid {
		return ""
	}
	return n.Time.Format(time.RFC3339)
}

// ─── Logging structuré ───────────────────────────────────────────────────────

func logInfo(handler, format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	log.Printf("[INFO]  %s | %s | %s", time.Now().Format(time.RFC3339), handler, msg)
}

func logError(handler, format string, args ...interface{}) {
	msg := fmt.Sprintf(format, args...)
	log.Printf("[ERROR] %s | %s | %s", time.Now().Format(time.RFC3339), handler, msg)
}

// ─── Validation ──────────────────────────────────────────────────────────────

var emailRegex = regexp.MustCompile(`^[^@\s]+@[^@\s]+\.[^@\s]+$`)

func isValidEmail(email string) bool {
	email = strings.TrimSpace(email)
	if len(email) < 3 || len(email) > 254 {
		return false
	}
	return emailRegex.MatchString(email)
}

// Fichier auth.go : authentification par jeton JWT (Bearer). Vérifie la signature
// HMAC et extrait l'identifiant et le rôle de l'utilisateur.

package middleware

import (
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"strings"

	"github.com/golang-jwt/jwt/v5"
)

// AuthRequired valide le jeton Bearer et renvoie (userId, role, true) si l'accès
// est autorisé ; sinon écrit une réponse 401 et renvoie ok=false.
func AuthRequired(w http.ResponseWriter, r *http.Request) (int, string, bool) {
	authHeader := r.Header.Get("Authorization")
	if authHeader == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "en-tête Authorization manquant"})
		return 0, "", false
	}

	parts := strings.Split(authHeader, " ")
	if len(parts) != 2 || parts[0] != "Bearer" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "format d'en-tête invalide"})
		return 0, "", false
	}

	tokenString := parts[1]
	secret := os.Getenv("JWT_SECRET")

	claims := jwt.MapClaims{}
	token, err := jwt.ParseWithClaims(tokenString, claims, func(token *jwt.Token) (interface{}, error) {
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, fmt.Errorf("unexpected signing method")
		}
		return []byte(secret), nil
	})

	if err != nil || !token.Valid {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "token invalide ou expiré"})
		return 0, "", false
	}

	// Assertions de type protégées : un token signé valide mais au format
	// inattendu ne doit pas provoquer de panic du handler.
	idClaim, okID := claims["id"].(float64)
	role, okRole := claims["role"].(string)
	if !okID || !okRole || role == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "token invalide ou expiré"})
		return 0, "", false
	}

	return int(idClaim), role, true
}

// AdminRequired indique si le rôle correspond à un administrateur.
func AdminRequired(role string) bool {
	return role == "admin"
}

// OptionalAuth extrait (userId, role) du token s'il est présent et valide, sans
// écrire d'erreur. Renvoie ok=false si absent/invalide (routes publiques qui
// adaptent leur réponse selon l'authentification).
func OptionalAuth(r *http.Request) (int, string, bool) {
	parts := strings.Split(r.Header.Get("Authorization"), " ")
	if len(parts) != 2 || parts[0] != "Bearer" {
		return 0, "", false
	}
	claims := jwt.MapClaims{}
	token, err := jwt.ParseWithClaims(parts[1], claims, func(t *jwt.Token) (interface{}, error) {
		if _, ok := t.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, fmt.Errorf("unexpected signing method")
		}
		return []byte(os.Getenv("JWT_SECRET")), nil
	})
	if err != nil || !token.Valid {
		return 0, "", false
	}
	idClaim, okID := claims["id"].(float64)
	role, okRole := claims["role"].(string)
	if !okID || !okRole {
		return 0, "", false
	}
	return int(idClaim), role, true
}

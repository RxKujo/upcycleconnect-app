package middleware

import (
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"strings"

	"github.com/golang-jwt/jwt/v5"
)

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

	userId := int(claims["id"].(float64))
	role := claims["role"].(string)

	return userId, role, true
}

func AdminRequired(role string) bool {
	return role == "admin"
}

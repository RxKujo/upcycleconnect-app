// Fichier cors.go : gestion des en-têtes CORS avec liste blanche d'origines
// (origines par défaut + variable d'environnement CORS_ALLOWED_ORIGINS).

package middleware

import (
	"net/http"
	"os"
	"strings"
	"sync"
)

// origines autorisées par défaut (environnement de développement local)
var defaultAllowedOrigins = []string{
	"http://localhost:8000",
	"http://127.0.0.1:8000",
	"http://localhost:5173",
	"http://127.0.0.1:5173",
}

var (
	allowedOriginsOnce sync.Once
	allowedOrigins     map[string]bool
)

// loadAllowedOrigins construit la liste blanche à partir des origines par défaut
// et de la variable d'environnement CORS_ALLOWED_ORIGINS (séparée par des virgules).
func loadAllowedOrigins() map[string]bool {
	allowedOriginsOnce.Do(func() {
		allowedOrigins = make(map[string]bool)
		for _, o := range defaultAllowedOrigins {
			allowedOrigins[o] = true
		}
		if env := os.Getenv("CORS_ALLOWED_ORIGINS"); env != "" {
			for _, o := range strings.Split(env, ",") {
				o = strings.TrimSpace(o)
				if o != "" {
					allowedOrigins[o] = true
				}
			}
		}
	})
	return allowedOrigins
}

// CORS positionne les en-têtes CORS sur la réponse ; l'origine n'est reflétée
// (avec credentials) que si elle figure dans la liste blanche.
func CORS(w http.ResponseWriter, r *http.Request) {
	origin := r.Header.Get("Origin")

	// On ne reflète l'origine (avec credentials) que si elle est dans la liste blanche.
	// Cela évite qu'un site tiers puisse effectuer des requêtes authentifiées.
	if origin != "" && loadAllowedOrigins()[origin] {
		w.Header().Set("Access-Control-Allow-Origin", origin)
		w.Header().Set("Vary", "Origin")
		w.Header().Set("Access-Control-Allow-Credentials", "true")
	}

	w.Header().Set("Access-Control-Allow-Headers", "Content-Type, Content-Length, Accept-Encoding, X-CSRF-Token, Authorization, accept, origin, Cache-Control, X-Requested-With")
	w.Header().Set("Access-Control-Allow-Methods", "POST, OPTIONS, GET, PUT, DELETE")
}

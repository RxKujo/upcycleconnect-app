package router

import (
	"api/internal/handlers"
	"api/internal/middleware"
	"encoding/json"
	"net/http"
)

type Router struct {
	handler http.Handler
}

func New() *Router {
	mux := http.NewServeMux()

	// ---------------------------------------------------------------
	// Public
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/evenements/catalogue", handlers.GetCatalogueEvenements)
	mux.HandleFunc("GET /api/v1/evenements/{id}", withId(handlers.GetEvenement))
	mux.HandleFunc("POST /api/v1/auth/register", handlers.Register)
	mux.HandleFunc("POST /api/v1/auth/register-particulier", handlers.RegisterParticulier)
	mux.HandleFunc("POST /api/v1/auth/register-professionnel", handlers.RegisterProfessionnel)
	mux.HandleFunc("POST /api/v1/auth/login", handlers.Login)

	// ---------------------------------------------------------------
	// Authenticated: profil utilisateur
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/utilisateurs/me", authUid(handlers.GetMe))
	mux.HandleFunc("PUT /api/v1/utilisateurs/me", authUid(handlers.UpdateMe))
	mux.HandleFunc("PUT /api/v1/utilisateurs/me/notifications", authUid(handlers.UpdateNotifications))
	mux.HandleFunc("GET /api/v1/utilisateurs/me/evenements-inscrits", authUid(handlers.GetEnrolledEvents))
	mux.HandleFunc("GET /api/v1/utilisateurs/me/export-pdf", authUid(handlers.ExportPDF))
	mux.HandleFunc("GET /api/v1/utilisateurs/{id}/planning", authIdUidRole(handlers.GetUtilisateurPlanning))

	// ---------------------------------------------------------------
	// Authenticated: evenements (inscriptions, tickets)
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/evenements/{id}/ticket", authId(handlers.GetTicketPDF))
	mux.HandleFunc("POST /api/v1/evenements/{id}/inscrire", authId(handlers.InscrireEvenement))

	// ---------------------------------------------------------------
	// Authenticated: annonces
	// ---------------------------------------------------------------
	mux.HandleFunc("POST /api/v1/annonces", authParticulier(handlers.CreateAnnonce))
	mux.HandleFunc("GET /api/v1/annonces/{id}", authIdUidRole(handlers.GetAnnonceAuth))
	mux.HandleFunc("DELETE /api/v1/annonces/{id}", authIdUidRole(handlers.DeleteAnnonce))
	mux.HandleFunc("PUT /api/v1/annonces/{id}/annuler", authIdUid(handlers.CancelAnnonce))

	// ---------------------------------------------------------------
	// Authenticated: catalogue
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/catalogue", authRole(handlers.GetCatalogueItems))
	mux.HandleFunc("GET /api/catalogue/{id}", authIdRole(handlers.GetCatalogueItem))
	mux.HandleFunc("DELETE /api/catalogue/{id}", authIdUidRole(handlers.DeleteCatalogueItem))
	mux.HandleFunc("PUT /api/catalogue/{id}/valider", authIdUidRole(handlers.ValiderCatalogueItem))
	mux.HandleFunc("PUT /api/catalogue/{id}/refuser", authIdUidRole(handlers.RefuserCatalogueItem))

	// ---------------------------------------------------------------
	// Admin: utilisateurs
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/utilisateurs", adminOnly(handlers.GetAllUtilisateurs))
	mux.HandleFunc("GET /api/v1/admin/abonnements", adminOnly(handlers.GetAbonnements))
	mux.HandleFunc("GET /api/v1/admin/utilisateurs/{id}", adminId(handlers.GetUtilisateur))
	mux.HandleFunc("DELETE /api/v1/admin/utilisateurs/{id}", adminIdUid(handlers.DeleteUtilisateur))
	mux.HandleFunc("PUT /api/v1/admin/utilisateurs/{id}/role", adminId(handlers.ChangeRole))
	mux.HandleFunc("PUT /api/v1/admin/utilisateurs/{id}/ban", adminId(handlers.BanUtilisateur))
	mux.HandleFunc("PUT /api/v1/admin/utilisateurs/{id}/unban", adminId(handlers.UnbanUtilisateur))
	mux.HandleFunc("POST /api/v1/admin/utilisateurs/{id}/subscription", adminIdUid(handlers.AssignSubscription))
	mux.HandleFunc("DELETE /api/v1/admin/utilisateurs/{id}/subscription/{subId}", func(w http.ResponseWriter, r *http.Request) {
		uid, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "admin" {
			jsonError(w, http.StatusForbidden, "acces refuse: privileges d'administrateur requis")
			return
		}
		handlers.RemoveSubscription(w, r, r.PathValue("id"), r.PathValue("subId"), uid)
	})

	// ---------------------------------------------------------------
	// Admin: commandes
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/commandes", adminOnly(handlers.GetCommandes))
	mux.HandleFunc("GET /api/v1/admin/commandes/{id}", adminId(handlers.GetCommande))
	mux.HandleFunc("PUT /api/v1/admin/commandes/{id}/statut", adminId(handlers.UpdateCommandeStatut))

	// ---------------------------------------------------------------
	// Admin: categories
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/categories", adminOnly(handlers.GetCategories))
	mux.HandleFunc("POST /api/v1/admin/categories", adminOnly(handlers.CreateCategorie))
	mux.HandleFunc("PUT /api/v1/admin/categories/{id}", adminId(handlers.UpdateCategorie))
	mux.HandleFunc("DELETE /api/v1/admin/categories/{id}", adminId(handlers.DeleteCategorie))

	// ---------------------------------------------------------------
	// Admin: prestations
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/prestations", adminOnly(handlers.GetPrestations))
	mux.HandleFunc("POST /api/v1/admin/prestations", adminOnly(handlers.CreatePrestation))
	mux.HandleFunc("GET /api/v1/admin/prestations/{id}", adminId(handlers.GetPrestation))
	mux.HandleFunc("PUT /api/v1/admin/prestations/{id}/valider", adminId(handlers.ValiderPrestation))
	mux.HandleFunc("PUT /api/v1/admin/prestations/{id}/refuser", adminId(handlers.RefuserPrestation))

	// ---------------------------------------------------------------
	// Admin: evenements
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/evenements", adminOnly(handlers.GetEvenements))
	mux.HandleFunc("POST /api/v1/admin/evenements", adminUid(handlers.CreateEvenement))
	mux.HandleFunc("GET /api/v1/admin/evenements/{id}", adminId(handlers.GetEvenement))
	mux.HandleFunc("PUT /api/v1/admin/evenements/{id}", adminId(handlers.UpdateEvenement))
	mux.HandleFunc("DELETE /api/v1/admin/evenements/{id}", adminId(handlers.DeleteEvenement))
	mux.HandleFunc("PUT /api/v1/admin/evenements/{id}/valider", adminIdUid(handlers.ValiderEvenement))
	mux.HandleFunc("PUT /api/v1/admin/evenements/{id}/attente", adminId(handlers.AttenteEvenement))
	mux.HandleFunc("PUT /api/v1/admin/evenements/{id}/refuser", adminId(handlers.RefuserEvenement))

	// ---------------------------------------------------------------
	// Admin: annonces
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/annonces", adminOnly(handlers.GetAnnonces))
	mux.HandleFunc("GET /api/v1/admin/annonces/{id}", adminId(handlers.GetAnnonce))
	mux.HandleFunc("PUT /api/v1/admin/annonces/{id}/valider", adminIdUid(handlers.ValiderAnnonce))
	mux.HandleFunc("PUT /api/v1/admin/annonces/{id}/refuser", adminId(handlers.RefuserAnnonce))
	mux.HandleFunc("PUT /api/v1/admin/annonces/{id}/attente", adminId(handlers.AttenteAnnonce))

	// ---------------------------------------------------------------
	// Admin: conteneurs
	// ---------------------------------------------------------------
	mux.HandleFunc("GET /api/v1/admin/conteneurs", adminOnly(handlers.GetAllConteneurs))
	mux.HandleFunc("POST /api/v1/admin/conteneurs", adminOnly(handlers.CreateConteneur))
	mux.HandleFunc("GET /api/v1/admin/conteneurs/{id}", adminId(handlers.GetConteneurDetails))
	mux.HandleFunc("POST /api/v1/admin/conteneurs/scan", adminOnly(handlers.ScanBarcodeAndUpdateCommande))
	mux.HandleFunc("POST /api/v1/admin/conteneurs/codes-barres", adminOnly(handlers.CreateCodeBarre))
	mux.HandleFunc("PUT /api/v1/admin/conteneurs/tickets/{id}/resolve", adminId(handlers.ResolveTicket))

	// Catch-all
	mux.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
		jsonError(w, http.StatusNotFound, "route non trouvee")
	})

	return &Router{handler: withCORS(mux)}
}

func (r *Router) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	r.handler.ServeHTTP(w, req)
}

// ---------------------------------------------------------------------------
// CORS middleware
// ---------------------------------------------------------------------------

func withCORS(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		middleware.CORS(w, r)
		if r.Method == "OPTIONS" {
			w.WriteHeader(http.StatusNoContent)
			return
		}
		next.ServeHTTP(w, r)
	})
}

// ---------------------------------------------------------------------------
// Route wrappers: public
// ---------------------------------------------------------------------------

func withId(h func(http.ResponseWriter, *http.Request, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		h(w, r, r.PathValue("id"))
	}
}

// ---------------------------------------------------------------------------
// Route wrappers: auth required
// ---------------------------------------------------------------------------

func authUid(h func(http.ResponseWriter, *http.Request, int)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, _, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, uid)
	}
}

func authRole(h func(http.ResponseWriter, *http.Request, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		_, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, role)
	}
}

func authId(h func(http.ResponseWriter, *http.Request, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		_, _, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, r.PathValue("id"))
	}
}

func authIdUid(h func(http.ResponseWriter, *http.Request, string, int)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, _, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, r.PathValue("id"), uid)
	}
}

func authIdRole(h func(http.ResponseWriter, *http.Request, string, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		_, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, r.PathValue("id"), role)
	}
}

func authIdUidRole(h func(http.ResponseWriter, *http.Request, string, int, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		h(w, r, r.PathValue("id"), uid, role)
	}
}

func authParticulier(h func(http.ResponseWriter, *http.Request, int)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "particulier" {
			jsonError(w, http.StatusForbidden, "acces refuse: seuls les particuliers peuvent poster des annonces")
			return
		}
		h(w, r, uid)
	}
}

// ---------------------------------------------------------------------------
// Route wrappers: admin required
// ---------------------------------------------------------------------------

func adminOnly(h http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		_, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "admin" {
			jsonError(w, http.StatusForbidden, "acces refuse: privileges d'administrateur requis")
			return
		}
		h(w, r)
	}
}

func adminId(h func(http.ResponseWriter, *http.Request, string)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		_, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "admin" {
			jsonError(w, http.StatusForbidden, "acces refuse: privileges d'administrateur requis")
			return
		}
		h(w, r, r.PathValue("id"))
	}
}

func adminUid(h func(http.ResponseWriter, *http.Request, int)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "admin" {
			jsonError(w, http.StatusForbidden, "acces refuse: privileges d'administrateur requis")
			return
		}
		h(w, r, uid)
	}
}

func adminIdUid(h func(http.ResponseWriter, *http.Request, string, int)) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		uid, role, ok := middleware.AuthRequired(w, r)
		if !ok {
			return
		}
		if role != "admin" {
			jsonError(w, http.StatusForbidden, "acces refuse: privileges d'administrateur requis")
			return
		}
		h(w, r, r.PathValue("id"), uid)
	}
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

func jsonError(w http.ResponseWriter, status int, message string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(map[string]string{"erreur": message})
}

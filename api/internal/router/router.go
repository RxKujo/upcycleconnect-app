package router

import (
	"api/internal/handlers"
	"api/internal/middleware"
	"encoding/json"
	"net/http"
	"strings"
)

type Router struct {
	mux *http.ServeMux
}

func New() *Router {
	return &Router{
		mux: http.NewServeMux(),
	}
}

func (r *Router) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	middleware.CORS(w, req)
	if req.Method == "OPTIONS" {
		w.WriteHeader(http.StatusNoContent)
		return
	}

	path := req.URL.Path
	method := req.Method

	// Public routes
	if match(path, "/api/v1/evenements/catalogue") && method == "GET" {
		handlers.GetCatalogueEvenements(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 1 && method == "GET" {
		handlers.GetEvenement(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/auth/register") && method == "POST" {
		handlers.Register(w, req)
		return
	}
	if match(path, "/api/v1/auth/register-particulier") && method == "POST" {
		handlers.RegisterParticulier(w, req)
		return
	}
	if match(path, "/api/v1/auth/login") && method == "POST" {
		handlers.Login(w, req)
		return
	}

	// Authenticated routes
	userId, role, ok := middleware.AuthRequired(w, req)
	if !ok {
		return
	}

	// === User profile routes (Task 4) ===
	if match(path, "/api/v1/utilisateurs/me") && method == "GET" {
		handlers.GetMe(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me") && method == "PUT" {
		handlers.UpdateMe(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me/notifications") && method == "PUT" {
		handlers.UpdateNotifications(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me/evenements-inscrits") && method == "GET" {
		handlers.GetEnrolledEvents(w, req, userId)
		return
	}

	// === Evenements (Public/User) ===
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 2 && parts[1] == "ticket" && method == "GET" {
		handlers.GetTicketPDF(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 2 && parts[1] == "inscrire" && method == "POST" {
		handlers.InscrireEvenement(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/utilisateurs/me/export-pdf") && method == "GET" {
		handlers.ExportPDF(w, req, userId)
		return
	}

	// === Annonces routes for authenticated users (Task 3) ===
	if match(path, "/api/v1/annonces") && method == "POST" {
		if role != "particulier" {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusForbidden)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "acces refuse: seuls les particuliers peuvent poster des annonces"})
			return
		}
		handlers.CreateAnnonce(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/annonces"); len(parts) == 1 && method == "GET" {
		handlers.GetAnnonceAuth(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/v1/annonces"); len(parts) == 2 && parts[1] == "annuler" && method == "PUT" {
		handlers.CancelAnnonce(w, req, parts[0], userId)
		return
	}
	if parts := splitPath(path, "/api/v1/annonces"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteAnnonce(w, req, parts[0], userId, role)
		return
	}

	// === Catalogue routes ===
	if match(path, "/api/catalogue") && method == "GET" {
		handlers.GetCatalogueItems(w, req, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 1 && method == "GET" {
		handlers.GetCatalogueItem(w, req, parts[0], role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 2 && parts[1] == "valider" && method == "PUT" {
		handlers.ValiderCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 2 && parts[1] == "refuser" && method == "PUT" {
		handlers.RefuserCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/utilisateurs"); len(parts) == 2 && parts[1] == "planning" && method == "GET" {
		handlers.GetUtilisateurPlanning(w, req, parts[0], userId, role)
		return
	}

	// === Admin routes (require admin role) ===
	if role != "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "acces refuse: privileges d'administrateur requis"})
		return
	}

	// Admin: Utilisateurs (Task 5)
	if match(path, "/api/v1/admin/utilisateurs") && method == "GET" {
		handlers.GetAllUtilisateurs(w, req)
		return
	}
	if match(path, "/api/v1/admin/abonnements") && method == "GET" {
		handlers.GetAbonnements(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 1 && method == "GET" {
		handlers.GetUtilisateur(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteUtilisateur(w, req, parts[0], userId)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "role" && method == "PUT" {
		handlers.ChangeRole(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "ban" && method == "PUT" {
		handlers.BanUtilisateur(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "unban" && method == "PUT" {
		handlers.UnbanUtilisateur(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "subscription" && method == "POST" {
		handlers.AssignSubscription(w, req, parts[0], userId)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 3 && parts[1] == "subscription" && method == "DELETE" {
		handlers.RemoveSubscription(w, req, parts[0], parts[2], userId)
		return
	}

	// Admin: Commandes
	if match(path, "/api/v1/admin/commandes") && method == "GET" {
		handlers.GetCommandes(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/commandes"); len(parts) == 1 && method == "GET" {
		handlers.GetCommande(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/commandes"); len(parts) == 2 && parts[1] == "statut" && method == "PUT" {
		handlers.UpdateCommandeStatut(w, req, parts[0])
		return
	}

	// Admin: Categories
	if match(path, "/api/v1/admin/categories") && method == "GET" {
		handlers.GetCategories(w, req)
		return
	}
	if match(path, "/api/v1/admin/categories") && method == "POST" {
		handlers.CreateCategorie(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/categories"); len(parts) == 1 && method == "PUT" {
		handlers.UpdateCategorie(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/categories"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteCategorie(w, req, parts[0])
		return
	}

	// Admin: Prestations
	if match(path, "/api/v1/admin/prestations") && method == "GET" {
		handlers.GetPrestations(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/prestations"); len(parts) == 1 && method == "GET" {
		handlers.GetPrestation(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/admin/prestations") && method == "POST" {
		handlers.CreatePrestation(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/prestations"); len(parts) == 2 && parts[1] == "valider" && method == "PUT" {
		handlers.ValiderPrestation(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/prestations"); len(parts) == 2 && parts[1] == "refuser" && method == "PUT" {
		handlers.RefuserPrestation(w, req, parts[0])
		return
	}

	// Admin: Evenements
	if match(path, "/api/v1/admin/evenements") && method == "GET" {
		handlers.GetEvenements(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 1 && method == "GET" {
		handlers.GetEvenement(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/admin/evenements") && method == "POST" {
		handlers.CreateEvenement(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 1 && method == "PUT" {
		handlers.UpdateEvenement(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteEvenement(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 2 && parts[1] == "valider" && method == "PUT" {
		handlers.ValiderEvenement(w, req, parts[0], userId)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 2 && parts[1] == "attente" && method == "PUT" {
		handlers.AttenteEvenement(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 2 && parts[1] == "refuser" && method == "PUT" {
		handlers.RefuserEvenement(w, req, parts[0])
		return
	}

	// Admin: Annonces
	if match(path, "/api/v1/admin/annonces") && method == "GET" {
		handlers.GetAnnonces(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/annonces"); len(parts) == 1 && method == "GET" {
		handlers.GetAnnonce(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/annonces"); len(parts) == 2 && parts[1] == "valider" && method == "PUT" {
		handlers.ValiderAnnonce(w, req, parts[0], userId)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/annonces"); len(parts) == 2 && parts[1] == "refuser" && method == "PUT" {
		handlers.RefuserAnnonce(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/annonces"); len(parts) == 2 && parts[1] == "attente" && method == "PUT" {
		handlers.AttenteAnnonce(w, req, parts[0])
		return
	}

	// Admin: Conteneurs
	if match(path, "/api/v1/admin/conteneurs") && method == "GET" {
		handlers.GetAllConteneurs(w, req)
		return
	}
	if match(path, "/api/v1/admin/conteneurs") && method == "POST" {
		handlers.CreateConteneur(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/conteneurs"); len(parts) == 1 && method == "GET" {
		handlers.GetConteneurDetails(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/admin/conteneurs/scan") && method == "POST" {
		handlers.ScanBarcodeAndUpdateCommande(w, req)
		return
	}
	if match(path, "/api/v1/admin/conteneurs/codes-barres") && method == "POST" {
		handlers.CreateCodeBarre(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/conteneurs/tickets"); len(parts) == 2 && parts[1] == "resolve" && method == "PUT" {
		handlers.ResolveTicket(w, req, parts[0])
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusNotFound)
	json.NewEncoder(w).Encode(map[string]string{"erreur": "route non trouvee"})
}

func match(path, pattern string) bool {
	return path == pattern
}

func splitPath(path, prefix string) []string {
	if !strings.HasPrefix(path, prefix) {
		return nil
	}
	rest := strings.TrimPrefix(path, prefix)
	rest = strings.Trim(rest, "/")
	if rest == "" {
		return []string{}
	}
	return strings.Split(rest, "/")
}

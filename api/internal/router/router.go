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

	if match(path, "/api/v1/auth/register") && method == "POST" {
		handlers.Register(w, req)
		return
	}
	if match(path, "/api/v1/auth/login") && method == "POST" {
		handlers.Login(w, req)
		return
	}

	if match(path, "/api/v1/public/annonces") && method == "GET" {
		handlers.GetPublicAnnonces(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/public/annonces"); len(parts) == 1 && method == "GET" {
		handlers.GetPublicAnnonce(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/public/articles") && method == "GET" {
		handlers.GetPublicArticles(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/public/articles"); len(parts) == 1 && method == "GET" {
		handlers.GetPublicArticle(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/public/forum") && method == "GET" {
		handlers.GetPublicForumSujets(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/public/forum"); len(parts) == 1 && method == "GET" {
		handlers.GetPublicForumSujet(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/public/stats") && method == "GET" {
		handlers.GetPublicStats(w, req)
		return
	}
	if match(path, "/api/v1/evenements/catalogue") && method == "GET" {
		handlers.GetPublicEvenements(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 1 && method == "GET" {
		handlers.GetPublicEvenement(w, req, parts[0])
		return
	}

	userId, role, ok := middleware.AuthRequired(w, req)
	if !ok {
		return
	}

	if match(path, "/api/v1/utilisateurs/me") && method == "GET" {
		handlers.GetMe(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me") && method == "PUT" {
		handlers.UpdateMe(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me/export-pdf") && method == "GET" {
		handlers.ExportUserData(w, req, userId)
		return
	}
	if match(path, "/api/v1/utilisateurs/me/notifications") && method == "PUT" {
		handlers.UpdateNotifications(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/utilisateurs/me"); len(parts) == 1 && parts[0] == "evenements-inscrits" && method == "GET" {
		handlers.GetMesEvenementsInscrits(w, req, userId)
		return
	}
	if match(path, "/api/v1/annonces") && method == "POST" {
		handlers.CreateAnnonce(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/annonces"); len(parts) == 1 && method == "GET" {
		handlers.GetAnnonceAuth(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/v1/annonces"); len(parts) == 2 && parts[1] == "annuler" && method == "POST" {
		handlers.CancelAnnonce(w, req, parts[0], userId)
		return
	}
	if (role == "salarie" || role == "admin") && match(path, "/api/v1/evenements") && method == "POST" {
		handlers.CreateEvenement(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 2 && parts[1] == "inscrire" && method == "POST" {
		handlers.InscrireEvenement(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/evenements"); len(parts) == 2 && parts[1] == "ticket" && method == "GET" {
		handlers.GetTicketPDF(w, req, parts[0])
		return
	}

	if match(path, "/api/v1/forum/sujets") && method == "POST" {
		handlers.CreateForumSujet(w, req, userId)
		return
	}
	if parts := splitPath(path, "/api/v1/forum/sujets"); len(parts) == 2 && parts[1] == "messages" && method == "POST" {
		handlers.CreateForumMessage(w, req, parts[0], userId)
		return
	}

	if match(path, "/api/v1/commandes/checkout") && method == "POST" {
		handlers.CheckoutPanier(w, req, userId)
		return
	}
	if match(path, "/api/v1/commandes/me") && method == "GET" {
		handlers.GetMesCommandes(w, req, userId)
		return
	}

	if match(path, "/api/v1/forum/signaler") && method == "POST" {
		handlers.SignalerMessage(w, req, userId)
		return
	}

	if (role == "salarie" || role == "admin") {
		if match(path, "/api/v1/salarie/stats") && method == "GET" {
			handlers.GetSalarieStats(w, req, userId)
			return
		}
		if match(path, "/api/v1/salarie/templates") && method == "GET" {
			handlers.GetSalarieTemplates(w, req)
			return
		}
		if match(path, "/api/v1/salarie/evenements") && method == "GET" {
			handlers.GetSalarieEvenements(w, req, userId)
			return
		}
		if match(path, "/api/v1/salarie/evenements") && method == "POST" {
			handlers.CreateSalarieEvenement(w, req, userId)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/evenements"); len(parts) == 1 && method == "GET" {
			handlers.GetSalarieEvenement(w, req, parts[0], userId, role)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/evenements"); len(parts) == 1 && method == "PUT" {
			handlers.UpdateSalarieEvenement(w, req, parts[0], userId, role)
			return
		}

		if match(path, "/api/v1/salarie/articles") && method == "GET" {
			handlers.GetArticles(w, req)
			return
		}
		if match(path, "/api/v1/salarie/articles") && method == "POST" {
			handlers.CreateArticle(w, req, userId)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/articles"); len(parts) == 1 && method == "GET" {
			handlers.GetArticle(w, req, parts[0])
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/articles"); len(parts) == 1 && method == "PUT" {
			handlers.UpdateArticle(w, req, parts[0], userId, role)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/articles"); len(parts) == 1 && method == "DELETE" {
			handlers.DeleteArticle(w, req, parts[0], userId, role)
			return
		}

		if match(path, "/api/v1/salarie/signalements") && method == "GET" {
			handlers.GetSignalements(w, req)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/messages"); len(parts) == 2 && parts[1] == "masquer" && method == "PUT" {
			handlers.MasquerMessage(w, req, parts[0])
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/messages"); len(parts) == 2 && parts[1] == "restaurer" && method == "PUT" {
			handlers.RestaurerMessage(w, req, parts[0])
			return
		}
		if match(path, "/api/v1/salarie/sujets") && method == "GET" {
			handlers.GetSujetsModeration(w, req)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/sujets"); len(parts) == 2 && parts[1] == "lock" && method == "PUT" {
			handlers.LockSujet(w, req, parts[0])
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/sujets"); len(parts) == 2 && parts[1] == "unlock" && method == "PUT" {
			handlers.UnlockSujet(w, req, parts[0])
			return
		}
		if match(path, "/api/v1/salarie/mots-bannis") && method == "GET" {
			handlers.GetMotsBannis(w, req)
			return
		}
		if match(path, "/api/v1/salarie/mots-bannis") && method == "POST" {
			handlers.AddMotBanni(w, req, userId)
			return
		}
		if parts := splitPath(path, "/api/v1/salarie/mots-bannis"); len(parts) == 1 && method == "DELETE" {
			handlers.DeleteMotBanni(w, req, parts[0])
			return
		}
	}

	if match(path, "/api/catalogue") && method == "GET" {
		handlers.GetCatalogueItems(w, req, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 1 && method == "GET" {
		handlers.GetCatalogueItem(w, req, parts[0], role)
		return
	}
	if match(path, "/api/catalogue") && method == "POST" {
		handlers.CreateCatalogueItem(w, req, userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 1 && method == "PUT" {
		handlers.UpdateCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 2 && parts[1] == "valider" && method == "POST" {
		handlers.ValiderCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 2 && parts[1] == "reserver" && method == "POST" {
		handlers.ReserverCatalogueItem(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/catalogue"); len(parts) == 2 && parts[1] == "reservations" && method == "GET" {
		handlers.GetCatalogueReservations(w, req, parts[0], userId, role)
		return
	}
	if parts := splitPath(path, "/api/utilisateurs"); len(parts) == 2 && parts[1] == "planning" && method == "GET" {
		handlers.GetUtilisateurPlanning(w, req, parts[0], userId, role)
		return
	}

	if role != "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé: privilèges d'administrateur requis"})
		return
	}

	if match(path, "/api/v1/admin/utilisateurs") && method == "GET" {
		handlers.GetAllUtilisateurs(w, req)
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 1 && method == "GET" {
		handlers.GetUtilisateur(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 1 && method == "DELETE" {
		handlers.DeleteUtilisateur(w, req, parts[0])
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
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "role" && method == "PUT" {
		handlers.UpdateUserRole(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "abonnement" && method == "GET" {
		handlers.GetUserSouscription(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "abonnement" && method == "POST" {
		handlers.AssignSouscription(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/utilisateurs"); len(parts) == 2 && parts[1] == "abonnement" && method == "DELETE" {
		handlers.RevokeSouscription(w, req, parts[0])
		return
	}
	if match(path, "/api/v1/admin/abonnements") && method == "GET" {
		handlers.GetAbonnements(w, req)
		return
	}

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
		handlers.UpdateEvenement(w, req, parts[0], userId)
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
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 2 && parts[1] == "refuser" && method == "PUT" {
		handlers.RefuserEvenement(w, req, parts[0])
		return
	}
	if parts := splitPath(path, "/api/v1/admin/evenements"); len(parts) == 2 && parts[1] == "attente" && method == "PUT" {
		handlers.AttenteEvenement(w, req, parts[0])
		return
	}

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
	json.NewEncoder(w).Encode(map[string]string{"erreur": "route non trouvée"})
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

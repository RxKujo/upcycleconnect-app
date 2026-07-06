package router

import (
	"api/internal/handlers"
	"api/internal/middleware"
	"encoding/json"
	"net/http"
	"strings"
)

// Constantes de chemins — source unique de vérité pour toutes les routes.
const (
	prefixAuth         = "/api/v1/auth"
	prefixPublic       = "/api/v1/public"
	prefixMe           = "/api/v1/utilisateurs/me"
	prefixPlanning     = "/api/v1/utilisateurs/me/planning"
	prefixAnnonces     = "/api/v1/annonces"
	prefixEvenements   = "/api/v1/evenements"
	prefixForum        = "/api/v1/forum"
	prefixCommandes    = "/api/v1/commandes"
	prefixStripe       = "/api/v1/stripe"
	prefixDepot        = "/api/v1/depot"
	prefixTutoriel     = "/api/v1/tutoriel"
	prefixCatalogue    = "/api/v1/catalogue"
	prefixUtilisateurs = "/api/v1/utilisateurs"

	prefixSalarie       = "/api/v1/salarie"
	prefixSalarieEv     = "/api/v1/salarie/evenements"
	prefixSalarieArt    = "/api/v1/salarie/articles"
	prefixSalarieSujets = "/api/v1/salarie/sujets"
	prefixSalarieMsg    = "/api/v1/salarie/messages"
	prefixSalarieMots   = "/api/v1/salarie/mots-bannis"

	prefixAdmin           = "/api/v1/admin"
	prefixAdminUsers      = "/api/v1/admin/utilisateurs"
	prefixAdminCategories = "/api/v1/admin/categories"
	prefixAdminCatObjets  = "/api/v1/admin/categories-objets"
	prefixAdminMateriaux  = "/api/v1/admin/materiaux"
	prefixAdminTemplates  = "/api/v1/admin/templates"
	prefixAdminEv         = "/api/v1/admin/evenements"
	prefixAdminAnnonces   = "/api/v1/admin/annonces"
	prefixAdminCommandes  = "/api/v1/admin/commandes"
	prefixAdminConteneurs = "/api/v1/admin/conteneurs"
	prefixAdminDepot      = "/api/v1/admin/depot/demandes"
	prefixAdminPaliers    = "/api/v1/admin/paliers"
	prefixAdminTutoriel   = "/api/v1/admin/tutoriel/etapes"
	prefixAdminLangues    = "/api/v1/admin/langues"
	prefixAdminTrad       = "/api/v1/admin/translations"
	prefixAdminNotifLog   = "/api/v1/admin/notifications"
	prefixAdminFinances   = "/api/v1/admin/finances"

	// Routes pro (Essential Pro & Expert Pro)
	prefixPro          = "/api/v1/pro"
	prefixAdminPub     = "/api/v1/admin/publicites"
	segAlertes         = "/alertes"
	segPublicites      = "/publicites"

	// Routes salarié — nouvelles
	prefixSalarieIdees   = "/api/v1/salarie/idees"
	prefixSalariePlanning = "/api/v1/salarie/planning"

	// Segments de suffixes réutilisés — évitent les littéraux répétés.
	segStats     = "/stats"
	segCatalogue = "/catalogue"
	segSujets    = "/sujets"
	segTickets   = "/tickets"
)

type Router struct {
	mux *http.ServeMux
}

func New() *Router {
	return &Router{mux: http.NewServeMux()}
}

func (r *Router) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	middleware.CORS(w, req)
	if req.Method == "OPTIONS" {
		w.WriteHeader(http.StatusNoContent)
		return
	}

	path := req.URL.Path
	method := req.Method

	if routePublic(w, req, path, method) {
		return
	}

	userId, role, ok := middleware.AuthRequired(w, req)
	if !ok {
		return
	}

	if routeAuth(w, req, path, method, userId, role) {
		return
	}

	if role == "professionnel" && routePro(w, req, path, method, userId) {
		return
	}

	if (role == "salarie" || role == "admin") && routeSalarie(w, req, path, method, userId, role) {
		return
	}

	if routeCatalogue(w, req, path, method, userId, role) {
		return
	}

	if role != "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé: privilèges d'administrateur requis"})
		return
	}

	if routeAdmin(w, req, path, method, userId) {
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusNotFound)
	json.NewEncoder(w).Encode(map[string]string{"erreur": "route non trouvée"})
}

// ─── Routes publiques ─────────────────────────────────────────────────────────

func routePublic(w http.ResponseWriter, req *http.Request, path, method string) bool {
	if routePublicAuth(w, req, path, method) {
		return true
	}
	if routePublicResources(w, req, path, method) {
		return true
	}
	if routePublicEvenements(w, req, path, method) {
		return true
	}
	if routePublicPublicites(w, req, path, method) {
		return true
	}
	// i18n — chargement des traductions par code ISO (sans auth, pour le frontend)
	pI18 := splitPath(path, prefixPublic+"/i18n")
	if len(pI18) == 1 && method == "GET" {
		handlers.GetTranslationsByISO(w, req, pI18[0])
		return true
	}
	switch {
	case match(path, prefixStripe+"/webhook") && method == "POST":
		handlers.StripeWebhook(w, req)
	case match(path, prefixStripe+"/config") && method == "GET":
		handlers.GetStripeConfig(w, req)
	case match(path, prefixTutoriel+"/etapes") && method == "GET":
		handlers.GetTutorielEtapes(w, req)
	default:
		return false
	}
	return true
}

func routePublicAuth(w http.ResponseWriter, req *http.Request, path, method string) bool {
	switch {
	case match(path, prefixAuth+"/register") && method == "POST":
		handlers.Register(w, req)
	case match(path, prefixAuth+"/login") && method == "POST":
		handlers.Login(w, req)
	case match(path, prefixAuth+"/forgot-password") && method == "POST":
		handlers.ForgotPassword(w, req)
	case match(path, prefixAuth+"/reset-password") && method == "POST":
		handlers.ResetPassword(w, req)
	default:
		return false
	}
	return true
}

func routePublicResources(w http.ResponseWriter, req *http.Request, path, method string) bool {
	// Précompute les splits coûteux une fois.
	pAnn := splitPath(path, prefixPublic+"/annonces")
	pArt := splitPath(path, prefixPublic+"/articles")
	pFor := splitPath(path, prefixPublic+"/forum")
	pCat := splitPath(path, prefixPublic+segCatalogue)

	switch {
	case match(path, prefixPublic+"/annonces") && method == "GET":
		handlers.GetPublicAnnonces(w, req)
	case len(pAnn) == 1 && method == "GET":
		handlers.GetPublicAnnonce(w, req, pAnn[0])

	case match(path, prefixPublic+"/articles") && method == "GET":
		handlers.GetPublicArticles(w, req)
	case len(pArt) == 1 && method == "GET":
		handlers.GetPublicArticle(w, req, pArt[0])

	case match(path, prefixPublic+"/forum") && method == "GET":
		handlers.GetPublicForumSujets(w, req)
	case len(pFor) == 1 && method == "GET":
		handlers.GetPublicForumSujet(w, req, pFor[0])

	case match(path, prefixPublic+"/abonnements") && method == "GET":
		handlers.GetAbonnementsPublic(w, req)
	case match(path, prefixPublic+segStats) && method == "GET":
		handlers.GetPublicStats(w, req)
	case match(path, prefixPublic+segCatalogue) && method == "GET":
		handlers.GetCatalogueItems(w, req, "")
	case len(pCat) == 1 && method == "GET":
		handlers.GetCatalogueItem(w, req, pCat[0], "")
	case match(path, prefixPublic+"/conteneurs") && method == "GET":
		handlers.GetPublicConteneursAvecGeo(w, req)
	case match(path, prefixPublic+"/materiaux") && method == "GET":
		handlers.GetMateriauxActifs(w, req)
	case match(path, prefixPublic+"/categories-objets") && method == "GET":
		handlers.GetCategoriesObjets(w, req)
	case match(path, prefixPublic+"/categories") && method == "GET":
		handlers.GetCategories(w, req)
	case match(path, prefixPublic+"/evenements") && method == "GET":
		handlers.GetPublicEvenements(w, req)

	default:
		return false
	}
	return true
}

func routePublicPublicites(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixPublic+segPublicites)
	switch {
	case match(path, prefixPublic+segPublicites) && method == "GET":
		handlers.GetPublicitesActives(w, req)
	case len(p) == 2 && p[1] == "clic" && method == "POST":
		handlers.EnregistrerClicPub(w, req, p[0])
	default:
		return false
	}
	return true
}

func routePublicEvenements(w http.ResponseWriter, req *http.Request, path, method string) bool {
	pEv := splitPath(path, prefixEvenements)
	switch {
	case match(path, prefixEvenements+segCatalogue) && method == "GET":
		handlers.GetPublicEvenements(w, req)
	case len(pEv) == 1 && method == "GET":
		handlers.GetPublicEvenement(w, req, pEv[0])
	default:
		return false
	}
	return true
}

// ─── Routes authentifiées (tous rôles) ───────────────────────────────────────

func routeAuth(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	return routeAuthMe(w, req, path, method, userId) ||
		routeAuthAnnonces(w, req, path, method, userId, role) ||
		routeAuthCommandes(w, req, path, method, userId) ||
		routeAuthStripe(w, req, path, method, userId) ||
		routeAuthForum(w, req, path, method, userId) ||
		routeAuthEvenements(w, req, path, method, userId, role) ||
		routeAuthTutoriel(w, req, path, method, userId) ||
		routeAuthDepot(w, req, path, method, userId)
}

func routeAuthMe(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	pPlan := splitPath(path, prefixPlanning)
	switch {
	case match(path, prefixMe) && method == "GET":
		handlers.GetMe(w, req, userId)
	case match(path, prefixMe) && method == "PUT":
		handlers.UpdateMe(w, req, userId)
	case match(path, prefixMe) && method == "DELETE":
		handlers.DeleteMe(w, req, userId)
	case match(path, prefixMe+"/score") && method == "GET":
		handlers.GetMyScore(w, req, userId)
	case match(path, prefixMe+"/export-pdf") && method == "GET":
		handlers.ExportUserData(w, req, userId)
	case match(path, prefixMe+"/password") && method == "PUT":
		handlers.ChangePassword(w, req, userId)
	case match(path, prefixMe+"/notifications") && method == "PUT":
		handlers.UpdateNotifications(w, req, userId)
	case match(path, prefixMe+"/reservations") && method == "GET":
		handlers.GetMesReservations(w, req, userId)
	case parts(path, prefixMe) == "evenements-inscrits" && method == "GET":
		handlers.GetMesEvenementsInscrits(w, req, userId)
	case match(path, prefixPlanning) && method == "GET":
		handlers.GetMonPlanning(w, req, userId)
	case match(path, prefixPlanning) && method == "POST":
		handlers.AddPlanningManuel(w, req, userId)
	case len(pPlan) == 1 && method == "DELETE":
		handlers.DeletePlanningItem(w, req, pPlan[0], userId)
	default:
		return false
	}
	return true
}

func routeAuthAnnonces(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p := splitPath(path, prefixAnnonces)
	switch {
	case match(path, prefixAnnonces) && method == "POST":
		handlers.CreateAnnonce(w, req, userId)
	case match(path, prefixAnnonces+"/me") && method == "GET":
		handlers.GetMesAnnonces(w, req, userId)
	case len(p) == 1 && method == "GET":
		handlers.GetAnnonceAuth(w, req, p[0], userId, role)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateAnnonce(w, req, p[0], userId)
	case len(p) == 2 && p[1] == "annuler" && method == "POST":
		handlers.CancelAnnonce(w, req, p[0], userId)
	default:
		return false
	}
	return true
}

func routeAuthCommandes(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	switch {
	case match(path, prefixCommandes+"/checkout") && method == "POST":
		handlers.CheckoutPanier(w, req, userId)
	case match(path, prefixCommandes+"/me") && method == "GET":
		handlers.GetMesCommandes(w, req, userId)
	case match(path, "/api/v1/ventes/me") && method == "GET":
		handlers.GetMesVentes(w, req, userId)
	default:
		return false
	}
	return true
}

func routeAuthStripe(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	switch {
	case match(path, prefixStripe+"/abonnement/checkout") && method == "POST":
		handlers.StripeCheckoutAbonnement(w, req, userId)
	case match(path, prefixStripe+"/abonnement/portail") && method == "POST":
		handlers.StripePortal(w, req, userId)
	case match(path, prefixStripe+"/facturation") && method == "GET":
		handlers.GetMaFacturation(w, req, userId)
	case match(path, prefixStripe+"/payment-intent/commande") && method == "POST":
		handlers.StripePaymentIntentCommande(w, req, userId)
	case match(path, prefixStripe+"/payment-intent/evenement") && method == "POST":
		handlers.StripePaymentIntentEvenement(w, req, userId)
	case match(path, prefixStripe+"/payment-intent/catalogue") && method == "POST":
		handlers.StripePaymentIntentCatalogue(w, req, userId)
	case match(path, prefixStripe+"/payment-intent/panier") && method == "POST":
		handlers.StripePaymentIntentPanier(w, req, userId)
	default:
		return false
	}
	return true
}

func routeAuthForum(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	pSuj := splitPath(path, prefixForum+segSujets)
	switch {
	case match(path, prefixForum+segSujets) && method == "POST":
		handlers.CreateForumSujet(w, req, userId)
	case len(pSuj) == 2 && pSuj[1] == "messages" && method == "POST":
		handlers.CreateForumMessage(w, req, pSuj[0], userId)
	case match(path, prefixForum+"/signaler") && method == "POST":
		handlers.SignalerMessage(w, req, userId)
	default:
		return false
	}
	return true
}

func routeAuthEvenements(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p := splitPath(path, prefixEvenements)
	switch {
	case (role == "salarie" || role == "admin") && match(path, prefixEvenements) && method == "POST":
		handlers.CreateEvenement(w, req, userId)
	case len(p) == 2 && p[1] == "inscrire" && method == "POST":
		handlers.InscrireEvenement(w, req, p[0])
	case len(p) == 2 && p[1] == "ticket" && method == "GET":
		handlers.GetTicketPDF(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAuthTutoriel(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	switch {
	case match(path, prefixTutoriel+"/etapes") && method == "GET":
		handlers.GetTutorielEtapes(w, req)
	case match(path, prefixTutoriel+"/statut") && method == "GET":
		handlers.GetTutorielStatut(w, req, userId)
	case match(path, prefixTutoriel+"/termine") && method == "POST":
		handlers.MarquerTutorielTermine(w, req, userId)
	case match(path, prefixTutoriel+"/passer") && method == "POST":
		handlers.PasserTutoriel(w, req, userId)
	default:
		return false
	}
	return true
}

func routeAuthDepot(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	switch {
	case match(path, prefixDepot+"/demande") && method == "POST":
		handlers.CreateDemandeDepot(w, req, userId)
	case match(path, prefixDepot+"/demandes/me") && method == "GET":
		handlers.GetMesDemandesDepot(w, req, userId)
	default:
		return false
	}
	return true
}

// ─── Routes professionnel (Essential Pro & Expert Pro) ───────────────────────

func routePro(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	pAlertes := splitPath(path, prefixPro+segAlertes)
	pPubs    := splitPath(path, prefixPro+segPublicites)

	switch {
	// Dashboard
	case match(path, prefixPro+"/dashboard") && method == "GET":
		handlers.GetDashboardEssential(w, req, userId)
	case match(path, prefixPro+"/dashboard/annuel") && method == "GET":
		handlers.GetDashboardExpert(w, req, userId)
	case match(path, prefixPro+"/dashboard/export-pdf") && method == "GET":
		handlers.ExportDashboardPDF(w, req, userId)

	// Alertes matériaux
	case match(path, prefixPro+segAlertes) && method == "GET":
		handlers.GetAlertesPro(w, req, userId)
	case match(path, prefixPro+segAlertes) && method == "POST":
		handlers.CreateAlertePro(w, req, userId)
	case len(pAlertes) == 1 && method == "DELETE":
		handlers.DeleteAlertePro(w, req, pAlertes[0], userId)

	// Badges
	case match(path, prefixPro+"/badges") && method == "GET":
		handlers.GetBadgesPro(w, req, userId)
	case match(path, prefixPro+"/badges/recalculer") && method == "POST":
		handlers.RecalculerBadgesPro(w, req, userId)

	// Publicités
	case match(path, prefixPro+segPublicites) && method == "GET":
		handlers.GetMesPublicites(w, req, userId)
	case match(path, prefixPro+segPublicites) && method == "POST":
		handlers.CreatePublicitePro(w, req, userId)
	case len(pPubs) == 1 && method == "DELETE":
		handlers.DeletePublicitePro(w, req, pPubs[0], userId)

	// Conteneurs
	case match(path, prefixPro+"/conteneurs/commandes") && method == "GET":
		handlers.GetCommandesEnConteneur(w, req, userId)
	case match(path, prefixPro+"/conteneurs/historique") && method == "GET":
		handlers.GetHistoriqueRecuperations(w, req, userId)
	case match(path, prefixPro+"/conteneurs/valider-reception") && method == "POST":
		handlers.ValiderReceptionConteneur(w, req, userId)

	default:
		return false
	}
	return true
}

// ─── Routes salarié ───────────────────────────────────────────────────────────

func routeSalarie(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	return routeSalarieGeneral(w, req, path, method, userId) ||
		routeSalarieEvenements(w, req, path, method, userId, role) ||
		routeSalarieArticles(w, req, path, method, userId, role) ||
		routeSalarieModeration(w, req, path, method, userId) ||
		routeSalarieIdees(w, req, path, method, userId, role) ||
		routeSalariePlanningDedicated(w, req, path, method, userId)
}

func routeSalarieGeneral(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	switch {
	case match(path, prefixSalarie+segStats) && method == "GET":
		handlers.GetSalarieStats(w, req, userId)
	case match(path, prefixSalarie+"/animateurs") && method == "GET":
		handlers.GetSalarieAnimateurs(w, req)
	case match(path, prefixSalarie+"/templates") && method == "GET":
		handlers.GetSalarieTemplates(w, req)
	case match(path, prefixSalarie+"/templates") && method == "POST":
		handlers.CreateTemplateSalarie(w, req, userId)
	default:
		tp := splitPath(path, prefixSalarie+"/templates")
		switch {
		case len(tp) == 1 && method == "PUT":
			handlers.UpdateTemplate(w, req, tp[0])
		case len(tp) == 1 && method == "DELETE":
			handlers.DeleteTemplate(w, req, tp[0])
		default:
			return false
		}
	}
	return true
}

func routeSalarieEvenements(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p := splitPath(path, prefixSalarieEv)
	switch {
	case match(path, prefixSalarieEv) && method == "GET":
		handlers.GetSalarieEvenements(w, req, userId)
	case match(path, prefixSalarieEv) && method == "POST":
		handlers.CreateSalarieEvenement(w, req, userId)
	case len(p) == 1 && method == "GET":
		handlers.GetSalarieEvenement(w, req, p[0], userId, role)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateSalarieEvenement(w, req, p[0], userId, role)
	default:
		return false
	}
	return true
}

func routeSalarieArticles(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p := splitPath(path, prefixSalarieArt)
	switch {
	case match(path, prefixSalarieArt) && method == "GET":
		handlers.GetArticles(w, req)
	case match(path, prefixSalarieArt) && method == "POST":
		handlers.CreateArticle(w, req, userId)
	case len(p) == 1 && method == "GET":
		handlers.GetArticle(w, req, p[0])
	case len(p) == 1 && method == "PUT":
		handlers.UpdateArticle(w, req, p[0], userId, role)
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteArticle(w, req, p[0], userId, role)
	default:
		return false
	}
	return true
}

func routeSalarieModeration(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	pMsg  := splitPath(path, prefixSalarieMsg)
	pSuj  := splitPath(path, prefixSalarieSujets)
	pMots := splitPath(path, prefixSalarieMots)
	switch {
	case match(path, prefixSalarie+"/signalements") && method == "GET":
		handlers.GetSignalements(w, req)

	case len(pMsg) == 2 && pMsg[1] == "masquer" && method == "PUT":
		handlers.MasquerMessage(w, req, pMsg[0])
	case len(pMsg) == 2 && pMsg[1] == "restaurer" && method == "PUT":
		handlers.RestaurerMessage(w, req, pMsg[0])

	case match(path, prefixSalarieSujets) && method == "GET":
		handlers.GetSujetsModeration(w, req)
	case len(pSuj) == 2 && pSuj[1] == "lock" && method == "PUT":
		handlers.LockSujet(w, req, pSuj[0])
	case len(pSuj) == 2 && pSuj[1] == "unlock" && method == "PUT":
		handlers.UnlockSujet(w, req, pSuj[0])

	case match(path, prefixSalarieMots) && method == "GET":
		handlers.GetMotsBannis(w, req)
	case match(path, prefixSalarieMots) && method == "POST":
		handlers.AddMotBanni(w, req, userId)
	case len(pMots) == 1 && method == "DELETE":
		handlers.DeleteMotBanni(w, req, pMots[0])

	default:
		return false
	}
	return true
}

// ─── Routes salarié — boîte à idées ──────────────────────────────────────────

func routeSalarieIdees(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p := splitPath(path, prefixSalarieIdees)
	switch {
	case match(path, prefixSalarieIdees) && method == "GET":
		handlers.GetIdeesSalaries(w, req, userId, role)
	case match(path, prefixSalarieIdees) && method == "POST":
		handlers.CreateIdee(w, req, userId)
	case len(p) == 1 && method == "GET":
		handlers.GetIdee(w, req, p[0], userId)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateIdee(w, req, p[0], userId, role)
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteIdee(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "voter" && method == "POST":
		handlers.VoterIdee(w, req, p[0], userId)
	case len(p) == 2 && p[1] == "statut" && method == "PUT":
		handlers.ChangeStatutIdee(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "archiver" && method == "POST":
		handlers.ArchiverIdee(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "desarchiver" && method == "POST":
		handlers.DesarchiverIdee(w, req, p[0], userId, role)
	default:
		return false
	}
	return true
}

// ─── Routes salarié — planning dédié ─────────────────────────────────────────
// Le planning salarié réutilise les mêmes handlers que le planning particulier
// (même table planning_utilisateurs), exposés sous /api/v1/salarie/planning
// pour que le middleware salarie.auth s'applique sans ambiguïté.

func routeSalariePlanningDedicated(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	p := splitPath(path, prefixSalariePlanning)
	switch {
	case match(path, prefixSalariePlanning) && method == "GET":
		handlers.GetMonPlanning(w, req, userId)
	case match(path, prefixSalariePlanning) && method == "POST":
		handlers.AddPlanningManuel(w, req, userId)
	case len(p) == 1 && method == "PUT":
		handlers.UpdatePlanningItem(w, req, p[0], userId)
	case len(p) == 1 && method == "DELETE":
		handlers.DeletePlanningItem(w, req, p[0], userId)
	default:
		return false
	}
	return true
}

// ─── Routes catalogue (auth) ─────────────────────────────────────────────────

func routeCatalogue(w http.ResponseWriter, req *http.Request, path, method string, userId int, role string) bool {
	p    := splitPath(path, prefixCatalogue)
	pUsr := splitPath(path, prefixUtilisateurs)
	switch {
	case match(path, prefixCatalogue) && method == "GET":
		handlers.GetCatalogueItems(w, req, role)
	case len(p) == 1 && method == "GET":
		handlers.GetCatalogueItem(w, req, p[0], role)
	case match(path, prefixCatalogue) && method == "POST":
		handlers.CreateCatalogueItem(w, req, userId, role)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateCatalogueItem(w, req, p[0], userId, role)
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteCatalogueItem(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "valider" && method == "POST":
		handlers.ValiderCatalogueItem(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "reserver" && method == "POST":
		handlers.ReserverCatalogueItem(w, req, p[0], userId, role)
	case len(p) == 2 && p[1] == "reservations" && method == "GET":
		handlers.GetCatalogueReservations(w, req, p[0], userId, role)
	case len(pUsr) == 2 && pUsr[1] == "planning" && method == "GET":
		handlers.GetUtilisateurPlanning(w, req, pUsr[0], userId, role)
	default:
		return false
	}
	return true
}

// ─── Routes admin ─────────────────────────────────────────────────────────────

func routeAdmin(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	return routeAdminUsers(w, req, path, method) ||
		routeAdminCategoriesObjets(w, req, path, method) ||
		routeAdminCategories(w, req, path, method) ||
		routeAdminMateriaux(w, req, path, method) ||
		routeAdminTemplates(w, req, path, method) ||
		routeAdminEvenements(w, req, path, method, userId) ||
		routeAdminAnnonces(w, req, path, method, userId) ||
		routeAdminOrders(w, req, path, method) ||
		routeAdminInfra(w, req, path, method) ||
		routeAdminScoring(w, req, path, method) ||
		routeAdminPro(w, req, path, method, userId) ||
		routeAdminLangues(w, req, path, method) ||
		routeAdminNotifications(w, req, path, method, userId) ||
		routeAdminFinances(w, req, path, method)
}

func routeAdminPro(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	p := splitPath(path, prefixAdminPub)
	switch {
	case match(path, prefixAdminPub) && method == "GET":
		handlers.AdminGetPublicites(w, req)
	case match(path, prefixAdminPub+"/stats") && method == "GET":
		handlers.AdminGetPublicitesStats(w, req)
	case match(path, prefixAdminPub+"/rotation") && method == "GET":
		handlers.AdminGetRotationWRR(w, req)
	case len(p) == 2 && p[1] == "valider" && method == "PUT":
		handlers.AdminValiderPublicite(w, req, p[0], userId)
	case len(p) == 2 && p[1] == "refuser" && method == "PUT":
		handlers.AdminRefuserPublicite(w, req, p[0], userId)
	case match(path, prefixAdminConteneurs+"/notifier-arrivee") && method == "POST":
		handlers.NotifierArriveeConteneur(w, req)
	default:
		return false
	}
	return true
}

func routeAdminUsers(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminUsers)
	pa := splitPath(path, prefixAdmin+"/abonnements")
	switch {
	case match(path, prefixAdmin+segStats) && method == "GET":
		handlers.GetAdminStats(w, req)
	case match(path, prefixAdminUsers) && method == "GET":
		handlers.GetAllUtilisateurs(w, req)
	case len(p) == 1 && method == "GET":
		handlers.GetUtilisateur(w, req, p[0])
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteUtilisateur(w, req, p[0])
	case len(p) == 2 && p[1] == "ban" && method == "PUT":
		handlers.BanUtilisateur(w, req, p[0])
	case len(p) == 2 && p[1] == "unban" && method == "PUT":
		handlers.UnbanUtilisateur(w, req, p[0])
	case len(p) == 2 && p[1] == "role" && method == "PUT":
		handlers.UpdateUserRole(w, req, p[0])
	case len(p) == 2 && p[1] == "abonnement" && method == "GET":
		handlers.GetUserSouscription(w, req, p[0])
	case len(p) == 2 && p[1] == "abonnement" && method == "POST":
		handlers.AssignSouscription(w, req, p[0])
	case len(p) == 2 && p[1] == "abonnement" && method == "DELETE":
		handlers.RevokeSouscription(w, req, p[0])
	case match(path, prefixAdmin+"/abonnements") && method == "GET":
		handlers.GetAbonnements(w, req)
	case match(path, prefixAdmin+"/abonnements") && method == "POST":
		handlers.CreateAbonnement(w, req)
	case len(pa) == 1 && method == "PUT":
		handlers.UpdateAbonnement(w, req, pa[0])
	case len(pa) == 1 && method == "DELETE":
		handlers.DeleteAbonnement(w, req, pa[0])
	case match(path, prefixAdmin+"/stripe/sync-plans") && method == "POST":
		handlers.AdminSyncStripePlans(w, req)
	default:
		return false
	}
	return true
}

func routeAdminCategoriesObjets(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminCatObjets)
	switch {
	case match(path, prefixAdminCatObjets) && method == "GET":
		handlers.GetCategoriesObjetsAdmin(w, req)
	case match(path, prefixAdminCatObjets) && method == "POST":
		handlers.CreateCategorieObjet(w, req)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateCategorieObjet(w, req, p[0])
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteCategorieObjet(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminCategories(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminCategories)
	switch {
	case match(path, prefixAdminCategories) && method == "GET":
		handlers.GetCategories(w, req)
	case match(path, prefixAdminCategories) && method == "POST":
		handlers.CreateCategorie(w, req)
	case len(p) == 1 && method == "PUT":
		handlers.UpdateCategorie(w, req, p[0])
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteCategorie(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminMateriaux(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminMateriaux)
	switch {
	case match(path, prefixAdminMateriaux) && method == "GET":
		handlers.GetMateriauxAdmin(w, req)
	case match(path, prefixAdminMateriaux) && method == "POST":
		handlers.CreateMateriau(w, req)
	case len(p) == 2 && p[1] == "toggle" && method == "PUT":
		handlers.ToggleMateriau(w, req, p[0])
	case len(p) == 1 && method == "PUT":
		handlers.UpdateMateriau(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminTemplates(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminTemplates)
	switch {
	case match(path, prefixAdminTemplates) && method == "GET":
		handlers.GetTemplatesAdmin(w, req)
	case match(path, prefixAdminTemplates) && method == "POST":
		handlers.CreateTemplate(w, req)
	case len(p) == 2 && p[1] == "toggle" && method == "PUT":
		handlers.ToggleTemplate(w, req, p[0])
	case len(p) == 1 && method == "PUT":
		handlers.UpdateTemplate(w, req, p[0])
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteTemplate(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminEvenements(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	p := splitPath(path, prefixAdminEv)
	switch {
	case match(path, prefixAdminEv) && method == "GET":
		handlers.GetEvenements(w, req)
	case match(path, prefixAdminEv) && method == "POST":
		handlers.CreateEvenement(w, req, userId)
	case len(p) == 1 && method == "GET":
		handlers.GetEvenement(w, req, p[0])
	case len(p) == 1 && method == "PUT":
		handlers.UpdateEvenement(w, req, p[0], userId)
	case len(p) == 1 && method == "DELETE":
		handlers.DeleteEvenement(w, req, p[0])
	case len(p) == 2 && p[1] == "inscrits" && method == "GET":
		handlers.GetEvenementInscrits(w, req, p[0])
	case len(p) == 2 && p[1] == "valider" && method == "PUT":
		handlers.ValiderEvenement(w, req, p[0], userId)
	case len(p) == 2 && p[1] == "refuser" && method == "PUT":
		handlers.RefuserEvenement(w, req, p[0])
	case len(p) == 2 && p[1] == "attente" && method == "PUT":
		handlers.AttenteEvenement(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminAnnonces(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	p := splitPath(path, prefixAdminAnnonces)
	switch {
	case match(path, prefixAdminAnnonces) && method == "GET":
		handlers.GetAnnonces(w, req)
	case len(p) == 1 && method == "GET":
		handlers.GetAnnonce(w, req, p[0])
	case len(p) == 2 && p[1] == "valider" && method == "PUT":
		handlers.ValiderAnnonce(w, req, p[0], userId)
	case len(p) == 2 && p[1] == "refuser" && method == "PUT":
		handlers.RefuserAnnonce(w, req, p[0])
	case len(p) == 2 && p[1] == "attente" && method == "PUT":
		handlers.AttenteAnnonce(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminOrders(w http.ResponseWriter, req *http.Request, path, method string) bool {
	p := splitPath(path, prefixAdminCommandes)
	switch {
	case match(path, prefixAdminCommandes) && method == "GET":
		handlers.GetCommandes(w, req)
	case len(p) == 1 && method == "GET":
		handlers.GetCommande(w, req, p[0])
	case len(p) == 2 && p[1] == "statut" && method == "PUT":
		handlers.UpdateCommandeStatut(w, req, p[0])
	default:
		return false
	}
	return true
}

func routeAdminInfra(w http.ResponseWriter, req *http.Request, path, method string) bool {
	pCont   := splitPath(path, prefixAdminConteneurs)
	pTick   := splitPath(path, prefixAdminConteneurs+segTickets)
	pDep    := splitPath(path, prefixAdminDepot)
	switch {
	case match(path, prefixAdminConteneurs) && method == "GET":
		handlers.GetAllConteneurs(w, req)
	case match(path, prefixAdminConteneurs) && method == "POST":
		handlers.CreateConteneur(w, req)
	case match(path, prefixAdminConteneurs+"/scan") && method == "POST":
		handlers.ScanBarcodeAndUpdateCommande(w, req)
	case match(path, prefixAdminConteneurs+"/codes-barres") && method == "POST":
		handlers.CreateCodeBarre(w, req)
	case len(pCont) == 2 && pCont[0] == "photos" && method == "DELETE":
		handlers.DeleteConteneurPhoto(w, req, pCont[1])
	case len(pCont) == 1 && method == "GET":
		handlers.GetConteneurDetails(w, req, pCont[0])
	case len(pCont) == 1 && method == "PUT":
		handlers.UpdateConteneur(w, req, pCont[0])
	case len(pTick) == 2 && pTick[1] == "resolve" && method == "PUT":
		handlers.ResolveTicket(w, req, pTick[0])

	case match(path, prefixAdminDepot) && method == "GET":
		handlers.AdminGetDemandesDepot(w, req)
	case len(pDep) == 2 && pDep[1] == "valider" && method == "PUT":
		handlers.AdminValiderDemandeDepot(w, req, pDep[0])
	case len(pDep) == 2 && pDep[1] == "refuser" && method == "PUT":
		handlers.AdminRefuserDemandeDepot(w, req, pDep[0])

	default:
		return false
	}
	return true
}

func routeAdminScoring(w http.ResponseWriter, req *http.Request, path, method string) bool {
	pPal := splitPath(path, prefixAdminPaliers)
	pTut := splitPath(path, prefixAdminTutoriel)
	switch {
	case match(path, prefixAdminPaliers) && method == "GET":
		handlers.GetPaliersAdmin(w, req)
	case len(pPal) == 1 && method == "PUT":
		handlers.UpdatePalier(w, req, pPal[0])
	case match(path, prefixAdmin+"/scores/recompute") && method == "POST":
		handlers.RecomputeScores(w, req)

	case match(path, prefixAdminTutoriel) && method == "GET":
		handlers.AdminGetTutorielEtapes(w, req)
	case len(pTut) == 1 && method == "PUT":
		handlers.AdminUpdateTutorielEtape(w, req, pTut[0])

	default:
		return false
	}
	return true
}

// ─── Routes admin — multilingue ───────────────────────────────────────────────

func routeAdminLangues(w http.ResponseWriter, req *http.Request, path, method string) bool {
	pL   := splitPath(path, prefixAdminLangues)
	pT   := splitPath(path, prefixAdminTrad)
	pI18 := splitPath(path, prefixPublic+"/i18n")
	switch {
	// Langues
	case match(path, prefixAdminLangues) && method == "GET":
		handlers.GetLangues(w, req)
	case match(path, prefixAdminLangues) && method == "POST":
		handlers.CreateLangue(w, req)
	case len(pL) == 1 && method == "PUT":
		handlers.UpdateLangue(w, req, pL[0])
	case len(pL) == 1 && method == "DELETE":
		handlers.DeleteLangue(w, req, pL[0])
	// Traductions
	case match(path, prefixAdminTrad) && method == "GET":
		handlers.GetTranslations(w, req)
	case len(pT) == 1 && pT[0] == "bulk" && method == "POST":
		handlers.BulkUpsertTranslations(w, req)
	case match(path, prefixAdminTrad) && method == "POST":
		handlers.UpsertTranslation(w, req)
	case len(pT) == 1 && method == "DELETE":
		handlers.DeleteTranslation(w, req, pT[0])
	// Endpoint public i18n — chargement des libellés par langue (sans auth)
	case len(pI18) == 1 && method == "GET":
		handlers.GetTranslationsByISO(w, req, pI18[0])
	default:
		return false
	}
	return true
}

// ─── Routes admin — supervision notifications ─────────────────────────────────

func routeAdminNotifications(w http.ResponseWriter, req *http.Request, path, method string, userId int) bool {
	pUser := splitPath(path, prefixAdminNotifLog+"/user")
	switch {
	case match(path, prefixAdminNotifLog+"/log") && method == "GET":
		handlers.GetNotificationsLog(w, req)
	case match(path, prefixAdminNotifLog+"/sites") && method == "GET":
		handlers.GetSitesUC(w, req)
	case match(path, prefixAdminNotifLog+"/groupe") && method == "POST":
		handlers.SendNotifGroupe(w, req, userId)
	case len(pUser) == 1 && method == "GET":
		handlers.GetUserPrefsNotif(w, req, pUser[0])
	case len(pUser) == 1 && method == "PUT":
		handlers.UpdateUserPrefsNotif(w, req, pUser[0])
	default:
		return false
	}
	return true
}

// ─── Routes admin — pilotage financier ───────────────────────────────────────

func routeAdminFinances(w http.ResponseWriter, req *http.Request, path, method string) bool {
	switch {
	case match(path, prefixAdminFinances+"/dashboard") && method == "GET":
		handlers.GetFinanceDashboard(w, req)
	case match(path, prefixAdminFinances+"/revenus") && method == "GET":
		handlers.GetRevenusSynthese(w, req)
	case match(path, prefixAdminFinances+"/factures") && method == "GET":
		handlers.GetFactures(w, req)
	case match(path, prefixAdminFinances+"/export-csv") && method == "GET":
		handlers.ExportFacturesCSV(w, req)
	case match(path, prefixAdminFinances+"/export-pdf") && method == "GET":
		handlers.ExportFacturesPDF(w, req)
	default:
		return false
	}
	return true
}

// ─── Helpers de routage ───────────────────────────────────────────────────────

func match(path, pattern string) bool {
	return path == pattern
}

// parts retourne le premier segment après le prefix, ou "" si absent/multiple.
func parts(path, prefix string) string {
	p := splitPath(path, prefix)
	if len(p) == 1 {
		return p[0]
	}
	return ""
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

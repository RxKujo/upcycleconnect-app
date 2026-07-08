package handlers

import (
	"api/internal/middleware"
	"api/internal/services"
	"net/http"
)

// ─── Badges Expert Pro ────────────────────────────────────────────────────────

type badgesProResponse struct {
	Obtenus     []services.BadgeUtilisateur `json:"obtenus"`
	Disponibles []services.BadgeDef         `json:"disponibles"`
}

// GetBadgesPro — badges obtenus + référentiel complet. Tout pro avec plan actif
// (affichés sur le profil même si seul Expert Pro les gagne activement).
func GetBadgesPro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.BadgesActives },
		"badges non inclus dans votre abonnement")
	if !ok {
		return
	}

	obtenus, err := services.GetUserBadges(userID)
	if err != nil {
		jsonErr(w, "erreur chargement badges", http.StatusInternalServerError)
		return
	}

	dispo, err := services.GetAllBadges()
	if err != nil {
		jsonErr(w, "erreur chargement référentiel badges", http.StatusInternalServerError)
		return
	}

	jsonOK(w, badgesProResponse{Obtenus: obtenus, Disponibles: dispo}, http.StatusOK)
}

// RecalculerBadgesPro — recalcule et attribue les badges (Expert Pro ; manuel ou après commande récupérée).
func RecalculerBadgesPro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.BadgesActives },
		"badges non inclus dans votre abonnement")
	if !ok {
		return
	}

	nouveaux, err := services.ComputeAndAwardBadges(userID)
	if err != nil {
		jsonErr(w, "erreur calcul badges", http.StatusInternalServerError)
		return
	}

	msg := "aucun nouveau badge"
	if len(nouveaux) > 0 {
		msg = "badges attribués"
	}
	jsonOK(w, map[string]interface{}{
		"message":         msg,
		"nouveaux_badges": nouveaux,
	}, http.StatusOK)
}

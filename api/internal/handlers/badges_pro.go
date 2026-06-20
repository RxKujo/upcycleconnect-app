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

// GetBadgesPro retourne les badges obtenus + le référentiel complet.
// Accessible à tout pro avec un plan actif (les badges sont affichés publiquement
// sur le profil même si seul Expert Pro les "gagne" activement).
func GetBadgesPro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireEssentialPro(userID, w)
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

// RecalculerBadgesPro force le recalcul et l'attribution des badges pour le pro courant.
// Expert Pro uniquement — appelé manuellement ou après chaque commande récupérée.
func RecalculerBadgesPro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireExpertPro(userID, w)
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

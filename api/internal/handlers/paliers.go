package handlers

import (
	"api/internal/services"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

// GetPaliersAdmin liste les paliers du barème Upcycling Score.
func GetPaliersAdmin(w http.ResponseWriter, r *http.Request) {
	paliers, err := services.GetPaliers()
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if paliers == nil {
		paliers = []services.Palier{}
	}
	jsonOK(w, paliers, http.StatusOK)
}

// UpdatePalier — met à jour un palier du barème.
func UpdatePalier(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Nom                  *string `json:"nom"`
		SeuilMin             *int    `json:"seuil_min"`
		Couleur              *string `json:"couleur"`
		ConfereCertification *bool   `json:"confere_certification"`
		MiseEnAvant          *bool   `json:"mise_en_avant"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	_, err := database.DB.Exec(`
		UPDATE paliers_score
		SET nom = COALESCE(?, nom),
		    seuil_min = COALESCE(?, seuil_min),
		    couleur = COALESCE(?, couleur),
		    confere_certification = COALESCE(?, confere_certification),
		    mise_en_avant = COALESCE(?, mise_en_avant)
		WHERE id_palier = ?`,
		req.Nom, req.SeuilMin, req.Couleur, req.ConfereCertification, req.MiseEnAvant, id)
	if err != nil {
		jsonErr(w, "impossible de mettre à jour le palier", http.StatusInternalServerError)
		return
	}

	// Seuils/certifications modifiés : recalcul de tous les comptes.
	go services.RecomputeAllScores()

	jsonOK(w, map[string]string{"message": "palier mis à jour"}, http.StatusOK)
}

// RecomputeScores — recalcule tous les scores depuis les transactions réelles.
func RecomputeScores(w http.ResponseWriter, r *http.Request) {
	n, err := services.RecomputeAllScores()
	if err != nil {
		jsonErr(w, "erreur lors du recalcul", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]interface{}{
		"message":               "scores recalculés",
		"utilisateurs_recalcules": n,
	}, http.StatusOK)
}

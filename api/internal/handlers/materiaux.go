package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

// scanMateriaux exécute une requête et retourne les matériaux.
func scanMateriaux(query string) ([]models.Materiau, error) {
	rows, err := database.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	materiaux := []models.Materiau{}
	for rows.Next() {
		var m models.Materiau
		if err := rows.Scan(&m.IDMateriau, &m.Code, &m.Libelle, &m.Icone, &m.Actif); err == nil {
			materiaux = append(materiaux, m)
		}
	}
	return materiaux, nil
}

// GetMateriauxActifs liste les matériaux actifs (formulaires, filtres publics).
func GetMateriauxActifs(w http.ResponseWriter, r *http.Request) {
	materiaux, err := scanMateriaux(
		"SELECT id_materiau, code, libelle, icone, actif FROM materiaux WHERE actif = 1 ORDER BY libelle")
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, materiaux, http.StatusOK)
}

// GetMateriauxAdmin liste tous les matériaux (actifs et inactifs).
func GetMateriauxAdmin(w http.ResponseWriter, r *http.Request) {
	materiaux, err := scanMateriaux(
		"SELECT id_materiau, code, libelle, icone, actif FROM materiaux ORDER BY libelle")
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, materiaux, http.StatusOK)
}

// materiauActif retourne vrai si le code correspond à un matériau actif.
func materiauActif(code string) bool {
	var n int
	database.DB.QueryRow("SELECT COUNT(*) FROM materiaux WHERE code = ? AND actif = 1", code).Scan(&n) //nolint:errcheck
	return n > 0
}

type materiauRequest struct {
	Code    string  `json:"code"`
	Libelle string  `json:"libelle"`
	Icone   *string `json:"icone"`
	Actif   *bool   `json:"actif"`
}

// CreateMateriau ajoute un matériau.
func CreateMateriau(w http.ResponseWriter, r *http.Request) {
	var req materiauRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Code == "" || req.Libelle == "" {
		jsonErr(w, "code et libellé requis", http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(
		"INSERT INTO materiaux (code, libelle, icone) VALUES (?, ?, ?)",
		req.Code, req.Libelle, req.Icone)
	if err != nil {
		jsonErr(w, "impossible de créer le matériau (code déjà utilisé ?)", http.StatusConflict)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "matériau créé", "id_materiau": id}, http.StatusCreated)
}

// UpdateMateriau met à jour libellé, icône, ordre et statut actif. Le code reste immuable
// (il est référencé par les annonces/alertes existantes).
func UpdateMateriau(w http.ResponseWriter, r *http.Request, id string) {
	var req materiauRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Libelle == "" {
		jsonErr(w, "libellé requis", http.StatusBadRequest)
		return
	}
	actif := true
	if req.Actif != nil {
		actif = *req.Actif
	}
	_, err := database.DB.Exec(
		"UPDATE materiaux SET libelle = ?, icone = COALESCE(?, icone), actif = ? WHERE id_materiau = ?",
		req.Libelle, req.Icone, actif, id)
	if err != nil {
		jsonErr(w, "impossible de mettre à jour le matériau", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "matériau mis à jour"}, http.StatusOK)
}

// ToggleMateriau active/désactive un matériau (suppression douce).
func ToggleMateriau(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE materiaux SET actif = NOT actif WHERE id_materiau = ?", id)
	if err != nil {
		jsonErr(w, "impossible de changer le statut", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "statut mis à jour"}, http.StatusOK)
}

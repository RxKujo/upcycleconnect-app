package handlers

import (
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strings"
)

// Template représente un modèle d'événement réutilisable.
type Template struct {
	ID     int             `json:"id_template"`
	Nom    string          `json:"nom_template"`
	Desc   string          `json:"description"`
	Modele json.RawMessage `json:"modele"`
	Actif  bool            `json:"actif"`
}

type templateRequest struct {
	NomTemplate string          `json:"nom_template"`
	Description string          `json:"description"`
	Modele      json.RawMessage `json:"modele"`
	Actif       *bool           `json:"actif"`
}

// scanTemplates exécute une requête et retourne les modèles.
func scanTemplates(query string) ([]Template, error) {
	rows, err := database.DB.Query(query)
	if err != nil {
		return nil, err
	}
	defer rows.Close()
	out := []Template{}
	for rows.Next() {
		var t Template
		var modeleStr string
		if err := rows.Scan(&t.ID, &t.Nom, &t.Desc, &modeleStr, &t.Actif); err == nil {
			t.Modele = json.RawMessage(modeleStr)
			out = append(out, t)
		}
	}
	return out, nil
}

// GetTemplatesAdmin — tous les modèles (actifs et inactifs).
func GetTemplatesAdmin(w http.ResponseWriter, r *http.Request) {
	out, err := scanTemplates(
		"SELECT id_template, nom_template, COALESCE(description, ''), COALESCE(modele, '{}'), actif FROM templates_evenements ORDER BY nom_template")
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, out, http.StatusOK)
}

// insertTemplate crée un modèle. Logique partagée admin / salarié.
func insertTemplate(req templateRequest) error {
	modele := strings.TrimSpace(string(req.Modele))
	if modele == "" || modele == "null" {
		modele = "{}"
	}
	_, err := database.DB.Exec(
		"INSERT INTO templates_evenements (nom_template, description, modele) VALUES (?, ?, CAST(? AS JSON))",
		req.NomTemplate, req.Description, modele)
	return err
}

// CreateTemplate ajoute un modèle (admin ou salarié — disponible pour tous).
func CreateTemplate(w http.ResponseWriter, r *http.Request) {
	var req templateRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || strings.TrimSpace(req.NomTemplate) == "" {
		jsonErr(w, "nom du modèle requis", http.StatusBadRequest)
		return
	}
	if err := insertTemplate(req); err != nil {
		jsonErr(w, "impossible de créer le modèle", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "modèle créé"}, http.StatusCreated)
}

func UpdateTemplate(w http.ResponseWriter, r *http.Request, id string) {
	var req templateRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || strings.TrimSpace(req.NomTemplate) == "" {
		jsonErr(w, "nom du modèle requis", http.StatusBadRequest)
		return
	}
	modele := strings.TrimSpace(string(req.Modele))
	if modele == "" || modele == "null" {
		modele = "{}"
	}
	actif := true
	if req.Actif != nil {
		actif = *req.Actif
	}
	_, err := database.DB.Exec(
		"UPDATE templates_evenements SET nom_template = ?, description = ?, modele = CAST(? AS JSON), actif = ? WHERE id_template = ?",
		req.NomTemplate, req.Description, modele, actif, id)
	if err != nil {
		jsonErr(w, "impossible de mettre à jour le modèle", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "modèle mis à jour"}, http.StatusOK)
}

// ToggleTemplate active/désactive un modèle (suppression douce).
func ToggleTemplate(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE templates_evenements SET actif = NOT actif WHERE id_template = ?", id)
	if err != nil {
		jsonErr(w, "impossible de changer le statut", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "statut mis à jour"}, http.StatusOK)
}

// DeleteTemplate supprime un modèle ; les événements liés sont détachés
// (id_template = NULL) pour respecter la FK.
func DeleteTemplate(w http.ResponseWriter, r *http.Request, id string) {
	if _, err := database.DB.Exec("UPDATE evenements SET id_template = NULL WHERE id_template = ?", id); err != nil {
		jsonErr(w, "impossible de détacher les événements liés", http.StatusInternalServerError)
		return
	}
	if _, err := database.DB.Exec("DELETE FROM templates_evenements WHERE id_template = ?", id); err != nil {
		jsonErr(w, "impossible de supprimer le modèle", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "modèle supprimé"}, http.StatusOK)
}

// CreateTemplateSalarie — enregistrement d'un modèle par un salarié.
func CreateTemplateSalarie(w http.ResponseWriter, r *http.Request, userId int) {
	CreateTemplate(w, r)
}

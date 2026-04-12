package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

func GetCategories(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_categorie, nom, description, date_creation FROM categories_prestations")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var categories []models.CategoriePrestation
	for rows.Next() {
		var cat models.CategoriePrestation
		if err := rows.Scan(&cat.IDCategorie, &cat.Nom, &cat.Description, &cat.DateCreation); err == nil {
			categories = append(categories, cat)
		}
	}
	
	if categories == nil {
		categories = []models.CategoriePrestation{}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(categories)
}

func CreateCategorie(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Nom         string `json:"nom"`
		Description string `json:"description"`
	}
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	res, err := database.DB.Exec("INSERT INTO categories_prestations (nom, description) VALUES (?, ?)", req.Nom, req.Description)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer la catégorie"})
		return
	}
	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "catégorie créée avec succès", "id": id})
}

func UpdateCategorie(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Nom         string `json:"nom"`
		Description string `json:"description"`
	}
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	_, err = database.DB.Exec("UPDATE categories_prestations SET nom = ?, description = ? WHERE id_categorie = ?", req.Nom, req.Description, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de modifier la catégorie"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "catégorie modifiée avec succès"})
}

func DeleteCategorie(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM categories_prestations WHERE id_categorie = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de supprimer la catégorie (peut-être des prestations associées)"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "catégorie supprimée avec succès"})
}

package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strings"
)

// scanCategoriesObjets exécute une requête et renvoie la liste des catégories d'objets.
func scanCategoriesObjets(query string, args ...interface{}) ([]models.CategorieObjet, error) {
	rows, err := database.DB.Query(query, args...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	cats := []models.CategorieObjet{}
	for rows.Next() {
		var c models.CategorieObjet
		if err := rows.Scan(&c.IDCategorieObjet, &c.Nom, &c.Actif, &c.DateCreation); err == nil {
			cats = append(cats, c)
		}
	}
	return cats, nil
}

// GetCategoriesObjets — endpoint public : catégories actives uniquement (pour le dépôt d'annonce).
func GetCategoriesObjets(w http.ResponseWriter, r *http.Request) {
	cats, err := scanCategoriesObjets(
		"SELECT id_categorie_objet, nom, actif, date_creation FROM categories_objets WHERE actif = 1 ORDER BY nom")
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, cats, http.StatusOK)
}

// GetCategoriesObjetsAdmin — endpoint admin : toutes les catégories (actives + inactives).
func GetCategoriesObjetsAdmin(w http.ResponseWriter, r *http.Request) {
	cats, err := scanCategoriesObjets(
		"SELECT id_categorie_objet, nom, actif, date_creation FROM categories_objets ORDER BY nom")
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, cats, http.StatusOK)
}

func CreateCategorieObjet(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Nom string `json:"nom"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	req.Nom = strings.TrimSpace(req.Nom)
	if len(req.Nom) < 2 || len(req.Nom) > 100 {
		jsonErr(w, "le nom doit contenir entre 2 et 100 caractères", http.StatusBadRequest)
		return
	}

	res, err := database.DB.Exec("INSERT INTO categories_objets (nom) VALUES (?)", req.Nom)
	if err != nil {
		jsonErr(w, "cette catégorie existe déjà ou est invalide", http.StatusBadRequest)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "catégorie créée", "id": id}, http.StatusCreated)
}

func UpdateCategorieObjet(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Nom   string `json:"nom"`
		Actif *bool  `json:"actif"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	req.Nom = strings.TrimSpace(req.Nom)
	if len(req.Nom) < 2 || len(req.Nom) > 100 {
		jsonErr(w, "le nom doit contenir entre 2 et 100 caractères", http.StatusBadRequest)
		return
	}
	actif := true
	if req.Actif != nil {
		actif = *req.Actif
	}

	if _, err := database.DB.Exec(
		"UPDATE categories_objets SET nom = ?, actif = ? WHERE id_categorie_objet = ?",
		req.Nom, actif, id); err != nil {
		jsonErr(w, "impossible de modifier la catégorie", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "catégorie modifiée"}, http.StatusOK)
}

func DeleteCategorieObjet(w http.ResponseWriter, r *http.Request, id string) {
	if _, err := database.DB.Exec("DELETE FROM categories_objets WHERE id_categorie_objet = ?", id); err != nil {
		jsonErr(w, "impossible de supprimer la catégorie", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "catégorie supprimée"}, http.StatusOK)
}

// categorieObjetValide vérifie qu'un nom de catégorie existe et est actif.
// Utilisé par CreateAnnonce pour empêcher toute saisie hors liste.
func categorieObjetValide(nom string) bool {
	var x int
	err := database.DB.QueryRow(
		"SELECT 1 FROM categories_objets WHERE nom = ? AND actif = 1 LIMIT 1", nom).Scan(&x)
	return err == nil
}

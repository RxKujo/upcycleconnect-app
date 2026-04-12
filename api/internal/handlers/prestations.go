package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

func GetPrestations(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_prestation, id_categorie, titre, description, prix, statut, date_creation FROM prestations")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var prestations []models.Prestation
	for rows.Next() {
		var p models.Prestation
		if err := rows.Scan(&p.IDPrestation, &p.IDCategorie, &p.Titre, &p.Description, &p.Prix, &p.Statut, &p.DateCreation); err == nil {
			prestations = append(prestations, p)
		}
	}
	if prestations == nil {
		prestations = []models.Prestation{}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(prestations)
}

func GetPrestation(w http.ResponseWriter, r *http.Request, id string) {
	var p models.Prestation
	err := database.DB.QueryRow("SELECT id_prestation, id_categorie, titre, description, prix, statut, date_creation FROM prestations WHERE id_prestation = ?", id).
		Scan(&p.IDPrestation, &p.IDCategorie, &p.Titre, &p.Description, &p.Prix, &p.Statut, &p.DateCreation)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "prestation non trouvée"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(p)
}

func CreatePrestation(w http.ResponseWriter, r *http.Request) {
	var req models.CreatePrestationRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	res, err := database.DB.Exec("INSERT INTO prestations (id_categorie, titre, description, prix, statut) VALUES (?, ?, ?, ?, 'validee')",
		req.IDCategorie, req.Titre, req.Description, req.Prix)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer la prestation"})
		return
	}
	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "prestation créée avec succès", "id": id})
}

func ValiderPrestation(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE prestations SET statut = 'validee' WHERE id_prestation = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la validation"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "prestation validée"})
}

func RefuserPrestation(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE prestations SET statut = 'refusee' WHERE id_prestation = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors du refus"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "prestation refusée"})
}

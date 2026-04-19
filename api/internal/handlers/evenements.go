package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

func GetEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		var e models.Evenement
		if err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation); err == nil {
			evenements = append(evenements, e)
		}
	}
	if evenements == nil {
		evenements = []models.Evenement{}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(evenements)
}

func GetEvenement(w http.ResponseWriter, r *http.Request, id string) {
	var e models.Evenement
	err := database.DB.QueryRow("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements WHERE id_evenement = ?", id).
		Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "événement non trouvé"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(e)
}

func CreateEvenement(w http.ResponseWriter, r *http.Request, adminId int) {
	var req models.CreateEvenementRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide', ?)`
	res, err := database.DB.Exec(query, req.IDCreateur, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix, adminId)
	
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer l'événement"})
		return
	}
	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "événement créé", "id": id})
}

func ValiderEvenement(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'valide', valide_par = ? WHERE id_evenement = ?", adminId, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la validation"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement validé"})
}

func RefuserEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'refuse' WHERE id_evenement = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors du refus"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement refusé"})
}

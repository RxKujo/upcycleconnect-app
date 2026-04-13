package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
)

func GetEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements")
	if err != nil {
		fmt.Printf("GetEvenements Query err: %v\n", err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		var e models.Evenement
		var lieu sql.NullString
		var validePar sql.NullInt64
		if err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &validePar, &e.DateCreation); err == nil {
			if lieu.Valid {
				e.Lieu = &lieu.String
			}
			if validePar.Valid {
				v := int(validePar.Int64)
				e.ValidePar = &v
			}
			evenements = append(evenements, e)
		} else {
			fmt.Printf("GetEvenements Scan err: %v\n", err)
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
	var lieu sql.NullString
	var validePar sql.NullInt64
	err := database.DB.QueryRow("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements WHERE id_evenement = ?", id).
		Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &validePar, &e.DateCreation)
	if err != nil {
		fmt.Printf("GetEvenement Scan err: %v\n", err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "événement non trouvé"})
		return
	}
	if lieu.Valid {
		e.Lieu = &lieu.String
	}
	if validePar.Valid {
		v := int(validePar.Int64)
		e.ValidePar = &v
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
	res, err := database.DB.Exec(query, req.IDCreateur, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut.Time, req.DateFin.Time, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix, adminId)
	
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

func AttenteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'en_attente', valide_par = NULL WHERE id_evenement = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la mise en attente"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement mis en attente"})
}

func UpdateEvenement(w http.ResponseWriter, r *http.Request, id string) {
	var req models.CreateEvenementRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `UPDATE evenements SET titre=?, description=?, type_evenement=?, format=?, lieu=?, date_debut=?, date_fin=?, nb_places_total=?, prix=? WHERE id_evenement=?`
	_, err = database.DB.Exec(query, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut.Time, req.DateFin.Time, req.NbPlacesTotal, req.Prix, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de mettre à jour l'événement"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement mis à jour"})
}

func DeleteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM evenements WHERE id_evenement = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de supprimer l'événement"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement supprimé"})
}

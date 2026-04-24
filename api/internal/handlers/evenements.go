package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strconv"
)

func GetEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT e.id_evenement, e.id_createur, e.titre, e.description, e.type_evenement, e.format, e.lieu,
		       e.date_debut, e.date_fin, e.nb_places_total, e.nb_places_dispo, e.prix, e.statut, e.valide_par, e.date_creation,
		       COUNT(i.id_inscription) AS nb_inscrits
		FROM evenements e
		LEFT JOIN inscriptions_evenements i ON i.id_evenement = e.id_evenement
		GROUP BY e.id_evenement
		ORDER BY e.date_creation DESC`)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		var e models.Evenement
		if err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation, &e.NbInscrits); err == nil {
			evenements = append(evenements, e)
		}
	}
	if evenements == nil {
		evenements = []models.Evenement{}
	}
	jsonOK(w, evenements, http.StatusOK)
}

func GetEvenement(w http.ResponseWriter, r *http.Request, id string) {
	var e models.Evenement
	err := database.DB.QueryRow(`
		SELECT e.id_evenement, e.id_createur, e.titre, e.description, e.type_evenement, e.format, e.lieu,
		       e.date_debut, e.date_fin, e.nb_places_total, e.nb_places_dispo, e.prix, e.statut, e.valide_par, e.date_creation,
		       COUNT(i.id_inscription) AS nb_inscrits
		FROM evenements e
		LEFT JOIN inscriptions_evenements i ON i.id_evenement = e.id_evenement
		WHERE e.id_evenement = ?
		GROUP BY e.id_evenement`, id).
		Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation, &e.NbInscrits)
	if err != nil {
		jsonErr(w, "événement non trouvé", http.StatusNotFound)
		return
	}
	e.Animateurs = fetchAnimateurs(e.IDEvenement)
	jsonOK(w, e, http.StatusOK)
}

func syncAnimateurs(eventId int64, animateurs []int) {
	database.DB.Exec("DELETE FROM animateurs_evenements WHERE id_evenement = ?", eventId)
	for _, uid := range animateurs {
		database.DB.Exec("INSERT INTO animateurs_evenements (id_evenement, id_salarie) VALUES (?, ?)", eventId, uid)
	}
}

func fetchAnimateurs(eventId int) []models.AnimateurInfo {
	rows, err := database.DB.Query(`SELECT u.id_utilisateur, u.nom, u.prenom FROM animateurs_evenements ae JOIN utilisateurs u ON u.id_utilisateur = ae.id_salarie WHERE ae.id_evenement = ?`, eventId)
	if err != nil {
		return []models.AnimateurInfo{}
	}
	defer rows.Close()
	var result []models.AnimateurInfo
	for rows.Next() {
		var a models.AnimateurInfo
		if rows.Scan(&a.IDUtilisateur, &a.Nom, &a.Prenom) == nil {
			result = append(result, a)
		}
	}
	if result == nil {
		return []models.AnimateurInfo{}
	}
	return result
}

func CreateEvenement(w http.ResponseWriter, r *http.Request, adminId int) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	res, err := database.DB.Exec(
		`INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')`,
		adminId, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix,
	)
	if err != nil {
		jsonErr(w, "impossible de créer l'événement", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	syncAnimateurs(id, req.Animateurs)
	jsonOK(w, map[string]interface{}{"message": "événement créé", "id": id}, http.StatusCreated)
}

func UpdateEvenement(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	res, err := database.DB.Exec(
		`UPDATE evenements SET titre=?, description=?, type_evenement=?, format=?, lieu=?, date_debut=?, date_fin=?, nb_places_total=?, prix=? WHERE id_evenement=?`,
		req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, req.Prix, id,
	)
	if err != nil {
		jsonErr(w, "impossible de modifier l'événement", http.StatusInternalServerError)
		return
	}
	rows, _ := res.RowsAffected()
	if rows == 0 {
		jsonErr(w, "événement non trouvé", http.StatusNotFound)
		return
	}
	eventId, _ := strconv.ParseInt(id, 10, 64)
	syncAnimateurs(eventId, req.Animateurs)
	jsonOK(w, map[string]string{"message": "événement mis à jour"}, http.StatusOK)
}

func DeleteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM evenements WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, "impossible de supprimer l'événement", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "événement supprimé"}, http.StatusOK)
}

func AttenteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'en_attente' WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, "erreur lors de la mise en attente", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "événement remis en attente"}, http.StatusOK)
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

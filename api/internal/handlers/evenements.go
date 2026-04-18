package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"strings"

	"github.com/golang-jwt/jwt/v5"
)

func fetchAnimateurs(eventId int) []models.AnimateurInfo {
	rows, err := database.DB.Query(`
		SELECT u.id_utilisateur, u.nom, u.prenom
		FROM animateurs_evenements ae
		JOIN utilisateurs u ON u.id_utilisateur = ae.id_salarie
		WHERE ae.id_evenement = ?`, eventId)
	if err != nil {
		return nil
	}
	defer rows.Close()
	var result []models.AnimateurInfo
	for rows.Next() {
		var a models.AnimateurInfo
		if err := rows.Scan(&a.IDUtilisateur, &a.Nom, &a.Prenom); err == nil {
			result = append(result, a)
		}
	}
	return result
}

func syncAnimateurs(eventId int64, animateurs []int) {
	database.DB.Exec("DELETE FROM animateurs_evenements WHERE id_evenement = ?", eventId)
	for _, uid := range animateurs {
		database.DB.Exec("INSERT INTO animateurs_evenements (id_evenement, id_salarie) VALUES (?, ?)", eventId, uid)
	}
}

func scanEvenement(rows interface{ Scan(...any) error }) (models.Evenement, error) {
	var e models.Evenement
	var lieu sql.NullString
	var validePar sql.NullInt64
	err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &validePar, &e.DateCreation)
	if err != nil {
		return e, err
	}
	if lieu.Valid {
		e.Lieu = &lieu.String
	}
	if validePar.Valid {
		v := int(validePar.Int64)
		e.ValidePar = &v
	}
	return e, nil
}

const evenementCols = "id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation"

func jsonOK(w http.ResponseWriter, data any) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(data)
}

func jsonErr(w http.ResponseWriter, status int, msg string) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	json.NewEncoder(w).Encode(map[string]string{"erreur": msg})
}

func GetEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT " + evenementCols + " FROM evenements ORDER BY date_creation DESC")
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "erreur serveur")
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		if e, err := scanEvenement(rows); err == nil {
			evenements = append(evenements, e)
		} else {
			fmt.Printf("GetEvenements Scan err: %v\n", err)
		}
	}
	if evenements == nil {
		evenements = []models.Evenement{}
	}
	jsonOK(w, evenements)
}

func GetCatalogueEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT "+evenementCols+" FROM evenements WHERE statut = 'valide' AND date_debut > NOW() ORDER BY date_debut ASC")
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "erreur serveur")
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		if e, err := scanEvenement(rows); err == nil {
			evenements = append(evenements, e)
		}
	}
	if evenements == nil {
		evenements = []models.Evenement{}
	}
	jsonOK(w, evenements)
}

func GetEvenement(w http.ResponseWriter, r *http.Request, id string) {
	row := database.DB.QueryRow("SELECT "+evenementCols+" FROM evenements WHERE id_evenement = ?", id)
	e, err := scanEvenement(row)
	if err != nil {
		jsonErr(w, http.StatusNotFound, "événement non trouvé")
		return
	}

	e.Animateurs = fetchAnimateurs(e.IDEvenement)

	authHeader := r.Header.Get("Authorization")
	if authHeader != "" {
		parts := strings.Split(authHeader, " ")
		if len(parts) == 2 && parts[0] == "Bearer" {
			secret := os.Getenv("JWT_SECRET")
			claims := jwt.MapClaims{}
			token, _ := jwt.ParseWithClaims(parts[1], claims, func(t *jwt.Token) (interface{}, error) {
				return []byte(secret), nil
			})
			if token != nil && token.Valid {
				userId := int(claims["id"].(float64))
				var exists bool
				database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM inscriptions_evenements WHERE id_evenement = ? AND id_utilisateur = ?)", e.IDEvenement, userId).Scan(&exists)
				e.IsRegistered = exists
			}
		}
	}

	jsonOK(w, e)
}

func CreateEvenement(w http.ResponseWriter, r *http.Request, adminId int) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, http.StatusBadRequest, "données invalides")
		return
	}

	res, err := database.DB.Exec(
		"INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')",
		req.IDCreateur, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut.Time, req.DateFin.Time, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix,
	)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "impossible de créer l'événement")
		return
	}

	id, _ := res.LastInsertId()
	syncAnimateurs(id, req.Animateurs)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]any{"message": "événement créé", "id": id})
}

func UpdateEvenement(w http.ResponseWriter, r *http.Request, id string) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, http.StatusBadRequest, "données invalides")
		return
	}

	_, err := database.DB.Exec(
		"UPDATE evenements SET titre=?, description=?, type_evenement=?, format=?, lieu=?, date_debut=?, date_fin=?, nb_places_total=?, prix=? WHERE id_evenement=?",
		req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut.Time, req.DateFin.Time, req.NbPlacesTotal, req.Prix, id,
	)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "impossible de mettre à jour l'événement")
		return
	}

	var eventId int64
	fmt.Sscan(id, &eventId)
	syncAnimateurs(eventId, req.Animateurs)

	jsonOK(w, map[string]string{"message": "événement mis à jour"})
}

func ValiderEvenement(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'valide', valide_par = ? WHERE id_evenement = ?", adminId, id)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "erreur lors de la validation")
		return
	}
	jsonOK(w, map[string]string{"message": "événement validé"})
}

func RefuserEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'refuse' WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "erreur lors du refus")
		return
	}
	jsonOK(w, map[string]string{"message": "événement refusé"})
}

func AttenteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'en_attente', valide_par = NULL WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "erreur lors de la mise en attente")
		return
	}
	jsonOK(w, map[string]string{"message": "événement mis en attente"})
}

func DeleteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM evenements WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, http.StatusInternalServerError, "impossible de supprimer l'événement")
		return
	}
	jsonOK(w, map[string]string{"message": "événement supprimé"})
}

package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"
)

type PlanningItem struct {
	IDPlanning      int     `json:"id_planning"`
	IDUtilisateur   int     `json:"id_utilisateur"`
	Titre           string  `json:"titre_creneau"`
	Description     *string `json:"description"`
	DateDebut       string  `json:"date_debut"`
	DateFin         string  `json:"date_fin"`
	TypeCreneau     string  `json:"type_creneau"`
	IDEvenement     *int    `json:"id_evenement"`
	IDCatalogueItem *int    `json:"id_catalogue_item"`
	EstManuel       bool    `json:"est_manuel"`
}

func GetMonPlanning(w http.ResponseWriter, r *http.Request, userId int) {
	rows, err := database.DB.Query(`
		SELECT id_planning, id_utilisateur, titre_creneau, description, date_debut, date_fin,
		       type_creneau, id_evenement, id_catalogue_item, est_manuel
		FROM planning_utilisateurs
		WHERE id_utilisateur = ?
		ORDER BY date_debut ASC`, userId)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	items := []PlanningItem{}
	for rows.Next() {
		var p PlanningItem
		var desc sql.NullString
		var idEv, idCat sql.NullInt64
		if err := rows.Scan(&p.IDPlanning, &p.IDUtilisateur, &p.Titre, &desc,
			&p.DateDebut, &p.DateFin, &p.TypeCreneau, &idEv, &idCat, &p.EstManuel); err != nil {
			continue
		}
		if desc.Valid {
			p.Description = &desc.String
		}
		if idEv.Valid {
			v := int(idEv.Int64)
			p.IDEvenement = &v
		}
		if idCat.Valid {
			v := int(idCat.Int64)
			p.IDCatalogueItem = &v
		}
		items = append(items, p)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(items)
}

func AddPlanningManuel(w http.ResponseWriter, r *http.Request, userId int) {
	var body struct {
		Titre       string `json:"titre_creneau"`
		Description string `json:"description"`
		DateDebut   string `json:"date_debut"`
		DateFin     string `json:"date_fin"`
		TypeCreneau string `json:"type_creneau"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		jsonError(w, "données invalides", http.StatusBadRequest)
		return
	}
	if body.Titre == "" || body.DateDebut == "" || body.DateFin == "" {
		jsonError(w, "titre, date_debut et date_fin requis", http.StatusBadRequest)
		return
	}
	typeCreneau := body.TypeCreneau
	validTypes := map[string]bool{"evenement": true, "formation": true, "reunion": true, "travail": true, "perso": true}
	if !validTypes[typeCreneau] {
		typeCreneau = "perso"
	}

	res, err := database.DB.Exec(`
		INSERT INTO planning_utilisateurs (id_utilisateur, titre_creneau, description, date_debut, date_fin, type_creneau, est_manuel)
		VALUES (?, ?, ?, ?, ?, ?, 1)`,
		userId, body.Titre, body.Description, body.DateDebut, body.DateFin, typeCreneau)
	if err != nil {
		jsonError(w, "erreur création", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"id_planning": id, "message": "créneau ajouté"})
}

func DeletePlanningItem(w http.ResponseWriter, r *http.Request, idStr string, userId int) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonError(w, "id invalide", http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(
		"DELETE FROM planning_utilisateurs WHERE id_planning = ? AND id_utilisateur = ? AND est_manuel = 1",
		id, userId)
	if err != nil {
		jsonError(w, "erreur suppression", http.StatusInternalServerError)
		return
	}
	n, _ := res.RowsAffected()
	if n == 0 {
		jsonError(w, "créneau introuvable ou non supprimable", http.StatusNotFound)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "créneau supprimé"})
}

// AddPlanningFromEvenement ajoute automatiquement un créneau après inscription à un événement
func AddPlanningFromEvenement(userId, evenementId int) {
	var titre, dateDebut, dateFin string
	err := database.DB.QueryRow(
		"SELECT titre, date_debut, date_fin FROM evenements WHERE id_evenement = ?", evenementId).
		Scan(&titre, &dateDebut, &dateFin)
	if err != nil {
		return
	}
	database.DB.Exec(`
		INSERT IGNORE INTO planning_utilisateurs (id_utilisateur, titre_creneau, date_debut, date_fin, type_creneau, id_evenement, est_manuel)
		VALUES (?, ?, ?, ?, 'evenement', ?, 0)`,
		userId, titre, dateDebut, dateFin, evenementId)
}

// AddPlanningFromFormation ajoute automatiquement un créneau après réservation d'une formation
func AddPlanningFromFormation(userId, catalogueItemId int) {
	var titre string
	var dateDebut, dateFin sql.NullString
	err := database.DB.QueryRow(
		"SELECT titre, date_debut, date_fin FROM catalogue_items WHERE id_catalogue_item = ?", catalogueItemId).
		Scan(&titre, &dateDebut, &dateFin)
	if err != nil {
		return
	}
	if !dateDebut.Valid || !dateFin.Valid {
		// Pas de dates définies, utiliser J+7 par défaut
		now := time.Now()
		db := now.AddDate(0, 0, 7).Format("2006-01-02 09:00:00")
		df := now.AddDate(0, 0, 7).Format("2006-01-02 17:00:00")
		dateDebut.String = db
		dateFin.String = df
	}
	database.DB.Exec(`
		INSERT IGNORE INTO planning_utilisateurs (id_utilisateur, titre_creneau, date_debut, date_fin, type_creneau, id_catalogue_item, est_manuel)
		VALUES (?, ?, ?, ?, 'formation', ?, 0)`,
		userId, titre, dateDebut.String, dateFin.String, catalogueItemId)
}

// helper (already exists in helpers.go but we need it accessible)
func jsonError(w http.ResponseWriter, msg string, code int) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(code)
	json.NewEncoder(w).Encode(map[string]string{"erreur": msg})
}

// splitLast splits "a/b/c" → ["a/b", "c"]
func splitLast(s string) (string, string) {
	i := strings.LastIndex(s, "/")
	if i < 0 {
		return "", s
	}
	return s[:i], s[i+1:]
}

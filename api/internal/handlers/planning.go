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

// GetMonPlanning renvoie l'agenda du salarié : ses créneaux manuels (+ réservations
// de formations catalogue) FUSIONNÉS, à la lecture, avec un créneau calculé par
// séance des événements VALIDÉS où il est concerné — qu'il les organise
// (evenements.id_createur), qu'il anime une séance (animateurs_seances) ou qu'il
// y soit inscrit (inscriptions_evenements). Rien n'est stocké : l'agenda est un
// miroir toujours à jour des événements, sans risque de doublon ni de désync.
func GetMonPlanning(w http.ResponseWriter, r *http.Request, userId int) {
	items := []PlanningItem{}

	// 1) Créneaux modifiables persistés : saisies manuelles + formations catalogue.
	//    On IGNORE volontairement les anciennes copies auto d'événements
	//    (est_manuel=0 AND id_catalogue_item IS NULL) : elles sont désormais
	//    recalculées en direct ci-dessous et feraient double emploi.
	rows, err := database.DB.Query(`
		SELECT id_planning, id_utilisateur, titre_creneau, description, date_debut, date_fin,
		       type_creneau, id_evenement, id_catalogue_item, est_manuel
		FROM planning_utilisateurs
		WHERE id_utilisateur = ?
		  AND (est_manuel = 1 OR id_catalogue_item IS NOT NULL)
		ORDER BY date_debut ASC`, userId)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

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

	// 2) Créneaux calculés en direct : une ligne par séance d'événement validé
	//    concernant le salarié. Les jointures sur cet utilisateur donnent 0 ou 1
	//    ligne chacune (pas de multiplication) ; le WHERE dédoublonne les rôles
	//    cumulés (ex. organisateur ET inscrit → un seul créneau par séance).
	seances, err := database.DB.Query(`
		SELECT s.id_seance, e.id_evenement, e.titre, s.titre, e.type_evenement,
		       s.date_debut, s.date_fin,
		       (e.id_createur = ?)           AS organise,
		       (a.id_salarie IS NOT NULL)    AS anime,
		       (i.id_utilisateur IS NOT NULL) AS inscrit
		FROM seances_evenements s
		JOIN evenements e ON e.id_evenement = s.id_evenement
		LEFT JOIN animateurs_seances a
		       ON a.id_seance = s.id_seance AND a.id_salarie = ?
		LEFT JOIN inscriptions_evenements i
		       ON i.id_evenement = e.id_evenement AND i.id_utilisateur = ?
		WHERE e.statut = 'valide'
		  AND (e.id_createur = ? OR a.id_salarie IS NOT NULL OR i.id_utilisateur IS NOT NULL)
		ORDER BY s.date_debut ASC`, userId, userId, userId, userId)
	if err == nil {
		defer seances.Close()
		for seances.Next() {
			var idSeance, idEvenement int
			var evTitre, typeEv, debut, fin string
			var seanceTitre sql.NullString
			var organise, anime, inscrit bool
			if err := seances.Scan(&idSeance, &idEvenement, &evTitre, &seanceTitre, &typeEv,
				&debut, &fin, &organise, &anime, &inscrit); err != nil {
				continue
			}

			titre := evTitre
			if seanceTitre.Valid && strings.TrimSpace(seanceTitre.String) != "" {
				titre = evTitre + " — " + seanceTitre.String
			}

			// Rôle affiché (priorité : organisateur > animateur > participant).
			role := "Vous participez à cet événement."
			if organise {
				role = "Vous organisez cet événement."
			} else if anime {
				role = "Vous animez cette séance."
			}

			typeCreneau := "evenement"
			if strings.EqualFold(typeEv, "formation") {
				typeCreneau = "formation"
			}

			idEv := idEvenement
			roleCopy := role
			items = append(items, PlanningItem{
				IDPlanning:    -idSeance, // id synthétique négatif : jamais en collision avec un id_planning réel
				IDUtilisateur: userId,
				Titre:         titre,
				Description:   &roleCopy,
				DateDebut:     debut,
				DateFin:       fin,
				TypeCreneau:   typeCreneau,
				IDEvenement:   &idEv,
				EstManuel:     false, // lecture seule côté front (ni Modifier ni Supprimer)
			})
		}
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

	// Garde-fou anti-chevauchement : refuse un créneau qui se superpose à un existant.
	// Deux intervalles [d1,f1) et [d2,f2) se chevauchent si d1 < f2 ET d2 < f1.
	var conflits int
	if err := database.DB.QueryRow(`
		SELECT COUNT(*) FROM planning_utilisateurs
		WHERE id_utilisateur = ? AND date_debut < ? AND date_fin > ?`,
		userId, body.DateFin, body.DateDebut).Scan(&conflits); err == nil && conflits > 0 {
		jsonError(w, "ce créneau chevauche un créneau existant", http.StatusConflict)
		return
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

func UpdatePlanningItem(w http.ResponseWriter, r *http.Request, idStr string, userId int) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonError(w, "id invalide", http.StatusBadRequest)
		return
	}
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

	// Vérifie l'existence et que le créneau est bien modifiable (manuel).
	var estManuel bool
	err = database.DB.QueryRow(
		"SELECT est_manuel FROM planning_utilisateurs WHERE id_planning = ? AND id_utilisateur = ?",
		id, userId).Scan(&estManuel)
	if err == sql.ErrNoRows {
		jsonError(w, "créneau introuvable", http.StatusNotFound)
		return
	}
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if !estManuel {
		jsonError(w, "ce créneau automatique n'est pas modifiable", http.StatusForbidden)
		return
	}

	// Garde-fou anti-chevauchement (en excluant le créneau lui-même).
	var conflits int
	if err := database.DB.QueryRow(`
		SELECT COUNT(*) FROM planning_utilisateurs
		WHERE id_utilisateur = ? AND id_planning <> ? AND date_debut < ? AND date_fin > ?`,
		userId, id, body.DateFin, body.DateDebut).Scan(&conflits); err == nil && conflits > 0 {
		jsonError(w, "ce créneau chevauche un créneau existant", http.StatusConflict)
		return
	}

	if _, err := database.DB.Exec(`
		UPDATE planning_utilisateurs
		SET titre_creneau = ?, description = ?, date_debut = ?, date_fin = ?, type_creneau = ?
		WHERE id_planning = ? AND id_utilisateur = ? AND est_manuel = 1`,
		body.Titre, body.Description, body.DateDebut, body.DateFin, typeCreneau, id, userId); err != nil {
		jsonError(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "créneau mis à jour"})
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

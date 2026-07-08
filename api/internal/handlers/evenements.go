package handlers

// Événements back-office : séances multi-jours, animateurs, inscriptions, validation.

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"
)

// --- Parsing dates & séances (helpers) ---

// seanceLayouts : formats de date acceptés du front (datetime-local, ISO, MySQL…).
var seanceLayouts = []string{
	"2006-01-02T15:04:05Z07:00",
	"2006-01-02T15:04:05Z",
	"2006-01-02T15:04:05",
	"2006-01-02 15:04:05",
	"2006-01-02T15:04",
	"2006-01-02 15:04",
}

// parseFlexibleTime : parse une date selon seanceLayouts.
func parseFlexibleTime(s string) (time.Time, bool) {
	s = strings.TrimSpace(s)
	for _, l := range seanceLayouts {
		if t, err := time.Parse(l, s); err == nil {
			return t, true
		}
	}
	return time.Time{}, false
}

// computeEnvelope : enveloppe (début min / fin max) + format/lieu de la 1re séance
// valide, pour alimenter les colonnes récapitulatives de evenements.
func computeEnvelope(seances []models.SeanceInput) (debut, fin time.Time, format string, lieu *string, ok bool) {
	for _, s := range seances {
		d, okD := parseFlexibleTime(s.DateDebut)
		f, okF := parseFlexibleTime(s.DateFin)
		if !okD || !okF {
			continue
		}
		if !ok || d.Before(debut) {
			debut = d
		}
		if !ok || f.After(fin) {
			fin = f
		}
		if !ok {
			format = s.Format
			if strings.TrimSpace(s.Lieu) != "" {
				v := s.Lieu
				lieu = &v
			}
			ok = true
		}
	}
	if format == "" {
		format = "presentiel"
	}
	return
}

// syncSeances : remplace les séances (+ leurs animateurs) et reconstruit
// animateurs_evenements comme l'union distincte des animateurs de séance.
func syncSeances(eventId int64, seances []models.SeanceInput) {
	// ON DELETE CASCADE nettoie animateurs_seances.
	database.DB.Exec("DELETE FROM seances_evenements WHERE id_evenement = ?", eventId)
	for i, s := range seances {
		d, okD := parseFlexibleTime(s.DateDebut)
		f, okF := parseFlexibleTime(s.DateFin)
		if !okD || !okF {
			continue
		}
		format := s.Format
		if format == "" {
			format = "presentiel"
		}
		var titre interface{}
		if strings.TrimSpace(s.Titre) != "" {
			titre = strings.TrimSpace(s.Titre)
		}
		var lieu interface{}
		if strings.TrimSpace(s.Lieu) != "" {
			lieu = strings.TrimSpace(s.Lieu)
		}
		res, err := database.DB.Exec(
			`INSERT INTO seances_evenements (id_evenement, titre, format, lieu, date_debut, date_fin, ordre) VALUES (?, ?, ?, ?, ?, ?, ?)`,
			eventId, titre, format, lieu, d, f, i,
		)
		if err != nil {
			continue
		}
		sid, _ := res.LastInsertId()
		for _, uid := range s.Animateurs {
			database.DB.Exec("INSERT IGNORE INTO animateurs_seances (id_seance, id_salarie) VALUES (?, ?)", sid, uid)
		}
	}
	// Union des animateurs au niveau événement.
	database.DB.Exec("DELETE FROM animateurs_evenements WHERE id_evenement = ?", eventId)
	database.DB.Exec(
		`INSERT INTO animateurs_evenements (id_evenement, id_salarie)
		 SELECT DISTINCT ?, a.id_salarie
		 FROM animateurs_seances a
		 JOIN seances_evenements s ON s.id_seance = a.id_seance
		 WHERE s.id_evenement = ?`,
		eventId, eventId,
	)
}

// fetchSeances : séances d'un événement, animateurs inclus.
func fetchSeances(eventId int) []models.Seance {
	rows, err := database.DB.Query(
		`SELECT id_seance, titre, format, lieu, date_debut, date_fin, ordre
		 FROM seances_evenements WHERE id_evenement = ? ORDER BY date_debut ASC, ordre ASC`, eventId)
	if err != nil {
		return []models.Seance{}
	}
	defer rows.Close()
	result := []models.Seance{}
	for rows.Next() {
		var s models.Seance
		var titre, lieu *string
		var debut, fin time.Time
		if err := rows.Scan(&s.IDSeance, &titre, &s.Format, &lieu, &debut, &fin, &s.Ordre); err != nil {
			continue
		}
		s.Titre = titre
		s.Lieu = lieu
		s.DateDebut = debut.Format("2006-01-02T15:04:05")
		s.DateFin = fin.Format("2006-01-02T15:04:05")
		s.Animateurs = fetchSeanceAnimateurs(s.IDSeance)
		result = append(result, s)
	}
	return result
}

func fetchSeanceAnimateurs(seanceId int) []models.AnimateurInfo {
	rows, err := database.DB.Query(
		`SELECT u.id_utilisateur, u.nom, u.prenom
		 FROM animateurs_seances a JOIN utilisateurs u ON u.id_utilisateur = a.id_salarie
		 WHERE a.id_seance = ?`, seanceId)
	if err != nil {
		return []models.AnimateurInfo{}
	}
	defer rows.Close()
	result := []models.AnimateurInfo{}
	for rows.Next() {
		var a models.AnimateurInfo
		if rows.Scan(&a.IDUtilisateur, &a.Nom, &a.Prenom) == nil {
			result = append(result, a)
		}
	}
	return result
}

// --- Handlers événements (CRUD) ---

// GetEvenements : liste des événements avec leur nombre d'inscrits.
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

// GetEvenement : détail d'un événement (animateurs + séances).
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
	e.Seances = fetchSeances(e.IDEvenement)
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

// CreateEvenement : crée un événement (en_attente) + séances/animateurs.
func CreateEvenement(w http.ResponseWriter, r *http.Request, adminId int) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	// Les séances (si fournies) déterminent l'enveloppe date/format/lieu.
	format, lieu, debut, fin := req.Format, req.Lieu, req.DateDebut, req.DateFin
	if len(req.Seances) > 0 {
		if d, f, fmt2, l, ok := computeEnvelope(req.Seances); ok {
			debut, fin, format, lieu = d, f, fmt2, l
		}
	}

	res, err := database.DB.Exec(
		`INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')`,
		adminId, req.Titre, req.Description, req.TypeEvenement, format, lieu, debut, fin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix,
	)
	if err != nil {
		jsonErr(w, "impossible de créer l'événement", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	if len(req.Seances) > 0 {
		syncSeances(id, req.Seances)
	} else {
		syncAnimateurs(id, req.Animateurs)
	}
	jsonOK(w, map[string]interface{}{"message": "événement créé", "id": id}, http.StatusCreated)
}

// UpdateEvenement : maj de l'événement + resync séances/animateurs.
func UpdateEvenement(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	var req models.CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	format, lieu, debut, fin := req.Format, req.Lieu, req.DateDebut, req.DateFin
	if len(req.Seances) > 0 {
		if d, f, fmt2, l, ok := computeEnvelope(req.Seances); ok {
			debut, fin, format, lieu = d, f, fmt2, l
		}
	}

	res, err := database.DB.Exec(
		`UPDATE evenements SET titre=?, description=?, type_evenement=?, format=?, lieu=?, date_debut=?, date_fin=?, nb_places_total=?, prix=? WHERE id_evenement=?`,
		req.Titre, req.Description, req.TypeEvenement, format, lieu, debut, fin, req.NbPlacesTotal, req.Prix, id,
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
	if len(req.Seances) > 0 {
		syncSeances(eventId, req.Seances)
	} else {
		syncAnimateurs(eventId, req.Animateurs)
	}
	jsonOK(w, map[string]string{"message": "événement mis à jour"}, http.StatusOK)
}

// --- Inscriptions ---

// GetEvenementInscrits : inscrits d'un événement (nom anonymisé RGPD).
func GetEvenementInscrits(w http.ResponseWriter, r *http.Request, id string) {
	rows, err := database.DB.Query(`
		SELECT u.id_utilisateur, u.prenom, u.nom, u.email, i.statut_paiement, i.date_inscription
		FROM inscriptions_evenements i
		JOIN utilisateurs u ON u.id_utilisateur = i.id_utilisateur
		WHERE i.id_evenement = ?
		ORDER BY i.date_inscription ASC
	`, id)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type Inscrit struct {
		IDUtilisateur  int    `json:"id_utilisateur"`
		Prenom         string `json:"prenom"`
		NomInitiale    string `json:"nom_initiale"`
		Email          string `json:"email"`
		StatutPaiement string `json:"statut_paiement"`
		DateInscription string `json:"date_inscription"`
	}

	inscrits := []Inscrit{}
	for rows.Next() {
		var ins Inscrit
		var nom string
		var dateIns time.Time
		if err := rows.Scan(&ins.IDUtilisateur, &ins.Prenom, &nom, &ins.Email, &ins.StatutPaiement, &dateIns); err == nil {
			if len(nom) > 0 {
				ins.NomInitiale = string([]rune(nom)[:1]) + "."
			}
			ins.DateInscription = dateIns.Format("2006-01-02T15:04:05Z")
			inscrits = append(inscrits, ins)
		}
	}
	jsonOK(w, inscrits, http.StatusOK)
}

// --- Suppression & workflow de validation ---

func DeleteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM evenements WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, "impossible de supprimer l'événement", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "événement supprimé"}, http.StatusOK)
}

// AttenteEvenement : remet un événement en attente.
func AttenteEvenement(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'en_attente' WHERE id_evenement = ?", id)
	if err != nil {
		jsonErr(w, "erreur lors de la mise en attente", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "événement remis en attente"}, http.StatusOK)
}

// ValiderEvenement : valide un événement et mémorise l'admin validateur.
func ValiderEvenement(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'valide', valide_par = ? WHERE id_evenement = ?", adminId, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la validation"})
		return
	}
	// Planning des salariés calculé en direct par GetMonPlanning : rien à insérer ici.
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "événement validé"})
}

// RefuserEvenement : passe un événement en 'refuse'.
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

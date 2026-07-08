package handlers

// Boîte à idées — échange interne salariés/admin (filtre dans le router).
// Titre + contenu + tags optionnels + vote (toggle idempotent).

import (
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
	"time"
)

// Idee : une idée, enrichie du vote de l'utilisateur courant.
type Idee struct {
	IDIdee          int     `json:"id_idee"`
	IDAuteur        int     `json:"id_auteur"`
	AuteurPrenom    string  `json:"auteur_prenom"`
	AuteurNomInit   string  `json:"auteur_nom_initiale"`
	Titre           string  `json:"titre"`
	Contenu         string  `json:"contenu"`
	Tags            *string `json:"tags,omitempty"`
	Statut          string  `json:"statut"`
	ArchivedAt      *string `json:"archived_at"`
	NbVotes         int     `json:"nb_votes"` // score net (upvotes - downvotes)
	DatePublication string  `json:"date_publication"`
	MonVote         int     `json:"mon_vote"` // -1, 0 ou 1 selon le vote de l'utilisateur courant
}

// IdeeRequest : corps JSON de création/modification.
type IdeeRequest struct {
	Titre   string `json:"titre"`
	Contenu string `json:"contenu"`
	Tags    string `json:"tags"`
}

// GetIdeesSalaries liste les idées.
//   - défaut : non archivées, triées par ?tri=populaire|recent.
//   - ?archives=1 : idées archivées.
func GetIdeesSalaries(w http.ResponseWriter, r *http.Request, userId int, role string) {
	archivesMode := r.URL.Query().Get("archives") == "1"
	tri := services.NormaliserTri(r.URL.Query().Get("tri"))

	where := "b.archived_at IS NULL"
	if archivesMode {
		where = "b.archived_at IS NOT NULL"
	}
	args := []interface{}{userId}

	rows, err := database.DB.Query(`
		SELECT b.id_idee, b.id_auteur, u.prenom, u.nom,
		       b.titre, b.contenu, b.tags, b.statut, b.archived_at,
		       b.nb_votes, b.date_publication,
		       COALESCE((SELECT v.valeur FROM votes_idees v WHERE v.id_idee=b.id_idee AND v.id_utilisateur=?), 0) AS mon_vote
		FROM boite_idees b
		JOIN utilisateurs u ON u.id_utilisateur = b.id_auteur
		WHERE `+where, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	// Date parsée conservée pour le tri via le service.
	type ideeAvecDate struct {
		idee Idee
		date time.Time
	}
	collectees := []ideeAvecDate{}
	for rows.Next() {
		var ide Idee
		var nom string
		var tags, archived sql.NullString
		var dt time.Time
		if err := rows.Scan(&ide.IDIdee, &ide.IDAuteur, &ide.AuteurPrenom, &nom,
			&ide.Titre, &ide.Contenu, &tags, &ide.Statut, &archived,
			&ide.NbVotes, &dt, &ide.MonVote); err == nil {
			if tags.Valid {
				ide.Tags = &tags.String
			}
			if archived.Valid {
				ide.ArchivedAt = &archived.String
			}
			if len(nom) > 0 {
				ide.AuteurNomInit = string([]rune(nom)[:1]) + "."
			}
			ide.DatePublication = dt.Format("2006-01-02T15:04:05Z")
			collectees = append(collectees, ideeAvecDate{idee: ide, date: dt})
		}
	}

	// Tri métier délégué au service.
	triables := make([]services.IdeeTriable, len(collectees))
	for i, c := range collectees {
		triables[i] = services.IdeeTriable{ID: c.idee.IDIdee, NbVotes: c.idee.NbVotes, DatePublication: c.date}
	}
	services.TrierIdees(triables, tri)

	parID := make(map[int]Idee, len(collectees))
	for _, c := range collectees {
		parID[c.idee.IDIdee] = c.idee
	}
	out := make([]Idee, 0, len(triables))
	for _, t := range triables {
		out = append(out, parID[t.ID])
	}
	jsonOK(w, out, http.StatusOK)
}

func GetIdee(w http.ResponseWriter, r *http.Request, id string, userId int) {
	var ide Idee
	var nom string
	var tags, archived sql.NullString
	var dt time.Time
	err := database.DB.QueryRow(`
		SELECT b.id_idee, b.id_auteur, u.prenom, u.nom,
		       b.titre, b.contenu, b.tags, b.statut, b.archived_at,
		       b.nb_votes, b.date_publication,
		       COALESCE((SELECT v.valeur FROM votes_idees v WHERE v.id_idee=b.id_idee AND v.id_utilisateur=?), 0) AS mon_vote
		FROM boite_idees b
		JOIN utilisateurs u ON u.id_utilisateur = b.id_auteur
		WHERE b.id_idee = ?`, userId, id).
		Scan(&ide.IDIdee, &ide.IDAuteur, &ide.AuteurPrenom, &nom,
			&ide.Titre, &ide.Contenu, &tags, &ide.Statut, &archived,
			&ide.NbVotes, &dt, &ide.MonVote)
	if err != nil {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}
	if tags.Valid {
		ide.Tags = &tags.String
	}
	if archived.Valid {
		ide.ArchivedAt = &archived.String
	}
	if len(nom) > 0 {
		ide.AuteurNomInit = string([]rune(nom)[:1]) + "."
	}
	ide.DatePublication = dt.Format("2006-01-02T15:04:05Z")
	jsonOK(w, ide, http.StatusOK)
}

func CreateIdee(w http.ResponseWriter, r *http.Request, userId int) {
	var req IdeeRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if strings.TrimSpace(req.Titre) == "" || strings.TrimSpace(req.Contenu) == "" {
		jsonErr(w, "titre et contenu requis", http.StatusBadRequest)
		return
	}
	var tags interface{}
	if strings.TrimSpace(req.Tags) != "" {
		tags = req.Tags
	}
	res, err := database.DB.Exec(`
		INSERT INTO boite_idees (id_auteur, titre, contenu, tags, nb_votes, date_publication)
		VALUES (?, ?, ?, ?, 0, NOW())`,
		userId, req.Titre, req.Contenu, tags)
	if err != nil {
		jsonErr(w, "erreur création", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"id_idee": id, "message": "idée ajoutée"}, http.StatusCreated)
}

// UpdateIdee : modifie une idée (auteur ou admin).
func UpdateIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if !chargerAuteurEtAutoriser(w, id, userId, role) {
		return
	}
	var req IdeeRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if strings.TrimSpace(req.Titre) == "" {
		jsonErr(w, "titre requis", http.StatusBadRequest)
		return
	}
	var tags interface{}
	if strings.TrimSpace(req.Tags) != "" {
		tags = req.Tags
	}
	_, err := database.DB.Exec(`
		UPDATE boite_idees SET titre = ?, contenu = ?, tags = ? WHERE id_idee = ?`,
		req.Titre, req.Contenu, tags, id)
	if err != nil {
		jsonErr(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "idée mise à jour"}, http.StatusOK)
}

// DeleteIdee : supprime une idée (auteur ou admin).
func DeleteIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if !chargerAuteurEtAutoriser(w, id, userId, role) {
		return
	}
	if _, err := database.DB.Exec("DELETE FROM boite_idees WHERE id_idee = ?", id); err != nil {
		jsonErr(w, "erreur suppression", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "idée supprimée"}, http.StatusOK)
}

// chargerAuteurEtAutoriser vérifie que l'appelant peut gérer l'idée (auteur ou admin).
// Écrit l'erreur et renvoie false si refusé ou idée absente.
func chargerAuteurEtAutoriser(w http.ResponseWriter, id string, userId int, role string) bool {
	var auteur int
	if err := database.DB.QueryRow("SELECT id_auteur FROM boite_idees WHERE id_idee = ?", id).Scan(&auteur); err != nil {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return false
	}
	if !services.PeutGererIdee(role, auteur == userId) {
		jsonErr(w, "non autorisé", http.StatusForbidden)
		return false
	}
	return true
}

// ChangeStatutIdee : fait évoluer le statut d'une idée (auteur ou admin).
func ChangeStatutIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if !chargerAuteurEtAutoriser(w, id, userId, role) {
		return
	}
	var req struct {
		Statut string `json:"statut"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if !services.IsStatutIdeeValide(req.Statut) {
		jsonErr(w, "statut invalide", http.StatusBadRequest)
		return
	}
	if _, err := database.DB.Exec("UPDATE boite_idees SET statut = ? WHERE id_idee = ?", req.Statut, id); err != nil {
		jsonErr(w, "erreur mise à jour du statut", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "statut mis à jour", "statut": req.Statut}, http.StatusOK)
}

// ArchiverIdee : archive une idée (auteur ou admin), non destructif.
func ArchiverIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if !chargerAuteurEtAutoriser(w, id, userId, role) {
		return
	}
	if _, err := database.DB.Exec("UPDATE boite_idees SET archived_at = NOW() WHERE id_idee = ?", id); err != nil {
		jsonErr(w, "erreur archivage", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "idée archivée"}, http.StatusOK)
}

// DesarchiverIdee : remet une idée dans le flux principal (auteur ou admin).
func DesarchiverIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if !chargerAuteurEtAutoriser(w, id, userId, role) {
		return
	}
	if _, err := database.DB.Exec("UPDATE boite_idees SET archived_at = NULL WHERE id_idee = ?", id); err != nil {
		jsonErr(w, "erreur désarchivage", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "idée désarchivée"}, http.StatusOK)
}

// VoterIdee : vote up/down idempotent. Corps {"valeur": 1|-1} (défaut 1).
//   - pas de vote  → insère ; score += valeur
//   - même sens    → retire ; score -= valeur
//   - sens opposé  → bascule ; score += 2 × valeur
// nb_votes stocke le score net (peut être négatif).
func VoterIdee(w http.ResponseWriter, r *http.Request, id string, userId int) {
	var exists bool
	if err := database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM boite_idees WHERE id_idee = ?)", id).Scan(&exists); err != nil || !exists {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}

	var body struct {
		Valeur int `json:"valeur"`
	}
	_ = json.NewDecoder(r.Body).Decode(&body)
	dir := 1
	if body.Valeur == -1 {
		dir = -1
	}

	var current int
	hasVote := database.DB.QueryRow(
		"SELECT valeur FROM votes_idees WHERE id_idee = ? AND id_utilisateur = ?", id, userId).Scan(&current) == nil

	var delta, monVote int
	switch {
	case !hasVote:
		if _, err := database.DB.Exec(
			"INSERT INTO votes_idees (id_idee, id_utilisateur, valeur) VALUES (?, ?, ?)", id, userId, dir); err != nil {
			jsonErr(w, "erreur vote", http.StatusInternalServerError)
			return
		}
		delta, monVote = dir, dir
	case current == dir:
		// Même sens → annulation.
		database.DB.Exec("DELETE FROM votes_idees WHERE id_idee = ? AND id_utilisateur = ?", id, userId)
		delta, monVote = -dir, 0
	default:
		// Bascule up↔down.
		database.DB.Exec("UPDATE votes_idees SET valeur = ? WHERE id_idee = ? AND id_utilisateur = ?", dir, id, userId)
		delta, monVote = 2*dir, dir
	}

	database.DB.Exec("UPDATE boite_idees SET nb_votes = nb_votes + ? WHERE id_idee = ?", delta, id)

	var score int
	database.DB.QueryRow("SELECT nb_votes FROM boite_idees WHERE id_idee = ?", id).Scan(&score)
	jsonOK(w, map[string]interface{}{"score": score, "mon_vote": monVote}, http.StatusOK)
}

package handlers

// Boîte à idées — espace d'échange interne entre salariés.
// Visible uniquement par les rôles "salarie" et "admin" (filtre dans le router).
// Format libre : titre + contenu + tags optionnels + système de vote (toggle idempotent).

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
	"time"
)

type Idee struct {
	IDIdee          int     `json:"id_idee"`
	IDAuteur        int     `json:"id_auteur"`
	AuteurPrenom    string  `json:"auteur_prenom"`
	AuteurNomInit   string  `json:"auteur_nom_initiale"`
	Titre           string  `json:"titre"`
	Contenu         string  `json:"contenu"`
	Tags            *string `json:"tags,omitempty"`
	NbVotes         int     `json:"nb_votes"`
	DatePublication string  `json:"date_publication"`
	AVote           bool    `json:"a_vote"`
}

type IdeeRequest struct {
	Titre   string `json:"titre"`
	Contenu string `json:"contenu"`
	Tags    string `json:"tags"`
}

// GetIdeesSalaries liste toutes les idées (ordre anti-chronologique).
func GetIdeesSalaries(w http.ResponseWriter, r *http.Request, userId int) {
	rows, err := database.DB.Query(`
		SELECT b.id_idee, b.id_auteur, u.prenom, u.nom,
		       b.titre, b.contenu, b.tags, b.nb_votes, b.date_publication,
		       EXISTS(SELECT 1 FROM votes_idees v WHERE v.id_idee=b.id_idee AND v.id_utilisateur=?) AS a_vote
		FROM boite_idees b
		JOIN utilisateurs u ON u.id_utilisateur = b.id_auteur
		ORDER BY b.date_publication DESC`, userId)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []Idee{}
	for rows.Next() {
		var ide Idee
		var nom string
		var tags sql.NullString
		var dt time.Time
		if err := rows.Scan(&ide.IDIdee, &ide.IDAuteur, &ide.AuteurPrenom, &nom,
			&ide.Titre, &ide.Contenu, &tags, &ide.NbVotes, &dt, &ide.AVote); err == nil {
			if tags.Valid {
				ide.Tags = &tags.String
			}
			if len(nom) > 0 {
				ide.AuteurNomInit = string([]rune(nom)[:1]) + "."
			}
			ide.DatePublication = dt.Format("2006-01-02T15:04:05Z")
			out = append(out, ide)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// GetIdee retourne le détail d'une idée.
func GetIdee(w http.ResponseWriter, r *http.Request, id string, userId int) {
	var ide Idee
	var nom string
	var tags sql.NullString
	var dt time.Time
	err := database.DB.QueryRow(`
		SELECT b.id_idee, b.id_auteur, u.prenom, u.nom,
		       b.titre, b.contenu, b.tags, b.nb_votes, b.date_publication,
		       EXISTS(SELECT 1 FROM votes_idees v WHERE v.id_idee=b.id_idee AND v.id_utilisateur=?) AS a_vote
		FROM boite_idees b
		JOIN utilisateurs u ON u.id_utilisateur = b.id_auteur
		WHERE b.id_idee = ?`, userId, id).
		Scan(&ide.IDIdee, &ide.IDAuteur, &ide.AuteurPrenom, &nom,
			&ide.Titre, &ide.Contenu, &tags, &ide.NbVotes, &dt, &ide.AVote)
	if err != nil {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}
	if tags.Valid {
		ide.Tags = &tags.String
	}
	if len(nom) > 0 {
		ide.AuteurNomInit = string([]rune(nom)[:1]) + "."
	}
	ide.DatePublication = dt.Format("2006-01-02T15:04:05Z")
	jsonOK(w, ide, http.StatusOK)
}

// CreateIdee crée une nouvelle idée dans la boîte à idées.
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

// UpdateIdee modifie une idée (auteur uniquement, ou admin).
func UpdateIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	var auteur int
	if err := database.DB.QueryRow("SELECT id_auteur FROM boite_idees WHERE id_idee = ?", id).Scan(&auteur); err != nil {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}
	if auteur != userId && role != "admin" {
		jsonErr(w, "non autorisé", http.StatusForbidden)
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

// DeleteIdee supprime une idée (auteur ou admin).
func DeleteIdee(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	var auteur int
	if err := database.DB.QueryRow("SELECT id_auteur FROM boite_idees WHERE id_idee = ?", id).Scan(&auteur); err != nil {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}
	if auteur != userId && role != "admin" {
		jsonErr(w, "non autorisé", http.StatusForbidden)
		return
	}
	if _, err := database.DB.Exec("DELETE FROM boite_idees WHERE id_idee = ?", id); err != nil {
		jsonErr(w, "erreur suppression", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "idée supprimée"}, http.StatusOK)
}

// VoterIdee toggle le vote d'un utilisateur sur une idée (vote / dévote idempotent).
func VoterIdee(w http.ResponseWriter, r *http.Request, id string, userId int) {
	// Vérifier que l'idée existe
	var exists bool
	if err := database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM boite_idees WHERE id_idee = ?)", id).Scan(&exists); err != nil || !exists {
		jsonErr(w, "idée introuvable", http.StatusNotFound)
		return
	}

	// Toggle : supprimer si déjà voté, insérer sinon
	res, err := database.DB.Exec(
		"DELETE FROM votes_idees WHERE id_idee = ? AND id_utilisateur = ?", id, userId)
	if err != nil {
		jsonErr(w, "erreur vote", http.StatusInternalServerError)
		return
	}
	affected, _ := res.RowsAffected()

	if affected > 0 {
		// Vote retiré → décrémenter
		database.DB.Exec("UPDATE boite_idees SET nb_votes = GREATEST(nb_votes - 1, 0) WHERE id_idee = ?", id)
		jsonOK(w, map[string]interface{}{"message": "vote retiré", "a_vote": false}, http.StatusOK)
	} else {
		// Nouveau vote
		if _, err := database.DB.Exec(
			"INSERT INTO votes_idees (id_idee, id_utilisateur) VALUES (?, ?)", id, userId); err != nil {
			jsonErr(w, "erreur vote", http.StatusInternalServerError)
			return
		}
		database.DB.Exec("UPDATE boite_idees SET nb_votes = nb_votes + 1 WHERE id_idee = ?", id)
		jsonOK(w, map[string]interface{}{"message": "vote enregistré", "a_vote": true}, http.StatusOK)
	}
}

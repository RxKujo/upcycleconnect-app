package handlers

// tutoriel.go — tutoriel first-login : étapes (filtrées par rôle), statut
// utilisateur (terminé/passé), CRUD admin.

import (
	"api/internal/middleware"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"time"
)

type TutorielEtape struct {
	IDEtape      int    `json:"id_etape"`
	Titre        string `json:"titre"`
	Contenu      string `json:"contenu"`
	Ordre        int    `json:"ordre"`
	CibleElement string `json:"cible_element"`
	Position     string `json:"position"`
	Page         string `json:"page"`
	Icone        string `json:"icone"`
	Role         string `json:"role"`
}

type TutorielStatut struct {
	TutoVu    bool    `json:"tuto_vu"`
	Termine   bool    `json:"termine"`
	Passe     bool    `json:"passe"`
	DateDebut *string `json:"date_debut"`
}

// GetTutorielEtapes — étapes actives. Auth optionnelle : si authentifié, communes
// + celles du rôle ; sinon communes seules (role NULL).
func GetTutorielEtapes(w http.ResponseWriter, r *http.Request) {
	_, role, authed := middleware.OptionalAuth(r)

	query := `
		SELECT id_etape, titre, contenu, ordre, COALESCE(cible_element,''), position, COALESCE(page,''), COALESCE(icone,''), COALESCE(role,'')
		FROM tutoriel_etapes
		WHERE est_actif = 1 AND (role IS NULL OR role = ?)
		ORDER BY ordre ASC`
	args := []interface{}{role}
	if !authed {
		query = `
			SELECT id_etape, titre, contenu, ordre, COALESCE(cible_element,''), position, COALESCE(page,''), COALESCE(icone,''), COALESCE(role,'')
			FROM tutoriel_etapes
			WHERE est_actif = 1 AND role IS NULL
			ORDER BY ordre ASC`
		args = nil
	}

	rows, err := database.DB.Query(query, args...)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	etapes := []TutorielEtape{}
	for rows.Next() {
		var e TutorielEtape
		if err := rows.Scan(&e.IDEtape, &e.Titre, &e.Contenu, &e.Ordre, &e.CibleElement, &e.Position, &e.Page, &e.Icone, &e.Role); err == nil {
			etapes = append(etapes, e)
		}
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(etapes)
}

// GetTutorielStatut — état du tutoriel (vu, terminé, passé).
func GetTutorielStatut(w http.ResponseWriter, r *http.Request, userId int) {
	var tutoVu bool
	database.DB.QueryRow("SELECT tuto_vu FROM utilisateurs WHERE id_utilisateur = ?", userId).Scan(&tutoVu)

	var statut TutorielStatut
	statut.TutoVu = tutoVu

	var termine, passe bool
	var dateDebut sql.NullString
	err := database.DB.QueryRow(`
		SELECT termine, passe, date_debut FROM utilisateurs_tutoriels WHERE id_utilisateur = ?`, userId).
		Scan(&termine, &passe, &dateDebut)
	if err == nil {
		statut.Termine = termine
		statut.Passe = passe
		if dateDebut.Valid {
			statut.DateDebut = &dateDebut.String
		}
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(statut)
}

// MarquerTutorielTermine — marque le tutoriel terminé.
func MarquerTutorielTermine(w http.ResponseWriter, r *http.Request, userId int) {
	now := time.Now().Format("2006-01-02 15:04:05")
	database.DB.Exec(`
		INSERT INTO utilisateurs_tutoriels (id_utilisateur, date_debut, date_fin, termine, passe)
		VALUES (?, ?, ?, 1, 0)
		ON DUPLICATE KEY UPDATE date_fin = ?, termine = 1`,
		userId, now, now, now)
	database.DB.Exec("UPDATE utilisateurs SET tuto_vu = 1 WHERE id_utilisateur = ?", userId)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "tutoriel terminé"})
}

// PasserTutoriel — marque le tutoriel passé (ignoré).
func PasserTutoriel(w http.ResponseWriter, r *http.Request, userId int) {
	now := time.Now().Format("2006-01-02 15:04:05")
	database.DB.Exec(`
		INSERT INTO utilisateurs_tutoriels (id_utilisateur, date_debut, date_fin, termine, passe)
		VALUES (?, ?, ?, 0, 1)
		ON DUPLICATE KEY UPDATE date_fin = ?, passe = 1`,
		userId, now, now, now)
	database.DB.Exec("UPDATE utilisateurs SET tuto_vu = 1 WHERE id_utilisateur = ?", userId)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "tutoriel passé"})
}

// Admin CRUD tutoriel

// AdminGetTutorielEtapes — toutes les étapes (actives ou non).
func AdminGetTutorielEtapes(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT id_etape, titre, contenu, ordre, COALESCE(cible_element,''), position, COALESCE(page,''), COALESCE(icone,''), COALESCE(role,''), est_actif
		FROM tutoriel_etapes ORDER BY ordre ASC`)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type EtapeAdmin struct {
		TutorielEtape
		EstActif bool `json:"est_actif"`
	}
	etapes := []EtapeAdmin{}
	for rows.Next() {
		var e EtapeAdmin
		if err := rows.Scan(&e.IDEtape, &e.Titre, &e.Contenu, &e.Ordre, &e.CibleElement, &e.Position, &e.Page, &e.Icone, &e.Role, &e.EstActif); err == nil {
			etapes = append(etapes, e)
		}
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(etapes)
}

// AdminUpdateTutorielEtape met à jour une étape (rôle/page vides -> NULL).
func AdminUpdateTutorielEtape(w http.ResponseWriter, r *http.Request, idStr string) {
	var body struct {
		Titre        string `json:"titre"`
		Contenu      string `json:"contenu"`
		Ordre        int    `json:"ordre"`
		CibleElement string `json:"cible_element"`
		Position     string `json:"position"`
		Page         string `json:"page"`
		Icone        string `json:"icone"`
		Role         string `json:"role"`
		EstActif     bool   `json:"est_actif"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		jsonError(w, "données invalides", http.StatusBadRequest)
		return
	}
	// role vide -> NULL (étape commune à tous les rôles).
	var role interface{}
	if body.Role != "" {
		role = body.Role
	}
	// page vide -> NULL (affichée sur la page courante, centrée).
	var page interface{}
	if body.Page != "" {
		page = body.Page
	}
	_, err := database.DB.Exec(`
		UPDATE tutoriel_etapes SET titre=?, contenu=?, ordre=?, cible_element=?, position=?, page=?, icone=?, role=?, est_actif=?
		WHERE id_etape=?`,
		body.Titre, body.Contenu, body.Ordre, body.CibleElement, body.Position, page, body.Icone, role, body.EstActif, idStr)
	if err != nil {
		jsonError(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "étape mise à jour"})
}

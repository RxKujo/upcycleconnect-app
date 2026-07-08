package handlers

// Admin multilingue : gestion no-code des langues et traductions (sans redéploiement). Flag RTL (WCAG 2.1).

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
)

// ─── Langues ─────────────────────────────────────────────────────────────────

// Langue représente une langue de l'interface (code ISO, activation, sens RTL).
type Langue struct {
	IDLangue  int    `json:"id_langue"`
	CodeISO   string `json:"code_iso"`
	Libelle   string `json:"libelle"`
	EstActive bool   `json:"est_active"`
	RTL       bool   `json:"rtl"`
}

// GetLangues liste toutes les langues, triées par libellé.
func GetLangues(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT id_langue, code_iso, libelle, est_active, COALESCE(rtl, 0)
		FROM langue ORDER BY libelle ASC`)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []Langue{}
	for rows.Next() {
		var l Langue
		if err := rows.Scan(&l.IDLangue, &l.CodeISO, &l.Libelle, &l.EstActive, &l.RTL); err == nil {
			out = append(out, l)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// CreateLangue ajoute une langue (active par défaut) après normalisation du code ISO.
func CreateLangue(w http.ResponseWriter, r *http.Request) {
	var req struct {
		CodeISO string `json:"code_iso"`
		Libelle string `json:"libelle"`
		RTL     bool   `json:"rtl"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	code := strings.ToLower(strings.TrimSpace(req.CodeISO))
	libelle := strings.TrimSpace(req.Libelle)
	if len(code) < 2 || libelle == "" {
		jsonErr(w, "code_iso (2+ chars) et libelle requis", http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(`
		INSERT INTO langue (code_iso, libelle, est_active, rtl) VALUES (?, ?, 1, ?)`,
		code, libelle, req.RTL)
	if err != nil {
		jsonErr(w, "langue déjà existante ou erreur serveur", http.StatusConflict)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"id_langue": id, "message": "langue créée"}, http.StatusCreated)
}

// UpdateLangue met à jour une langue en ne modifiant que les champs fournis.
func UpdateLangue(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Libelle   string `json:"libelle"`
		EstActive *bool  `json:"est_active"`
		RTL       *bool  `json:"rtl"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	// Lecture de l'état actuel : ne modifier que les champs fournis.
	var cur Langue
	if err := database.DB.QueryRow(
		"SELECT id_langue, code_iso, libelle, est_active, COALESCE(rtl,0) FROM langue WHERE id_langue = ?", id,
	).Scan(&cur.IDLangue, &cur.CodeISO, &cur.Libelle, &cur.EstActive, &cur.RTL); err != nil {
		jsonErr(w, "langue introuvable", http.StatusNotFound)
		return
	}
	if strings.TrimSpace(req.Libelle) != "" {
		cur.Libelle = strings.TrimSpace(req.Libelle)
	}
	if req.EstActive != nil {
		cur.EstActive = *req.EstActive
	}
	if req.RTL != nil {
		cur.RTL = *req.RTL
	}

	_, err := database.DB.Exec(`
		UPDATE langue SET libelle = ?, est_active = ?, rtl = ? WHERE id_langue = ?`,
		cur.Libelle, cur.EstActive, cur.RTL, id)
	if err != nil {
		jsonErr(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "langue mise à jour"}, http.StatusOK)
}

// DeleteLangue supprime une langue (échoue si des traductions y sont liées).
func DeleteLangue(w http.ResponseWriter, r *http.Request, id string) {
	res, err := database.DB.Exec("DELETE FROM langue WHERE id_langue = ?", id)
	if err != nil {
		jsonErr(w, "impossible de supprimer (traductions liées ?)", http.StatusConflict)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "langue introuvable", http.StatusNotFound)
		return
	}
	jsonOK(w, map[string]string{"message": "langue supprimée"}, http.StatusOK)
}

// ─── Traductions ─────────────────────────────────────────────────────────────

// Translation représente une valeur traduite pour un couple (clé, langue).
type Translation struct {
	IDTranslation int    `json:"id_translation"`
	Cle           string `json:"cle"`
	IDLangue      int    `json:"id_langue"`
	CodeISO       string `json:"code_iso"`
	Valeur        string `json:"valeur"`
}

// GetTranslations liste les traductions, filtrables par clé (LIKE) et par langue.
func GetTranslations(w http.ResponseWriter, r *http.Request) {
	cle := r.URL.Query().Get("cle")
	lang := r.URL.Query().Get("langue")

	query := `
		SELECT t.id_translation, t.cle, t.id_langue, l.code_iso, t.valeur
		FROM translations t
		JOIN langue l ON l.id_langue = t.id_langue
		WHERE 1=1`
	args := []interface{}{}
	if cle != "" {
		query += " AND t.cle LIKE ?"
		args = append(args, "%"+cle+"%")
	}
	if lang != "" {
		query += " AND l.code_iso = ?"
		args = append(args, lang)
	}
	query += " ORDER BY t.cle ASC, l.code_iso ASC"

	rows, err := database.DB.Query(query, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []Translation{}
	for rows.Next() {
		var t Translation
		if err := rows.Scan(&t.IDTranslation, &t.Cle, &t.IDLangue, &t.CodeISO, &t.Valeur); err == nil {
			out = append(out, t)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// UpsertTranslation crée ou met à jour une traduction (INSERT … ON DUPLICATE KEY UPDATE).
func UpsertTranslation(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Cle      string `json:"cle"`
		IDLangue int    `json:"id_langue"`
		Valeur   string `json:"valeur"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	cle := strings.TrimSpace(req.Cle)
	valeur := strings.TrimSpace(req.Valeur)
	if cle == "" || req.IDLangue == 0 || valeur == "" {
		jsonErr(w, "cle, id_langue et valeur requis", http.StatusBadRequest)
		return
	}

	_, err := database.DB.Exec(`
		INSERT INTO translations (cle, id_langue, valeur)
		VALUES (?, ?, ?)
		ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)`,
		cle, req.IDLangue, valeur)
	if err != nil {
		jsonErr(w, "erreur upsert traduction : "+err.Error(), http.StatusInternalServerError)
		return
	}

	var idTrad int
	database.DB.QueryRow(
		"SELECT id_translation FROM translations WHERE cle = ? AND id_langue = ?",
		cle, req.IDLangue).Scan(&idTrad)

	jsonOK(w, map[string]interface{}{"id_translation": idTrad, "message": "traduction enregistrée"}, http.StatusOK)
}

// BulkUpsertTranslations : grille entière en une fois. Valeur non vide => upsert, vide => suppression.
// Idempotent (UNIQUE cle+id_langue).
func BulkUpsertTranslations(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Items []struct {
			Cle      string `json:"cle"`
			IDLangue int    `json:"id_langue"`
			Valeur   string `json:"valeur"`
		} `json:"items"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	var nbUpsert, nbDelete int
	for _, it := range req.Items {
		cle := strings.TrimSpace(it.Cle)
		valeur := strings.TrimSpace(it.Valeur)
		if cle == "" || it.IDLangue == 0 {
			continue
		}
		if valeur == "" {
			res, _ := tx.Exec(`DELETE FROM translations WHERE cle = ? AND id_langue = ?`, cle, it.IDLangue)
			if n, _ := res.RowsAffected(); n > 0 {
				nbDelete++
			}
			continue
		}
		if _, err := tx.Exec(`
			INSERT INTO translations (cle, id_langue, valeur)
			VALUES (?, ?, ?)
			ON DUPLICATE KEY UPDATE valeur = VALUES(valeur)`, cle, it.IDLangue, valeur); err != nil {
			jsonErr(w, "erreur enregistrement : "+err.Error(), http.StatusInternalServerError)
			return
		}
		nbUpsert++
	}

	if err := tx.Commit(); err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]interface{}{"message": "traductions enregistrées", "upsert": nbUpsert, "suppr": nbDelete}, http.StatusOK)
}

// DeleteTranslation supprime une traduction par son identifiant.
func DeleteTranslation(w http.ResponseWriter, r *http.Request, id string) {
	res, err := database.DB.Exec("DELETE FROM translations WHERE id_translation = ?", id)
	if err != nil {
		jsonErr(w, "erreur suppression", http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "traduction introuvable", http.StatusNotFound)
		return
	}
	jsonOK(w, map[string]string{"message": "traduction supprimée"}, http.StatusOK)
}

// GetTranslationsByISO : map cle→valeur d'une langue. Endpoint public (libellés UI du frontend).
func GetTranslationsByISO(w http.ResponseWriter, r *http.Request, codeISO string) {
	rows, err := database.DB.Query(`
		SELECT t.cle, t.valeur
		FROM translations t
		JOIN langue l ON l.id_langue = t.id_langue
		WHERE l.code_iso = ? AND l.est_active = 1
		ORDER BY t.cle ASC`, codeISO)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := map[string]string{}
	for rows.Next() {
		var cle, valeur sql.NullString
		if rows.Scan(&cle, &valeur) == nil && cle.Valid {
			out[cle.String] = valeur.String
		}
	}
	jsonOK(w, out, http.StatusOK)
}

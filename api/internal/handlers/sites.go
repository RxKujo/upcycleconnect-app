package handlers

// sites.go — sites UpcycleConnect (site_uc) : listes + CRUD admin.

import (
	"api/internal/models"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
)

// scanSite lit une ligne site_uc (sans compteurs).
func scanSite(rows *sql.Rows) (models.SiteUC, error) {
	var s models.SiteUC
	var adresse, ville, cp sql.NullString
	if err := rows.Scan(&s.IDSite, &s.NomSite, &adresse, &ville, &cp); err != nil {
		return s, err
	}
	if adresse.Valid {
		s.Adresse = &adresse.String
	}
	if ville.Valid {
		s.Ville = &ville.String
	}
	if cp.Valid {
		s.CodePostal = &cp.String
	}
	return s, nil
}

// GetSites — liste simple des sites (sélecteurs). Tout utilisateur authentifié.
func GetSites(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(
		"SELECT id_site, nom_site, adresse, ville, code_postal FROM site_uc ORDER BY nom_site")
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	list := []models.SiteUC{}
	for rows.Next() {
		if s, err := scanSite(rows); err == nil {
			list = append(list, s)
		}
	}
	jsonOK(w, list, http.StatusOK)
}

// GetSitesAdmin — sites avec compteurs (salariés, matériels).
func GetSitesAdmin(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT s.id_site, s.nom_site, s.adresse, s.ville, s.code_postal,
		       (SELECT COUNT(*) FROM utilisateurs u WHERE u.id_site_uc = s.id_site) AS nb_salaries,
		       (SELECT COUNT(*) FROM materiels m WHERE m.id_site = s.id_site)       AS nb_materiels
		FROM site_uc s ORDER BY s.nom_site`)
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	list := []models.SiteUC{}
	for rows.Next() {
		var s models.SiteUC
		var adresse, ville, cp sql.NullString
		if err := rows.Scan(&s.IDSite, &s.NomSite, &adresse, &ville, &cp, &s.NbSalaries, &s.NbMateriels); err != nil {
			continue
		}
		if adresse.Valid {
			s.Adresse = &adresse.String
		}
		if ville.Valid {
			s.Ville = &ville.String
		}
		if cp.Valid {
			s.CodePostal = &cp.String
		}
		list = append(list, s)
	}
	jsonOK(w, list, http.StatusOK)
}

type siteRequest struct {
	NomSite    string  `json:"nom_site"`
	Adresse    *string `json:"adresse"`
	Ville      *string `json:"ville"`
	CodePostal *string `json:"code_postal"`
}

func (req *siteRequest) valide() (string, bool) {
	req.NomSite = strings.TrimSpace(req.NomSite)
	if len(req.NomSite) < 2 || len(req.NomSite) > 200 {
		return "le nom du site doit contenir entre 2 et 200 caractères", false
	}
	return "", true
}

func CreateSite(w http.ResponseWriter, r *http.Request) {
	var req siteRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	if msg, ok := req.valide(); !ok {
		jsonErr(w, msg, http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(
		"INSERT INTO site_uc (nom_site, adresse, ville, code_postal) VALUES (?, ?, ?, ?)",
		req.NomSite, req.Adresse, req.Ville, req.CodePostal)
	if err != nil {
		jsonErr(w, "impossible de créer le site", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "site créé", "id": id}, http.StatusCreated)
}

func UpdateSite(w http.ResponseWriter, r *http.Request, id string) {
	var req siteRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	if msg, ok := req.valide(); !ok {
		jsonErr(w, msg, http.StatusBadRequest)
		return
	}
	if _, err := database.DB.Exec(
		"UPDATE site_uc SET nom_site = ?, adresse = ?, ville = ?, code_postal = ? WHERE id_site = ?",
		req.NomSite, req.Adresse, req.Ville, req.CodePostal, id); err != nil {
		jsonErr(w, "impossible de modifier le site", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "site modifié"}, http.StatusOK)
}

// DeleteSite supprime un site ; salariés et matériel rattachés sont détachés
// (id_site → NULL) plutôt que perdus.
func DeleteSite(w http.ResponseWriter, r *http.Request, id string) {
	database.DB.Exec("UPDATE utilisateurs SET id_site_uc = NULL WHERE id_site_uc = ?", id) //nolint:errcheck
	database.DB.Exec("UPDATE materiels SET id_site = NULL WHERE id_site = ?", id)           //nolint:errcheck
	if _, err := database.DB.Exec("DELETE FROM site_uc WHERE id_site = ?", id); err != nil {
		jsonErr(w, "impossible de supprimer le site", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "site supprimé"}, http.StatusOK)
}

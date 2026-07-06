package handlers

import (
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
)

// AbonnementInput = payload admin pour créer / modifier un plan d'abonnement.
type AbonnementInput struct {
	Nom               string   `json:"nom"`
	TypeCible         string   `json:"type_cible"`
	PrixMensuel       float64  `json:"prix_mensuel"`
	PrixAnnuel        *float64 `json:"prix_annuel"`
	Description       string   `json:"description"`
	Couleur           string   `json:"couleur"`
	NbAlertesMax      *int     `json:"nb_alertes_max"`
	RayonAlerteMaxKm  *int     `json:"rayon_alerte_max_km"`
	DashboardMensuel  bool     `json:"dashboard_mensuel"`
	DashboardAnnuel   bool     `json:"dashboard_annuel"`
	ExportPDF         bool     `json:"export_pdf"`
	AlertesActives    bool     `json:"alertes_actives"`
	AlertesPush       bool     `json:"alertes_push"`
	BadgesActives     bool     `json:"badges_actives"`
	PublicitesActives bool     `json:"publicites_actives"`
}

// validateAbonnement normalise et valide l'entrée. Retourne un message non vide si invalide.
func validateAbonnement(in *AbonnementInput) string {
	in.Nom = strings.TrimSpace(in.Nom)
	in.Couleur = strings.TrimSpace(in.Couleur)
	if in.Nom == "" {
		return "le nom est obligatoire"
	}
	if in.TypeCible != "particulier" && in.TypeCible != "professionnel" {
		return "type cible invalide"
	}
	if in.PrixMensuel < 0 {
		return "le prix mensuel doit être positif"
	}
	if in.PrixAnnuel != nil && *in.PrixAnnuel < 0 {
		return "le prix annuel doit être positif"
	}
	if in.Couleur == "" {
		in.Couleur = "#244F26"
	}
	return ""
}

func descOrNil(s string) interface{} {
	if strings.TrimSpace(s) == "" {
		return nil
	}
	return s
}

func CreateAbonnement(w http.ResponseWriter, r *http.Request) {
	var in AbonnementInput
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if msg := validateAbonnement(&in); msg != "" {
		jsonErr(w, msg, http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(`
		INSERT INTO abonnements (nom, type_cible, prix_mensuel, prix_annuel, description, couleur,
			nb_alertes_max, rayon_alerte_max_km, dashboard_mensuel, dashboard_annuel, export_pdf,
			alertes_actives, alertes_push, badges_actives, publicites_actives)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		in.Nom, in.TypeCible, in.PrixMensuel, in.PrixAnnuel, descOrNil(in.Description), in.Couleur,
		in.NbAlertesMax, in.RayonAlerteMaxKm, in.DashboardMensuel, in.DashboardAnnuel, in.ExportPDF,
		in.AlertesActives, in.AlertesPush, in.BadgesActives, in.PublicitesActives)
	if err != nil {
		jsonErr(w, "création impossible: "+err.Error(), http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"id_abonnement": id, "message": "plan créé"}, http.StatusCreated)
}

func UpdateAbonnement(w http.ResponseWriter, r *http.Request, idStr string) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonErr(w, "id invalide", http.StatusBadRequest)
		return
	}
	var in AbonnementInput
	if err := json.NewDecoder(r.Body).Decode(&in); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if msg := validateAbonnement(&in); msg != "" {
		jsonErr(w, msg, http.StatusBadRequest)
		return
	}
	res, err := database.DB.Exec(`
		UPDATE abonnements SET nom=?, type_cible=?, prix_mensuel=?, prix_annuel=?, description=?, couleur=?,
			nb_alertes_max=?, rayon_alerte_max_km=?, dashboard_mensuel=?, dashboard_annuel=?, export_pdf=?,
			alertes_actives=?, alertes_push=?, badges_actives=?, publicites_actives=?
		WHERE id_abonnement=?`,
		in.Nom, in.TypeCible, in.PrixMensuel, in.PrixAnnuel, descOrNil(in.Description), in.Couleur,
		in.NbAlertesMax, in.RayonAlerteMaxKm, in.DashboardMensuel, in.DashboardAnnuel, in.ExportPDF,
		in.AlertesActives, in.AlertesPush, in.BadgesActives, in.PublicitesActives, id)
	if err != nil {
		jsonErr(w, "mise à jour impossible: "+err.Error(), http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		// n==0 peut aussi signifier "valeurs identiques" ; on vérifie l'existence.
		var exists bool
		database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM abonnements WHERE id_abonnement = ?)", id).Scan(&exists)
		if !exists {
			jsonErr(w, "plan introuvable", http.StatusNotFound)
			return
		}
	}
	jsonOK(w, map[string]string{"message": "plan mis à jour"}, http.StatusOK)
}

func DeleteAbonnement(w http.ResponseWriter, r *http.Request, idStr string) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonErr(w, "id invalide", http.StatusBadRequest)
		return
	}
	// Refus si des souscriptions (actives ou passées) référencent ce plan (FK).
	var nb int
	database.DB.QueryRow("SELECT COUNT(*) FROM souscriptions WHERE id_abonnement = ?", id).Scan(&nb)
	if nb > 0 {
		jsonErr(w, "impossible de supprimer : des souscriptions sont rattachées à ce plan", http.StatusConflict)
		return
	}
	res, err := database.DB.Exec("DELETE FROM abonnements WHERE id_abonnement = ?", id)
	if err != nil {
		jsonErr(w, "suppression impossible: "+err.Error(), http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "plan introuvable", http.StatusNotFound)
		return
	}
	jsonOK(w, map[string]string{"message": "plan supprimé"}, http.StatusOK)
}

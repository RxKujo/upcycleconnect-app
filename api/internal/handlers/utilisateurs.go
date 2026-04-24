package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"fmt"
	"net/http"
	"time"
)

func GetMe(w http.ResponseWriter, r *http.Request, id int) {
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouvé"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

func UpdateMe(w http.ResponseWriter, r *http.Request, id int) {
	var req struct {
		Telephone *string `json:"telephone"`
		Ville     *string `json:"ville"`
	}

	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET telephone = COALESCE(?, telephone), ville = COALESCE(?, ville) WHERE id_utilisateur = ?`
	_, err = database.DB.Exec(query, req.Telephone, req.Ville, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la mise à jour"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "profil mis à jour avec succès"})
}

func GetAllUtilisateurs(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_utilisateur, nom, prenom, email, role, est_banni FROM utilisateurs")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var utilisateurs []map[string]interface{}
	for rows.Next() {
		var id int
		var nom, prenom, email, role string
		var estBanni bool
		if err := rows.Scan(&id, &nom, &prenom, &email, &role, &estBanni); err == nil {
			utilisateurs = append(utilisateurs, map[string]interface{}{
				"id_utilisateur": id, "nom": nom, "prenom": prenom, "email": email, "role": role, "est_banni": estBanni,
			})
		}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(utilisateurs)
}

func GetUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouvé"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

func BanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		DateFinBan string `json:"date_fin_ban"`
	}
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET est_banni = true, date_fin_ban = ? WHERE id_utilisateur = ?`
	_, err = database.DB.Exec(query, req.DateFinBan, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de bannir l'utilisateur"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur banni"})
}

func UnbanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	query := `UPDATE utilisateurs SET est_banni = false, date_fin_ban = NULL WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de débannir l'utilisateur"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur débanni"})
}

func GetMesEvenementsInscrits(w http.ResponseWriter, r *http.Request, userId int) {
	rows, err := database.DB.Query(`
		SELECT e.id_evenement, e.titre, e.date_debut, e.date_fin, e.lieu, i.statut_paiement, i.date_inscription
		FROM inscriptions_evenements i
		JOIN evenements e ON e.id_evenement = i.id_evenement
		WHERE i.id_utilisateur = ?
		ORDER BY e.date_debut ASC`, userId)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type EvenementInscrit struct {
		IDEvenement     int        `json:"id_evenement"`
		Titre           string     `json:"titre"`
		DateDebut       time.Time  `json:"date_debut"`
		DateFin         time.Time  `json:"date_fin"`
		Lieu            *string    `json:"lieu,omitempty"`
		StatutPaiement  string     `json:"statut_paiement"`
		DateInscription time.Time  `json:"date_inscription"`
	}

	var result []EvenementInscrit
	for rows.Next() {
		var ev EvenementInscrit
		if err := rows.Scan(&ev.IDEvenement, &ev.Titre, &ev.DateDebut, &ev.DateFin, &ev.Lieu, &ev.StatutPaiement, &ev.DateInscription); err == nil {
			result = append(result, ev)
		}
	}
	if result == nil {
		result = []EvenementInscrit{}
	}
	jsonOK(w, result, http.StatusOK)
}

func UpdateNotifications(w http.ResponseWriter, r *http.Request, userId int) {
	var req struct {
		NotifPushActive  *bool `json:"notif_push_active"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if req.NotifPushActive != nil {
		database.DB.Exec("UPDATE utilisateurs SET notif_push_active = ? WHERE id_utilisateur = ?", *req.NotifPushActive, userId)
	}
	jsonOK(w, map[string]string{"message": "préférences mises à jour"}, http.StatusOK)
}

func DeleteUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("DELETE FROM utilisateurs WHERE id_utilisateur = ?", id)
	if err != nil {
		jsonErr(w, "impossible de supprimer l'utilisateur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "utilisateur supprimé"}, http.StatusOK)
}

func UpdateUserRole(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Role string `json:"role"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	valid := map[string]bool{"particulier": true, "professionnel": true, "salarie": true, "admin": true}
	if !valid[req.Role] {
		jsonErr(w, "rôle invalide", http.StatusBadRequest)
		return
	}
	_, err := database.DB.Exec("UPDATE utilisateurs SET role = ? WHERE id_utilisateur = ?", req.Role, id)
	if err != nil {
		jsonErr(w, "impossible de modifier le rôle", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "rôle mis à jour"}, http.StatusOK)
}

func GetAbonnements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_abonnement, nom, prix_mensuel, type_cible, description FROM abonnements ORDER BY type_cible, prix_mensuel")
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	var result []map[string]interface{}
	for rows.Next() {
		var id int
		var nom, typeCible string
		var prix float64
		var desc *string
		if rows.Scan(&id, &nom, &prix, &typeCible, &desc) == nil {
			result = append(result, map[string]interface{}{
				"id_abonnement": id, "nom": nom, "prix_mensuel": prix, "type_cible": typeCible, "description": desc,
			})
		}
	}
	if result == nil {
		result = []map[string]interface{}{}
	}
	jsonOK(w, result, http.StatusOK)
}

func GetUserSouscription(w http.ResponseWriter, r *http.Request, id string) {
	var sub map[string]interface{}
	row := database.DB.QueryRow(`
		SELECT s.id_souscription, s.id_abonnement, a.nom, s.date_debut, s.date_fin, s.est_active, s.gere_par_admin
		FROM souscriptions s JOIN abonnements a ON a.id_abonnement = s.id_abonnement
		WHERE s.id_utilisateur = ? AND s.est_active = TRUE
		ORDER BY s.date_debut DESC LIMIT 1`, id)
	var idSouscription, idAbonnement int
	var nomAbonnement string
	var dateDebut time.Time
	var dateFin *time.Time
	var estActive, gereParAdmin bool
	err := row.Scan(&idSouscription, &idAbonnement, &nomAbonnement, &dateDebut, &dateFin, &estActive, &gereParAdmin)
	if err != nil {
		jsonOK(w, nil, http.StatusOK)
		return
	}
	sub = map[string]interface{}{
		"id_souscription": idSouscription, "id_abonnement": idAbonnement, "nom": nomAbonnement,
		"date_debut": dateDebut, "date_fin": dateFin, "est_active": estActive, "gere_par_admin": gereParAdmin,
	}
	jsonOK(w, sub, http.StatusOK)
}

func AssignSouscription(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		IDAbonnement int    `json:"id_abonnement"`
		DateFin      string `json:"date_fin"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.IDAbonnement == 0 {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	database.DB.Exec("UPDATE souscriptions SET est_active = FALSE WHERE id_utilisateur = ? AND est_active = TRUE", id)
	var dateFin interface{}
	if req.DateFin != "" {
		dateFin = req.DateFin
	}
	_, err := database.DB.Exec(
		"INSERT INTO souscriptions (id_utilisateur, id_abonnement, date_fin, est_active, gere_par_admin) VALUES (?, ?, ?, TRUE, TRUE)",
		id, req.IDAbonnement, dateFin)
	if err != nil {
		jsonErr(w, "impossible d'assigner l'abonnement", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "abonnement assigné"}, http.StatusCreated)
}

func RevokeSouscription(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE souscriptions SET est_active = FALSE WHERE id_utilisateur = ? AND est_active = TRUE", id)
	if err != nil {
		jsonErr(w, "erreur lors de la révocation", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "abonnement révoqué"}, http.StatusOK)
}

func ExportUserData(w http.ResponseWriter, r *http.Request, userId int) {
	var u models.Utilisateur
	err := database.DB.QueryRow(`SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, date_creation FROM utilisateurs WHERE id_utilisateur = ?`, userId).
		Scan(&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.DateCreation)
	if err != nil {
		jsonErr(w, "utilisateur non trouvé", http.StatusNotFound)
		return
	}

	export := fmt.Sprintf("EXPORT DONNEES UPCYCLECONNECT\nDate: %s\n\nID: %d\nNom: %s %s\nEmail: %s\nTelephone: %s\nVille: %s\nRole: %s\nInscrit le: %s\n",
		time.Now().Format("02/01/2006 15:04"),
		u.IDUtilisateur, u.Prenom, u.Nom,
		u.Email,
		func() string { if u.Telephone != nil { return *u.Telephone }; return "" }(),
		func() string { if u.Ville != nil { return *u.Ville }; return "" }(),
		u.Role,
		u.DateCreation.Format("02/01/2006"),
	)

	w.Header().Set("Content-Type", "text/plain; charset=utf-8")
	w.Header().Set("Content-Disposition", "attachment; filename=\"mes_donnees_upcycleconnect.txt\"")
	w.WriteHeader(http.StatusOK)
	w.Write([]byte(export))
}

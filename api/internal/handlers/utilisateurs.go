package handlers

import (
	"api/internal/models"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
	"os"
	"path/filepath"
	"time"
)

func GetMe(w http.ResponseWriter, r *http.Request, id int) {
	var u models.Utilisateur
	var adresse, photo sql.NullString
	var siretVerifie, notifPush, notifEmail, estCertifie sql.NullBool
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, adresse_complete, photo_profil_url,
	                 role, est_banni, date_fin_ban, nom_entreprise, numero_siret, siret_verifie, notif_push_active,
	                 notif_email_active, COALESCE(upcycling_score, 0), est_certifie, date_creation
	          FROM utilisateurs WHERE id_utilisateur = ?`

	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &adresse, &photo,
		&u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &siretVerifie, &notifPush,
		&notifEmail, &u.UpcyclingScore, &estCertifie, &u.DateCreation)

	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouvé"})
		return
	}

	if adresse.Valid {
		u.AdresseComplete = &adresse.String
	}
	if photo.Valid {
		u.PhotoProfilURL = &photo.String
	}
	if siretVerifie.Valid {
		u.SiretVerifie = &siretVerifie.Bool
	}
	if notifPush.Valid {
		u.NotifPushActive = &notifPush.Bool
	}
	if notifEmail.Valid {
		u.NotifEmailActive = &notifEmail.Bool
	}
	u.EstCertifie = estCertifie.Valid && estCertifie.Bool

	// Niveau (palier) correspondant au score courant.
	if paliers, perr := services.GetPaliers(); perr == nil {
		u.NiveauScore = services.NiveauPourScore(paliers, u.UpcyclingScore).Nom
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

// GetMyScore retourne le détail de l'Upcycling Score de l'utilisateur courant
// (niveau, prochain palier, déchets évités, progression, barème complet).
func GetMyScore(w http.ResponseWriter, r *http.Request, id int) {
	detail, err := services.GetUserScoreDetail(id)
	if err != nil {
		jsonErr(w, "erreur lors du calcul du score", http.StatusInternalServerError)
		return
	}
	jsonOK(w, detail, http.StatusOK)
}

func UpdateMe(w http.ResponseWriter, r *http.Request, id int) {
	var req struct {
		Telephone       *string `json:"telephone"`
		Ville           *string `json:"ville"`
		AdresseComplete *string `json:"adresse_complete"`
		PhotoProfil     *string `json:"photo_profil"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	var photoURL *string
	if req.PhotoProfil != nil && *req.PhotoProfil != "" {
		filename, data, err := decodeBase64Image(*req.PhotoProfil)
		if err == nil {
			uploadDir := getUploadDir()
			os.MkdirAll(uploadDir, 0755)
			filePath := filepath.Join(uploadDir, filename)
			if werr := os.WriteFile(filePath, data, 0644); werr == nil {
				rel := "photos/" + filename
				photoURL = &rel
			}
		}
	}

	query := `UPDATE utilisateurs SET
		telephone = COALESCE(?, telephone),
		ville = COALESCE(?, ville),
		adresse_complete = COALESCE(?, adresse_complete),
		photo_profil_url = COALESCE(?, photo_profil_url)
		WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, req.Telephone, req.Ville, req.AdresseComplete, photoURL, id)
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
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation,
	                 COALESCE(upcycling_score, 0), COALESCE(est_certifie, false)
	          FROM utilisateurs WHERE id_utilisateur = ?`
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation,
		&u.UpcyclingScore, &u.EstCertifie)
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
		NotifEmailActive *bool `json:"notif_email_active"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if req.NotifPushActive != nil {
		database.DB.Exec("UPDATE utilisateurs SET notif_push_active = ? WHERE id_utilisateur = ?", *req.NotifPushActive, userId)
	}
	if req.NotifEmailActive != nil {
		database.DB.Exec("UPDATE utilisateurs SET notif_email_active = ? WHERE id_utilisateur = ?", *req.NotifEmailActive, userId)
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

func DeleteMe(w http.ResponseWriter, r *http.Request, userId int) {
	anon := fmt.Sprintf("supprime_%d", userId)
	_, err := database.DB.Exec(`
		UPDATE utilisateurs SET
			nom = ?, prenom = 'Utilisateur', email = ?,
			mot_de_passe_hash = '', telephone = NULL, ville = NULL,
			adresse_complete = NULL, photo_profil_url = NULL,
			onesignal_player_id = NULL, nom_entreprise = NULL,
			est_banni = TRUE
		WHERE id_utilisateur = ?`,
		anon, anon+"@supprime.invalid", userId)
	if err != nil {
		jsonErr(w, "erreur lors de la suppression du compte", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "compte anonymisé — vos données personnelles ont été effacées"}, http.StatusOK)
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

	pdfBytes, err := services.GenerateUserDataPDF(u, time.Now().Format("02/01/2006 15:04"))
	if err != nil {
		jsonErr(w, "erreur lors de la génération du PDF", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", "attachment; filename=\"mes_donnees_upcycleconnect.pdf\"")
	w.WriteHeader(http.StatusOK)
	w.Write(pdfBytes)
}

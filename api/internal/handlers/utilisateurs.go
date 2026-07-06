package handlers

import (
	"api/internal/middleware"
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

	// Réponse = utilisateur + plan d'abonnement (pour les professionnels).
	out := struct {
		models.Utilisateur
		Plan *middleware.PlanInfo `json:"plan,omitempty"`
	}{Utilisateur: u}
	if u.Role == "professionnel" {
		if p, perr := middleware.GetUserPlanInfo(id); perr == nil && p.EstProFessionnel {
			out.Plan = p
		}
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(out)
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
		SELECT e.id_evenement, e.titre, e.date_debut, e.date_fin, e.lieu, e.statut, i.statut_paiement, i.date_inscription
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
		Statut          string     `json:"statut"`
		StatutPaiement  string     `json:"statut_paiement"`
		DateInscription time.Time  `json:"date_inscription"`
	}

	var result []EvenementInscrit
	for rows.Next() {
		var ev EvenementInscrit
		if err := rows.Scan(&ev.IDEvenement, &ev.Titre, &ev.DateDebut, &ev.DateFin, &ev.Lieu, &ev.Statut, &ev.StatutPaiement, &ev.DateInscription); err == nil {
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
	rows, err := database.DB.Query(`
		SELECT id_abonnement, nom, prix_mensuel, prix_annuel, type_cible, description, couleur,
		       nb_alertes_max, rayon_alerte_max_km, dashboard_annuel, badges_actives
		FROM abonnements ORDER BY type_cible, prix_mensuel`)
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
		var prixAnnuel sql.NullFloat64
		var desc, couleur sql.NullString
		var nbAlertes, rayon sql.NullInt64
		var dashboard, badges bool
		if rows.Scan(&id, &nom, &prix, &prixAnnuel, &typeCible, &desc, &couleur,
			&nbAlertes, &rayon, &dashboard, &badges) != nil {
			continue
		}
		m := map[string]interface{}{
			"id_abonnement": id, "nom": nom, "prix_mensuel": prix, "type_cible": typeCible,
			"description":      nullableString(desc),
			"couleur":          nullableString(couleur),
			"prix_annuel":      nullableFloat(prixAnnuel),
			"nb_alertes_max":   nullableInt(nbAlertes),
			"rayon_alerte_max_km": nullableInt(rayon),
			"dashboard_annuel": dashboard,
			"badges_actives":   badges,
		}
		result = append(result, m)
	}
	if result == nil {
		result = []map[string]interface{}{}
	}
	jsonOK(w, result, http.StatusOK)
}

func nullableString(v sql.NullString) interface{} {
	if v.Valid {
		return v.String
	}
	return nil
}

func nullableFloat(v sql.NullFloat64) interface{} {
	if v.Valid {
		return v.Float64
	}
	return nil
}

func nullableInt(v sql.NullInt64) interface{} {
	if v.Valid {
		return v.Int64
	}
	return nil
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
	// ── Profil complet ──
	var u models.Utilisateur
	var tel, ville, cp, adresse, entreprise sql.NullString
	var score sql.NullFloat64
	var certifie bool
	if err := database.DB.QueryRow(`
		SELECT id_utilisateur, nom, prenom, email, telephone, ville, code_postal, adresse_complete,
		       role, upcycling_score, est_certifie, nom_entreprise, date_creation
		FROM utilisateurs WHERE id_utilisateur = ?`, userId).
		Scan(&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &tel, &ville, &cp, &adresse,
			&u.Role, &score, &certifie, &entreprise, &u.DateCreation); err != nil {
		jsonErr(w, "utilisateur non trouvé", http.StatusNotFound)
		return
	}

	ns := func(v sql.NullString) string {
		if v.Valid {
			return v.String
		}
		return ""
	}
	certTxt := "Non"
	if certifie {
		certTxt = "Oui"
	}
	profil := [][2]string{
		{"Identifiant", fmt.Sprintf("%d", u.IDUtilisateur)},
		{"Nom", u.Nom},
		{"Prénom", u.Prenom},
		{"Email", u.Email},
		{"Téléphone", ns(tel)},
		{"Ville", ns(ville)},
		{"Code postal", ns(cp)},
		{"Adresse", ns(adresse)},
		{"Rôle", u.Role},
		{"Score upcycling", fmt.Sprintf("%.0f", score.Float64)},
		{"Compte certifié", certTxt},
		{"Inscrit le", u.DateCreation.Format("02/01/2006")},
	}
	if ns(entreprise) != "" {
		profil = append(profil, [2]string{"Entreprise", ns(entreprise)})
	}

	df := func(t time.Time) string { return t.Format("02/01/2006") }
	prixOuDon := func(typ string, prix float64) string {
		if typ == "don" {
			return "Don"
		}
		return fmt.Sprintf("%.2f EUR", prix)
	}

	// ── Annonces déposées ──
	annonces := ExportSectionRows(`
		SELECT titre, type_annonce, COALESCE(prix,0), statut, date_creation
		FROM annonces WHERE id_particulier = ? ORDER BY date_creation DESC`, userId,
		func(rows *sql.Rows) []string {
			var titre, typ, statut string
			var prix float64
			var d time.Time
			rows.Scan(&titre, &typ, &prix, &statut, &d)
			return []string{titre, typ, prixOuDon(typ, prix), statut, df(d)}
		})

	// ── Commandes (achats) ──
	commandes := ExportSectionRows(`
		SELECT c.id_commande, a.titre, c.statut, c.date_commande
		FROM commandes c JOIN annonces a ON a.id_annonce = c.id_annonce
		WHERE c.id_acheteur = ? ORDER BY c.date_commande DESC`, userId,
		func(rows *sql.Rows) []string {
			var id int
			var titre, statut string
			var d time.Time
			rows.Scan(&id, &titre, &statut, &d)
			return []string{fmt.Sprintf("#%d", id), titre, statut, df(d)}
		})

	// ── Inscriptions aux événements ──
	inscriptions := ExportSectionRows(`
		SELECT e.titre, i.statut_paiement, COALESCE(i.prix_paye,0), i.date_inscription
		FROM inscriptions_evenements i JOIN evenements e ON e.id_evenement = i.id_evenement
		WHERE i.id_utilisateur = ? ORDER BY i.date_inscription DESC`, userId,
		func(rows *sql.Rows) []string {
			var titre, paiement string
			var prix float64
			var d time.Time
			rows.Scan(&titre, &paiement, &prix, &d)
			return []string{titre, paiement, fmt.Sprintf("%.2f EUR", prix), df(d)}
		})

	// ── Forum : sujets créés ──
	sujets := ExportSectionRows(`
		SELECT titre, categorie, statut, date_creation
		FROM forum_sujets WHERE id_createur = ? ORDER BY date_creation DESC`, userId,
		func(rows *sql.Rows) []string {
			var titre, cat, statut string
			var d time.Time
			rows.Scan(&titre, &cat, &statut, &d)
			return []string{titre, cat, statut, df(d)}
		})

	// ── Forum : messages publiés ──
	messages := ExportSectionRows(`
		SELECT contenu, date_publication
		FROM forum_messages WHERE id_auteur = ? ORDER BY date_publication DESC`, userId,
		func(rows *sql.Rows) []string {
			var contenu string
			var d time.Time
			rows.Scan(&contenu, &d)
			return []string{contenu, df(d)}
		})

	data := services.UserExportData{
		User:   u,
		Genere: time.Now().Format("02/01/2006 15:04"),
		Profil: profil,
		Sections: []services.ExportSection{
			{Titre: "Mes annonces", Headers: []string{"Titre", "Type", "Prix", "Statut", "Date"},
				Largeur: []float64{0.34, 0.13, 0.16, 0.19, 0.18}, Rows: annonces, Vide: "Aucune annonce déposée."},
			{Titre: "Mes commandes", Headers: []string{"N°", "Article", "Statut", "Date"},
				Largeur: []float64{0.12, 0.42, 0.24, 0.22}, Rows: commandes, Vide: "Aucune commande."},
			{Titre: "Mes inscriptions aux événements", Headers: []string{"Événement", "Paiement", "Prix", "Date"},
				Largeur: []float64{0.40, 0.20, 0.18, 0.22}, Rows: inscriptions, Vide: "Aucune inscription."},
			{Titre: "Forum — sujets créés", Headers: []string{"Sujet", "Catégorie", "Statut", "Date"},
				Largeur: []float64{0.38, 0.22, 0.18, 0.22}, Rows: sujets, Vide: "Aucun sujet créé."},
			{Titre: "Forum — messages publiés", Headers: []string{"Message", "Date"},
				Largeur: []float64{0.76, 0.24}, Rows: messages, Vide: "Aucun message publié."},
		},
	}

	pdfBytes, err := services.GenerateUserDataPDF(data)
	if err != nil {
		jsonErr(w, "erreur lors de la génération du PDF", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", "attachment; filename=\"mes_donnees_upcycleconnect.pdf\"")
	w.WriteHeader(http.StatusOK)
	w.Write(pdfBytes)
}

// ExportSectionRows exécute une requête et transforme chaque ligne en []string
// via mapper. Retourne une slice vide (jamais nil) si aucune donnée ou erreur.
func ExportSectionRows(query string, userId int, mapper func(*sql.Rows) []string) [][]string {
	out := [][]string{}
	rows, err := database.DB.Query(query, userId)
	if err != nil {
		return out
	}
	defer rows.Close()
	for rows.Next() {
		out = append(out, mapper(rows))
	}
	return out
}

package handlers

// Supervision des notifications — back-office admin.
// Réutilise onesignal_service et email_service existants, ne les recode pas.
// Ajoute : journal d'audit, gestion des préfs par utilisateur, envoi groupé par site.

import (
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strconv"
	"time"
)

// ─── Journal des envois ───────────────────────────────────────────────────────

type NotifLogEntry struct {
	IDLog           int     `json:"id_log"`
	TypeEnvoi       string  `json:"type_envoi"`
	IDEnvoyeur      *int    `json:"id_envoyeur,omitempty"`
	IDDestinataire  *int    `json:"id_destinataire,omitempty"`
	DestNom         *string `json:"destinataire_nom,omitempty"`
	Segment         *string `json:"segment,omitempty"`
	Titre           string  `json:"titre"`
	Contenu         string  `json:"contenu"`
	NbDestinataires int     `json:"nb_destinataires"`
	Statut          string  `json:"statut"`
	ErreurDetail    *string `json:"erreur_detail,omitempty"`
	DateEnvoi       string  `json:"date_envoi"`
}

func GetNotificationsLog(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	typeEnvoi := q.Get("type")
	dateFrom := q.Get("from")
	dateTo := q.Get("to")
	destID := q.Get("user_id")

	base := `
		SELECT n.id_log, n.type_envoi, n.id_envoyeur, n.id_destinataire,
		       CASE WHEN u.id_utilisateur IS NOT NULL
		            THEN CONCAT(u.prenom,' ',LEFT(u.nom,1),'.')
		            ELSE NULL END,
		       n.segment, n.titre, n.contenu, n.nb_destinataires,
		       n.statut, n.erreur_detail, n.date_envoi
		FROM notifications_envoi_log n
		LEFT JOIN utilisateurs u ON u.id_utilisateur = n.id_destinataire
		WHERE 1=1`
	args := []interface{}{}

	if typeEnvoi != "" {
		base += " AND n.type_envoi = ?"
		args = append(args, typeEnvoi)
	}
	if dateFrom != "" {
		base += " AND n.date_envoi >= ?"
		args = append(args, dateFrom)
	}
	if dateTo != "" {
		base += " AND n.date_envoi <= ?"
		args = append(args, dateTo+" 23:59:59")
	}
	if destID != "" {
		base += " AND n.id_destinataire = ?"
		args = append(args, destID)
	}
	base += " ORDER BY n.date_envoi DESC LIMIT 500"

	rows, err := database.DB.Query(base, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []NotifLogEntry{}
	for rows.Next() {
		var e NotifLogEntry
		var idEnv, idDest sql.NullInt64
		var destNom, segment, errDetail sql.NullString
		var dt time.Time
		if err := rows.Scan(&e.IDLog, &e.TypeEnvoi, &idEnv, &idDest,
			&destNom, &segment, &e.Titre, &e.Contenu,
			&e.NbDestinataires, &e.Statut, &errDetail, &dt); err == nil {
			if idEnv.Valid {
				v := int(idEnv.Int64)
				e.IDEnvoyeur = &v
			}
			if idDest.Valid {
				v := int(idDest.Int64)
				e.IDDestinataire = &v
			}
			if destNom.Valid {
				e.DestNom = &destNom.String
			}
			if segment.Valid {
				e.Segment = &segment.String
			}
			if errDetail.Valid {
				e.ErreurDetail = &errDetail.String
			}
			e.DateEnvoi = dt.Format("2006-01-02T15:04:05Z")
			out = append(out, e)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// ─── Préférences de notification par utilisateur ─────────────────────────────

type UserNotifPrefs struct {
	IDUtilisateur   int    `json:"id_utilisateur"`
	NomComplet      string `json:"nom_complet"`
	Email           string `json:"email"`
	NotifPushActive bool   `json:"notif_push_active"`
	NotifEmailActive bool  `json:"notif_email_active"`
	OneSignalID     *string `json:"onesignal_player_id,omitempty"`
}

func GetUserPrefsNotif(w http.ResponseWriter, r *http.Request, userID string) {
	var p UserNotifPrefs
	var oneSignalID sql.NullString
	var notifEmail sql.NullBool
	err := database.DB.QueryRow(`
		SELECT id_utilisateur, CONCAT(prenom,' ',nom), email,
		       COALESCE(notif_push_active, 1), COALESCE(notif_email_active, 1),
		       onesignal_player_id
		FROM utilisateurs WHERE id_utilisateur = ?`, userID).
		Scan(&p.IDUtilisateur, &p.NomComplet, &p.Email,
			&p.NotifPushActive, &notifEmail, &oneSignalID)
	if err != nil {
		jsonErr(w, "utilisateur introuvable", http.StatusNotFound)
		return
	}
	if notifEmail.Valid {
		p.NotifEmailActive = notifEmail.Bool
	} else {
		p.NotifEmailActive = true
	}
	if oneSignalID.Valid {
		p.OneSignalID = &oneSignalID.String
	}
	jsonOK(w, p, http.StatusOK)
}

func UpdateUserPrefsNotif(w http.ResponseWriter, r *http.Request, userID string) {
	var req struct {
		NotifPushActive  *bool `json:"notif_push_active"`
		NotifEmailActive *bool `json:"notif_email_active"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if req.NotifPushActive == nil && req.NotifEmailActive == nil {
		jsonErr(w, "au moins un champ requis", http.StatusBadRequest)
		return
	}

	if req.NotifPushActive != nil {
		database.DB.Exec("UPDATE utilisateurs SET notif_push_active = ? WHERE id_utilisateur = ?",
			*req.NotifPushActive, userID)
	}
	if req.NotifEmailActive != nil {
		database.DB.Exec("UPDATE utilisateurs SET notif_email_active = ? WHERE id_utilisateur = ?",
			*req.NotifEmailActive, userID)
	}
	jsonOK(w, map[string]string{"message": "préférences mises à jour"}, http.StatusOK)
}

// ─── Envoi groupé par site ────────────────────────────────────────────────────

// GetSitesUC retourne la liste des sites pour alimenter le sélecteur d'envoi groupé.
func GetSitesUC(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT s.id_site, s.nom_site, s.ville, COUNT(u.id_utilisateur) AS nb_utilisateurs
		FROM site_uc s
		LEFT JOIN utilisateurs u ON u.id_site_uc = s.id_site
		GROUP BY s.id_site ORDER BY s.nom_site ASC`)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type Site struct {
		IDSite         int    `json:"id_site"`
		NomSite        string `json:"nom_site"`
		Ville          string `json:"ville"`
		NbUtilisateurs int    `json:"nb_utilisateurs"`
	}
	out := []Site{}
	for rows.Next() {
		var s Site
		var ville sql.NullString
		if rows.Scan(&s.IDSite, &s.NomSite, &ville, &s.NbUtilisateurs) == nil {
			s.Ville = ville.String
			out = append(out, s)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// SendNotifGroupe envoie une notification push et/ou email à un segment (site, rôle, ou tous).
func SendNotifGroupe(w http.ResponseWriter, r *http.Request, adminID int) {
	var req struct {
		Titre    string `json:"titre"`
		Contenu  string `json:"contenu"`
		TypeEnvoi string `json:"type_envoi"` // "push", "email", "push_email"
		// Filtres de segment (au moins un requis)
		IDSite *int    `json:"id_site,omitempty"`
		Role   *string `json:"role,omitempty"` // "salarie", "particulier", etc.
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if req.Titre == "" || req.Contenu == "" {
		jsonErr(w, "titre et contenu requis", http.StatusBadRequest)
		return
	}
	if req.TypeEnvoi == "" {
		req.TypeEnvoi = "push"
	}
	// Normaliser : le client peut envoyer "groupe_push" / "groupe_email"
	switch req.TypeEnvoi {
	case "groupe_push":
		req.TypeEnvoi = "push"
	case "groupe_email":
		req.TypeEnvoi = "email"
	}

	// Construire le filtre de segment
	segQuery := `
		SELECT id_utilisateur, email, COALESCE(onesignal_player_id,''), notif_push_active, COALESCE(notif_email_active,1)
		FROM utilisateurs WHERE est_banni = 0`
	args := []interface{}{}
	segment := "tous"

	if req.IDSite != nil {
		segQuery += " AND id_site_uc = ?"
		args = append(args, *req.IDSite)
		segment = "site_id=" + strconv.Itoa(*req.IDSite)
	}
	if req.Role != nil {
		segQuery += " AND role = ?"
		args = append(args, *req.Role)
		segment += ",role=" + *req.Role
	}

	rows, err := database.DB.Query(segQuery, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type dest struct {
		id          int
		email       string
		playerID    string
		pushActive  bool
		emailActive bool
	}
	dests := []dest{}
	for rows.Next() {
		var d dest
		rows.Scan(&d.id, &d.email, &d.playerID, &d.pushActive, &d.emailActive)
		dests = append(dests, d)
	}

	if len(dests) == 0 {
		jsonErr(w, "aucun utilisateur dans ce segment", http.StatusBadRequest)
		return
	}

	// Envoi push groupé
	var pushErr, emailErr error
	nbEnvoyesPush, nbEnvoyesEmail := 0, 0

	if req.TypeEnvoi == "push" || req.TypeEnvoi == "push_email" {
		playerIDs := []string{}
		for _, d := range dests {
			if d.pushActive && d.playerID != "" {
				playerIDs = append(playerIDs, d.playerID)
				nbEnvoyesPush++
			}
		}
		if len(playerIDs) > 0 {
			pushErr = services.SendPushToPlayers(playerIDs, req.Titre, req.Contenu, nil)
		}
	}

	if req.TypeEnvoi == "email" || req.TypeEnvoi == "push_email" {
		for _, d := range dests {
			if d.emailActive && d.email != "" {
				if err := services.SendSimpleEmail(d.email, req.Titre, req.Contenu); err != nil {
					emailErr = err
				} else {
					nbEnvoyesEmail++
				}
			}
		}
	}

	// Logger dans notifications_envoi_log
	statut := "envoye"
	var errDetail *string
	if pushErr != nil || emailErr != nil {
		statut = "erreur"
		detail := ""
		if pushErr != nil {
			detail += "push: " + pushErr.Error()
		}
		if emailErr != nil {
			if detail != "" {
				detail += "; "
			}
			detail += "email: " + emailErr.Error()
		}
		errDetail = &detail
	}

	typeLog := req.TypeEnvoi
	if typeLog == "push_email" {
		typeLog = "groupe_push"
	} else {
		typeLog = "groupe_" + req.TypeEnvoi
	}
	database.DB.Exec(`
		INSERT INTO notifications_envoi_log
		  (type_envoi, id_envoyeur, segment, titre, contenu, nb_destinataires, statut, erreur_detail)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
		typeLog, adminID, segment, req.Titre, req.Contenu,
		nbEnvoyesPush+nbEnvoyesEmail, statut, errDetail)

	jsonOK(w, map[string]interface{}{
		"message":          "envoi groupé lancé",
		"nb_push":          nbEnvoyesPush,
		"nb_email":         nbEnvoyesEmail,
		"total_destinataires": len(dests),
	}, http.StatusOK)
}

// logNotifEnvoi enregistre un envoi unitaire dans le journal (appelé depuis onesignal/email).
func logNotifEnvoi(typeEnvoi string, idEnvoyeur *int, idDest *int, segment *string, titre, contenu string, nb int, statut string, errDetail *string) {
	database.DB.Exec(`
		INSERT INTO notifications_envoi_log
		  (type_envoi, id_envoyeur, id_destinataire, segment, titre, contenu, nb_destinataires, statut, erreur_detail)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
		typeEnvoi, idEnvoyeur, idDest, segment, titre, contenu, nb, statut, errDetail)
}

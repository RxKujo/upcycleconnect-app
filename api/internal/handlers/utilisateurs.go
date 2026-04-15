package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"math"
	"net/http"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	"github.com/jung-kurt/gofpdf"
)

// ==================== PROFILE (Task 4) ====================

func GetMe(w http.ResponseWriter, r *http.Request, id int) {
	log.Printf("[INFO] %s | GetMe | User %d fetching profile\n", time.Now().Format(time.RFC3339), id)

	var u models.Utilisateur
	var adresse, photoProfil, telephone, ville, nomEntreprise, numeroSiret sql.NullString
	var dateFinBan sql.NullTime

	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, adresse_complete, photo_profil_url,
	          role, est_banni, date_fin_ban, nom_entreprise, numero_siret,
	          COALESCE(upcycling_score, 0), COALESCE(est_certifie, 0),
	          date_creation
	          FROM utilisateurs WHERE id_utilisateur = ?`

	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email,
		&telephone, &ville, &adresse, &photoProfil,
		&u.Role, &u.EstBanni, &dateFinBan,
		&nomEntreprise, &numeroSiret,
		&u.UpcyclingScore, &u.EstCertifie,
		&u.DateCreation)

	// Set default notification settings (since columns don't exist yet)
	u.NotifPushActive = true
	u.NotifEmailActive = true

	if err != nil {
		log.Printf("[ERROR] %s | GetMe | User %d not found: %v\n", time.Now().Format(time.RFC3339), id, err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouve"})
		return
	}

	if telephone.Valid {
		u.Telephone = &telephone.String
	}
	if ville.Valid {
		u.Ville = &ville.String
	}
	if adresse.Valid {
		u.AdresseComplete = &adresse.String
	}
	if photoProfil.Valid {
		u.PhotoProfilURL = &photoProfil.String
	}
	if dateFinBan.Valid {
		u.DateFinBan = &dateFinBan.Time
	}
	if nomEntreprise.Valid {
		u.NomEntreprise = &nomEntreprise.String
	}
	if numeroSiret.Valid {
		u.NumeroSiret = &numeroSiret.String
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

func UpdateMe(w http.ResponseWriter, r *http.Request, id int) {
	log.Printf("[INFO] %s | UpdateProfile | User %d updating profile\n", time.Now().Format(time.RFC3339), id)

	var req struct {
		Telephone       *string `json:"telephone"`
		Ville           *string `json:"ville"`
		AdresseComplete *string `json:"adresse_complete"`
		PhotoProfil     *string `json:"photo_profil"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	// Handle photo upload
	var photoURL *string
	if req.PhotoProfil != nil && *req.PhotoProfil != "" {
		ext, data, err := decodeBase64Image(*req.PhotoProfil)
		if err != nil {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "photo invalide"})
			return
		}

		uploadDir := getUploadDir()
		os.MkdirAll(uploadDir, 0755)

		filename := fmt.Sprintf("%d_profile.%s", id, ext)
		filePath := filepath.Join(uploadDir, filename)
		if err := os.WriteFile(filePath, data, 0644); err != nil {
			log.Printf("[ERROR] %s | UpdateProfile | File write err: %v\n", time.Now().Format(time.RFC3339), err)
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur sauvegarde photo"})
			return
		}
		url := "photos/" + filename
		photoURL = &url
	}

	query := `UPDATE utilisateurs SET
		telephone = COALESCE(?, telephone),
		ville = COALESCE(?, ville),
		adresse_complete = COALESCE(?, adresse_complete)`
	args := []interface{}{req.Telephone, req.Ville, req.AdresseComplete}

	if photoURL != nil {
		query += `, photo_profil_url = ?`
		args = append(args, *photoURL)
	}

	query += ` WHERE id_utilisateur = ?`
	args = append(args, id)

	_, err := database.DB.Exec(query, args...)
	if err != nil {
		log.Printf("[ERROR] %s | UpdateProfile | DB err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la mise a jour"})
		return
	}

	log.Printf("[INFO] %s | UpdateProfile | User %d updated profile\n", time.Now().Format(time.RFC3339), id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "profil mis a jour avec succes"})
}

func UpdateNotifications(w http.ResponseWriter, r *http.Request, id int) {
	log.Printf("[INFO] %s | UpdateNotifications | User %d\n", time.Now().Format(time.RFC3339), id)

	var req struct {
		NotifPushActive  *bool `json:"notif_push_active"`
		NotifEmailActive *bool `json:"notif_email_active"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	// Note: notif_push_active and notif_email_active columns don't exist yet
	// For now, we just log the preference and return success
	// TODO: Add these columns to database schema

	pushFlag := "unchanged"
	emailFlag := "unchanged"
	if req.NotifPushActive != nil {
		pushFlag = fmt.Sprintf("%v", *req.NotifPushActive)
	}
	if req.NotifEmailActive != nil {
		emailFlag = fmt.Sprintf("%v", *req.NotifEmailActive)
	}

	log.Printf("[INFO] %s | UpdateNotifications | User %d set push=%s, email=%s\n", time.Now().Format(time.RFC3339), id, pushFlag, emailFlag)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "preferences mises a jour"})
}

func GetEnrolledEvents(w http.ResponseWriter, r *http.Request, userId int) {
	log.Printf("[INFO] %s | GetEnrolledEvents | User %d\n", time.Now().Format(time.RFC3339), userId)

	rows, err := database.DB.Query(`
		SELECT e.id_evenement, e.titre, e.date_debut, e.lieu, e.statut,
		       COALESCE(i.statut_paiement, 'inconnu'), COALESCE(i.prix_paye, 0)
		FROM inscriptions_evenements i
		JOIN evenements e ON i.id_evenement = e.id_evenement
		WHERE i.id_utilisateur = ?
		ORDER BY e.date_debut DESC`, userId)
	if err != nil {
		log.Printf("[ERROR] %s | GetEnrolledEvents | Query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var events []map[string]interface{}
	for rows.Next() {
		var idEv int
		var titre, lieu, statut, statutPaiement string
		var dateDebut time.Time
		var prixPaye float64

		if err := rows.Scan(&idEv, &titre, &dateDebut, &lieu, &statut, &statutPaiement, &prixPaye); err == nil {
			events = append(events, map[string]interface{}{
				"id_evenement":    idEv,
				"titre":           titre,
				"date_debut":      dateDebut,
				"lieu":            lieu,
				"statut":          statut,
				"statut_paiement": statutPaiement,
				"prix_paye":       prixPaye,
			})
		}
	}
	if events == nil {
		events = []map[string]interface{}{}
	}

	log.Printf("[INFO] %s | GetEnrolledEvents | User %d retrieved %d events\n", time.Now().Format(time.RFC3339), userId, len(events))

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(events)
}

func ExportPDF(w http.ResponseWriter, r *http.Request, userId int) {
	log.Printf("[INFO] %s | ExportPDF | User %d exporting profile data\n", time.Now().Format(time.RFC3339), userId)

	// Get user info
	var nom, prenom, email, role string
	var telephone, ville sql.NullString
	var dateCreation time.Time
	var upcyclingScore int
	var estCertifie bool

	err := database.DB.QueryRow(`SELECT nom, prenom, email, telephone, ville, role, date_creation,
		COALESCE(upcycling_score, 0), COALESCE(est_certifie, 0)
		FROM utilisateurs WHERE id_utilisateur = ?`, userId).
		Scan(&nom, &prenom, &email, &telephone, &ville, &role, &dateCreation, &upcyclingScore, &estCertifie)
	if err != nil {
		log.Printf("[ERROR] %s | ExportPDF | User query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouve"})
		return
	}

	// Get ads
	adRows, _ := database.DB.Query(`SELECT titre, statut, date_creation FROM annonces WHERE id_particulier = ? ORDER BY date_creation DESC`, userId)
	var ads []map[string]string
	if adRows != nil {
		defer adRows.Close()
		for adRows.Next() {
			var t, s string
			var d time.Time
			if adRows.Scan(&t, &s, &d) == nil {
				ads = append(ads, map[string]string{"titre": t, "statut": s, "date": d.Format("02/01/2006")})
			}
		}
	}

	// Get enrolled events
	evRows, _ := database.DB.Query(`
		SELECT e.titre, e.date_debut, COALESCE(i.statut_paiement, 'inconnu')
		FROM inscriptions_evenements i
		JOIN evenements e ON i.id_evenement = e.id_evenement
		WHERE i.id_utilisateur = ?`, userId)
	var events []map[string]string
	if evRows != nil {
		defer evRows.Close()
		for evRows.Next() {
			var t, sp string
			var d time.Time
			if evRows.Scan(&t, &d, &sp) == nil {
				events = append(events, map[string]string{"titre": t, "date": d.Format("02/01/2006"), "statut_paiement": sp})
			}
		}
	}

	// Generate PDF using gofpdf (without UTF-8 accents)
	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.SetFont("Arial", "", 10)
	pdf.AddPage()

	// Title
	pdf.SetFont("Arial", "B", 16)
	pdf.CellFormat(0, 10, "UPCYCLECONNECT", "", 1, "C", false, 0, "")
	pdf.CellFormat(0, 8, "Export Donnees Personnelles", "", 1, "C", false, 0, "")

	pdf.SetFont("Arial", "", 9)
	now := time.Now()
	pdf.CellFormat(0, 6, fmt.Sprintf("Date d'export: %s", now.Format("02/01/2006 15:04")), "", 1, "C", false, 0, "")
	pdf.Ln(5)

	// Personal Info
	pdf.SetFont("Arial", "B", 11)
	pdf.CellFormat(0, 8, "INFORMATIONS PERSONNELLES", "", 1, "", false, 0, "")
	pdf.SetFont("Arial", "", 10)

	pdf.CellFormat(40, 6, "Nom:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, fmt.Sprintf("%s %s", prenom, nom), "", 1, "", false, 0, "")

	pdf.CellFormat(40, 6, "Email:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, email, "", 1, "", false, 0, "")

	telStr := "Non renseigne"
	if telephone.Valid {
		telStr = telephone.String
	}
	pdf.CellFormat(40, 6, "Telephone:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, telStr, "", 1, "", false, 0, "")

	villeStr := "Non renseigne"
	if ville.Valid {
		villeStr = ville.String
	}
	pdf.CellFormat(40, 6, "Ville:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, villeStr, "", 1, "", false, 0, "")

	pdf.CellFormat(40, 6, "Role:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, role, "", 1, "", false, 0, "")

	pdf.CellFormat(40, 6, "Inscription:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, dateCreation.Format("02/01/2006"), "", 1, "", false, 0, "")

	pdf.CellFormat(40, 6, "Score:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, fmt.Sprintf("%d", upcyclingScore), "", 1, "", false, 0, "")

	certifStr := "Non"
	if estCertifie {
		certifStr = "Oui"
	}
	pdf.CellFormat(40, 6, "Certifie:", "", 0, "", false, 0, "")
	pdf.CellFormat(0, 6, certifStr, "", 1, "", false, 0, "")

	pdf.Ln(5)

	// Ads
	pdf.SetFont("Arial", "B", 11)
	pdf.CellFormat(0, 8, "ANNONCES PUBLIEES", "", 1, "", false, 0, "")
	pdf.SetFont("Arial", "", 10)

	if len(ads) == 0 {
		pdf.CellFormat(0, 6, "Aucune annonce publiee.", "", 1, "", false, 0, "")
	} else {
		for i, ad := range ads {
			pdf.CellFormat(0, 6, fmt.Sprintf("%d. %s (Statut: %s, %s)", i+1, ad["titre"], ad["statut"], ad["date"]), "", 1, "", false, 0, "")
		}
	}

	pdf.Ln(3)

	// Events
	pdf.SetFont("Arial", "B", 11)
	pdf.CellFormat(0, 8, "EVENEMENTS INSCRITS", "", 1, "", false, 0, "")
	pdf.SetFont("Arial", "", 10)

	if len(events) == 0 {
		pdf.CellFormat(0, 6, "Aucun evenement inscrit.", "", 1, "", false, 0, "")
	} else {
		for i, ev := range events {
			pdf.CellFormat(0, 6, fmt.Sprintf("%d. %s (%s, Paiement: %s)", i+1, ev["titre"], ev["date"], ev["statut_paiement"]), "", 1, "", false, 0, "")
		}
	}

	// Footer
	pdf.Ln(10)
	pdf.SetFont("Arial", "I", 8)
	pdf.CellFormat(0, 6, "Document genere automatiquement par UpcycleConnect", "", 1, "C", false, 0, "")

	// Output PDF
	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		log.Printf("[ERROR] %s | ExportPDF | PDF generation err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur generation PDF"})
		return
	}

	filename := fmt.Sprintf("profile_%d_%s.pdf", userId, now.Format("2006-01-02"))
	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="%s"`, filename))
	w.Header().Set("Content-Length", strconv.Itoa(buf.Len()))
	w.WriteHeader(http.StatusOK)
	w.Write(buf.Bytes())

	log.Printf("[INFO] %s | ExportPDF | User %d exported profile data\n", time.Now().Format(time.RFC3339), userId)
}

// ==================== ADMIN (Task 5) ====================

func GetAllUtilisateurs(w http.ResponseWriter, r *http.Request) {
	log.Printf("[INFO] %s | GetUsers | Admin listing users\n", time.Now().Format(time.RFC3339))

	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, photo_profil_url, date_creation FROM utilisateurs WHERE 1=1`
	var args []interface{}

	// Filters
	params := r.URL.Query()
	if roleFilter := params.Get("role"); roleFilter != "" {
		query += " AND role = ?"
		args = append(args, roleFilter)
	}
	if bannedFilter := params.Get("est_banni"); bannedFilter != "" {
		if bannedFilter == "true" {
			query += " AND est_banni = 1"
		} else {
			query += " AND est_banni = 0"
		}
	}
	if search := params.Get("search"); search != "" {
		query += " AND (email LIKE ? OR nom LIKE ? OR prenom LIKE ?)"
		s := "%" + search + "%"
		args = append(args, s, s, s)
	}

	// Exclude soft-deleted (Note: deleted_at column not implemented yet)
	// query += " AND deleted_at IS NULL"

	query += " ORDER BY date_creation DESC"

	// Pagination
	page := 1
	limit := 20
	if p := params.Get("page"); p != "" {
		if v, err := strconv.Atoi(p); err == nil && v > 0 {
			page = v
		}
	}
	if l := params.Get("limit"); l != "" {
		if v, err := strconv.Atoi(l); err == nil && v > 0 && v <= 100 {
			limit = v
		}
	}

	// Count total
	countQuery := strings.Replace(query, "SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, photo_profil_url, date_creation", "SELECT COUNT(*)", 1)
	// Remove ORDER BY for count
	if idx := strings.Index(countQuery, " ORDER BY"); idx != -1 {
		countQuery = countQuery[:idx]
	}
	var total int
	err := database.DB.QueryRow(countQuery, args...).Scan(&total)
	if err != nil {
		log.Printf("[ERROR] %s | GetUsers | Count query err: %v | Query: %s\n", time.Now().Format(time.RFC3339), err, countQuery)
		// Ignore count error, continue anyway
	}

	offset := (page - 1) * limit
	query += " LIMIT ? OFFSET ?"
	args = append(args, limit, offset)

	rows, err := database.DB.Query(query, args...)
	if err != nil {
		log.Printf("[ERROR] %s | GetUsers | Query err: %v | Query: %s\n", time.Now().Format(time.RFC3339), err, query)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]interface{}{
			"erreur": "erreur serveur",
			"debug": fmt.Sprintf("%v", err),
		})
		return
	}
	defer rows.Close()

	var utilisateurs []map[string]interface{}
	for rows.Next() {
		var id int
		var nom, prenom, email, role string
		var telephone, ville, photoProfil sql.NullString
		var estBanni bool
		var dateCreation time.Time

		if err := rows.Scan(&id, &nom, &prenom, &email, &telephone, &ville, &role, &estBanni, &photoProfil, &dateCreation); err == nil {
			u := map[string]interface{}{
				"id_utilisateur":  id,
				"nom":             nom,
				"prenom":          prenom,
				"email":           email,
				"role":            role,
				"est_banni":       estBanni,
				"date_creation":   dateCreation,
			}
			if telephone.Valid {
				u["telephone"] = telephone.String
			}
			if ville.Valid {
				u["ville"] = ville.String
			}
			if photoProfil.Valid {
				u["photo_profil_url"] = photoProfil.String
			}
			utilisateurs = append(utilisateurs, u)
		}
	}
	if utilisateurs == nil {
		utilisateurs = []map[string]interface{}{}
	}

	totalPages := int(math.Ceil(float64(total) / float64(limit)))

	log.Printf("[INFO] %s | GetUsers | Listed %d users (page %d/%d)\n", time.Now().Format(time.RFC3339), len(utilisateurs), page, totalPages)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"utilisateurs": utilisateurs,
		"total":        total,
		"page":         page,
		"limit":        limit,
		"total_pages":  totalPages,
	})
}

func GetUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	log.Printf("[INFO] %s | GetUserDetails | Viewing user %s\n", time.Now().Format(time.RFC3339), id)

	var u models.Utilisateur
	var adresse, photoProfil, telephone, ville sql.NullString

	// Parse ID to int
	userID, err := strconv.Atoi(id)
	if err != nil {
		log.Printf("[ERROR] %s | GetUtilisateur | Invalid ID: %s\n", time.Now().Format(time.RFC3339), id)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ID invalide"})
		return
	}

	// Use the exact same query structure as GetAllUtilisateurs which works
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, photo_profil_url, date_creation FROM utilisateurs WHERE id_utilisateur = ?`

	log.Printf("[DEBUG] %s | GetUtilisateur | Executing query for userID: %d\n", time.Now().Format(time.RFC3339), userID)

	err = database.DB.QueryRow(query, userID).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email,
		&telephone, &ville, &u.Role, &u.EstBanni, &photoProfil,
		&u.DateCreation)

	if err != nil {
		log.Printf("[ERROR] %s | GetUtilisateur | Scan error: %v | Query: %s | userID: %d\n", time.Now().Format(time.RFC3339), err, query, userID)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]interface{}{
			"erreur": "utilisateur non trouve",
			"debug": fmt.Sprintf("%v", err),
		})
		return
	}

	if telephone.Valid {
		u.Telephone = &telephone.String
	}
	if ville.Valid {
		u.Ville = &ville.String
	}
	if adresse.Valid {
		u.AdresseComplete = &adresse.String
	}
	if photoProfil.Valid {
		u.PhotoProfilURL = &photoProfil.String
	}

	// Also get current subscription
	var subscription map[string]interface{}
	var idSouscription int
	var nomAbonnement string
	var dateFin time.Time
	var gereParAdmin bool
	err = database.DB.QueryRow(`
		SELECT s.id_souscription, a.nom, s.date_fin, COALESCE(s.gere_par_admin, 0)
		FROM souscriptions s
		JOIN abonnements a ON s.id_abonnement = a.id_abonnement
		WHERE s.id_utilisateur = ? AND s.est_active = 1
		ORDER BY s.date_debut DESC LIMIT 1`, userID).
		Scan(&idSouscription, &nomAbonnement, &dateFin, &gereParAdmin)
	if err == nil {
		subscription = map[string]interface{}{
			"id_souscription": idSouscription,
			"nom_abonnement":  nomAbonnement,
			"date_fin":        dateFin,
			"gere_par_admin":  gereParAdmin,
		}
	}

	result := map[string]interface{}{
		"utilisateur":  u,
		"subscription": subscription,
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(result)
}

func ChangeRole(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		Role string `json:"role"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	validRoles := map[string]bool{"particulier": true, "professionnel": true, "salarie": true, "admin": true}
	if !validRoles[req.Role] {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "role invalide"})
		return
	}

	_, err := database.DB.Exec("UPDATE utilisateurs SET role = ? WHERE id_utilisateur = ?", req.Role, id)
	if err != nil {
		log.Printf("[ERROR] %s | ChangeRole | DB err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	log.Printf("[WARN] %s | ChangeRole | Changed user %s role to %s\n", time.Now().Format(time.RFC3339), id, req.Role)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "role mis a jour", "role": req.Role})
}

func BanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		DateFinBan *string `json:"date_fin_ban"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		// Try without body (permanent ban)
		req.DateFinBan = nil
	}

	// Cannot ban admin
	var role string
	err := database.DB.QueryRow("SELECT role FROM utilisateurs WHERE id_utilisateur = ?", id).Scan(&role)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouve"})
		return
	}
	if role == "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de bannir un administrateur"})
		return
	}

	var query string
	var args []interface{}

	if req.DateFinBan != nil && *req.DateFinBan != "" {
		query = `UPDATE utilisateurs SET est_banni = 1, date_fin_ban = ? WHERE id_utilisateur = ?`
		args = []interface{}{*req.DateFinBan, id}
		log.Printf("[WARN] %s | BanUser | Banned user %s until %s\n", time.Now().Format(time.RFC3339), id, *req.DateFinBan)
	} else {
		query = `UPDATE utilisateurs SET est_banni = 1, date_fin_ban = NULL WHERE id_utilisateur = ?`
		args = []interface{}{id}
		log.Printf("[WARN] %s | BanUser | Permanently banned user %s\n", time.Now().Format(time.RFC3339), id)
	}

	_, err = database.DB.Exec(query, args...)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de bannir l'utilisateur"})
		return
	}

	// Stub email notification
	log.Printf("[INFO] %s | BanUser | Email notification stub: user %s has been banned\n", time.Now().Format(time.RFC3339), id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur banni"})
}

func UnbanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	query := `UPDATE utilisateurs SET est_banni = 0, date_fin_ban = NULL WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de debannir l'utilisateur"})
		return
	}

	log.Printf("[INFO] %s | UnbanUser | Unbanned user %s\n", time.Now().Format(time.RFC3339), id)
	log.Printf("[INFO] %s | UnbanUser | Email notification stub: user %s has been unbanned\n", time.Now().Format(time.RFC3339), id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur debanni"})
}

func DeleteUtilisateur(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	log.Printf("[WARN] %s | DeleteUser | Admin %d deleting user %s\n", time.Now().Format(time.RFC3339), adminId, id)

	// Soft delete
	_, err := database.DB.Exec("UPDATE utilisateurs SET deleted_at = NOW() WHERE id_utilisateur = ?", id)
	if err != nil {
		log.Printf("[ERROR] %s | DeleteUser | DB err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "compte supprime"})
}

func AssignSubscription(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	log.Printf("[INFO] %s | AssignSubscription | Admin %d assigning subscription to user %s\n", time.Now().Format(time.RFC3339), adminId, id)

	var req struct {
		IDAbonnement int    `json:"id_abonnement"`
		DateFin      string `json:"date_fin"`
		GereParAdmin bool   `json:"gere_par_admin"`
	}

	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	// Deactivate existing subscriptions
	_, _ = tx.Exec("UPDATE souscriptions SET est_active = 0 WHERE id_utilisateur = ? AND est_active = 1", id)

	// Insert new subscription
	res, err := tx.Exec(`INSERT INTO souscriptions (id_utilisateur, id_abonnement, date_debut, date_fin, est_active, gere_par_admin) VALUES (?, ?, NOW(), ?, 1, ?)`,
		id, req.IDAbonnement, req.DateFin, req.GereParAdmin)
	if err != nil {
		tx.Rollback()
		log.Printf("[ERROR] %s | AssignSubscription | Insert err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de l'attribution de l'abonnement"})
		return
	}

	subId, _ := res.LastInsertId()
	tx.Commit()

	log.Printf("[INFO] %s | AssignSubscription | Admin %d assigned plan %d to user %s (sub %d)\n", time.Now().Format(time.RFC3339), adminId, req.IDAbonnement, id, subId)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":         "abonnement attribue",
		"id_souscription": subId,
	})
}

func RemoveSubscription(w http.ResponseWriter, r *http.Request, userId string, subId string, adminId int) {
	log.Printf("[INFO] %s | RemoveSubscription | Admin %d removing subscription %s from user %s\n", time.Now().Format(time.RFC3339), adminId, subId, userId)

	_, err := database.DB.Exec("UPDATE souscriptions SET est_active = 0 WHERE id_souscription = ? AND id_utilisateur = ?", subId, userId)
	if err != nil {
		log.Printf("[ERROR] %s | RemoveSubscription | DB err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "abonnement desactive"})
}

// GetAbonnements returns all available subscription plans (for admin dropdown)
func GetAbonnements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_abonnement, nom, prix_mensuel FROM abonnements ORDER BY prix_mensuel")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var plans []map[string]interface{}
	for rows.Next() {
		var id int
		var nom string
		var prix float64
		if rows.Scan(&id, &nom, &prix) == nil {
			plans = append(plans, map[string]interface{}{"id_abonnement": id, "nom": nom, "prix_mensuel": prix})
		}
	}
	if plans == nil {
		plans = []map[string]interface{}{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(plans)
}

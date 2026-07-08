package handlers

// Conteneurs (back-office) : CRUD + photos, codes-barres, tickets d'incident, miroir GLPI.

import (
	"api/internal/models"
	"api/internal/services"
	"api/pkg/database"
	"api/pkg/glpi"
	"encoding/json"
	"log"
	"net/http"
	"strconv"
	"strings"
	"time"
)

// --- Notifications & photos (helpers) ---

// notifyAcheteurDepot : email « objet arrivé en conteneur » au pro. Complément au
// push OneSignal (utile en local et si le pro n'a pas activé les push).
func notifyAcheteurDepot(idCommande int) {
	var idAcheteur int
	var email, titre, conteneurRef, adresse, ville, limite string
	err := database.DB.QueryRow(`
		SELECT c.id_acheteur, u.email, a.titre,
		       COALESCE(cn.conteneur_ref,''), COALESCE(cn.adresse,''), COALESCE(cn.ville,''),
		       COALESCE(DATE_FORMAT(c.date_limite_recuperation, '%d/%m/%Y'), '')
		FROM commandes c
		JOIN utilisateurs u ON u.id_utilisateur = c.id_acheteur
		JOIN annonces a ON a.id_annonce = c.id_annonce
		LEFT JOIN conteneurs cn ON cn.id_conteneur = c.id_conteneur
		WHERE c.id_commande = ?`, idCommande).Scan(&idAcheteur, &email, &titre, &conteneurRef, &adresse, &ville, &limite)
	if err != nil {
		log.Printf("[notifyAcheteurDepot] récupération cmd %d: %v", idCommande, err)
		return
	}
	lieu := strings.TrimSpace(adresse)
	if ville != "" {
		if lieu != "" {
			lieu += ", "
		}
		lieu += ville
	}
	subject := "Votre objet est prêt à être récupéré — " + titre
	body := "Bonjour,\n\nVotre commande \"" + titre + "\" est arrivée dans le conteneur " + conteneurRef
	if lieu != "" {
		body += " (" + lieu + ")"
	}
	body += ".\n"
	if limite != "" {
		body += "Vous avez jusqu'au " + limite + " pour la récupérer.\n"
	}
	body += "\nRendez-vous dans votre espace pro (Mes conteneurs) pour valider la réception avec le code-barre.\n\nL'équipe UpcycleConnect"
	if err := services.SendSimpleEmail(email, subject, body); err != nil {
		log.Printf("[notifyAcheteurDepot] envoi email: %v", err)
	}

	// Push OneSignal, ciblé par External ID (= id acheteur).
	pushBody := "« " + titre + " » est arrivé dans le conteneur " + conteneurRef
	if lieu != "" {
		pushBody += " (" + lieu + ")"
	}
	if err := services.SendPushToExternalIDs(
		[]string{strconv.Itoa(idAcheteur)},
		"Votre objet est prêt à être récupéré",
		pushBody,
		map[string]string{"type": "depot_arrive", "id_commande": strconv.Itoa(idCommande)},
	); err != nil {
		log.Printf("[notifyAcheteurDepot] envoi push: %v", err)
	}
}

// loadPhotosConteneur : photos ordonnées d'un conteneur.
func loadPhotosConteneur(idConteneur int) []models.PhotoConteneur {
	photos := []models.PhotoConteneur{}
	rows, err := database.DB.Query(
		"SELECT id_photo, url_photo FROM photos_conteneurs WHERE id_conteneur = ? ORDER BY ordre, id_photo", idConteneur)
	if err != nil {
		return photos
	}
	defer rows.Close()
	for rows.Next() {
		var p models.PhotoConteneur
		if rows.Scan(&p.IDPhoto, &p.URL) == nil {
			photos = append(photos, p)
		}
	}
	return photos
}

// --- CRUD conteneurs ---

// GetAllConteneurs : tous les conteneurs avec leurs photos.
func GetAllConteneurs(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_conteneur, conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut FROM conteneurs")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	conteneurs := []models.Conteneur{}
	for rows.Next() {
		var c models.Conteneur
		if err := rows.Scan(&c.IDConteneur, &c.ConteneurRef, &c.Adresse, &c.Ville, &c.CodePostal, &c.Latitude, &c.Longitude, &c.Capacite, &c.Statut); err == nil {
			conteneurs = append(conteneurs, c)
		}
	}
	for i := range conteneurs {
		conteneurs[i].Photos = loadPhotosConteneur(conteneurs[i].IDConteneur)
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(conteneurs)
}

// CreateConteneur : crée un conteneur (statut « actif ») + photos.
func CreateConteneur(w http.ResponseWriter, r *http.Request) {
	var req models.CreateConteneurRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `INSERT INTO conteneurs (conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut)
	          VALUES (?, ?, ?, ?, ?, ?, ?, 'actif')`
	result, err := database.DB.Exec(query, req.ConteneurRef, req.Adresse, req.Ville, req.CodePostal,
		req.Latitude, req.Longitude, req.Capacite)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer le conteneur"})
		return
	}

	id, _ := result.LastInsertId()
	insertPhotosConteneur(int(id), req.Images)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "conteneur créé", "id_conteneur": id})
}

// insertPhotosConteneur : ajoute des photos, en continuant l'ordre existant.
func insertPhotosConteneur(idConteneur int, urls []string) {
	if len(urls) == 0 {
		return
	}
	var maxOrdre int
	database.DB.QueryRow("SELECT COALESCE(MAX(ordre), -1) FROM photos_conteneurs WHERE id_conteneur = ?", idConteneur).Scan(&maxOrdre) //nolint:errcheck
	for _, u := range urls {
		if u == "" {
			continue
		}
		maxOrdre++
		database.DB.Exec("INSERT INTO photos_conteneurs (id_conteneur, url_photo, ordre) VALUES (?, ?, ?)", idConteneur, u, maxOrdre) //nolint:errcheck
	}
}

// UpdateConteneur : maj des champs + AJOUTE les photos fournies
// (suppression des existantes via DeleteConteneurPhoto).
func UpdateConteneur(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		ConteneurRef string   `json:"conteneur_ref"`
		Adresse      string   `json:"adresse"`
		Ville        string   `json:"ville"`
		CodePostal   *string  `json:"code_postal"`
		Latitude     *float64 `json:"latitude"`
		Longitude    *float64 `json:"longitude"`
		Images       []string `json:"images"`
		Capacite     int      `json:"capacite"`
		Statut       string   `json:"statut"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	statuts := map[string]bool{"actif": true, "plein": true, "maintenance": true, "hors_service": true}
	if !statuts[req.Statut] {
		req.Statut = "actif"
	}

	_, err := database.DB.Exec(`
		UPDATE conteneurs
		SET conteneur_ref = ?, adresse = ?, ville = ?, code_postal = ?,
		    latitude = ?, longitude = ?, capacite = ?, statut = ?
		WHERE id_conteneur = ?`,
		req.ConteneurRef, req.Adresse, req.Ville, req.CodePostal,
		req.Latitude, req.Longitude, req.Capacite, req.Statut, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de mettre à jour le conteneur"})
		return
	}

	if idInt, convErr := strconv.Atoi(id); convErr == nil {
		insertPhotosConteneur(idInt, req.Images)
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "conteneur mis à jour"})
}

// DeleteConteneurPhoto : supprime une photo de la galerie.
func DeleteConteneurPhoto(w http.ResponseWriter, r *http.Request, photoID string) {
	_, err := database.DB.Exec("DELETE FROM photos_conteneurs WHERE id_photo = ?", photoID)
	w.Header().Set("Content-Type", "application/json")
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de supprimer la photo"})
		return
	}
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "photo supprimée"})
}

// GetConteneurDetails : commandes, tickets et photos d'un conteneur.
func GetConteneurDetails(w http.ResponseWriter, r *http.Request, id string) {

	var commandes []models.CommandeConteneur
	rowsCmd, _ := database.DB.Query("SELECT id_commande, id_annonce, id_acheteur, id_conteneur, statut FROM commandes WHERE id_conteneur = ?", id)
	for rowsCmd != nil && rowsCmd.Next() {
		var c models.CommandeConteneur
		rowsCmd.Scan(&c.IDCommande, &c.IDAnnonce, &c.IDAcheteur, &c.IDConteneur, &c.Statut)
		commandes = append(commandes, c)
	}
	if rowsCmd != nil {
		rowsCmd.Close()
	}

	var tickets []models.TicketIncident
	rowsTck, _ := database.DB.Query("SELECT id_ticket, sujet, description, statut, date_creation FROM tickets_incidents WHERE id_conteneur = ?", id)
	for rowsTck != nil && rowsTck.Next() {
		var t models.TicketIncident
		rowsTck.Scan(&t.IDTicket, &t.Sujet, &t.Description, &t.Statut, &t.DateCreation)
		tickets = append(tickets, t)
	}
	if rowsTck != nil {
		rowsTck.Close()
	}

	photos := []models.PhotoConteneur{}
	if idInt, err := strconv.Atoi(id); err == nil {
		photos = loadPhotosConteneur(idInt)
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"commandes": commandes,
		"tickets":   tickets,
		"photos":    photos,
	})
}

// --- Codes-barres (dépôt / récupération) ---

// CreateCodeBarre : enregistre un code-barre lié à une commande.
func CreateCodeBarre(w http.ResponseWriter, r *http.Request) {
	var req models.CreateCodeBarreRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	query := `INSERT INTO codes_barres (id_commande, code_valeur, type_code, pdf_url, date_creation) VALUES (?, ?, ?, ?, ?)`
	_, err := database.DB.Exec(query, req.IDCommande, req.CodeValeur, req.TypeCode, req.PdfUrl, time.Now())
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur insertion"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]string{"message": "code création succès"})
}

// ScanBarcodeAndUpdateCommande : traite un scan et fait avancer la commande
// selon le type de code (dépôt particulier ou récupération pro).
func ScanBarcodeAndUpdateCommande(w http.ResponseWriter, r *http.Request) {
	var req struct {
		CodeValeur string `json:"code_valeur"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Bad request", http.StatusBadRequest)
		return
	}

	var idCodeBarre, idCommande int
	var typeCode string
	err := database.DB.QueryRow("SELECT id_code_barre, id_commande, type_code FROM codes_barres WHERE code_valeur = ? AND date_utilisation IS NULL", req.CodeValeur).Scan(&idCodeBarre, &idCommande, &typeCode)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "code barre invalide ou déjà utilisé"})
		return
	}

	var newStatut string
	switch typeCode {
	case "depot_particulier":
		// Dépôt particulier : l'objet entre en conteneur, on notifie le pro.
		newStatut = "en_conteneur"
		database.DB.Exec("UPDATE codes_barres SET date_utilisation = ? WHERE id_code_barre = ?", time.Now(), idCodeBarre) //nolint:errcheck
		database.DB.Exec("UPDATE commandes SET statut = 'en_conteneur' WHERE id_commande = ?", idCommande)                 //nolint:errcheck
		go func(cmdID int) {
			var playerID, conteneurRef string
			database.DB.QueryRow(`
				SELECT COALESCE(u.onesignal_player_id,''), COALESCE(cn.conteneur_ref,'')
				FROM commandes c
				JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
				LEFT JOIN conteneurs cn ON c.id_conteneur = cn.id_conteneur
				WHERE c.id_commande = ?`, cmdID).Scan(&playerID, &conteneurRef) //nolint:errcheck
			if playerID != "" {
				services.NotifierObjetsEnConteneur(playerID, cmdID, conteneurRef)
			}
		}(idCommande)
		// Email de complément (indispensable en local).
		go notifyAcheteurDepot(idCommande)

	case "recuperation_pro":
		// Récupération normalement en self-scan pro ; ici l'admin peut la forcer (litige).
		// Délégué à finaliserRecuperation (même point unique) : statut + score + badges.
		newStatut = "recuperee"
		var acheteurID int
		database.DB.QueryRow("SELECT id_acheteur FROM commandes WHERE id_commande = ?", idCommande).Scan(&acheteurID) //nolint:errcheck
		if err := finaliserRecuperation(idCommande, acheteurID, req.CodeValeur); err != nil {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la finalisation"})
			return
		}

	default:
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "type de code inconnu"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "commande mise à jour", "nouveau_statut": newStatut})
}

// --- Tickets d'incident & miroir GLPI ---

// ResolveTicket : marque le ticket résolu et propage à GLPI.
func ResolveTicket(w http.ResponseWriter, r *http.Request, id string) {
	query := `UPDATE tickets_incidents SET statut = 'resolu', date_resolution = ? WHERE id_ticket = ?`
	_, err := database.DB.Exec(query, time.Now(), id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	// Miroir GLPI : passe le ticket en « résolu » côté GLPI.
	go syncTicketResoluGLPI(id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "ticket résolu"})
}

// mirrorSignalementToGLPI : crée un ticket GLPI pour un signalement forum et stocke l'id. No-op si GLPI non configuré.
func mirrorSignalementToGLPI(sigID int64, idMessage int, motif string) {
	if !glpi.Configured() {
		return
	}
	sujet := "Signalement forum — message #" + strconv.Itoa(idMessage)
	desc := "Motif : " + motif
	glpiID, err := glpi.CreateTicket(sujet, desc)
	if err != nil {
		log.Printf("[GLPI] signalement forum #%d : %v", sigID, err)
		return
	}
	if glpiID != "" {
		database.DB.Exec("UPDATE signalements_forum SET glpi_ticket_id = ? WHERE id_signalement = ?", glpiID, sigID) //nolint:errcheck
	}
}

// syncTicketResoluGLPI : propage la résolution à GLPI si le ticket y est miroité.
func syncTicketResoluGLPI(id string) {
	var glpiID string
	if err := database.DB.QueryRow(
		"SELECT COALESCE(glpi_ticket_id, '') FROM tickets_incidents WHERE id_ticket = ?", id).Scan(&glpiID); err != nil {
		return
	}
	if err := glpi.UpdateTicketStatus(glpiID, glpi.StatusSolved); err != nil {
		log.Printf("[GLPI] maj statut ticket %s : %v", id, err)
	}
}

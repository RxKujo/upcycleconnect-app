package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"crypto/rand"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)

func GetAnnonces(w http.ResponseWriter, r *http.Request) {
	log.Printf("[INFO] %s | GetAnnonces | Listing all annonces\n", time.Now().Format(time.RFC3339))

	rows, err := database.DB.Query("SELECT id_annonce, id_particulier, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par FROM annonces WHERE statut != 'supprimee' ORDER BY date_creation DESC")
	if err != nil {
		log.Printf("[ERROR] %s | GetAnnonces | Query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var annonces []models.Annonce
	for rows.Next() {
		var a models.Annonce
		var prix sql.NullFloat64
		var motifRefus, motifRetrait sql.NullString
		var validePar sql.NullInt64

		if err := rows.Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar); err == nil {
			if prix.Valid {
				p := prix.Float64
				a.Prix = &p
			}
			if motifRefus.Valid {
				mr := motifRefus.String
				a.MotifRefus = &mr
			}
			if motifRetrait.Valid {
				mt := motifRetrait.String
				a.MotifRetrait = &mt
			}
			if validePar.Valid {
				v := int(validePar.Int64)
				a.ValidePar = &v
			}
			annonces = append(annonces, a)
		} else {
			log.Printf("[ERROR] %s | GetAnnonces | Scan err: %v\n", time.Now().Format(time.RFC3339), err)
		}
	}
	if annonces == nil {
		annonces = []models.Annonce{}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(annonces)
}

func GetAnnonceAuth(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	log.Printf("[INFO] %s | GetAnnonce | User %d viewing annonce %s\n", time.Now().Format(time.RFC3339), userId, id)

	var a models.Annonce
	var prix sql.NullFloat64
	var motifRefus, motifRetrait sql.NullString
	var validePar sql.NullInt64

	err := database.DB.QueryRow("SELECT id_annonce, id_particulier, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par FROM annonces WHERE id_annonce = ?", id).
		Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar)
	if err != nil {
		log.Printf("[ERROR] %s | GetAnnonce | Not found: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "annonce non trouvee"})
		return
	}

	// Particulier can only see their own annonce
	if role != "admin" && a.IDParticulier != userId {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "acces refuse"})
		return
	}

	if prix.Valid {
		p := prix.Float64
		a.Prix = &p
	}
	if motifRefus.Valid {
		mr := motifRefus.String
		a.MotifRefus = &mr
	}
	if motifRetrait.Valid {
		mt := motifRetrait.String
		a.MotifRetrait = &mt
	}
	if validePar.Valid {
		v := int(validePar.Int64)
		a.ValidePar = &v
	}

	// Load objects
	objRows, err := database.DB.Query("SELECT id_objet, id_annonce, categorie, materiau, etat, poids_kg FROM objets_annonces WHERE id_annonce = ?", id)
	if err == nil {
		defer objRows.Close()
		for objRows.Next() {
			var o models.ObjetAnnonce
			var poids sql.NullFloat64
			if err := objRows.Scan(&o.IDObjet, &o.IDAnnonce, &o.Categorie, &o.Materiau, &o.Etat, &poids); err == nil {
				if poids.Valid {
					p := poids.Float64
					o.PoidsKg = &p
				}
				// Load photos for this object
				photoRows, err := database.DB.Query("SELECT id_photo, id_objet, url, ordre FROM photos_objets WHERE id_objet = ? ORDER BY ordre", o.IDObjet)
				if err == nil {
					for photoRows.Next() {
						var ph models.PhotoObjet
						if err := photoRows.Scan(&ph.IDPhoto, &ph.IDObjet, &ph.URL, &ph.Ordre); err == nil {
							o.Photos = append(o.Photos, ph)
						}
					}
					photoRows.Close()
				}
				if o.Photos == nil {
					o.Photos = []models.PhotoObjet{}
				}
				a.Objets = append(a.Objets, o)
			}
		}
	}
	if a.Objets == nil {
		a.Objets = []models.ObjetAnnonce{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(a)
}

func GetAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	var a models.Annonce
	var prix sql.NullFloat64
	var motifRefus, motifRetrait sql.NullString
	var validePar sql.NullInt64

	err := database.DB.QueryRow("SELECT id_annonce, id_particulier, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par FROM annonces WHERE id_annonce = ?", id).
		Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "annonce non trouvee"})
		return
	}

	if prix.Valid {
		p := prix.Float64
		a.Prix = &p
	}
	if motifRefus.Valid {
		mr := motifRefus.String
		a.MotifRefus = &mr
	}
	if motifRetrait.Valid {
		mt := motifRetrait.String
		a.MotifRetrait = &mt
	}
	if validePar.Valid {
		v := int(validePar.Int64)
		a.ValidePar = &v
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(a)
}

func CreateAnnonce(w http.ResponseWriter, r *http.Request, userId int) {
	log.Printf("[INFO] %s | CreateAnnonce | User %d creating annonce\n", time.Now().Format(time.RFC3339), userId)

	var req models.CreateAnnonceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		log.Printf("[ERROR] %s | CreateAnnonce | Decode err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	// Validate titre
	if len(req.Titre) < 3 || len(req.Titre) > 200 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "le titre doit contenir entre 3 et 200 caracteres"})
		return
	}

	// Validate description
	if len(req.Description) < 10 || len(req.Description) > 5000 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "la description doit contenir entre 10 et 5000 caracteres"})
		return
	}

	// Validate type_annonce
	if req.TypeAnnonce != "don" && req.TypeAnnonce != "vente" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "type_annonce doit etre 'don' ou 'vente'"})
		return
	}

	// Validate mode_remise
	if req.ModeRemise != "conteneur" && req.ModeRemise != "main_propre" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "mode_remise doit etre 'conteneur' ou 'main_propre'"})
		return
	}

	// At least 1 object required
	if len(req.Objets) == 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "au moins un objet est requis"})
		return
	}

	// Validate objects and count total photos
	validMateriaux := map[string]bool{"bois": true, "metal": true, "textile": true, "plastique": true, "verre": true, "electronique": true, "autre": true}
	validEtats := map[string]bool{"neuf": true, "bon": true, "use": true, "a_reparer": true}
	totalPhotos := 0

	for i, obj := range req.Objets {
		if !validMateriaux[obj.Materiau] {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": fmt.Sprintf("materiau invalide pour l'objet %d", i+1)})
			return
		}
		if !validEtats[obj.Etat] {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": fmt.Sprintf("etat invalide pour l'objet %d", i+1)})
			return
		}
		if len(obj.Photos) == 0 {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": fmt.Sprintf("au moins une photo requise pour l'objet %d", i+1)})
			return
		}
		totalPhotos += len(obj.Photos)
	}

	if totalPhotos > 10 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "maximum 10 photos par annonce"})
		return
	}

	// Begin transaction
	tx, err := database.DB.Begin()
	if err != nil {
		log.Printf("[ERROR] %s | CreateAnnonce | TX begin err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	// Insert annonce
	res, err := tx.Exec(`INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut) VALUES (?, ?, ?, ?, ?, ?, 'en_attente')`,
		userId, req.Titre, req.Description, req.TypeAnnonce, req.Prix, req.ModeRemise)
	if err != nil {
		tx.Rollback()
		log.Printf("[ERROR] %s | CreateAnnonce | Insert annonce err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la creation de l'annonce"})
		return
	}

	annonceId, _ := res.LastInsertId()

	// Ensure upload dir exists
	uploadDir := getUploadDir()
	os.MkdirAll(uploadDir, 0755)

	photoOrdre := 0
	for _, obj := range req.Objets {
		objRes, err := tx.Exec(`INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (?, ?, ?, ?, ?)`,
			annonceId, obj.Categorie, obj.Materiau, obj.Etat, obj.PoidsKg)
		if err != nil {
			tx.Rollback()
			log.Printf("[ERROR] %s | CreateAnnonce | Insert objet err: %v\n", time.Now().Format(time.RFC3339), err)
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de l'ajout d'un objet"})
			return
		}
		objetId, _ := objRes.LastInsertId()

		for _, photoB64 := range obj.Photos {
			photoOrdre++

			// Decode base64 and detect type
			ext, data, err := decodeBase64Image(photoB64)
			if err != nil {
				tx.Rollback()
				log.Printf("[ERROR] %s | CreateAnnonce | Photo decode err: %v\n", time.Now().Format(time.RFC3339), err)
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusBadRequest)
				json.NewEncoder(w).Encode(map[string]string{"erreur": fmt.Sprintf("photo invalide: %v", err)})
				return
			}

			if len(data) > 5*1024*1024 {
				tx.Rollback()
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusBadRequest)
				json.NewEncoder(w).Encode(map[string]string{"erreur": "une photo depasse 5 Mo"})
				return
			}

			filename := generateUUID() + "." + ext
			filePath := filepath.Join(uploadDir, filename)

			if err := os.WriteFile(filePath, data, 0644); err != nil {
				tx.Rollback()
				log.Printf("[ERROR] %s | CreateAnnonce | File write err: %v\n", time.Now().Format(time.RFC3339), err)
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusInternalServerError)
				json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur sauvegarde photo"})
				return
			}

			photoURL := "photos/" + filename
			_, err = tx.Exec(`INSERT INTO photos_objets (id_objet, url_photo, ordre) VALUES (?, ?, ?)`, objetId, photoURL, photoOrdre)
			if err != nil {
				tx.Rollback()
				log.Printf("[ERROR] %s | CreateAnnonce | Insert photo err: %v\n", time.Now().Format(time.RFC3339), err)
				w.Header().Set("Content-Type", "application/json")
				w.WriteHeader(http.StatusInternalServerError)
				json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur enregistrement photo"})
				return
			}
		}
	}

	if err := tx.Commit(); err != nil {
		log.Printf("[ERROR] %s | CreateAnnonce | Commit err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	log.Printf("[INFO] %s | CreateAnnonce | User %d created annonce %d\n", time.Now().Format(time.RFC3339), userId, annonceId)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":    "annonce creee avec succes",
		"id_annonce": annonceId,
		"statut":     "en_attente",
	})
}

func CancelAnnonce(w http.ResponseWriter, r *http.Request, id string, userId int) {
	log.Printf("[INFO] %s | CancelAnnonce | User %d cancelling annonce %s\n", time.Now().Format(time.RFC3339), userId, id)

	var req models.CancelAnnonceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "donnees invalides"})
		return
	}

	// Check annonce exists and belongs to user
	var annonceUserId int
	var statut string
	err := database.DB.QueryRow("SELECT id_particulier, statut FROM annonces WHERE id_annonce = ?", id).Scan(&annonceUserId, &statut)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "annonce non trouvee"})
		return
	}

	if annonceUserId != userId {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "acces refuse"})
		return
	}

	if statut != "en_attente" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "seules les annonces en attente peuvent etre annulees"})
		return
	}

	_, err = database.DB.Exec("UPDATE annonces SET statut = 'annulee', motif_retrait = ? WHERE id_annonce = ?", req.MotifRetrait, id)
	if err != nil {
		log.Printf("[ERROR] %s | CancelAnnonce | Update err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	log.Printf("[INFO] %s | CancelAnnonce | User %d cancelled annonce %s\n", time.Now().Format(time.RFC3339), userId, id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message": "annonce annulee",
		"statut":  "annulee",
	})
}

func DeleteAnnonce(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	log.Printf("[INFO] %s | DeleteAnnonce | User %d deleting annonce %s\n", time.Now().Format(time.RFC3339), userId, id)

	var annonceUserId int
	var statut string
	err := database.DB.QueryRow("SELECT id_particulier, statut FROM annonces WHERE id_annonce = ?", id).Scan(&annonceUserId, &statut)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "annonce non trouvee"})
		return
	}

	// Only creator or admin can delete
	if role != "admin" && annonceUserId != userId {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "acces refuse"})
		return
	}

	if statut != "validee" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "seules les annonces validees peuvent etre supprimees"})
		return
	}

	// Delete photos from disk
	uploadDir := getUploadDir()
	rows, err := database.DB.Query(`SELECT po.url_photo FROM photos_objets po JOIN objets_annonces oa ON po.id_objet = oa.id_objet WHERE oa.id_annonce = ?`, id)
	if err == nil {
		defer rows.Close()
		for rows.Next() {
			var url string
			if rows.Scan(&url) == nil {
				filePath := filepath.Join(uploadDir, filepath.Base(url))
				os.Remove(filePath)
			}
		}
	}

	// Soft delete
	_, err = database.DB.Exec("UPDATE annonces SET statut = 'supprimee' WHERE id_annonce = ?", id)
	if err != nil {
		log.Printf("[ERROR] %s | DeleteAnnonce | Update err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	log.Printf("[INFO] %s | DeleteAnnonce | User %d deleted annonce %s\n", time.Now().Format(time.RFC3339), userId, id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce supprimee"})
}

func ValiderAnnonce(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	tx, err := database.DB.Begin()
	if err != nil {
		http.Error(w, "DB error", http.StatusInternalServerError)
		return
	}

	var idParticulier int
	var titre, modeRemise string
	err = tx.QueryRow("SELECT id_particulier, titre, mode_remise FROM annonces WHERE id_annonce = ?", id).Scan(&idParticulier, &titre, &modeRemise)
	if err != nil {
		tx.Rollback()
		http.Error(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	_, err = tx.Exec("UPDATE annonces SET statut = 'validee', valide_par = ?, motif_refus = NULL WHERE id_annonce = ?", adminId, id)
	if err != nil {
		tx.Rollback()
		http.Error(w, "erreur maj", http.StatusInternalServerError)
		return
	}

	sujetNotif := "Votre annonce a ete validee !"
	contenuNotif := fmt.Sprintf("Excellente nouvelle, votre annonce \"%s\" est maintenant en ligne.", titre)
	if modeRemise == "conteneur" {
		contenuNotif += " Un code-barre vous sera transmis pour le depot."
	}
	_, _ = tx.Exec("INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')",
		idParticulier, sujetNotif, contenuNotif)

	tx.Commit()

	requiresBarcode := modeRemise == "conteneur"

	log.Printf("[INFO] %s | ValiderAnnonce | Admin %d validated annonce %s\n", time.Now().Format(time.RFC3339), adminId, id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":          "annonce validee",
		"requires_barcode": requiresBarcode,
	})
}

func RefuserAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	var req models.AnnonceValidationRequest
	_ = json.NewDecoder(r.Body).Decode(&req)

	tx, _ := database.DB.Begin()
	var idParticulier int
	var titre string
	err := tx.QueryRow("SELECT id_particulier, titre FROM annonces WHERE id_annonce = ?", id).Scan(&idParticulier, &titre)
	if err != nil {
		tx.Rollback()
		http.Error(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	motif := "Non conforme aux regles de la plateforme."
	if req.MotifRefus != nil && *req.MotifRefus != "" {
		motif = *req.MotifRefus
	}

	_, err = tx.Exec("UPDATE annonces SET statut = 'refusee', motif_refus = ? WHERE id_annonce = ?", motif, id)
	if err != nil {
		tx.Rollback()
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	sujetNotif := "Votre annonce n'a pas ete validee"
	contenuNotif := fmt.Sprintf("Votre annonce \"%s\" a ete refusee pour le motif suivant : %s", titre, motif)
	_, _ = tx.Exec("INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')",
		idParticulier, sujetNotif, contenuNotif)

	tx.Commit()

	log.Printf("[INFO] %s | RefuserAnnonce | Annonce %s refused\n", time.Now().Format(time.RFC3339), id)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce refusee"})
}

func AttenteAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE annonces SET statut = 'en_attente', valide_par = NULL, motif_refus = NULL WHERE id_annonce = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce remise en attente"})
}

// Helper: get upload directory
func getUploadDir() string {
	dir := os.Getenv("UPLOAD_DIR")
	if dir == "" {
		dir = "../web/public/uploads/photos"
	}
	return dir
}

// Helper: decode base64 image
func decodeBase64Image(b64 string) (string, []byte, error) {
	// Handle data URI scheme
	if strings.HasPrefix(b64, "data:image/") {
		parts := strings.SplitN(b64, ",", 2)
		if len(parts) != 2 {
			return "", nil, fmt.Errorf("format base64 invalide")
		}
		header := parts[0] // e.g. data:image/jpeg;base64
		b64 = parts[1]

		ext := "jpg"
		if strings.Contains(header, "image/png") {
			ext = "png"
		} else if strings.Contains(header, "image/webp") {
			ext = "webp"
		} else if strings.Contains(header, "image/jpeg") || strings.Contains(header, "image/jpg") {
			ext = "jpg"
		}

		data, err := base64.StdEncoding.DecodeString(b64)
		if err != nil {
			// Try with padding
			data, err = base64.RawStdEncoding.DecodeString(b64)
			if err != nil {
				return "", nil, fmt.Errorf("decodage base64 echoue: %v", err)
			}
		}
		return ext, data, nil
	}

	// Raw base64 without header - try to detect from magic bytes
	data, err := base64.StdEncoding.DecodeString(b64)
	if err != nil {
		data, err = base64.RawStdEncoding.DecodeString(b64)
		if err != nil {
			return "", nil, fmt.Errorf("decodage base64 echoue: %v", err)
		}
	}

	ext := "jpg" // default
	if len(data) >= 4 {
		if data[0] == 0x89 && data[1] == 0x50 {
			ext = "png"
		} else if data[0] == 0x52 && data[1] == 0x49 {
			ext = "webp"
		}
	}

	return ext, data, nil
}

// generateUUID creates a UUID v4 string without external dependency
func generateUUID() string {
	b := make([]byte, 16)
	rand.Read(b)
	b[6] = (b[6] & 0x0f) | 0x40
	b[8] = (b[8] & 0x3f) | 0x80
	return fmt.Sprintf("%08x-%04x-%04x-%04x-%012x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:16])
}

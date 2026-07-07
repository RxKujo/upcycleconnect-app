package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"api/pkg/storage"
	"crypto/rand"
	"database/sql"
	"encoding/base64"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
	"strings"
)

// loadAnnonceWithObjets charge une annonce complète (objets + photos) en deux
// requêtes au lieu de N+1 : une pour l'annonce, une JOIN pour objets+photos.
func loadAnnonceWithObjets(id string) (*models.Annonce, error) {
	var a models.Annonce
	var prix sql.NullFloat64
	var motifRefus, motifRetrait, adresseRemise sql.NullString
	var validePar, idConteneur sql.NullInt64
	// Champs du conteneur lié (LEFT JOIN, donc nullable).
	var cRef, cAdr, cVille, cCP sql.NullString
	var cLat, cLng sql.NullFloat64

	err := database.DB.QueryRow(`
		SELECT a.id_annonce, a.id_particulier, a.titre, a.description, a.type_annonce,
		       a.prix, a.mode_remise, a.id_conteneur, a.adresse_remise, a.statut,
		       a.motif_refus, a.motif_retrait, a.date_creation, a.valide_par,
		       c.conteneur_ref, c.adresse, c.ville, c.code_postal, c.latitude, c.longitude
		FROM annonces a
		LEFT JOIN conteneurs c ON c.id_conteneur = a.id_conteneur
		WHERE a.id_annonce = ?`, id).
		Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce,
			&prix, &a.ModeRemise, &idConteneur, &adresseRemise, &a.Statut,
			&motifRefus, &motifRetrait, &a.DateCreation, &validePar,
			&cRef, &cAdr, &cVille, &cCP, &cLat, &cLng)
	if err != nil {
		return nil, err
	}

	a.Prix = scanNullFloat64(prix)
	a.MotifRefus = scanNullString(motifRefus)
	a.MotifRetrait = scanNullString(motifRetrait)
	a.ValidePar = scanNullInt(validePar)
	a.AdresseRemise = scanNullString(adresseRemise)
	if idConteneur.Valid {
		idc := int(idConteneur.Int64)
		a.IDConteneur = &idc
		if cAdr.Valid {
			a.Conteneur = &models.ConteneurInfo{
				IDConteneur: idc,
				Ref:         cRef.String,
				Adresse:     cAdr.String,
				Ville:       cVille.String,
				CodePostal:  scanNullString(cCP),
				Latitude:    scanNullFloat64(cLat),
				Longitude:   scanNullFloat64(cLng),
			}
		}
	}

	// Une seule JOIN charge tous les objets ET toutes leurs photos.
	rows, err := database.DB.Query(`
		SELECT o.id_objet, o.categorie, o.materiau, o.etat, o.poids_kg,
		       p.id_photo, p.url_photo, p.ordre
		FROM objets_annonces o
		LEFT JOIN photos_objets p ON p.id_objet = o.id_objet
		WHERE o.id_annonce = ?
		ORDER BY o.id_objet, p.ordre`, id)
	if err != nil {
		a.Objets = []models.ObjetAnnonce{}
		return &a, nil
	}
	defer rows.Close()

	objMap := map[int]*models.ObjetAnnonce{}
	var objOrder []int
	for rows.Next() {
		var oid int
		var cat, mat, etat string
		var poids sql.NullFloat64
		var pid sql.NullInt64
		var photoURL sql.NullString
		var photoOrdre sql.NullInt64

		if err := rows.Scan(&oid, &cat, &mat, &etat, &poids, &pid, &photoURL, &photoOrdre); err != nil {
			continue
		}
		if _, exists := objMap[oid]; !exists {
			objMap[oid] = &models.ObjetAnnonce{
				IDObjet:   oid,
				IDAnnonce: a.IDAnnonce,
				Categorie: cat,
				Materiau:  mat,
				Etat:      etat,
				PoidsKg:   scanNullFloat64(poids),
				Photos:    []models.PhotoObjet{},
			}
			objOrder = append(objOrder, oid)
		}
		if pid.Valid && photoURL.Valid {
			objMap[oid].Photos = append(objMap[oid].Photos, models.PhotoObjet{
				IDPhoto: int(pid.Int64),
				IDObjet: oid,
				URL:     photoURL.String,
				Ordre:   int(photoOrdre.Int64),
			})
		}
	}

	a.Objets = make([]models.ObjetAnnonce, 0, len(objOrder))
	for _, oid := range objOrder {
		a.Objets = append(a.Objets, *objMap[oid])
	}
	return &a, nil
}

// ─── Constantes de messages d'erreur ─────────────────────────────────────────

const (
	errServeur       = "erreur serveur"
	errDonneesInval  = "données invalides"
	errAnnonceIntro  = "annonce non trouvée"
	errAccesRefuse   = "accès refusé"
	logFmtAnnonceUser = "user=%d annonce=%s"
	logFmtUpdate     = "update: %v"
)

// ─── Handlers ────────────────────────────────────────────────────────────────

func GetAnnonces(w http.ResponseWriter, r *http.Request) {
	logInfo("GetAnnonces", "listing all")

	rows, err := database.DB.Query(`
		SELECT id_annonce, id_particulier, titre, description, type_annonce,
		       prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par
		FROM annonces WHERE statut != 'supprimee' ORDER BY date_creation DESC`)
	if err != nil {
		logError("GetAnnonces", "query: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	annonces := []models.Annonce{}
	for rows.Next() {
		var a models.Annonce
		var prix sql.NullFloat64
		var motifRefus, motifRetrait sql.NullString
		var validePar sql.NullInt64

		if err := rows.Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce,
			&prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar); err != nil {
			logError("GetAnnonces", "scan: %v", err)
			continue
		}
		a.Prix = scanNullFloat64(prix)
		a.MotifRefus = scanNullString(motifRefus)
		a.MotifRetrait = scanNullString(motifRetrait)
		a.ValidePar = scanNullInt(validePar)
		annonces = append(annonces, a)
	}
	jsonOK(w, annonces, http.StatusOK)
}

func GetMesAnnonces(w http.ResponseWriter, r *http.Request, userId int) {
	logInfo("GetMesAnnonces", "user=%d", userId)

	rows, err := database.DB.Query(`
		SELECT id_annonce, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, date_creation
		FROM annonces WHERE id_particulier = ? AND statut != 'supprimee' ORDER BY date_creation DESC`, userId)
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type mesAnnonceItem struct {
		IDAnnonce    int     `json:"id_annonce"`
		Titre        string  `json:"titre"`
		Description  string  `json:"description"`
		TypeAnnonce  string  `json:"type_annonce"`
		Prix         *float64 `json:"prix,omitempty"`
		ModeRemise   string  `json:"mode_remise"`
		Statut       string  `json:"statut"`
		MotifRefus   *string `json:"motif_refus,omitempty"`
		Photo        *string `json:"photo,omitempty"`
		DateCreation string  `json:"date_creation"`
	}

	items := []mesAnnonceItem{}
	var ids []interface{}
	for rows.Next() {
		var it mesAnnonceItem
		var prix sql.NullFloat64
		var motifRefus sql.NullString
		var dateCreation sql.NullTime
		if err := rows.Scan(&it.IDAnnonce, &it.Titre, &it.Description, &it.TypeAnnonce,
			&prix, &it.ModeRemise, &it.Statut, &motifRefus, &dateCreation); err != nil {
			continue
		}
		it.Prix = scanNullFloat64(prix)
		it.MotifRefus = scanNullString(motifRefus)
		it.DateCreation = scanNullTime(dateCreation)
		items = append(items, it)
		ids = append(ids, it.IDAnnonce)
	}

	// Charge la première photo de chaque annonce en UNE seule requête (fix N+1).
	if len(ids) > 0 {
		phs := strings.Repeat("?,", len(ids))
		phs = phs[:len(phs)-1]
		photoMap := map[int]string{}
		photoRows, err := database.DB.Query(fmt.Sprintf(`
			SELECT oa.id_annonce, MIN(po.url_photo)
			FROM photos_objets po
			JOIN objets_annonces oa ON po.id_objet = oa.id_objet
			WHERE oa.id_annonce IN (%s)
			GROUP BY oa.id_annonce`, phs), ids...)
		if err == nil {
			defer photoRows.Close()
			for photoRows.Next() {
				var aid int
				var url string
				if photoRows.Scan(&aid, &url) == nil {
					photoMap[aid] = url
				}
			}
		}
		for i := range items {
			if url, ok := photoMap[items[i].IDAnnonce]; ok {
				u := url
				items[i].Photo = &u
			}
		}
	}

	jsonOK(w, items, http.StatusOK)
}

func GetAnnonceAuth(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	logInfo("GetAnnonceAuth", logFmtAnnonceUser, userId, id)

	a, err := loadAnnonceWithObjets(id)
	if err != nil {
		jsonErr(w, errAnnonceIntro, http.StatusNotFound)
		return
	}
	if role != "admin" && a.IDParticulier != userId {
		jsonErr(w, errAccesRefuse, http.StatusForbidden)
		return
	}
	jsonOK(w, a, http.StatusOK)
}

func GetAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	a, err := loadAnnonceWithObjets(id)
	if err != nil {
		jsonErr(w, errAnnonceIntro, http.StatusNotFound)
		return
	}
	jsonOK(w, a, http.StatusOK)
}

// conteneurActif vérifie qu'un conteneur existe et est en statut 'actif'.
func conteneurActif(id int) bool {
	var x int
	err := database.DB.QueryRow(
		"SELECT 1 FROM conteneurs WHERE id_conteneur = ? AND statut = 'actif' LIMIT 1", id).Scan(&x)
	return err == nil
}

func CreateAnnonce(w http.ResponseWriter, r *http.Request, userId int) {
	logInfo("CreateAnnonce", "user=%d", userId)

	var req models.CreateAnnonceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		logError("CreateAnnonce", "decode: %v", err)
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}

	if len(req.Titre) < 3 || len(req.Titre) > 200 {
		jsonErr(w, "le titre doit contenir entre 3 et 200 caractères", http.StatusBadRequest)
		return
	}
	if len(req.Description) < 10 || len(req.Description) > 5000 {
		jsonErr(w, "la description doit contenir entre 10 et 5000 caractères", http.StatusBadRequest)
		return
	}
	if req.TypeAnnonce != "don" && req.TypeAnnonce != "vente" {
		jsonErr(w, "type_annonce doit être 'don' ou 'vente'", http.StatusBadRequest)
		return
	}

	// Prix : strictement > 0 pour une vente ; ignoré (null) pour un don.
	if req.TypeAnnonce == "vente" {
		if req.Prix == nil || *req.Prix <= 0 {
			jsonErr(w, "un prix strictement supérieur à 0 est requis pour une vente", http.StatusBadRequest)
			return
		}
	} else {
		req.Prix = nil
	}

	if req.ModeRemise != "conteneur" && req.ModeRemise != "main_propre" {
		jsonErr(w, "mode_remise doit être 'conteneur' ou 'main_propre'", http.StatusBadRequest)
		return
	}

	// Remise : on exige le conteneur OU l'adresse selon le mode, et on neutralise l'autre.
	if req.ModeRemise == "conteneur" {
		if req.IDConteneur == nil || !conteneurActif(*req.IDConteneur) {
			jsonErr(w, "veuillez sélectionner un conteneur actif", http.StatusBadRequest)
			return
		}
		req.AdresseRemise = nil
	} else { // main_propre
		if req.AdresseRemise == nil || len(strings.TrimSpace(*req.AdresseRemise)) < 5 {
			jsonErr(w, "veuillez indiquer une adresse de remise en main propre", http.StatusBadRequest)
			return
		}
		adr := strings.TrimSpace(*req.AdresseRemise)
		if len(adr) > 255 {
			jsonErr(w, "l'adresse de remise est trop longue (max 255 caractères)", http.StatusBadRequest)
			return
		}
		req.AdresseRemise = &adr
		req.IDConteneur = nil
	}

	if len(req.Objets) == 0 {
		jsonErr(w, "au moins un objet est requis", http.StatusBadRequest)
		return
	}

	validEtats := map[string]bool{"neuf": true, "bon": true, "use": true, "a_reparer": true}
	totalPhotos := 0

	for i, obj := range req.Objets {
		if !categorieObjetValide(strings.TrimSpace(obj.Categorie)) {
			jsonErr(w, fmt.Sprintf("catégorie invalide pour l'objet %d", i+1), http.StatusBadRequest)
			return
		}
		if !materiauActif(obj.Materiau) {
			jsonErr(w, fmt.Sprintf("matériau invalide pour l'objet %d", i+1), http.StatusBadRequest)
			return
		}
		if !validEtats[obj.Etat] {
			jsonErr(w, fmt.Sprintf("état invalide pour l'objet %d", i+1), http.StatusBadRequest)
			return
		}
		if obj.PoidsKg == nil || *obj.PoidsKg <= 0 {
			jsonErr(w, fmt.Sprintf("le poids (kg) est requis pour l'objet %d (une estimation suffit)", i+1), http.StatusBadRequest)
			return
		}
		if len(obj.Photos) == 0 {
			jsonErr(w, fmt.Sprintf("au moins une photo requise pour l'objet %d", i+1), http.StatusBadRequest)
			return
		}
		totalPhotos += len(obj.Photos)
	}
	if totalPhotos > 10 {
		jsonErr(w, "maximum 10 photos par annonce", http.StatusBadRequest)
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		logError("CreateAnnonce", "tx begin: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	res, err := tx.Exec(
		`INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, id_conteneur, adresse_remise, statut)
		 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')`,
		userId, req.Titre, req.Description, req.TypeAnnonce, req.Prix, req.ModeRemise, req.IDConteneur, req.AdresseRemise)
	if err != nil {
		tx.Rollback()
		logError("CreateAnnonce", "insert annonce: %v", err)
		jsonErr(w, "erreur lors de la création de l'annonce", http.StatusInternalServerError)
		return
	}
	annonceId, _ := res.LastInsertId()

	photoOrdre := 0
	for _, obj := range req.Objets {
		objRes, err := tx.Exec(
			`INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (?, ?, ?, ?, ?)`,
			annonceId, obj.Categorie, obj.Materiau, obj.Etat, obj.PoidsKg)
		if err != nil {
			tx.Rollback()
			logError("CreateAnnonce", "insert objet: %v", err)
			jsonErr(w, "erreur lors de l'ajout d'un objet", http.StatusInternalServerError)
			return
		}
		objetId, _ := objRes.LastInsertId()

		for _, photoB64 := range obj.Photos {
			photoOrdre++
			ext, data, err := decodeBase64Image(photoB64)
			if err != nil {
				tx.Rollback()
				logError("CreateAnnonce", "photo decode: %v", err)
				jsonErr(w, fmt.Sprintf("photo invalide: %v", err), http.StatusBadRequest)
				return
			}
			if len(data) > 5*1024*1024 {
				tx.Rollback()
				jsonErr(w, "une photo dépasse 5 Mo", http.StatusBadRequest)
				return
			}
			filename := generateUUID() + "." + ext
			key := "photos/" + filename
			if err := storage.Default().Save(key, data, storage.ContentType(key)); err != nil {
				tx.Rollback()
				logError("CreateAnnonce", "file write: %v", err)
				jsonErr(w, "erreur sauvegarde photo", http.StatusInternalServerError)
				return
			}
			if _, err = tx.Exec(
				`INSERT INTO photos_objets (id_objet, url_photo, ordre) VALUES (?, ?, ?)`,
				objetId, key, photoOrdre); err != nil {
				tx.Rollback()
				logError("CreateAnnonce", "insert photo: %v", err)
				jsonErr(w, "erreur enregistrement photo", http.StatusInternalServerError)
				return
			}
		}
	}

	if err := tx.Commit(); err != nil {
		logError("CreateAnnonce", "commit: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	logInfo("CreateAnnonce", "user=%d created annonce=%d", userId, annonceId)
	jsonOK(w, map[string]interface{}{
		"message":    "annonce créée avec succès",
		"id_annonce": annonceId,
		"statut":     "en_attente",
	}, http.StatusCreated)
}

func CancelAnnonce(w http.ResponseWriter, r *http.Request, id string, userId int) {
	logInfo("CancelAnnonce", logFmtAnnonceUser, userId, id)

	var req models.CancelAnnonceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}

	var ownerID int
	var statut string
	if err := database.DB.QueryRow(
		`SELECT id_particulier, statut FROM annonces WHERE id_annonce = ?`, id).
		Scan(&ownerID, &statut); err != nil {
		jsonErr(w, errAnnonceIntro, http.StatusNotFound)
		return
	}
	if ownerID != userId {
		jsonErr(w, errAccesRefuse, http.StatusForbidden)
		return
	}
	if statut != "en_attente" {
		jsonErr(w, "seules les annonces en attente peuvent être annulées", http.StatusBadRequest)
		return
	}

	if _, err := database.DB.Exec(
		`UPDATE annonces SET statut = 'annulee', motif_retrait = ? WHERE id_annonce = ?`,
		req.MotifRetrait, id); err != nil {
		logError("CancelAnnonce", logFmtUpdate, err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	logInfo("CancelAnnonce", "user=%d cancelled annonce=%s", userId, id)
	jsonOK(w, map[string]string{"message": "annonce annulée", "statut": "annulee"}, http.StatusOK)
}

func UpdateAnnonce(w http.ResponseWriter, r *http.Request, id string, userId int) {
	logInfo("UpdateAnnonce", logFmtAnnonceUser, userId, id)

	var req models.UpdateAnnonceRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	if req.Titre == "" || req.Description == "" || req.ModeRemise == "" {
		jsonErr(w, "titre, description et mode_remise sont requis", http.StatusBadRequest)
		return
	}

	var ownerID int
	var statut string
	if err := database.DB.QueryRow(
		`SELECT id_particulier, statut FROM annonces WHERE id_annonce = ?`, id).
		Scan(&ownerID, &statut); err != nil {
		jsonErr(w, errAnnonceIntro, http.StatusNotFound)
		return
	}
	if ownerID != userId {
		jsonErr(w, errAccesRefuse, http.StatusForbidden)
		return
	}
	if statut != "en_attente" && statut != "validee" {
		jsonErr(w, "seules les annonces en attente ou validées peuvent être modifiées", http.StatusBadRequest)
		return
	}

	// Modifier une annonce validée la remet en modération.
	newStatut := statut
	if statut == "validee" {
		newStatut = "en_attente"
	}

	if _, err := database.DB.Exec(
		`UPDATE annonces SET titre = ?, description = ?, prix = ?, mode_remise = ?, statut = ? WHERE id_annonce = ?`,
		req.Titre, req.Description, req.Prix, req.ModeRemise, newStatut, id); err != nil {
		logError("UpdateAnnonce", logFmtUpdate, err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	logInfo("UpdateAnnonce", "annonce=%s → statut=%s", id, newStatut)
	jsonOK(w, map[string]interface{}{"message": "annonce mise à jour", "statut": newStatut}, http.StatusOK)
}

func DeleteAnnonce(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	logInfo("DeleteAnnonce", logFmtAnnonceUser, userId, id)

	var ownerID int
	var statut string
	if err := database.DB.QueryRow(
		`SELECT id_particulier, statut FROM annonces WHERE id_annonce = ?`, id).
		Scan(&ownerID, &statut); err != nil {
		jsonErr(w, errAnnonceIntro, http.StatusNotFound)
		return
	}
	if role != "admin" && ownerID != userId {
		jsonErr(w, errAccesRefuse, http.StatusForbidden)
		return
	}
	if statut != "validee" {
		jsonErr(w, "seules les annonces validées peuvent être supprimées", http.StatusBadRequest)
		return
	}

	// Supprime les fichiers photos du stockage.
	photoRows, err := database.DB.Query(
		`SELECT po.url_photo FROM photos_objets po JOIN objets_annonces oa ON po.id_objet = oa.id_objet WHERE oa.id_annonce = ?`, id)
	if err == nil {
		defer photoRows.Close()
		for photoRows.Next() {
			var url string
			if photoRows.Scan(&url) == nil {
				storage.Default().Delete(url) //nolint:errcheck
			}
		}
	}

	if _, err := database.DB.Exec(`UPDATE annonces SET statut = 'supprimee' WHERE id_annonce = ?`, id); err != nil {
		logError("DeleteAnnonce", logFmtUpdate, err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	logInfo("DeleteAnnonce", "user=%d deleted annonce=%s", userId, id)
	jsonOK(w, map[string]string{"message": "annonce supprimée"}, http.StatusOK)
}

func ValiderAnnonce(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	tx, err := database.DB.Begin()
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	var idParticulier int
	var titre, modeRemise string
	if err := tx.QueryRow(
		`SELECT id_particulier, titre, mode_remise FROM annonces WHERE id_annonce = ?`, id).
		Scan(&idParticulier, &titre, &modeRemise); err != nil {
		tx.Rollback()
		jsonErr(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	if _, err := tx.Exec(
		`UPDATE annonces SET statut = 'validee', valide_par = ?, motif_refus = NULL WHERE id_annonce = ?`,
		adminId, id); err != nil {
		tx.Rollback()
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	contenu := fmt.Sprintf("Excellente nouvelle, votre annonce \"%s\" est maintenant en ligne.", titre)
	if modeRemise == "conteneur" {
		contenu += " Un code-barre vous sera transmis pour le dépôt."
	}
	_, _ = tx.Exec(
		`INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')`,
		idParticulier, "Votre annonce a été validée !", contenu)

	tx.Commit()
	logInfo("ValiderAnnonce", "admin=%d validated annonce=%s", adminId, id)

	// Déclencher les alertes matériaux en arrière-plan
	go func(annonceIDStr string) {
		annonceID, err := strconv.Atoi(annonceIDStr)
		if err != nil {
			return
		}
		var ville string
		database.DB.QueryRow(`
			SELECT COALESCE(u.ville, '')
			FROM annonces a
			JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
			WHERE a.id_annonce = ?`, annonceID).Scan(&ville)

		matRows, err := database.DB.Query(
			`SELECT DISTINCT materiau FROM objets_annonces WHERE id_annonce = ? AND materiau IS NOT NULL AND materiau != ''`,
			annonceID)
		if err != nil {
			return
		}
		defer matRows.Close()
		for matRows.Next() {
			var mat string
			if matRows.Scan(&mat) == nil && mat != "" {
				SendAlertesMateriau(annonceID, mat, ville)
			}
		}
	}(id)

	jsonOK(w, map[string]interface{}{
		"message":          "annonce validée",
		"requires_barcode": modeRemise == "conteneur",
	}, http.StatusOK)
}

func RefuserAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	var req models.AnnonceValidationRequest
	_ = json.NewDecoder(r.Body).Decode(&req)

	tx, err := database.DB.Begin()
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	var idParticulier int
	var titre string
	if err := tx.QueryRow(
		`SELECT id_particulier, titre FROM annonces WHERE id_annonce = ?`, id).
		Scan(&idParticulier, &titre); err != nil {
		tx.Rollback()
		jsonErr(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	motif := "Non conforme aux règles de la plateforme."
	if req.MotifRefus != nil && *req.MotifRefus != "" {
		motif = *req.MotifRefus
	}

	if _, err := tx.Exec(
		`UPDATE annonces SET statut = 'refusee', motif_refus = ? WHERE id_annonce = ?`, motif, id); err != nil {
		tx.Rollback()
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}

	_, _ = tx.Exec(
		`INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')`,
		idParticulier,
		"Votre annonce n'a pas été validée",
		fmt.Sprintf("Votre annonce \"%s\" a été refusée : %s", titre, motif))

	tx.Commit()
	logInfo("RefuserAnnonce", "annonce=%s refused", id)
	jsonOK(w, map[string]string{"message": "annonce refusée"}, http.StatusOK)
}

func AttenteAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	if _, err := database.DB.Exec(
		`UPDATE annonces SET statut = 'en_attente', valide_par = NULL, motif_refus = NULL WHERE id_annonce = ?`, id); err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "annonce remise en attente"}, http.StatusOK)
}

// ─── Utilitaires fichiers ─────────────────────────────────────────────────────

func decodeBase64Image(b64 string) (string, []byte, error) {
	ext := "jpg"
	raw := b64

	if strings.HasPrefix(b64, "data:image/") {
		parts := strings.SplitN(b64, ",", 2)
		if len(parts) != 2 {
			return "", nil, fmt.Errorf("format base64 invalide")
		}
		header, payload := parts[0], parts[1]
		raw = payload
		if strings.Contains(header, "image/png") {
			ext = "png"
		} else if strings.Contains(header, "image/webp") {
			ext = "webp"
		}
	}

	data, err := base64.StdEncoding.DecodeString(raw)
	if err != nil {
		data, err = base64.RawStdEncoding.DecodeString(raw)
		if err != nil {
			return "", nil, fmt.Errorf("décodage base64 échoué: %v", err)
		}
	}

	// Détection magique si pas de header MIME.
	if !strings.HasPrefix(b64, "data:image/") && len(data) >= 4 {
		if data[0] == 0x89 && data[1] == 0x50 {
			ext = "png"
		} else if data[0] == 0x52 && data[1] == 0x49 {
			ext = "webp"
		}
	}

	return ext, data, nil
}

func generateUUID() string {
	b := make([]byte, 16)
	rand.Read(b)
	b[6] = (b[6] & 0x0f) | 0x40
	b[8] = (b[8] & 0x3f) | 0x80
	return fmt.Sprintf("%08x-%04x-%04x-%04x-%012x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:16])
}

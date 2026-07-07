package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"api/pkg/storage"
	"database/sql"
	"encoding/json"
	"net/http"
	"strconv"
)

// États autorisés pour un matériel.
var etatsMateriel = map[string]bool{"neuf": true, "bon": true, "use": true, "a_reparer": true}

// ─── Helpers ──────────────────────────────────────────────────────────────────

func loadPhotosMateriel(idMateriel int) []models.PhotoMateriel {
	photos := []models.PhotoMateriel{}
	rows, err := database.DB.Query(
		"SELECT id_photo, url_photo FROM photos_materiels WHERE id_materiel = ? ORDER BY ordre, id_photo", idMateriel)
	if err != nil {
		return photos
	}
	defer rows.Close()
	for rows.Next() {
		var p models.PhotoMateriel
		if rows.Scan(&p.IDPhoto, &p.URL) == nil {
			photos = append(photos, p)
		}
	}
	return photos
}

// insertPhotosMateriel décode des images base64, les pousse sur le stockage
// (S3/MinIO) et enregistre leurs clés, en continuant l'ordre existant.
func insertPhotosMateriel(idMateriel int, images []string) {
	if len(images) == 0 {
		return
	}
	maxOrdre := -1
	database.DB.QueryRow("SELECT COALESCE(MAX(ordre), -1) FROM photos_materiels WHERE id_materiel = ?", idMateriel).Scan(&maxOrdre) //nolint:errcheck
	for _, b64 := range images {
		if b64 == "" {
			continue
		}
		ext, data, err := decodeBase64Image(b64)
		if err != nil {
			logError("insertPhotosMateriel", "decode: %v", err)
			continue
		}
		if len(data) > 5*1024*1024 {
			continue
		}
		key := "materiels/" + generateUUID() + "." + ext
		if err := storage.Default().Save(key, data, storage.ContentType(key)); err != nil {
			logError("insertPhotosMateriel", "save: %v", err)
			continue
		}
		maxOrdre++
		database.DB.Exec("INSERT INTO photos_materiels (id_materiel, url_photo, ordre) VALUES (?, ?, ?)", idMateriel, key, maxOrdre) //nolint:errcheck
	}
}

// loadReservationActive renvoie la réservation active (non retournée) d'un
// matériel, avec le titre de l'événement associé, ou nil.
func loadReservationActive(idMateriel int) *models.ReservationMateriel {
	var r models.ReservationMateriel
	var idEv sql.NullInt64
	var titre sql.NullString
	var retour sql.NullTime
	err := database.DB.QueryRow(`
		SELECT r.id_reservation, r.id_materiel, r.id_salarie, r.id_evenement, e.titre,
		       r.date_reservation, r.date_retour
		FROM reservations_materiels r
		LEFT JOIN evenements e ON e.id_evenement = r.id_evenement
		WHERE r.id_materiel = ? AND r.date_retour IS NULL
		ORDER BY r.date_reservation DESC LIMIT 1`, idMateriel).
		Scan(&r.IDReservation, &r.IDMateriel, &r.IDSalarie, &idEv, &titre, &r.DateReservation, &retour)
	if err != nil {
		return nil
	}
	if idEv.Valid {
		v := int(idEv.Int64)
		r.IDEvenement = &v
	}
	if titre.Valid {
		r.TitreEvenement = &titre.String
	}
	if retour.Valid {
		r.DateRetour = &retour.Time
	}
	return &r
}

func scanMateriel(rows *sql.Rows) (models.Materiel, error) {
	var m models.Materiel
	var desc sql.NullString
	var idSite sql.NullInt64
	err := rows.Scan(&m.IDMateriel, &m.Nom, &desc, &m.Etat, &m.EstDisponible, &idSite)
	if err == nil {
		if desc.Valid {
			m.Description = &desc.String
		}
		if idSite.Valid {
			v := int(idSite.Int64)
			m.IDSite = &v
		}
	}
	return m, err
}

// ─── Handlers ─────────────────────────────────────────────────────────────────

// userSite renvoie le site (id_site_uc) du salarié, s'il en a un.
func userSite(userId int) sql.NullInt64 {
	var site sql.NullInt64
	database.DB.QueryRow("SELECT id_site_uc FROM utilisateurs WHERE id_utilisateur = ?", userId).Scan(&site) //nolint:errcheck
	return site
}

// GetMateriels liste l'inventaire visible par le salarié : le matériel de son
// site + le matériel sans site (partagé). Un salarié sans site voit tout.
func GetMateriels(w http.ResponseWriter, r *http.Request, userId int) {
	site := userSite(userId)

	var rows *sql.Rows
	var err error
	if site.Valid {
		rows, err = database.DB.Query(
			"SELECT id_materiel, nom, description, etat, est_disponible, id_site FROM materiels WHERE id_site = ? OR id_site IS NULL ORDER BY nom",
			site.Int64)
	} else {
		rows, err = database.DB.Query(
			"SELECT id_materiel, nom, description, etat, est_disponible, id_site FROM materiels ORDER BY nom")
	}
	if err != nil {
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	list := []models.Materiel{}
	for rows.Next() {
		m, err := scanMateriel(rows)
		if err != nil {
			continue
		}
		m.Photos = loadPhotosMateriel(m.IDMateriel)
		if res := loadReservationActive(m.IDMateriel); res != nil {
			m.EstReserve = true
			m.Reservation = res
		}
		list = append(list, m)
	}
	jsonOK(w, list, http.StatusOK)
}

// GetMateriel renvoie le détail d'un matériel.
func GetMateriel(w http.ResponseWriter, r *http.Request, id string) {
	idMateriel, err := strconv.Atoi(id)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	var m models.Materiel
	var desc sql.NullString
	var idSite sql.NullInt64
	err = database.DB.QueryRow(
		"SELECT id_materiel, nom, description, etat, est_disponible, id_site FROM materiels WHERE id_materiel = ?", idMateriel).
		Scan(&m.IDMateriel, &m.Nom, &desc, &m.Etat, &m.EstDisponible, &idSite)
	if err != nil {
		jsonErr(w, "matériel non trouvé", http.StatusNotFound)
		return
	}
	if desc.Valid {
		m.Description = &desc.String
	}
	if idSite.Valid {
		v := int(idSite.Int64)
		m.IDSite = &v
	}
	m.Photos = loadPhotosMateriel(m.IDMateriel)
	if res := loadReservationActive(m.IDMateriel); res != nil {
		m.EstReserve = true
		m.Reservation = res
	}
	jsonOK(w, m, http.StatusOK)
}

// CreateMateriel ajoute un objet à l'inventaire.
func CreateMateriel(w http.ResponseWriter, r *http.Request, userId int) {
	var req models.CreateMaterielRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	if req.Nom == "" {
		jsonErr(w, "le nom est requis", http.StatusBadRequest)
		return
	}
	if req.Etat == "" {
		req.Etat = "bon"
	}
	if !etatsMateriel[req.Etat] {
		jsonErr(w, "état invalide", http.StatusBadRequest)
		return
	}
	dispo := true
	if req.EstDisponible != nil {
		dispo = *req.EstDisponible
	}
	// Par défaut, le matériel est rattaché au site du salarié créateur.
	if req.IDSite == nil {
		if site := userSite(userId); site.Valid {
			v := int(site.Int64)
			req.IDSite = &v
		}
	}

	res, err := database.DB.Exec(
		"INSERT INTO materiels (nom, description, etat, est_disponible, id_site) VALUES (?, ?, ?, ?, ?)",
		req.Nom, req.Description, req.Etat, dispo, req.IDSite)
	if err != nil {
		logError("CreateMateriel", "insert: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	insertPhotosMateriel(int(id), req.Images)

	jsonOK(w, map[string]interface{}{"message": "matériel créé", "id_materiel": id}, http.StatusCreated)
}

// UpdateMateriel met à jour un matériel et AJOUTE les nouvelles photos.
func UpdateMateriel(w http.ResponseWriter, r *http.Request, id string) {
	idMateriel, err := strconv.Atoi(id)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	var req models.CreateMaterielRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, errDonneesInval, http.StatusBadRequest)
		return
	}
	if req.Nom == "" {
		jsonErr(w, "le nom est requis", http.StatusBadRequest)
		return
	}
	if req.Etat == "" || !etatsMateriel[req.Etat] {
		jsonErr(w, "état invalide", http.StatusBadRequest)
		return
	}
	dispo := true
	if req.EstDisponible != nil {
		dispo = *req.EstDisponible
	}

	_, err = database.DB.Exec(
		"UPDATE materiels SET nom = ?, description = ?, etat = ?, est_disponible = ?, id_site = ? WHERE id_materiel = ?",
		req.Nom, req.Description, req.Etat, dispo, req.IDSite, idMateriel)
	if err != nil {
		logError("UpdateMateriel", "update: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	insertPhotosMateriel(idMateriel, req.Images)

	jsonOK(w, map[string]string{"message": "matériel mis à jour"}, http.StatusOK)
}

// DeleteMateriel supprime un matériel, ses photos (fichiers + lignes) et ses
// réservations.
func DeleteMateriel(w http.ResponseWriter, r *http.Request, id string) {
	idMateriel, err := strconv.Atoi(id)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	for _, p := range loadPhotosMateriel(idMateriel) {
		storage.Default().Delete(p.URL) //nolint:errcheck
	}
	database.DB.Exec("DELETE FROM reservations_materiels WHERE id_materiel = ?", idMateriel) //nolint:errcheck
	if _, err := database.DB.Exec("DELETE FROM materiels WHERE id_materiel = ?", idMateriel); err != nil {
		logError("DeleteMateriel", "delete: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "matériel supprimé"}, http.StatusOK)
}

// DeleteMaterielPhoto supprime une photo (fichier + ligne).
func DeleteMaterielPhoto(w http.ResponseWriter, r *http.Request, id, photoID string) {
	idPhoto, err := strconv.Atoi(photoID)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	var url string
	if err := database.DB.QueryRow("SELECT url_photo FROM photos_materiels WHERE id_photo = ?", idPhoto).Scan(&url); err == nil {
		storage.Default().Delete(url) //nolint:errcheck
	}
	database.DB.Exec("DELETE FROM photos_materiels WHERE id_photo = ?", idPhoto) //nolint:errcheck
	jsonOK(w, map[string]string{"message": "photo supprimée"}, http.StatusOK)
}

// ReserverMateriel réserve un matériel (optionnellement pour un événement).
func ReserverMateriel(w http.ResponseWriter, r *http.Request, id string, userId int) {
	idMateriel, err := strconv.Atoi(id)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	var req models.ReserverMaterielRequest
	json.NewDecoder(r.Body).Decode(&req) //nolint:errcheck

	if loadReservationActive(idMateriel) != nil {
		jsonErr(w, "matériel déjà réservé", http.StatusConflict)
		return
	}
	_, err = database.DB.Exec(
		"INSERT INTO reservations_materiels (id_materiel, id_salarie, id_evenement) VALUES (?, ?, ?)",
		idMateriel, userId, req.IDEvenement)
	if err != nil {
		logError("ReserverMateriel", "insert: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "matériel réservé"}, http.StatusCreated)
}

// RetourMateriel clôt la réservation active d'un matériel (retour).
func RetourMateriel(w http.ResponseWriter, r *http.Request, id string) {
	idMateriel, err := strconv.Atoi(id)
	if err != nil {
		jsonErr(w, "identifiant invalide", http.StatusBadRequest)
		return
	}
	_, err = database.DB.Exec(
		"UPDATE reservations_materiels SET date_retour = NOW() WHERE id_materiel = ? AND date_retour IS NULL", idMateriel)
	if err != nil {
		logError("RetourMateriel", "update: %v", err)
		jsonErr(w, errServeur, http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "retour enregistré"}, http.StatusOK)
}

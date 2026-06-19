package handlers

import (
	"api/pkg/database"
	"crypto/rand"
	"database/sql"
	"encoding/hex"
	"encoding/json"
	"net/http"
	"strconv"
	"time"
)

type DemandeDepot struct {
	IDDemande          int     `json:"id_demande"`
	IDUtilisateur      int     `json:"id_utilisateur"`
	IDConteneur        *int    `json:"id_conteneur"`
	IDAnnnonce         *int    `json:"id_annonce"`
	Titre              string  `json:"titre"`
	Description        string  `json:"description"`
	TypeObjet          string  `json:"type_objet"`
	Quantite           int     `json:"quantite"`
	AdresseRetrait     *string `json:"adresse_retrait"`
	CodePostalRetrait  *string `json:"code_postal_retrait"`
	VilleRetrait       *string `json:"ville_retrait"`
	Statut             string  `json:"statut"`
	CodeBarre          *string `json:"code_barre"`
	MotifRefus         *string `json:"motif_refus"`
	DateDemande        string  `json:"date_demande"`
	DateTraitement     *string `json:"date_traitement"`
}

func GetPublicConteneursAvecGeo(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT id_conteneur, conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut
		FROM conteneurs WHERE statut = 'actif'`)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type ConteneurGeo struct {
		IDConteneur  int      `json:"id_conteneur"`
		Ref          string   `json:"conteneur_ref"`
		Adresse      string   `json:"adresse"`
		Ville        string   `json:"ville"`
		CodePostal   *string  `json:"code_postal"`
		Latitude     *float64 `json:"latitude"`
		Longitude    *float64 `json:"longitude"`
		Capacite     int      `json:"capacite"`
		Statut       string   `json:"statut"`
	}

	conteneurs := []ConteneurGeo{}
	for rows.Next() {
		var c ConteneurGeo
		var cp sql.NullString
		var lat, lng sql.NullFloat64
		if err := rows.Scan(&c.IDConteneur, &c.Ref, &c.Adresse, &c.Ville, &cp, &lat, &lng, &c.Capacite, &c.Statut); err != nil {
			continue
		}
		if cp.Valid {
			c.CodePostal = &cp.String
		}
		if lat.Valid {
			c.Latitude = &lat.Float64
		}
		if lng.Valid {
			c.Longitude = &lng.Float64
		}
		conteneurs = append(conteneurs, c)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(conteneurs)
}

func CreateDemandeDepot(w http.ResponseWriter, r *http.Request, userId int) {
	var body struct {
		Titre             string `json:"titre"`
		Description       string `json:"description"`
		TypeObjet         string `json:"type_objet"`
		Quantite          int    `json:"quantite"`
		AdresseRetrait    string `json:"adresse_retrait"`
		CodePostalRetrait string `json:"code_postal_retrait"`
		VilleRetrait      string `json:"ville_retrait"`
		IDConteneur       *int   `json:"id_conteneur"`
		IDAnnnonce        *int   `json:"id_annonce"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		jsonError(w, "données invalides", http.StatusBadRequest)
		return
	}
	if body.Titre == "" || body.Description == "" || body.TypeObjet == "" {
		jsonError(w, "titre, description et type_objet requis", http.StatusBadRequest)
		return
	}
	if body.Quantite <= 0 {
		body.Quantite = 1
	}

	res, err := database.DB.Exec(`
		INSERT INTO demandes_depot
		(id_utilisateur, id_conteneur, id_annonce, titre, description, type_objet, quantite,
		 adresse_retrait, code_postal_retrait, ville_retrait, statut)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')`,
		userId, body.IDConteneur, body.IDAnnnonce, body.Titre, body.Description, body.TypeObjet,
		body.Quantite, body.AdresseRetrait, body.CodePostalRetrait, body.VilleRetrait)
	if err != nil {
		jsonError(w, "erreur création", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"id_demande": id, "message": "demande créée"})
}

func GetMesDemandesDepot(w http.ResponseWriter, r *http.Request, userId int) {
	rows, err := database.DB.Query(`
		SELECT id_demande, id_utilisateur, id_conteneur, id_annonce, titre, description, type_objet, quantite,
		       adresse_retrait, code_postal_retrait, ville_retrait, statut, code_barre, motif_refus,
		       date_demande, date_traitement
		FROM demandes_depot WHERE id_utilisateur = ? ORDER BY date_demande DESC`, userId)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	demandes := []DemandeDepot{}
	for rows.Next() {
		var d DemandeDepot
		var idCont, idAnn sql.NullInt64
		var adr, cp, ville, cb, motif, dateTraite sql.NullString
		if err := rows.Scan(&d.IDDemande, &d.IDUtilisateur, &idCont, &idAnn, &d.Titre, &d.Description,
			&d.TypeObjet, &d.Quantite, &adr, &cp, &ville, &d.Statut, &cb, &motif, &d.DateDemande, &dateTraite); err != nil {
			continue
		}
		if idCont.Valid {
			v := int(idCont.Int64)
			d.IDConteneur = &v
		}
		if idAnn.Valid {
			v := int(idAnn.Int64)
			d.IDAnnnonce = &v
		}
		if adr.Valid {
			d.AdresseRetrait = &adr.String
		}
		if cp.Valid {
			d.CodePostalRetrait = &cp.String
		}
		if ville.Valid {
			d.VilleRetrait = &ville.String
		}
		if cb.Valid {
			d.CodeBarre = &cb.String
		}
		if motif.Valid {
			d.MotifRefus = &motif.String
		}
		if dateTraite.Valid {
			d.DateTraitement = &dateTraite.String
		}
		demandes = append(demandes, d)
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(demandes)
}

func AdminGetDemandesDepot(w http.ResponseWriter, r *http.Request) {
	statut := r.URL.Query().Get("statut")
	query := `SELECT d.id_demande, d.id_utilisateur, d.id_conteneur, d.id_annonce, d.titre, d.description,
	                 d.type_objet, d.quantite, d.adresse_retrait, d.code_postal_retrait, d.ville_retrait,
	                 d.statut, d.code_barre, d.motif_refus, d.date_demande, d.date_traitement,
	                 u.nom, u.prenom, u.email
	          FROM demandes_depot d
	          JOIN utilisateurs u ON d.id_utilisateur = u.id_utilisateur`
	args := []interface{}{}
	if statut != "" {
		query += " WHERE d.statut = ?"
		args = append(args, statut)
	}
	query += " ORDER BY d.date_demande DESC"

	rows, err := database.DB.Query(query, args...)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type DemandeAdmin struct {
		DemandeDepot
		NomUtilisateur string `json:"nom_utilisateur"`
		Email          string `json:"email"`
	}

	demandes := []DemandeAdmin{}
	for rows.Next() {
		var d DemandeAdmin
		var idCont, idAnn sql.NullInt64
		var adr, cp, ville, cb, motif, dateTraite sql.NullString
		if err := rows.Scan(&d.IDDemande, &d.IDUtilisateur, &idCont, &idAnn, &d.Titre, &d.Description,
			&d.TypeObjet, &d.Quantite, &adr, &cp, &ville, &d.Statut, &cb, &motif, &d.DateDemande, &dateTraite,
			&d.NomUtilisateur, new(string), &d.Email); err != nil {
			continue
		}
		if idCont.Valid {
			v := int(idCont.Int64)
			d.IDConteneur = &v
		}
		if idAnn.Valid {
			v := int(idAnn.Int64)
			d.IDAnnnonce = &v
		}
		if adr.Valid {
			d.AdresseRetrait = &adr.String
		}
		if cp.Valid {
			d.CodePostalRetrait = &cp.String
		}
		if ville.Valid {
			d.VilleRetrait = &ville.String
		}
		if cb.Valid {
			d.CodeBarre = &cb.String
		}
		if motif.Valid {
			d.MotifRefus = &motif.String
		}
		if dateTraite.Valid {
			d.DateTraitement = &dateTraite.String
		}
		demandes = append(demandes, d)
	}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(demandes)
}

func AdminValiderDemandeDepot(w http.ResponseWriter, r *http.Request, idStr string) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonError(w, "id invalide", http.StatusBadRequest)
		return
	}

	// Générer un code-barre unique
	b := make([]byte, 16)
	rand.Read(b)
	codeBarre := "UC-" + hex.EncodeToString(b)
	now := time.Now().Format("2006-01-02 15:04:05")

	_, err = database.DB.Exec(`
		UPDATE demandes_depot SET statut='code_envoye', code_barre=?, date_traitement=? WHERE id_demande=?`,
		codeBarre, now, id)
	if err != nil {
		jsonError(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":    "demande validée, code-barre généré",
		"code_barre": codeBarre,
	})
}

func AdminRefuserDemandeDepot(w http.ResponseWriter, r *http.Request, idStr string) {
	id, err := strconv.Atoi(idStr)
	if err != nil {
		jsonError(w, "id invalide", http.StatusBadRequest)
		return
	}

	var body struct {
		MotifRefus string `json:"motif_refus"`
	}
	json.NewDecoder(r.Body).Decode(&body)
	now := time.Now().Format("2006-01-02 15:04:05")

	_, err = database.DB.Exec(`
		UPDATE demandes_depot SET statut='refusee', motif_refus=?, date_traitement=? WHERE id_demande=?`,
		body.MotifRefus, now, id)
	if err != nil {
		jsonError(w, "erreur mise à jour", http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{"message": "demande refusée"})
}

package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"time"
)

type PublicAnnonceVendeur struct {
	Prenom       string `json:"prenom"`
	NomInitiale  string `json:"nom_initiale"`
	Ville        string `json:"ville"`
	Score        int    `json:"score_upcycling"`
	Certifie     bool   `json:"certifie"`
}

type PublicAnnoncePhoto struct {
	URL   string `json:"url"`
	Ordre int    `json:"ordre"`
}

type PublicAnnonceObjet struct {
	IDObjet   int                  `json:"id_objet"`
	Categorie string              `json:"categorie"`
	Materiau  string              `json:"materiau"`
	Etat      string              `json:"etat"`
	PoidsKg   *float64            `json:"poids_kg,omitempty"`
	Photos    []PublicAnnoncePhoto `json:"photos"`
}

type PublicAnnonce struct {
	IDAnnonce    int                  `json:"id_annonce"`
	Titre        string              `json:"titre"`
	Description  string              `json:"description"`
	TypeAnnonce  string              `json:"type_annonce"`
	Prix         *float64            `json:"prix,omitempty"`
	ModeRemise   string              `json:"mode_remise"`
	Ville        string              `json:"ville"`
	DateCreation string              `json:"date_creation"`
	Vendeur      PublicAnnonceVendeur `json:"vendeur"`
	Objets       []PublicAnnonceObjet `json:"objets"`
}

func GetPublicAnnonces(w http.ResponseWriter, r *http.Request) {
	log.Printf("[INFO] %s | GetPublicAnnonces | Listing public annonces\n", time.Now().Format(time.RFC3339))

	rows, err := database.DB.Query(`
		SELECT a.id_annonce, a.titre, a.description, a.type_annonce, a.prix, a.mode_remise, a.date_creation,
			   u.prenom, u.nom, COALESCE(u.ville, '') as ville, COALESCE(u.upcycling_score, 0) as score, COALESCE(u.est_certifie, false) as certifie
		FROM annonces a
		JOIN utilisateurs u ON a.id_particulier = u.id_utilisateur
		WHERE a.statut = 'validee'
		ORDER BY a.date_creation DESC
	`)
	if err != nil {
		log.Printf("[ERROR] %s | GetPublicAnnonces | Query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var annonces []PublicAnnonce
	for rows.Next() {
		var a PublicAnnonce
		var prix sql.NullFloat64
		var nom string
		var dateCreation time.Time

		if err := rows.Scan(&a.IDAnnonce, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &dateCreation,
			&a.Vendeur.Prenom, &nom, &a.Vendeur.Ville, &a.Vendeur.Score, &a.Vendeur.Certifie); err == nil {
			if prix.Valid {
				p := prix.Float64
				a.Prix = &p
			}
			a.DateCreation = dateCreation.Format("2006-01-02T15:04:05Z")
			a.Ville = a.Vendeur.Ville

			if len(nom) > 0 {
				a.Vendeur.NomInitiale = string([]rune(nom)[:1]) + "."
			}
			a.Vendeur.Certifie = a.Vendeur.Score >= 500

			a.Objets = loadPublicObjets(a.IDAnnonce)

			annonces = append(annonces, a)
		} else {
			log.Printf("[ERROR] %s | GetPublicAnnonces | Scan err: %v\n", time.Now().Format(time.RFC3339), err)
		}
	}
	if annonces == nil {
		annonces = []PublicAnnonce{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(annonces)
}

func GetPublicAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	log.Printf("[INFO] %s | GetPublicAnnonce | Viewing annonce %s\n", time.Now().Format(time.RFC3339), id)

	var a PublicAnnonce
	var prix sql.NullFloat64
	var nom string
	var dateCreation time.Time

	err := database.DB.QueryRow(`
		SELECT a.id_annonce, a.titre, a.description, a.type_annonce, a.prix, a.mode_remise, a.date_creation,
			   u.prenom, u.nom, COALESCE(u.ville, '') as ville, COALESCE(u.upcycling_score, 0) as score, COALESCE(u.est_certifie, false) as certifie
		FROM annonces a
		JOIN utilisateurs u ON a.id_particulier = u.id_utilisateur
		WHERE a.id_annonce = ? AND a.statut = 'validee'
	`, id).Scan(&a.IDAnnonce, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &dateCreation,
		&a.Vendeur.Prenom, &nom, &a.Vendeur.Ville, &a.Vendeur.Score, &a.Vendeur.Certifie)

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
	a.DateCreation = dateCreation.Format("2006-01-02T15:04:05Z")
	a.Ville = a.Vendeur.Ville

	if len(nom) > 0 {
		a.Vendeur.NomInitiale = string([]rune(nom)[:1]) + "."
	}
	a.Vendeur.Certifie = a.Vendeur.Score >= 500

	a.Objets = loadPublicObjets(a.IDAnnonce)

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(a)
}

func loadPublicObjets(annonceId int) []PublicAnnonceObjet {
	rows, err := database.DB.Query("SELECT id_objet, categorie, materiau, etat, poids_kg FROM objets_annonces WHERE id_annonce = ?", annonceId)
	if err != nil {
		return []PublicAnnonceObjet{}
	}
	defer rows.Close()

	var objets []PublicAnnonceObjet
	for rows.Next() {
		var o PublicAnnonceObjet
		var poids sql.NullFloat64
		if err := rows.Scan(&o.IDObjet, &o.Categorie, &o.Materiau, &o.Etat, &poids); err == nil {
			if poids.Valid {
				p := poids.Float64
				o.PoidsKg = &p
			}
			o.Photos = loadPublicPhotos(o.IDObjet)
			objets = append(objets, o)
		}
	}
	if objets == nil {
		return []PublicAnnonceObjet{}
	}
	return objets
}

func loadPublicPhotos(objetId int) []PublicAnnoncePhoto {
	rows, err := database.DB.Query("SELECT url_photo, ordre FROM photos_objets WHERE id_objet = ? ORDER BY ordre", objetId)
	if err != nil {
		return []PublicAnnoncePhoto{}
	}
	defer rows.Close()

	var photos []PublicAnnoncePhoto
	for rows.Next() {
		var p PublicAnnoncePhoto
		if err := rows.Scan(&p.URL, &p.Ordre); err == nil {
			photos = append(photos, p)
		}
	}
	if photos == nil {
		return []PublicAnnoncePhoto{}
	}
	return photos
}

type PublicArticle struct {
	IDArticle       int     `json:"id_article"`
	Titre           string  `json:"titre"`
	Contenu         string  `json:"contenu"`
	Categorie       *string `json:"categorie,omitempty"`
	DatePublication *string `json:"date_publication,omitempty"`
	AuteurPrenom    string  `json:"auteur_prenom"`
	AuteurNom       string  `json:"auteur_nom_initiale"`
}

func GetPublicArticles(w http.ResponseWriter, r *http.Request) {
	log.Printf("[INFO] %s | GetPublicArticles | Listing public articles\n", time.Now().Format(time.RFC3339))

	rows, err := database.DB.Query(`
		SELECT a.id_article, a.titre, a.contenu, a.categorie, a.date_publication,
			   u.prenom, u.nom
		FROM articles_news a
		JOIN utilisateurs u ON a.id_auteur = u.id_utilisateur
		WHERE a.statut = 'publie'
		ORDER BY a.date_publication DESC
	`)
	if err != nil {
		log.Printf("[ERROR] %s | GetPublicArticles | Query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var articles []PublicArticle
	for rows.Next() {
		var a PublicArticle
		var categorie sql.NullString
		var datePub sql.NullTime
		var nom string

		if err := rows.Scan(&a.IDArticle, &a.Titre, &a.Contenu, &categorie, &datePub, &a.AuteurPrenom, &nom); err == nil {
			if categorie.Valid {
				a.Categorie = &categorie.String
			}
			if datePub.Valid {
				d := datePub.Time.Format("2006-01-02T15:04:05Z")
				a.DatePublication = &d
			}
			if len(nom) > 0 {
				a.AuteurNom = string([]rune(nom)[:1]) + "."
			}
			articles = append(articles, a)
		} else {
			log.Printf("[ERROR] %s | GetPublicArticles | Scan err: %v\n", time.Now().Format(time.RFC3339), err)
		}
	}
	if articles == nil {
		articles = []PublicArticle{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(articles)
}

func GetPublicArticle(w http.ResponseWriter, r *http.Request, id string) {
	var a PublicArticle
	var categorie sql.NullString
	var datePub sql.NullTime
	var nom string

	err := database.DB.QueryRow(`
		SELECT a.id_article, a.titre, a.contenu, a.categorie, a.date_publication,
			   u.prenom, u.nom
		FROM articles_news a
		JOIN utilisateurs u ON a.id_auteur = u.id_utilisateur
		WHERE a.id_article = ? AND a.statut = 'publie'
	`, id).Scan(&a.IDArticle, &a.Titre, &a.Contenu, &categorie, &datePub, &a.AuteurPrenom, &nom)

	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "article non trouve"})
		return
	}

	if categorie.Valid {
		a.Categorie = &categorie.String
	}
	if datePub.Valid {
		d := datePub.Time.Format("2006-01-02T15:04:05Z")
		a.DatePublication = &d
	}
	if len(nom) > 0 {
		a.AuteurNom = string([]rune(nom)[:1]) + "."
	}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(a)
}

type PublicForumSujet struct {
	IDSujet       int    `json:"id_sujet"`
	Titre         string `json:"titre"`
	Categorie     string `json:"categorie"`
	DateCreation  string `json:"date_creation"`
	CreateurPrenom string `json:"createur_prenom"`
	CreateurNom   string `json:"createur_nom_initiale"`
	NbMessages    int    `json:"nb_messages"`
}

type PublicForumMessage struct {
	IDMessage       int    `json:"id_message"`
	IDParentMessage *int   `json:"id_parent_message"`
	Contenu         string `json:"contenu"`
	DatePublication string `json:"date_publication"`
	AuteurPrenom    string `json:"auteur_prenom"`
	AuteurNom       string `json:"auteur_nom_initiale"`
}

type PublicForumSujetDetail struct {
	IDSujet        int                  `json:"id_sujet"`
	Titre          string               `json:"titre"`
	Categorie      string               `json:"categorie"`
	DateCreation   string               `json:"date_creation"`
	CreateurPrenom string               `json:"createur_prenom"`
	CreateurNom    string               `json:"createur_nom_initiale"`
	Messages       []PublicForumMessage  `json:"messages"`
}

func GetPublicForumSujets(w http.ResponseWriter, r *http.Request) {
	log.Printf("[INFO] %s | GetPublicForumSujets | Listing public forum sujets\n", time.Now().Format(time.RFC3339))

	rows, err := database.DB.Query(`
		SELECT fs.id_sujet, fs.titre, COALESCE(fs.categorie, '') as categorie, fs.date_creation,
			   u.prenom, u.nom,
			   (SELECT COUNT(*) FROM forum_messages fm WHERE fm.id_sujet = fs.id_sujet) as nb_messages
		FROM forum_sujets fs
		JOIN utilisateurs u ON fs.id_createur = u.id_utilisateur
		WHERE fs.statut = 'ouvert'
		ORDER BY fs.date_creation DESC
	`)
	if err != nil {
		log.Printf("[ERROR] %s | GetPublicForumSujets | Query err: %v\n", time.Now().Format(time.RFC3339), err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var sujets []PublicForumSujet
	for rows.Next() {
		var s PublicForumSujet
		var dateCreation time.Time
		var nom string

		if err := rows.Scan(&s.IDSujet, &s.Titre, &s.Categorie, &dateCreation, &s.CreateurPrenom, &nom, &s.NbMessages); err == nil {
			s.DateCreation = dateCreation.Format("2006-01-02T15:04:05Z")
			if len(nom) > 0 {
				s.CreateurNom = string([]rune(nom)[:1]) + "."
			}
			sujets = append(sujets, s)
		} else {
			log.Printf("[ERROR] %s | GetPublicForumSujets | Scan err: %v\n", time.Now().Format(time.RFC3339), err)
		}
	}
	if sujets == nil {
		sujets = []PublicForumSujet{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(sujets)
}

func GetPublicForumSujet(w http.ResponseWriter, r *http.Request, id string) {
	var s PublicForumSujetDetail
	var dateCreation time.Time
	var nom string
	var categorie sql.NullString

	err := database.DB.QueryRow(`
		SELECT fs.id_sujet, fs.titre, fs.categorie, fs.date_creation,
			   u.prenom, u.nom
		FROM forum_sujets fs
		JOIN utilisateurs u ON fs.id_createur = u.id_utilisateur
		WHERE fs.id_sujet = ? AND fs.statut = 'ouvert'
	`, id).Scan(&s.IDSujet, &s.Titre, &categorie, &dateCreation, &s.CreateurPrenom, &nom)

	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "sujet non trouve"})
		return
	}

	s.DateCreation = dateCreation.Format("2006-01-02T15:04:05Z")
	if categorie.Valid {
		s.Categorie = categorie.String
	}
	if len(nom) > 0 {
		s.CreateurNom = string([]rune(nom)[:1]) + "."
	}

	msgRows, err := database.DB.Query(`
		SELECT fm.id_message, fm.id_parent_message, fm.contenu, fm.date_publication,
			   u.prenom, u.nom
		FROM forum_messages fm
		JOIN utilisateurs u ON fm.id_auteur = u.id_utilisateur
		WHERE fm.id_sujet = ? AND fm.est_signale = FALSE
		ORDER BY fm.date_publication ASC
	`, id)
	if err == nil {
		defer msgRows.Close()
		for msgRows.Next() {
			var m PublicForumMessage
			var datePub time.Time
			var mNom string
			var parentID sql.NullInt64
			if err := msgRows.Scan(&m.IDMessage, &parentID, &m.Contenu, &datePub, &m.AuteurPrenom, &mNom); err == nil {
				m.DatePublication = datePub.Format("2006-01-02T15:04:05Z")
				if parentID.Valid {
					p := int(parentID.Int64)
					m.IDParentMessage = &p
				}
				if len(mNom) > 0 {
					m.AuteurNom = string([]rune(mNom)[:1]) + "."
				}
				s.Messages = append(s.Messages, m)
			}
		}
	}
	if s.Messages == nil {
		s.Messages = []PublicForumMessage{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "no-store")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(s)
}

type PublicStats struct {
	ObjetsSauves int `json:"objets_sauves"`
	Membres      int `json:"membres"`
	AteliersAn   int `json:"ateliers_an"`
}

func GetPublicStats(w http.ResponseWriter, r *http.Request) {
	var stats PublicStats

	database.DB.QueryRow("SELECT COUNT(*) FROM annonces WHERE statut IN ('validee','vendue')").Scan(&stats.ObjetsSauves)
	database.DB.QueryRow("SELECT COUNT(*) FROM utilisateurs WHERE role = 'particulier'").Scan(&stats.Membres)
	database.DB.QueryRow(fmt.Sprintf("SELECT COUNT(*) FROM evenements WHERE YEAR(date_debut) = %d", time.Now().Year())).Scan(&stats.AteliersAn)

	w.Header().Set("Content-Type", "application/json")
	w.Header().Set("Cache-Control", "public, max-age=3600")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(stats)
}

func GetPublicEvenements(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements WHERE statut = 'valide' ORDER BY date_debut ASC")
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var evenements []map[string]interface{}
	for rows.Next() {
		var e struct {
			IDEvenement   int
			IDCreateur    int
			Titre         string
			Description   string
			TypeEvenement string
			Format        string
			Lieu          sql.NullString
			DateDebut     time.Time
			DateFin       time.Time
			NbPlacesTotal int
			NbPlacesDispo int
			Prix          float64
			Statut        string
			ValidePar     sql.NullInt64
			DateCreation  time.Time
		}
		if err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation); err != nil {
			continue
		}
		item := map[string]interface{}{
			"id_evenement": e.IDEvenement, "titre": e.Titre, "description": e.Description,
			"type_evenement": e.TypeEvenement, "format": e.Format, "lieu": e.Lieu.String,
			"date_debut": e.DateDebut, "date_fin": e.DateFin,
			"nb_places_total": e.NbPlacesTotal, "nb_places_dispo": e.NbPlacesDispo,
			"prix": e.Prix, "statut": e.Statut,
		}
		evenements = append(evenements, item)
	}
	if evenements == nil {
		evenements = []map[string]interface{}{}
	}
	jsonOK(w, evenements, http.StatusOK)
}

func GetPublicEvenement(w http.ResponseWriter, r *http.Request, id string) {
	var e struct {
		IDEvenement   int
		IDCreateur    int
		Titre         string
		Description   string
		TypeEvenement string
		Format        string
		Lieu          sql.NullString
		DateDebut     time.Time
		DateFin       time.Time
		NbPlacesTotal int
		NbPlacesDispo int
		Prix          float64
		Statut        string
		ValidePar     sql.NullInt64
		DateCreation  time.Time
	}
	err := database.DB.QueryRow("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements WHERE id_evenement = ? AND statut = 'valide'", id).
		Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation)
	if err != nil {
		jsonErr(w, "événement non trouvé", http.StatusNotFound)
		return
	}
	jsonOK(w, map[string]interface{}{
		"id_evenement": e.IDEvenement, "titre": e.Titre, "description": e.Description,
		"type_evenement": e.TypeEvenement, "format": e.Format, "lieu": e.Lieu.String,
		"date_debut": e.DateDebut, "date_fin": e.DateFin,
		"nb_places_total": e.NbPlacesTotal, "nb_places_dispo": e.NbPlacesDispo,
		"prix": e.Prix, "statut": e.Statut,
	}, http.StatusOK)
}

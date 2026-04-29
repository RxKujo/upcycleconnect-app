package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strings"
	"time"
)

// ===== STATS =====

type SalarieStats struct {
	EvenementsAttente int `json:"evenements_attente"`
	EvenementsValides int `json:"evenements_valides"`
	ArticlesBrouillon int `json:"articles_brouillon"`
	ArticlesPublies   int `json:"articles_publies"`
	Signalements      int `json:"signalements"`
}

func GetSalarieStats(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	var s SalarieStats
	database.DB.QueryRow("SELECT COUNT(*) FROM evenements WHERE id_createur = ? AND statut='en_attente'", userId).Scan(&s.EvenementsAttente)
	database.DB.QueryRow("SELECT COUNT(*) FROM evenements WHERE id_createur = ? AND statut='valide'", userId).Scan(&s.EvenementsValides)
	database.DB.QueryRow("SELECT COUNT(*) FROM articles_news WHERE id_auteur = ? AND statut='brouillon'", userId).Scan(&s.ArticlesBrouillon)
	database.DB.QueryRow("SELECT COUNT(*) FROM articles_news WHERE id_auteur = ? AND statut='publie'", userId).Scan(&s.ArticlesPublies)
	database.DB.QueryRow("SELECT COUNT(*) FROM signalements_forum WHERE statut='en_cours'").Scan(&s.Signalements)
	json.NewEncoder(w).Encode(s)
}

// ===== ÉVÉNEMENTS =====

type SalarieEvenement struct {
	IDEvenement   int     `json:"id_evenement"`
	Titre         string  `json:"titre"`
	Description   string  `json:"description"`
	TypeEvenement string  `json:"type_evenement"`
	Format        string  `json:"format"`
	Lieu          *string `json:"lieu,omitempty"`
	DateDebut     string  `json:"date_debut"`
	DateFin       string  `json:"date_fin"`
	NbPlacesTotal int     `json:"nb_places_total"`
	NbPlacesDispo int     `json:"nb_places_dispo"`
	Prix          float64 `json:"prix"`
	Statut        string  `json:"statut"`
}

type CreateEvenementRequest struct {
	Titre         string  `json:"titre"`
	Description   string  `json:"description"`
	TypeEvenement string  `json:"type_evenement"`
	Format        string  `json:"format"`
	Lieu          string  `json:"lieu"`
	DateDebut     string  `json:"date_debut"`
	DateFin       string  `json:"date_fin"`
	NbPlacesTotal int     `json:"nb_places_total"`
	Prix          float64 `json:"prix"`
	IDTemplate    *int    `json:"id_template,omitempty"`
}

func GetSalarieEvenements(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query(`
		SELECT id_evenement, titre, description, type_evenement, format, lieu, date_debut, date_fin,
		       nb_places_total, nb_places_dispo, prix, statut
		FROM evenements WHERE id_createur = ? ORDER BY date_creation DESC
	`, userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	list := []SalarieEvenement{}
	for rows.Next() {
		var e SalarieEvenement
		var lieu sql.NullString
		var debut, fin time.Time
		if err := rows.Scan(&e.IDEvenement, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &lieu,
			&debut, &fin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut); err == nil {
			if lieu.Valid {
				v := lieu.String
				e.Lieu = &v
			}
			e.DateDebut = debut.Format("2006-01-02T15:04:05")
			e.DateFin = fin.Format("2006-01-02T15:04:05")
			list = append(list, e)
		}
	}
	json.NewEncoder(w).Encode(list)
}

func GetSalarieEvenement(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	w.Header().Set("Content-Type", "application/json")
	var e SalarieEvenement
	var lieu sql.NullString
	var debut, fin time.Time
	var idCreateur int
	err := database.DB.QueryRow(`
		SELECT id_createur, id_evenement, titre, description, type_evenement, format, lieu, date_debut, date_fin,
		       nb_places_total, nb_places_dispo, prix, statut
		FROM evenements WHERE id_evenement = ?
	`, id).Scan(&idCreateur, &e.IDEvenement, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &lieu,
		&debut, &fin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "introuvable"})
		return
	}
	if idCreateur != userId && role != "admin" {
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "non autorisé"})
		return
	}
	if lieu.Valid {
		v := lieu.String
		e.Lieu = &v
	}
	e.DateDebut = debut.Format("2006-01-02T15:04:05")
	e.DateFin = fin.Format("2006-01-02T15:04:05")
	json.NewEncoder(w).Encode(e)
}

func CreateSalarieEvenement(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	var req CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}
	if req.NbPlacesTotal < 1 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "nombre de places invalide"})
		return
	}

	var lieu interface{}
	if strings.TrimSpace(req.Lieu) != "" {
		lieu = req.Lieu
	}
	res, err := database.DB.Exec(`
		INSERT INTO evenements (id_createur, id_template, titre, description, type_evenement, format, lieu,
			date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')
	`, userId, req.IDTemplate, req.Titre, req.Description, req.TypeEvenement, req.Format, lieu,
		req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "création impossible: " + err.Error()})
		return
	}
	id, _ := res.LastInsertId()
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]any{"id_evenement": id, "message": "événement créé, en attente de validation"})
}

func UpdateSalarieEvenement(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	w.Header().Set("Content-Type", "application/json")
	var idCreateur int
	var statut string
	if err := database.DB.QueryRow("SELECT id_createur, statut FROM evenements WHERE id_evenement = ?", id).Scan(&idCreateur, &statut); err != nil {
		w.WriteHeader(http.StatusNotFound)
		return
	}
	if idCreateur != userId && role != "admin" {
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "non autorisé"})
		return
	}
	if statut != "en_attente" && role != "admin" {
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "événement déjà validé, non modifiable"})
		return
	}
	var req CreateEvenementRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	var lieu interface{}
	if strings.TrimSpace(req.Lieu) != "" {
		lieu = req.Lieu
	}
	_, err := database.DB.Exec(`
		UPDATE evenements SET titre = ?, description = ?, type_evenement = ?, format = ?, lieu = ?,
			date_debut = ?, date_fin = ?, nb_places_total = ?, nb_places_dispo = ?, prix = ?
		WHERE id_evenement = ?
	`, req.Titre, req.Description, req.TypeEvenement, req.Format, lieu,
		req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix, id)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "mis à jour"})
}

func GetSalarieTemplates(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query("SELECT id_template, nom_template, COALESCE(description, ''), COALESCE(modele, '{}') FROM templates_evenements")
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	type Tpl struct {
		ID     int    `json:"id_template"`
		Nom    string `json:"nom_template"`
		Desc   string `json:"description"`
		Modele json.RawMessage `json:"modele"`
	}
	out := []Tpl{}
	for rows.Next() {
		var t Tpl
		var modeleStr string
		if err := rows.Scan(&t.ID, &t.Nom, &t.Desc, &modeleStr); err == nil {
			t.Modele = json.RawMessage(modeleStr)
			out = append(out, t)
		}
	}
	json.NewEncoder(w).Encode(out)
}

// ===== ARTICLES =====

type Article struct {
	IDArticle        int     `json:"id_article"`
	Titre            string  `json:"titre"`
	Contenu          string  `json:"contenu"`
	Categorie        *string `json:"categorie"`
	Statut           string  `json:"statut"`
	DatePublication  *string `json:"date_publication,omitempty"`
	IDAuteur         int     `json:"id_auteur"`
}

type ArticleRequest struct {
	Titre     string `json:"titre"`
	Contenu   string `json:"contenu"`
	Categorie string `json:"categorie"`
	Statut    string `json:"statut"`
}

func GetArticles(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query(`
		SELECT id_article, titre, contenu, categorie, statut, date_publication, id_auteur
		FROM articles_news ORDER BY id_article DESC
	`)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	out := []Article{}
	for rows.Next() {
		var a Article
		var cat sql.NullString
		var pub sql.NullTime
		if err := rows.Scan(&a.IDArticle, &a.Titre, &a.Contenu, &cat, &a.Statut, &pub, &a.IDAuteur); err == nil {
			if cat.Valid {
				v := cat.String
				a.Categorie = &v
			}
			if pub.Valid {
				v := pub.Time.Format("2006-01-02T15:04:05Z")
				a.DatePublication = &v
			}
			out = append(out, a)
		}
	}
	json.NewEncoder(w).Encode(out)
}

func GetArticle(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	var a Article
	var cat sql.NullString
	var pub sql.NullTime
	err := database.DB.QueryRow(`
		SELECT id_article, titre, contenu, categorie, statut, date_publication, id_auteur
		FROM articles_news WHERE id_article = ?
	`, id).Scan(&a.IDArticle, &a.Titre, &a.Contenu, &cat, &a.Statut, &pub, &a.IDAuteur)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		return
	}
	if cat.Valid {
		v := cat.String
		a.Categorie = &v
	}
	if pub.Valid {
		v := pub.Time.Format("2006-01-02T15:04:05Z")
		a.DatePublication = &v
	}
	json.NewEncoder(w).Encode(a)
}

func CreateArticle(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	var req ArticleRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	if strings.TrimSpace(req.Titre) == "" || strings.TrimSpace(req.Contenu) == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "titre et contenu requis"})
		return
	}
	var cat interface{}
	if strings.TrimSpace(req.Categorie) != "" {
		cat = req.Categorie
	}
	statut := req.Statut
	if statut != "brouillon" && statut != "publie" && statut != "archive" {
		statut = "brouillon"
	}
	var datePub interface{}
	if statut == "publie" {
		datePub = time.Now()
	}
	res, err := database.DB.Exec(`
		INSERT INTO articles_news (id_auteur, titre, contenu, categorie, statut, date_publication)
		VALUES (?, ?, ?, ?, ?, ?)
	`, userId, req.Titre, req.Contenu, cat, statut, datePub)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
		return
	}
	id, _ := res.LastInsertId()
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]any{"id_article": id, "message": "article créé"})
}

func UpdateArticle(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	w.Header().Set("Content-Type", "application/json")
	var auteur int
	var oldStatut string
	if err := database.DB.QueryRow("SELECT id_auteur, statut FROM articles_news WHERE id_article = ?", id).Scan(&auteur, &oldStatut); err != nil {
		w.WriteHeader(http.StatusNotFound)
		return
	}
	if auteur != userId && role != "admin" {
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "non autorisé"})
		return
	}
	var req ArticleRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	var cat interface{}
	if strings.TrimSpace(req.Categorie) != "" {
		cat = req.Categorie
	}
	statut := req.Statut
	if statut != "brouillon" && statut != "publie" && statut != "archive" {
		statut = oldStatut
	}
	if oldStatut != "publie" && statut == "publie" {
		_, err := database.DB.Exec(`UPDATE articles_news SET titre=?, contenu=?, categorie=?, statut=?, date_publication=? WHERE id_article=?`,
			req.Titre, req.Contenu, cat, statut, time.Now(), id)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
			return
		}
	} else {
		_, err := database.DB.Exec(`UPDATE articles_news SET titre=?, contenu=?, categorie=?, statut=? WHERE id_article=?`,
			req.Titre, req.Contenu, cat, statut, id)
		if err != nil {
			w.WriteHeader(http.StatusInternalServerError)
			json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
			return
		}
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "mis à jour"})
}

func DeleteArticle(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	w.Header().Set("Content-Type", "application/json")
	var auteur int
	if err := database.DB.QueryRow("SELECT id_auteur FROM articles_news WHERE id_article = ?", id).Scan(&auteur); err != nil {
		w.WriteHeader(http.StatusNotFound)
		return
	}
	if auteur != userId && role != "admin" {
		w.WriteHeader(http.StatusForbidden)
		return
	}
	if _, err := database.DB.Exec("DELETE FROM articles_news WHERE id_article = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "supprimé"})
}

// ===== MODÉRATION FORUM =====

type Signalement struct {
	IDSignalement   int    `json:"id_signalement"`
	IDMessage       int    `json:"id_message"`
	Motif           string `json:"motif"`
	DateSignalement string `json:"date_signalement"`
	Statut          string `json:"statut"`
	Contenu         string `json:"contenu"`
	AuteurMessage   string `json:"auteur_message"`
	IDSujet         int    `json:"id_sujet"`
	TitreSujet      string `json:"titre_sujet"`
	EstMasque       bool   `json:"est_masque"`
}

func GetSignalements(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query(`
		SELECT s.id_signalement, s.id_message, COALESCE(s.motif, ''), s.date_signalement, s.statut,
		       fm.contenu, CONCAT(u.prenom, ' ', LEFT(u.nom, 1), '.'),
		       fs.id_sujet, fs.titre, fm.est_signale
		FROM signalements_forum s
		JOIN forum_messages fm ON fm.id_message = s.id_message
		JOIN forum_sujets fs ON fs.id_sujet = fm.id_sujet
		JOIN utilisateurs u ON u.id_utilisateur = fm.id_auteur
		ORDER BY s.date_signalement DESC
	`)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
		return
	}
	defer rows.Close()
	out := []Signalement{}
	for rows.Next() {
		var s Signalement
		var dt time.Time
		if err := rows.Scan(&s.IDSignalement, &s.IDMessage, &s.Motif, &dt, &s.Statut,
			&s.Contenu, &s.AuteurMessage, &s.IDSujet, &s.TitreSujet, &s.EstMasque); err == nil {
			s.DateSignalement = dt.Format("2006-01-02T15:04:05Z")
			out = append(out, s)
		}
	}
	json.NewEncoder(w).Encode(out)
}

func MasquerMessage(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	if _, err := database.DB.Exec("UPDATE forum_messages SET est_signale = 1 WHERE id_message = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	database.DB.Exec("UPDATE signalements_forum SET statut = 'traite' WHERE id_message = ?", id)
	json.NewEncoder(w).Encode(map[string]string{"message": "masqué"})
}

func RestaurerMessage(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	if _, err := database.DB.Exec("UPDATE forum_messages SET est_signale = 0 WHERE id_message = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	database.DB.Exec("UPDATE signalements_forum SET statut = 'rejete' WHERE id_message = ?", id)
	json.NewEncoder(w).Encode(map[string]string{"message": "restauré"})
}

type SujetModeration struct {
	IDSujet      int    `json:"id_sujet"`
	Titre        string `json:"titre"`
	Statut       string `json:"statut"`
	DateCreation string `json:"date_creation"`
	NbMessages   int    `json:"nb_messages"`
	Createur     string `json:"createur"`
}

func GetSujetsModeration(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query(`
		SELECT fs.id_sujet, fs.titre, fs.statut, fs.date_creation,
		       (SELECT COUNT(*) FROM forum_messages fm WHERE fm.id_sujet = fs.id_sujet),
		       CONCAT(u.prenom, ' ', LEFT(u.nom, 1), '.')
		FROM forum_sujets fs
		JOIN utilisateurs u ON u.id_utilisateur = fs.id_createur
		ORDER BY fs.date_creation DESC
	`)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	out := []SujetModeration{}
	for rows.Next() {
		var s SujetModeration
		var dt time.Time
		if err := rows.Scan(&s.IDSujet, &s.Titre, &s.Statut, &dt, &s.NbMessages, &s.Createur); err == nil {
			s.DateCreation = dt.Format("2006-01-02T15:04:05Z")
			out = append(out, s)
		}
	}
	json.NewEncoder(w).Encode(out)
}

func LockSujet(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	if _, err := database.DB.Exec("UPDATE forum_sujets SET statut = 'ferme' WHERE id_sujet = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "verrouillé"})
}

func UnlockSujet(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	if _, err := database.DB.Exec("UPDATE forum_sujets SET statut = 'ouvert' WHERE id_sujet = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "rouvert"})
}

type MotBanni struct {
	IDMot     int    `json:"id_mot"`
	Mot       string `json:"mot"`
	DateAjout string `json:"date_ajout"`
}

func GetMotsBannis(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	rows, err := database.DB.Query("SELECT id_mot, mot, date_ajout FROM mots_bannis ORDER BY mot ASC")
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer rows.Close()
	out := []MotBanni{}
	for rows.Next() {
		var m MotBanni
		var dt time.Time
		if err := rows.Scan(&m.IDMot, &m.Mot, &dt); err == nil {
			m.DateAjout = dt.Format("2006-01-02T15:04:05Z")
			out = append(out, m)
		}
	}
	json.NewEncoder(w).Encode(out)
}

func AddMotBanni(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	var req struct{ Mot string `json:"mot"` }
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	mot := strings.ToLower(strings.TrimSpace(req.Mot))
	if mot == "" {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "mot vide"})
		return
	}
	res, err := database.DB.Exec("INSERT INTO mots_bannis (mot, ajoute_par) VALUES (?, ?)", mot, userId)
	if err != nil {
		w.WriteHeader(http.StatusConflict)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ce mot est déjà banni"})
		return
	}
	id, _ := res.LastInsertId()
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]any{"id_mot": id, "mot": mot})
}

func DeleteMotBanni(w http.ResponseWriter, r *http.Request, id string) {
	w.Header().Set("Content-Type", "application/json")
	if _, err := database.DB.Exec("DELETE FROM mots_bannis WHERE id_mot = ?", id); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	json.NewEncoder(w).Encode(map[string]string{"message": "supprimé"})
}

// ===== SIGNALER (côté public, mais nécessite auth) =====

type SignalerRequest struct {
	IDMessage int    `json:"id_message"`
	Motif     string `json:"motif"`
}

func SignalerMessage(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")
	var req SignalerRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		return
	}
	if req.IDMessage <= 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "id_message requis"})
		return
	}
	var exists bool
	database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM forum_messages WHERE id_message = ?)", req.IDMessage).Scan(&exists)
	if !exists {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "message introuvable"})
		return
	}
	var alreadyReported bool
	database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM signalements_forum WHERE id_message = ? AND id_signaleur = ? AND statut = 'en_cours')",
		req.IDMessage, userId).Scan(&alreadyReported)
	if alreadyReported {
		w.WriteHeader(http.StatusConflict)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "vous avez déjà signalé ce message"})
		return
	}
	if _, err := database.DB.Exec("INSERT INTO signalements_forum (id_message, id_signaleur, motif) VALUES (?, ?, ?)",
		req.IDMessage, userId, req.Motif); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
		return
	}
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]string{"message": "signalement enregistré"})
}

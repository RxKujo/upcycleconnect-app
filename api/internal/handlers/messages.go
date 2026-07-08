package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"
)

// Messagerie acheteur ↔ vendeur, liée à une annonce (coordination main propre…).

// --- Helpers ---

func nomInitiale(nom string) string {
	r := []rune(strings.TrimSpace(nom))
	if len(r) == 0 {
		return ""
	}
	return string(r[0]) + "."
}

// participantConversation : renvoie (estParticipant, idAcheteur, idVendeur).
func participantConversation(convID, userID int) (bool, int, int, error) {
	var acheteur, vendeur int
	err := database.DB.QueryRow(
		"SELECT id_acheteur, id_vendeur FROM conversations WHERE id_conversation = ?", convID).
		Scan(&acheteur, &vendeur)
	if err == sql.ErrNoRows {
		return false, 0, 0, nil
	}
	if err != nil {
		return false, 0, 0, err
	}
	return userID == acheteur || userID == vendeur, acheteur, vendeur, nil
}

// --- Handlers ---

// POST /api/v1/conversations { id_annonce }
// Crée ou retrouve la conversation acheteur (courant) ↔ vendeur. Interdit de se contacter soi-même.
func CreateOrGetConversation(w http.ResponseWriter, r *http.Request, userID int) {
	var body struct {
		IDAnnonce int `json:"id_annonce"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil || body.IDAnnonce == 0 {
		jsonErr(w, "id_annonce requis", http.StatusBadRequest)
		return
	}

	var idVendeur int
	err := database.DB.QueryRow(
		"SELECT id_particulier FROM annonces WHERE id_annonce = ?", body.IDAnnonce).Scan(&idVendeur)
	if err == sql.ErrNoRows {
		jsonErr(w, "annonce introuvable", http.StatusNotFound)
		return
	}
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if idVendeur == userID {
		jsonErr(w, "vous ne pouvez pas vous contacter vous-même", http.StatusBadRequest)
		return
	}

	// Conversation existante ?
	var idConv int
	err = database.DB.QueryRow(
		"SELECT id_conversation FROM conversations WHERE id_annonce = ? AND id_acheteur = ?",
		body.IDAnnonce, userID).Scan(&idConv)
	if err == sql.ErrNoRows {
		res, e := database.DB.Exec(
			"INSERT INTO conversations (id_annonce, id_acheteur, id_vendeur) VALUES (?, ?, ?)",
			body.IDAnnonce, userID, idVendeur)
		if e != nil {
			jsonErr(w, "erreur création conversation", http.StatusInternalServerError)
			return
		}
		id64, _ := res.LastInsertId()
		idConv = int(id64)
	} else if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}

	jsonOK(w, map[string]interface{}{"id_conversation": idConv}, http.StatusOK)
}

// GET /api/v1/conversations  — liste des conversations de l'utilisateur.
func GetConversations(w http.ResponseWriter, r *http.Request, userID int) {
	rows, err := database.DB.Query(`
		SELECT c.id_conversation, c.id_annonce, a.titre, c.id_acheteur,
		       ua.prenom, ua.nom, uv.prenom, uv.nom,
		       (SELECT m.contenu   FROM messages m WHERE m.id_conversation = c.id_conversation ORDER BY m.id_message DESC LIMIT 1),
		       (SELECT m.date_envoi FROM messages m WHERE m.id_conversation = c.id_conversation ORDER BY m.id_message DESC LIMIT 1),
		       (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id_conversation AND m.id_expediteur <> ? AND m.lu = 0)
		FROM conversations c
		JOIN annonces a      ON a.id_annonce = c.id_annonce
		JOIN utilisateurs ua ON ua.id_utilisateur = c.id_acheteur
		JOIN utilisateurs uv ON uv.id_utilisateur = c.id_vendeur
		WHERE c.id_acheteur = ? OR c.id_vendeur = ?
		HAVING (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id_conversation) > 0
		ORDER BY (SELECT m.id_message FROM messages m WHERE m.id_conversation = c.id_conversation ORDER BY m.id_message DESC LIMIT 1) DESC`,
		userID, userID, userID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type Conv struct {
		IDConversation int    `json:"id_conversation"`
		IDAnnonce      int    `json:"id_annonce"`
		TitreAnnonce   string `json:"titre_annonce"`
		AutreNom       string `json:"autre_nom"`
		DernierMessage string `json:"dernier_message"`
		DateDernier    string `json:"date_dernier"`
		NonLus         int    `json:"non_lus"`
	}

	list := []Conv{}
	for rows.Next() {
		var c Conv
		var idAcheteur int
		var prenomA, nomA, prenomV, nomV string
		var dernier sql.NullString
		var dateDernier sql.NullTime
		if err := rows.Scan(&c.IDConversation, &c.IDAnnonce, &c.TitreAnnonce, &idAcheteur,
			&prenomA, &nomA, &prenomV, &nomV, &dernier, &dateDernier, &c.NonLus); err != nil {
			continue
		}
		// L'« autre » partie dépend du rôle du user courant.
		if userID == idAcheteur {
			c.AutreNom = strings.TrimSpace(prenomV + " " + nomInitiale(nomV))
		} else {
			c.AutreNom = strings.TrimSpace(prenomA + " " + nomInitiale(nomA))
		}
		if dernier.Valid {
			c.DernierMessage = dernier.String
		}
		c.DateDernier = scanNullTime(dateDernier)
		list = append(list, c)
	}
	jsonOK(w, list, http.StatusOK)
}

// GET /api/v1/conversations/{id}/messages — fil + marque comme lus les messages reçus.
func GetConversationMessages(w http.ResponseWriter, r *http.Request, userID int, idStr string) {
	convID, err := strconv.Atoi(idStr)
	if err != nil {
		jsonErr(w, "id invalide", http.StatusBadRequest)
		return
	}
	ok, acheteur, vendeur, err := participantConversation(convID, userID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if !ok {
		jsonErr(w, "accès refusé", http.StatusForbidden)
		return
	}

	// Marque comme lus les messages reçus.
	database.DB.Exec(
		"UPDATE messages SET lu = 1 WHERE id_conversation = ? AND id_expediteur <> ? AND lu = 0",
		convID, userID)

	// En-tête : annonce + nom de l'autre.
	var idAnnonce int
	var titre, typeAnn, modeRemise, statutAnn, prenomA, nomA, prenomV, nomV string
	var prix sql.NullFloat64
	database.DB.QueryRow(`
		SELECT a.id_annonce, a.titre, a.type_annonce, a.prix, a.mode_remise, a.statut,
		       ua.prenom, ua.nom, uv.prenom, uv.nom
		FROM conversations c
		JOIN annonces a ON a.id_annonce = c.id_annonce
		JOIN utilisateurs ua ON ua.id_utilisateur = c.id_acheteur
		JOIN utilisateurs uv ON uv.id_utilisateur = c.id_vendeur
		WHERE c.id_conversation = ?`, convID).
		Scan(&idAnnonce, &titre, &typeAnn, &prix, &modeRemise, &statutAnn, &prenomA, &nomA, &prenomV, &nomV)
	autreNom := strings.TrimSpace(prenomV + " " + nomInitiale(nomV))
	if userID != acheteur {
		autreNom = strings.TrimSpace(prenomA + " " + nomInitiale(nomA))
	}

	// Première photo de l'annonce (mini-carte).
	var photo sql.NullString
	database.DB.QueryRow(`
		SELECT p.url_photo FROM objets_annonces o
		JOIN photos_objets p ON p.id_objet = o.id_objet
		WHERE o.id_annonce = ? ORDER BY o.id_objet, p.ordre LIMIT 1`, idAnnonce).Scan(&photo)

	annonce := map[string]interface{}{
		"id_annonce":   idAnnonce,
		"titre":        titre,
		"type_annonce": typeAnn,
		"mode_remise":  modeRemise,
		"statut":       statutAnn,
		"est_vendeur":  userID == vendeur,
		"prix":         nil,
		"photo":        nil,
	}
	if prix.Valid {
		annonce["prix"] = prix.Float64
	}
	if photo.Valid {
		annonce["photo"] = photo.String
	}

	rows, err := database.DB.Query(
		"SELECT id_message, id_expediteur, contenu, date_envoi FROM messages WHERE id_conversation = ? ORDER BY id_message ASC",
		convID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type Msg struct {
		IDMessage int    `json:"id_message"`
		Contenu   string `json:"contenu"`
		EstMoi    bool   `json:"est_moi"`
		DateEnvoi string `json:"date_envoi"`
	}
	msgs := []Msg{}
	for rows.Next() {
		var m Msg
		var expediteur int
		var dt time.Time
		if err := rows.Scan(&m.IDMessage, &expediteur, &m.Contenu, &dt); err != nil {
			continue
		}
		m.EstMoi = expediteur == userID
		m.DateEnvoi = dt.Format(time.RFC3339)
		msgs = append(msgs, m)
	}

	jsonOK(w, map[string]interface{}{
		"titre_annonce": titre,
		"autre_nom":     autreNom,
		"annonce":       annonce,
		"messages":      msgs,
	}, http.StatusOK)
}

// PUT /api/v1/conversations/{id}/vendu — le vendeur déclare l'annonce vendue (main propre).
// L'acheteur enregistré est celui de la conversation.
func DeclarerVenduConversation(w http.ResponseWriter, r *http.Request, userID int, idStr string) {
	convID, err := strconv.Atoi(idStr)
	if err != nil {
		jsonErr(w, "id invalide", http.StatusBadRequest)
		return
	}
	var idAnnonce, idAcheteur, idVendeur int
	err = database.DB.QueryRow(
		"SELECT id_annonce, id_acheteur, id_vendeur FROM conversations WHERE id_conversation = ?", convID).
		Scan(&idAnnonce, &idAcheteur, &idVendeur)
	if err == sql.ErrNoRows {
		jsonErr(w, "conversation introuvable", http.StatusNotFound)
		return
	}
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if userID != idVendeur {
		jsonErr(w, "seul le vendeur peut déclarer l'annonce vendue", http.StatusForbidden)
		return
	}

	res, err := database.DB.Exec(
		"UPDATE annonces SET statut = 'vendue', id_acheteur = ?, date_vente = NOW() WHERE id_annonce = ? AND statut = 'validee'",
		idAcheteur, idAnnonce)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "l'annonce n'est pas dans un état vendable (déjà vendue ou non validée)", http.StatusConflict)
		return
	}
	jsonOK(w, map[string]string{"statut": "vendue"}, http.StatusOK)
}

// POST /api/v1/conversations/{id}/messages  { contenu }
func SendMessage(w http.ResponseWriter, r *http.Request, userID int, idStr string) {
	convID, err := strconv.Atoi(idStr)
	if err != nil {
		jsonErr(w, "id invalide", http.StatusBadRequest)
		return
	}
	ok, _, _, err := participantConversation(convID, userID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if !ok {
		jsonErr(w, "accès refusé", http.StatusForbidden)
		return
	}

	var body struct {
		Contenu string `json:"contenu"`
	}
	if err := json.NewDecoder(r.Body).Decode(&body); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	body.Contenu = strings.TrimSpace(body.Contenu)
	if body.Contenu == "" {
		jsonErr(w, "message vide", http.StatusBadRequest)
		return
	}
	if len(body.Contenu) > 5000 {
		jsonErr(w, "message trop long (max 5000 caractères)", http.StatusBadRequest)
		return
	}

	res, err := database.DB.Exec(
		"INSERT INTO messages (id_conversation, id_expediteur, contenu) VALUES (?, ?, ?)",
		convID, userID, body.Contenu)
	if err != nil {
		jsonErr(w, "erreur envoi", http.StatusInternalServerError)
		return
	}
	id64, _ := res.LastInsertId()

	jsonOK(w, map[string]interface{}{
		"id_message": id64,
		"contenu":    body.Contenu,
		"est_moi":    true,
		"date_envoi": time.Now().Format(time.RFC3339),
	}, http.StatusCreated)
}

// GET /api/v1/messages/unread-count — total non lus (pour la pastille du widget).
func GetUnreadCount(w http.ResponseWriter, r *http.Request, userID int) {
	var n int
	database.DB.QueryRow(`
		SELECT COUNT(*)
		FROM messages m
		JOIN conversations c ON c.id_conversation = m.id_conversation
		WHERE (c.id_acheteur = ? OR c.id_vendeur = ?)
		  AND m.id_expediteur <> ? AND m.lu = 0`,
		userID, userID, userID).Scan(&n)
	jsonOK(w, map[string]int{"non_lus": n}, http.StatusOK)
}

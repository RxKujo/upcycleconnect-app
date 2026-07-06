package handlers

import (
	"api/internal/middleware"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"time"
)

// ─── Conteneurs — côté professionnel ─────────────────────────────────────────

type commandeConteneurPro struct {
	IDCommande    int     `json:"id_commande"`
	IDAnnonce     int     `json:"id_annonce"`
	TitreAnnonce  string  `json:"titre_annonce"`
	ConteneurRef  string  `json:"conteneur_ref"`
	AdresseCont   string  `json:"adresse_conteneur"`
	Statut        string  `json:"statut"`
	DateLimite    *string `json:"date_limite_recuperation"`
	CodeBarre     *string `json:"code_barre"`
}

// GetCommandesEnConteneur liste les commandes en attente de récupération pour le pro.
func GetCommandesEnConteneur(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireEssentialPro(userID, w)
	if !ok {
		return
	}

	rows, err := database.DB.Query(`
		SELECT c.id_commande, c.id_annonce, a.titre,
		       ct.conteneur_ref, ct.adresse,
		       c.statut,
		       DATE_FORMAT(c.date_limite_recuperation, '%Y-%m-%dT%H:%i:%s'),
		       cb.code_valeur
		FROM commandes c
		JOIN annonces a   ON a.id_annonce   = c.id_annonce
		JOIN conteneurs ct ON ct.id_conteneur = c.id_conteneur
		LEFT JOIN codes_barres cb ON cb.id_commande = c.id_commande
		                          AND cb.type_code   = 'recuperation_pro'
		WHERE c.id_acheteur = ?
		  AND c.statut IN ('en_conteneur')
		ORDER BY c.date_limite_recuperation ASC`, userID)
	if err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var commandes []commandeConteneurPro
	for rows.Next() {
		var cmd commandeConteneurPro
		var dlim, code sql.NullString
		rows.Scan(
			&cmd.IDCommande, &cmd.IDAnnonce, &cmd.TitreAnnonce,
			&cmd.ConteneurRef, &cmd.AdresseCont,
			&cmd.Statut, &dlim, &code,
		) //nolint:errcheck
		if dlim.Valid { cmd.DateLimite = &dlim.String }
		if code.Valid { cmd.CodeBarre = &code.String }
		commandes = append(commandes, cmd)
	}
	jsonOK(w, commandes, http.StatusOK)
}

type historiqueRecupPro struct {
	IDCommande     int     `json:"id_commande"`
	TitreAnnonce   string  `json:"titre_annonce"`
	ConteneurRef   string  `json:"conteneur_ref"`
	AdresseCont    string  `json:"adresse_conteneur"`
	Statut         string  `json:"statut"` // recuperee | expiree
	DateRecuperee  *string `json:"date_recuperee"`
	DateLimite     *string `json:"date_limite_recuperation"`
}

// GetHistoriqueRecuperations liste les récupérations passées du pro (récupérées + expirées).
func GetHistoriqueRecuperations(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireEssentialPro(userID, w)
	if !ok {
		return
	}

	rows, err := database.DB.Query(`
		SELECT c.id_commande, a.titre,
		       ct.conteneur_ref, ct.adresse, c.statut,
		       DATE_FORMAT(cb.date_utilisation, '%Y-%m-%dT%H:%i:%s'),
		       DATE_FORMAT(c.date_limite_recuperation, '%Y-%m-%dT%H:%i:%s')
		FROM commandes c
		JOIN annonces a    ON a.id_annonce    = c.id_annonce
		JOIN conteneurs ct ON ct.id_conteneur = c.id_conteneur
		LEFT JOIN codes_barres cb ON cb.id_commande = c.id_commande
		                          AND cb.type_code   = 'recuperation_pro'
		WHERE c.id_acheteur = ?
		  AND c.statut IN ('recuperee','expiree')
		ORDER BY COALESCE(cb.date_utilisation, c.date_limite_recuperation) DESC`, userID)
	if err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var historique []historiqueRecupPro
	for rows.Next() {
		var h historiqueRecupPro
		var dateRecup, dateLim sql.NullString
		rows.Scan(&h.IDCommande, &h.TitreAnnonce, &h.ConteneurRef, &h.AdresseCont,
			&h.Statut, &dateRecup, &dateLim) //nolint:errcheck
		if dateRecup.Valid {
			h.DateRecuperee = &dateRecup.String
		}
		if dateLim.Valid {
			h.DateLimite = &dateLim.String
		}
		historique = append(historique, h)
	}
	jsonOK(w, historique, http.StatusOK)
}

// ValiderReceptionConteneur marque une commande comme récupérée via le code-barre.
func ValiderReceptionConteneur(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireEssentialPro(userID, w)
	if !ok {
		return
	}

	var req struct {
		CodeBarre string `json:"code_barre"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.CodeBarre == "" {
		jsonErr(w, "code_barre requis", http.StatusBadRequest)
		return
	}

	// Vérifier que le code-barre correspond à une commande du pro
	var commandeID int
	err := database.DB.QueryRow(`
		SELECT c.id_commande
		FROM codes_barres cb
		JOIN commandes c ON c.id_commande = cb.id_commande
		WHERE cb.code_valeur = ?
		  AND cb.type_code   = 'recuperation_pro'
		  AND cb.date_utilisation IS NULL
		  AND c.id_acheteur = ?
		  AND c.statut = 'en_conteneur'`, req.CodeBarre, userID).Scan(&commandeID)
	if err == sql.ErrNoRows {
		jsonErr(w, "code-barre invalide, déjà utilisé, ou commande non trouvée", http.StatusNotFound)
		return
	}
	if err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}

	// Vérifier le délai de récupération (1 semaine)
	var dateLimit sql.NullTime
	database.DB.QueryRow(`SELECT date_limite_recuperation FROM commandes WHERE id_commande = ?`, commandeID).Scan(&dateLimit) //nolint:errcheck
	if dateLimit.Valid && time.Now().After(dateLimit.Time) {
		jsonErr(w, "délai de récupération dépassé (1 semaine)", http.StatusGone)
		return
	}

	if err := finaliserRecuperation(commandeID, userID, req.CodeBarre); err != nil {
		jsonErr(w, "erreur lors de la validation", http.StatusInternalServerError)
		return
	}

	jsonOK(w, map[string]interface{}{
		"message":     "réception validée",
		"id_commande": commandeID,
	}, http.StatusOK)
}

// finaliserRecuperation est LE point unique de finalisation d'une récupération
// (modèle self-scan). Dans une transaction : passe la commande en 'recuperee',
// marque le code-barre utilisé. Puis crédite l'Upcycling Score (tous plans) et
// recalcule les badges (Expert Pro uniquement).
func finaliserRecuperation(commandeID, userID int, codeBarre string) error {
	tx, err := database.DB.Begin()
	if err != nil {
		return err
	}
	defer tx.Rollback() //nolint:errcheck

	now := time.Now()
	if _, err := tx.Exec(`UPDATE commandes SET statut = 'recuperee' WHERE id_commande = ?`, commandeID); err != nil {
		return err
	}
	if _, err := tx.Exec(`UPDATE codes_barres SET date_utilisation = ? WHERE code_valeur = ?`, now, codeBarre); err != nil {
		return err
	}
	if err := tx.Commit(); err != nil {
		return err
	}

	// Créditer l'Upcycling Score du vendeur et de l'acheteur (tous les plans).
	services.AwardScoreForCommande(commandeID)

	// Recalculer les badges après récupération (plans avec badges uniquement).
	plan, _ := middleware.GetUserPlanInfo(userID)
	if plan != nil && plan.BadgesActives {
		go services.ComputeAndAwardBadges(userID) //nolint:errcheck
	}
	return nil
}

// NotifierArriveeConteneur est appelé par le worker/admin lors du scan dépôt.
// Envoie une notification push OneSignal au pro acheteur.
func NotifierArriveeConteneur(w http.ResponseWriter, r *http.Request) {
	var req struct {
		CommandeID   int    `json:"id_commande"`
		ConteneurRef string `json:"conteneur_ref"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	var playerID string
	var acheteurID int
	err := database.DB.QueryRow(`
		SELECT c.id_acheteur, u.onesignal_player_id
		FROM commandes c
		JOIN utilisateurs u ON u.id_utilisateur = c.id_acheteur
		WHERE c.id_commande = ?`, req.CommandeID).Scan(&acheteurID, &playerID)
	if err != nil {
		jsonErr(w, "commande introuvable", http.StatusNotFound)
		return
	}

	if playerID != "" {
		go services.NotifierObjetsEnConteneur(playerID, req.CommandeID, req.ConteneurRef) //nolint:errcheck
	}

	jsonOK(w, map[string]string{"message": "notification envoyée"}, http.StatusOK)
}

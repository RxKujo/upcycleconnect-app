package handlers

// Publicites : cote pro (creation, suppression, depenses), affichage public WRR, moderation admin.

import (
	"api/internal/middleware"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
)

// ─── Publicités — côté professionnel ─────────────────────────────────────────

const maxPubsParPro = 5
const coutMensuelPub = 100.00

// GetMesPublicites : publicités du pro connecté.
func GetMesPublicites(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.PublicitesActives },
		"publicités non incluses dans votre abonnement")
	if !ok {
		return
	}

	pubs, err := services.GetPublicitesPro(userID)
	if err != nil {
		jsonErr(w, "erreur chargement publicités", http.StatusInternalServerError)
		return
	}
	jsonOK(w, pubs, http.StatusOK)
}

// CreatePublicitePro : crée une pub (statut en_attente, validation admin requise).
func CreatePublicitePro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.PublicitesActives },
		"publicités non incluses dans votre abonnement")
	if !ok {
		return
	}

	count, err := services.CountPublicitesActivesPro(userID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if count >= maxPubsParPro {
		jsonErr(w, "maximum 5 publicités actives par professionnel", http.StatusForbidden)
		return
	}

	var req struct {
		Titre     string `json:"titre"`
		VisuelURL string `json:"visuel_url"`
		URLCible  string `json:"url_cible"`
		DateDebut string `json:"date_debut"`
		DateFin   string `json:"date_fin"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Titre == "" {
		jsonErr(w, "données invalides (titre obligatoire)", http.StatusBadRequest)
		return
	}

	res, err := database.DB.Exec(`
		INSERT INTO publicites
		  (id_professionnel, titre, visuel_url, url_cible, date_debut, date_fin,
		   statut, cout_mensuel, poids_affichage)
		VALUES (?, ?, NULLIF(?,  ''), NULLIF(?, ''),
		        NULLIF(?, ''), NULLIF(?, ''),
		        'en_attente', ?, 1)`,
		userID, req.Titre, req.VisuelURL, req.URLCible,
		req.DateDebut, req.DateFin, coutMensuelPub)
	if err != nil {
		jsonErr(w, "impossible de créer la publicité", http.StatusInternalServerError)
		return
	}

	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{
		"message":      "publicité soumise, en attente de validation admin",
		"id_publicite": id,
		"cout_mensuel": coutMensuelPub,
	}, http.StatusCreated)
}

// DeletePublicitePro : supprime une pub et annule sa subscription Stripe.
func DeletePublicitePro(w http.ResponseWriter, r *http.Request, pubID string, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.PublicitesActives },
		"publicités non incluses dans votre abonnement")
	if !ok {
		return
	}

	var stripeSubID sql.NullString
	var statut string
	err := database.DB.QueryRow(
		`SELECT COALESCE(stripe_subscription_id,''), statut FROM publicites WHERE id_publicite = ? AND id_professionnel = ?`,
		pubID, userID,
	).Scan(&stripeSubID, &statut)
	if err != nil {
		jsonErr(w, "publicité introuvable", http.StatusNotFound)
		return
	}

	deletable := map[string]bool{"en_attente": true, "refusee": true, "expiree": true, "active": true, "suspendue": true}
	if !deletable[statut] {
		jsonErr(w, "publicité non supprimable (statut : "+statut+")", http.StatusForbidden)
		return
	}

	// Annuler la subscription Stripe si elle existe.
	if stripeSubID.Valid && stripeSubID.String != "" {
		if err := services.CancelPubSubscription(stripeSubID.String); err != nil {
			logError("DeletePublicitePro", "annulation Stripe sub=%s: %v", stripeSubID.String, err)
			// On continue : Stripe enverra un webhook subscription.deleted.
		}
	}

	if _, err := database.DB.Exec(
		`DELETE FROM publicites WHERE id_publicite = ? AND id_professionnel = ?`, pubID, userID,
	); err != nil {
		jsonErr(w, "erreur suppression", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "publicité supprimée"}, http.StatusOK)
}

// GetProDepensesMois : répartition des dépenses du pro pour le mois en cours (objets, pubs, abonnement).
func GetProDepensesMois(w http.ResponseWriter, r *http.Request, userID int) {
	// Pubs : chaque pub active due au tarif mensuel (mois entamé = mois payé).
	var nbPubsActives int
	database.DB.QueryRow(
		`SELECT COUNT(*) FROM publicites WHERE id_professionnel = ? AND statut = 'active'`,
		userID).Scan(&nbPubsActives)
	depensePub := float64(nbPubsActives) * coutMensuelPub

	// Abonnement : prix mensuel du plan actif.
	var depenseAbo float64
	database.DB.QueryRow(`
		SELECT COALESCE(a.prix_mensuel, 0)
		FROM souscriptions s
		JOIN abonnements a ON a.id_abonnement = s.id_abonnement
		WHERE s.id_utilisateur = ? AND s.est_active = 1
		ORDER BY s.date_debut DESC LIMIT 1`, userID).Scan(&depenseAbo)

	// Objets : total des achats du mois en cours.
	var depenseObjets float64
	database.DB.QueryRow(`
		SELECT COALESCE(SUM(COALESCE(a.prix, 0)), 0)
		FROM commandes c
		JOIN annonces a ON a.id_annonce = c.id_annonce
		WHERE c.id_acheteur = ?
		  AND MONTH(c.date_commande) = MONTH(NOW())
		  AND YEAR(c.date_commande)  = YEAR(NOW())`, userID).Scan(&depenseObjets)

	jsonOK(w, map[string]interface{}{
		"objets":          depenseObjets,
		"publicite":       depensePub,
		"abonnement":      depenseAbo,
		"total":           depensePub + depenseAbo + depenseObjets,
		"nb_pubs_actives": nbPubsActives,
	}, http.StatusOK)
}

// ─── Publicités — côté public (affichage WRR sur pages particuliers) ─────────

// GetPublicitesActives : jusqu'à 3 pubs via WRR. Route publique (pas d'auth).
func GetPublicitesActives(w http.ResponseWriter, r *http.Request) {
	pubs, err := services.PickPublicitesWRR(3)
	if err != nil {
		jsonErr(w, "erreur chargement publicités", http.StatusInternalServerError)
		return
	}
	if pubs == nil {
		pubs = []services.PubliciteAffichage{}
	}
	jsonOK(w, pubs, http.StatusOK)
}

// EnregistrerClicPub : incrémente le compteur de clics d'une pub.
func EnregistrerClicPub(w http.ResponseWriter, r *http.Request, pubID string) {
	if err := services.EnregistrerClicPublicite(parseIntOrZero(pubID)); err != nil {
		jsonErr(w, "erreur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "clic enregistré"}, http.StatusOK)
}

// ─── Publicités — admin ───────────────────────────────────────────────────────

// AdminValiderPublicite : valide une pub en attente, crée la subscription Stripe et la met en ligne.
func AdminValiderPublicite(w http.ResponseWriter, r *http.Request, pubID string, adminID int) {
	// proID nécessaire pour la subscription Stripe.
	var proID int
	err := database.DB.QueryRow(
		`SELECT id_professionnel FROM publicites WHERE id_publicite = ? AND statut = 'en_attente'`, pubID,
	).Scan(&proID)
	if err != nil {
		jsonErr(w, "publicité introuvable ou déjà traitée", http.StatusNotFound)
		return
	}

	// Subscription Stripe (no-op si STRIPE_PRICE_PUB_MENSUEL non défini).
	stripeSubID, err := services.CreatePubSubscription(proID, parseIntOrZero(pubID))
	if err != nil {
		logError("AdminValiderPublicite", "Stripe subscription pub=%s: %v", pubID, err)
		jsonErr(w, "erreur création abonnement Stripe : "+err.Error(), http.StatusInternalServerError)
		return
	}

	var stripeSubNullable *string
	if stripeSubID != "" {
		stripeSubNullable = &stripeSubID
	}

	res, err := database.DB.Exec(`
		UPDATE publicites
		SET statut = 'active', valide_par = ?, stripe_subscription_id = ?
		WHERE id_publicite = ? AND statut = 'en_attente'`,
		adminID, stripeSubNullable, pubID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "publicité introuvable ou déjà traitée", http.StatusNotFound)
		return
	}
	// Initialise l'entrée de rotation WRR.
	database.DB.Exec(`INSERT IGNORE INTO publicites_rotation (id_publicite) VALUES (?)`, pubID) //nolint:errcheck
	jsonOK(w, map[string]string{"message": "publicité validée et mise en ligne"}, http.StatusOK)
}

// AdminRefuserPublicite : refuse une pub en attente.
func AdminRefuserPublicite(w http.ResponseWriter, r *http.Request, pubID string, adminID int) {
	var req struct {
		Motif string `json:"motif"`
	}
	json.NewDecoder(r.Body).Decode(&req) //nolint:errcheck

	var motif interface{}
	if req.Motif != "" {
		motif = req.Motif
	}
	_, err := database.DB.Exec(`
		UPDATE publicites
		SET statut = 'refusee', valide_par = ?, motif_refus = ?
		WHERE id_publicite = ? AND statut = 'en_attente'`,
		adminID, motif, pubID)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	jsonOK(w, map[string]string{"message": "publicité refusée"}, http.StatusOK)
}

// AdminGetPublicites : toutes les pubs (admin), filtre optionnel par statut.
func AdminGetPublicites(w http.ResponseWriter, r *http.Request) {
	type pubAdmin struct {
		IDPublicite int     `json:"id_publicite"`
		Titre       string  `json:"titre"`
		Statut      string  `json:"statut"`
		Cout        float64 `json:"cout_mensuel"`
		NbVues      int     `json:"nb_vues"`
		NbClics     int     `json:"nb_clics"`
		Entreprise  string  `json:"nom_entreprise"`
		VisuelURL   string  `json:"visuel_url"`
		URLCible    string  `json:"url_cible"`
		MotifRefus  string  `json:"motif_refus"`
		DateDebut   *string `json:"date_debut"`
		DateFin     *string `json:"date_fin"`
	}

	const base = `
		SELECT p.id_publicite, p.titre, p.statut,
		       COALESCE(p.cout_mensuel, 100.00), p.nb_vues, p.nb_clics,
		       COALESCE(u.nom_entreprise,''),
		       COALESCE(p.visuel_url,''), COALESCE(p.url_cible,''), COALESCE(p.motif_refus,''),
		       DATE_FORMAT(p.date_debut,'%Y-%m-%dT%H:%i:%s'),
		       DATE_FORMAT(p.date_fin,'%Y-%m-%dT%H:%i:%s')
		FROM publicites p
		JOIN utilisateurs u ON u.id_utilisateur = p.id_professionnel`

	statut := r.URL.Query().Get("statut")
	var (
		dbRows *sql.Rows
		err    error
	)
	if statut != "" {
		dbRows, err = database.DB.Query(base+" WHERE p.statut = ? ORDER BY p.id_publicite DESC", statut)
	} else {
		dbRows, err = database.DB.Query(base + " ORDER BY p.id_publicite DESC")
	}
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer dbRows.Close()

	var pubs []pubAdmin
	for dbRows.Next() {
		var p pubAdmin
		var debut, fin sql.NullString
		if err := dbRows.Scan(&p.IDPublicite, &p.Titre, &p.Statut, &p.Cout,
			&p.NbVues, &p.NbClics, &p.Entreprise, &p.VisuelURL, &p.URLCible, &p.MotifRefus, &debut, &fin); err != nil {
			jsonErr(w, "erreur serveur", http.StatusInternalServerError)
			return
		}
		if debut.Valid {
			p.DateDebut = &debut.String
		}
		if fin.Valid {
			p.DateFin = &fin.String
		}
		pubs = append(pubs, p)
	}
	jsonOK(w, pubs, http.StatusOK)
}

// ─── Statistiques publicités — delta ticket 4 ────────────────────────────────

// AdminGetPublicitesStats : stats par campagne (vues, clics, CTR).
func AdminGetPublicitesStats(w http.ResponseWriter, r *http.Request) {
	type pubStats struct {
		IDPublicite int     `json:"id_publicite"`
		Titre       string  `json:"titre"`
		Statut      string  `json:"statut"`
		Entreprise  string  `json:"nom_entreprise"`
		NbVues      int     `json:"nb_vues"`
		NbClics     int     `json:"nb_clics"`
		CTR         float64 `json:"ctr_pct"` // clics/vues * 100
		CoutMensuel float64 `json:"cout_mensuel"`
	}

	rows, err := database.DB.Query(`
		SELECT p.id_publicite, p.titre, p.statut,
		       COALESCE(u.nom_entreprise,''), p.nb_vues, p.nb_clics,
		       COALESCE(p.cout_mensuel, 100.00)
		FROM publicites p
		JOIN utilisateurs u ON u.id_utilisateur = p.id_professionnel
		ORDER BY p.nb_vues DESC`)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var stats []pubStats
	for rows.Next() {
		var s pubStats
		if err := rows.Scan(&s.IDPublicite, &s.Titre, &s.Statut,
			&s.Entreprise, &s.NbVues, &s.NbClics, &s.CoutMensuel); err == nil {
			if s.NbVues > 0 {
				s.CTR = float64(s.NbClics) / float64(s.NbVues) * 100.0
			}
			stats = append(stats, s)
		}
	}
	if stats == nil {
		stats = []pubStats{}
	}
	jsonOK(w, stats, http.StatusOK)
}

// AdminGetRotationWRR : état de publicites_rotation, pour auditer la répartition sans SQL direct.
func AdminGetRotationWRR(w http.ResponseWriter, r *http.Request) {
	type rotationEntry struct {
		IDPublicite   int    `json:"id_publicite"`
		Titre         string `json:"titre"`
		Entreprise    string `json:"nom_entreprise"`
		Statut        string `json:"statut"`
		Poids         int    `json:"poids_affichage"`
		ScoreRotation int64  `json:"score_rotation"`
		NbAffichages  int64  `json:"nb_affichages"`
		DerniereVue   string `json:"derniere_vue,omitempty"`
	}

	rows, err := database.DB.Query(`
		SELECT p.id_publicite, p.titre, COALESCE(u.nom_entreprise,''), p.statut,
		       p.poids_affichage,
		       COALESCE(pr.score_rotation, 0),
		       COALESCE(pr.nb_affichages, 0),
		       COALESCE(DATE_FORMAT(pr.derniere_vue,'%Y-%m-%dT%H:%i:%s'), '')
		FROM publicites p
		JOIN utilisateurs u ON u.id_utilisateur = p.id_professionnel
		LEFT JOIN publicites_rotation pr ON pr.id_publicite = p.id_publicite
		WHERE p.statut = 'active'
		ORDER BY pr.score_rotation DESC, p.id_publicite ASC`)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var entries []rotationEntry
	for rows.Next() {
		var e rotationEntry
		if err := rows.Scan(&e.IDPublicite, &e.Titre, &e.Entreprise, &e.Statut,
			&e.Poids, &e.ScoreRotation, &e.NbAffichages, &e.DerniereVue); err == nil {
			entries = append(entries, e)
		}
	}
	if entries == nil {
		entries = []rotationEntry{}
	}
	jsonOK(w, map[string]interface{}{
		"description":  "Score WRR : plus le score est élevé, plus la pub sera sélectionnée au prochain appel WRR. Un score de 0 signifie qu'elle vient d'être affichée.",
		"pubs_actives": entries,
	}, http.StatusOK)
}

// ─── Utilitaires ─────────────────────────────────────────────────────────────

// parseIntOrZero : chaine -> entier positif, 0 si invalide.
func parseIntOrZero(s string) int {
	n := 0
	for _, c := range s {
		if c < '0' || c > '9' {
			return 0
		}
		n = n*10 + int(c-'0')
	}
	return n
}

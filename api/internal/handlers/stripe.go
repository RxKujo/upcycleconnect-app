package handlers

import (
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/stripe/stripe-go/v82"
	portalsession "github.com/stripe/stripe-go/v82/billingportal/session"
	checkoutsession "github.com/stripe/stripe-go/v82/checkout/session"
	stripecustomer "github.com/stripe/stripe-go/v82/customer"
	stripepaymentintent "github.com/stripe/stripe-go/v82/paymentintent"
	stripeprice "github.com/stripe/stripe-go/v82/price"
	stripeproduct "github.com/stripe/stripe-go/v82/product"
	stripewebhook "github.com/stripe/stripe-go/v82/webhook"
)

func init() {
	stripe.Key = os.Getenv("STRIPE_SECRET_KEY")
}

// GetAbonnementsPublic retourne les plans d'abonnement pro avec les prix (public, pas d'auth)
func GetAbonnementsPublic(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	rows, err := database.DB.Query(`
		SELECT id_abonnement, nom, prix_mensuel, prix_annuel, type_cible, COALESCE(description, ''),
		       nb_alertes_max, rayon_alerte_max_km, dashboard_annuel, badges_actives
		FROM abonnements
		WHERE type_cible = 'professionnel'
		ORDER BY prix_mensuel ASC
	`)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	type Plan struct {
		IDAbonnement  int      `json:"id_abonnement"`
		Nom           string   `json:"nom"`
		PrixMensuel   float64  `json:"prix_mensuel"`
		PrixAnnuel    *float64 `json:"prix_annuel"`
		TypeCible     string   `json:"type_cible"`
		Description   string   `json:"description"`
		NbAlertesMax  *int     `json:"nb_alertes_max"`
		RayonMax      *int     `json:"rayon_alerte_max_km"`
		DashboardAnnuel bool   `json:"dashboard_annuel"`
		BadgesActives bool     `json:"badges_actives"`
	}

	var plans []Plan
	for rows.Next() {
		var p Plan
		var prixAnnuel sql.NullFloat64
		var nbAlertes sql.NullInt64
		var rayon sql.NullInt64
		if err := rows.Scan(&p.IDAbonnement, &p.Nom, &p.PrixMensuel, &prixAnnuel, &p.TypeCible,
			&p.Description, &nbAlertes, &rayon, &p.DashboardAnnuel, &p.BadgesActives); err == nil {
			if prixAnnuel.Valid {
				v := prixAnnuel.Float64
				p.PrixAnnuel = &v
			}
			if nbAlertes.Valid {
				v := int(nbAlertes.Int64)
				p.NbAlertesMax = &v
			}
			if rayon.Valid {
				v := int(rayon.Int64)
				p.RayonMax = &v
			}
			plans = append(plans, p)
		}
	}
	if plans == nil {
		plans = []Plan{}
	}
	json.NewEncoder(w).Encode(plans)
}

// GetStripeConfig retourne la clé publique Stripe au frontend
func GetStripeConfig(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{
		"publishable_key": os.Getenv("STRIPE_PUBLISHABLE_KEY"),
	})
}

// getOrCreateStripeCustomer trouve ou crée un customer Stripe pour un utilisateur
func getOrCreateStripeCustomer(userId int) (string, error) {
	var stripeCustomerID sql.NullString
	var email, nom, prenom string
	err := database.DB.QueryRow(
		`SELECT stripe_customer_id, email, nom, prenom FROM utilisateurs WHERE id_utilisateur = ?`,
		userId,
	).Scan(&stripeCustomerID, &email, &nom, &prenom)
	if err != nil {
		return "", fmt.Errorf("utilisateur introuvable: %w", err)
	}

	if stripeCustomerID.Valid && stripeCustomerID.String != "" {
		return stripeCustomerID.String, nil
	}

	params := &stripe.CustomerParams{
		Email: stripe.String(email),
		Name:  stripe.String(prenom + " " + nom),
		Metadata: map[string]string{
			"user_id": strconv.Itoa(userId),
		},
	}
	c, err := stripecustomer.New(params)
	if err != nil {
		return "", fmt.Errorf("erreur création client Stripe: %w", err)
	}

	_, err = database.DB.Exec(
		`UPDATE utilisateurs SET stripe_customer_id = ? WHERE id_utilisateur = ?`,
		c.ID, userId,
	)
	if err != nil {
		log.Printf("Erreur sauvegarde stripe_customer_id: %v", err)
	}

	return c.ID, nil
}

// StripeCheckoutAbonnement crée une Stripe Checkout Session pour un abonnement
func StripeCheckoutAbonnement(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req struct {
		IDAbonnement int    `json:"id_abonnement"`
		Periodicite  string `json:"periodicite"` // "mensuel" ou "annuel"
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}
	if req.Periodicite != "mensuel" && req.Periodicite != "annuel" {
		req.Periodicite = "mensuel"
	}

	var priceIDMensuel, priceIDAnnuel sql.NullString
	var nom string
	err := database.DB.QueryRow(
		`SELECT nom, stripe_price_id_mensuel, stripe_price_id_annuel FROM abonnements WHERE id_abonnement = ?`,
		req.IDAbonnement,
	).Scan(&nom, &priceIDMensuel, &priceIDAnnuel)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "abonnement introuvable"})
		return
	}

	var priceID string
	if req.Periodicite == "annuel" {
		if !priceIDAnnuel.Valid || priceIDAnnuel.String == "" {
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "tarif annuel non configuré — lancez la synchronisation Stripe"})
			return
		}
		priceID = priceIDAnnuel.String
	} else {
		if !priceIDMensuel.Valid || priceIDMensuel.String == "" {
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "tarif mensuel non configuré — lancez la synchronisation Stripe"})
			return
		}
		priceID = priceIDMensuel.String
	}

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	baseURL := os.Getenv("APP_URL")
	if baseURL == "" {
		baseURL = "http://localhost:8000"
	}

	params := &stripe.CheckoutSessionParams{
		Customer: stripe.String(customerID),
		Mode:     stripe.String(string(stripe.CheckoutSessionModeSubscription)),
		LineItems: []*stripe.CheckoutSessionLineItemParams{
			{
				Price:    stripe.String(priceID),
				Quantity: stripe.Int64(1),
			},
		},
		Metadata: map[string]string{
			"user_id":       strconv.Itoa(userId),
			"id_abonnement": strconv.Itoa(req.IDAbonnement),
			"periodicite":   req.Periodicite,
		},
		SuccessURL: stripe.String(baseURL + "/abonnement/succes?session_id={CHECKOUT_SESSION_ID}"),
		CancelURL:  stripe.String(baseURL + "/abonnement/annule"),
	}

	s, err := checkoutsession.New(params)
	if err != nil {
		log.Printf("Erreur Stripe Checkout abonnement: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur création session de paiement"})
		return
	}

	json.NewEncoder(w).Encode(map[string]string{"url": s.URL})
}

// StripePortal crée une session du portail de gestion Stripe
func StripePortal(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	baseURL := os.Getenv("APP_URL")
	if baseURL == "" {
		baseURL = "http://localhost:8000"
	}

	params := &stripe.BillingPortalSessionParams{
		Customer:  stripe.String(customerID),
		ReturnURL: stripe.String(baseURL + "/professionnel/abonnement"),
	}

	ps, err := portalsession.New(params)
	if err != nil {
		log.Printf("Erreur Stripe Portal: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur portail de gestion"})
		return
	}

	json.NewEncoder(w).Encode(map[string]string{"url": ps.URL})
}

// StripePaymentIntentCommande crée un Payment Intent pour l'achat d'une annonce
func StripePaymentIntentCommande(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req struct {
		IDAnnonce  int    `json:"id_annonce"`
		ModeRemise string `json:"mode_remise"` // "conteneur" ou "main_propre"
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}
	if req.ModeRemise != "conteneur" && req.ModeRemise != "main_propre" {
		req.ModeRemise = "main_propre"
	}

	detail, errStatus, err := ValidateAnnonceForPurchase(req.IDAnnonce, userId)
	if err != nil {
		w.WriteHeader(errStatus)
		json.NewEncoder(w).Encode(map[string]string{"erreur": err.Error()})
		return
	}

	amountCents := int64(detail.Total * 100)

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	params := &stripe.PaymentIntentParams{
		Amount:   stripe.Int64(amountCents),
		Currency: stripe.String("eur"),
		Customer: stripe.String(customerID),
		Metadata: map[string]string{
			"type":           "commande",
			"id_annonce":     strconv.Itoa(req.IDAnnonce),
			"id_acheteur":    strconv.Itoa(userId),
			"id_vendeur":     strconv.Itoa(detail.IDVendeur),
			"commission_pct": fmt.Sprintf("%.2f", detail.CommissionPct),
			"mode_remise":    req.ModeRemise,
		},
		AutomaticPaymentMethods: &stripe.PaymentIntentAutomaticPaymentMethodsParams{
			Enabled: stripe.Bool(true),
		},
	}

	pi, err := stripepaymentintent.New(params)
	if err != nil {
		log.Printf("Erreur PaymentIntent commande: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur création paiement"})
		return
	}

	json.NewEncoder(w).Encode(map[string]any{
		"client_secret":  pi.ClientSecret,
		"payment_intent": pi.ID,
		"montant_total":  detail.Total,
		"prix_article":   detail.Prix,
		"commission":     detail.Commission,
		"commission_pct": detail.CommissionPct,
		"titre":          detail.Titre,
	})
}

// StripePaymentIntentEvenement crée un Payment Intent pour une place d'événement
func StripePaymentIntentEvenement(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req struct {
		IDEvenement int `json:"id_evenement"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	var titre string
	var prix sql.NullFloat64
	var placesDispo int
	err := database.DB.QueryRow(`
		SELECT titre, prix, nb_places_dispo FROM evenements WHERE id_evenement = ? AND statut = 'valide'
	`, req.IDEvenement).Scan(&titre, &prix, &placesDispo)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "événement introuvable ou non disponible"})
		return
	}
	if !prix.Valid || prix.Float64 == 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "cet événement est gratuit, utilisez l'inscription directe"})
		return
	}
	if placesDispo <= 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "plus de places disponibles"})
		return
	}

	// Vérifier pas déjà inscrit
	var already int
	database.DB.QueryRow(
		`SELECT COUNT(*) FROM inscriptions_evenements WHERE id_evenement = ? AND id_utilisateur = ? AND statut_paiement != 'rembourse'`,
		req.IDEvenement, userId,
	).Scan(&already)
	if already > 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "vous êtes déjà inscrit à cet événement"})
		return
	}

	amountCents := int64(prix.Float64 * 100)

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	params := &stripe.PaymentIntentParams{
		Amount:   stripe.Int64(amountCents),
		Currency: stripe.String("eur"),
		Customer: stripe.String(customerID),
		Metadata: map[string]string{
			"type":           "evenement",
			"id_evenement":   strconv.Itoa(req.IDEvenement),
			"id_utilisateur": strconv.Itoa(userId),
		},
		AutomaticPaymentMethods: &stripe.PaymentIntentAutomaticPaymentMethodsParams{
			Enabled: stripe.Bool(true),
		},
	}

	pi, err := stripepaymentintent.New(params)
	if err != nil {
		log.Printf("Erreur PaymentIntent événement: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur création paiement"})
		return
	}

	json.NewEncoder(w).Encode(map[string]any{
		"client_secret":  pi.ClientSecret,
		"payment_intent": pi.ID,
		"montant":        prix.Float64,
		"titre":          titre,
	})
}

// StripePaymentIntentCatalogue crée un Payment Intent pour une formation payante
// du catalogue (mirroir de StripePaymentIntentEvenement). La réservation effective
// est créée par le webhook payment_intent.succeeded (handleCataloguePaid).
func StripePaymentIntentCatalogue(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req struct {
		IDCatalogueItem int `json:"id_catalogue_item"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	var titre string
	var prix sql.NullFloat64
	var places int
	err := database.DB.QueryRow(`
		SELECT titre, prix, nb_places_dispo FROM catalogue_items WHERE id_catalogue_item = ? AND statut = 'publie'
	`, req.IDCatalogueItem).Scan(&titre, &prix, &places)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "formation introuvable ou non disponible"})
		return
	}
	if !prix.Valid || prix.Float64 == 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "cette formation est gratuite, utilisez la réservation directe"})
		return
	}
	if places <= 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "aucune place disponible"})
		return
	}

	var already int
	database.DB.QueryRow(
		`SELECT COUNT(*) FROM catalogue_reservations WHERE id_catalogue_item = ? AND id_utilisateur = ? AND statut_paiement != 'annule'`,
		req.IDCatalogueItem, userId,
	).Scan(&already)
	if already > 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "vous avez déjà réservé cette formation"})
		return
	}

	amountCents := int64(prix.Float64 * 100)

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	params := &stripe.PaymentIntentParams{
		Amount:   stripe.Int64(amountCents),
		Currency: stripe.String("eur"),
		Customer: stripe.String(customerID),
		Metadata: map[string]string{
			"type":              "catalogue",
			"id_catalogue_item": strconv.Itoa(req.IDCatalogueItem),
			"id_utilisateur":    strconv.Itoa(userId),
		},
		AutomaticPaymentMethods: &stripe.PaymentIntentAutomaticPaymentMethodsParams{
			Enabled: stripe.Bool(true),
		},
	}

	pi, err := stripepaymentintent.New(params)
	if err != nil {
		log.Printf("Erreur PaymentIntent formation: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur création paiement"})
		return
	}

	json.NewEncoder(w).Encode(map[string]any{
		"client_secret":  pi.ClientSecret,
		"payment_intent": pi.ID,
		"montant":        prix.Float64,
		"titre":          titre,
	})
}

// StripePaymentIntentPanier crée un seul Payment Intent pour tout le panier
func StripePaymentIntentPanier(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req struct {
		Items []struct {
			IDAnnonce  int    `json:"id_annonce"`
			ModeRemise string `json:"mode_remise"`
		} `json:"items"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || len(req.Items) == 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "panier vide ou données invalides"})
		return
	}

	type ItemDetail struct {
		IDAnnonce     int     `json:"id_annonce"`
		Titre         string  `json:"titre"`
		Prix          float64 `json:"prix"`
		Commission    float64 `json:"commission"`
		CommissionPct float64 `json:"commission_pct"`
		ModeRemise    string  `json:"mode_remise"`
		TotalItem     float64 `json:"total_item"`
	}

	var details []ItemDetail
	var totalCents int64
	var metaItems []string

	for _, item := range req.Items {
		var titre, roleVendeur, statut string
		var prix sql.NullFloat64
		var idVendeur int
		err := database.DB.QueryRow(`
			SELECT a.id_particulier, a.titre, a.prix, a.statut, u.role
			FROM annonces a
			JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
			WHERE a.id_annonce = ? AND a.type_annonce = 'vente' AND a.statut = 'validee'
		`, item.IDAnnonce).Scan(&idVendeur, &titre, &prix, &statut, &roleVendeur)
		if err != nil || idVendeur == userId || !prix.Valid || prix.Float64 == 0 {
			continue
		}

		commissionPct := 10.0
		if roleVendeur == "professionnel" {
			commissionPct = 5.0
		}
		commission := prix.Float64 * commissionPct / 100
		totalItem := prix.Float64 + commission
		totalCents += int64(totalItem * 100)

		modeRemise := item.ModeRemise
		if modeRemise != "conteneur" && modeRemise != "main_propre" {
			modeRemise = "main_propre"
		}

		details = append(details, ItemDetail{
			IDAnnonce:     item.IDAnnonce,
			Titre:         titre,
			Prix:          prix.Float64,
			Commission:    commission,
			CommissionPct: commissionPct,
			ModeRemise:    modeRemise,
			TotalItem:     totalItem,
		})
		metaItems = append(metaItems, fmt.Sprintf("%d:%.2f:%s", item.IDAnnonce, commissionPct, modeRemise))
	}

	if len(details) == 0 || totalCents == 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "aucun article disponible dans le panier"})
		return
	}

	customerID, err := getOrCreateStripeCustomer(userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur client Stripe"})
		return
	}

	// Metadata: "id_annonce:commission_pct:mode_remise" séparés par "|"
	itemsMeta := ""
	for i, m := range metaItems {
		if i > 0 {
			itemsMeta += "|"
		}
		itemsMeta += m
	}

	params := &stripe.PaymentIntentParams{
		Amount:   stripe.Int64(totalCents),
		Currency: stripe.String("eur"),
		Customer: stripe.String(customerID),
		Metadata: map[string]string{
			"type":        "panier",
			"id_acheteur": strconv.Itoa(userId),
			"items":       itemsMeta,
		},
		AutomaticPaymentMethods: &stripe.PaymentIntentAutomaticPaymentMethodsParams{
			Enabled: stripe.Bool(true),
		},
	}

	pi, err := stripepaymentintent.New(params)
	if err != nil {
		log.Printf("Erreur PaymentIntent panier: %v", err)
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur création paiement"})
		return
	}

	json.NewEncoder(w).Encode(map[string]any{
		"client_secret":  pi.ClientSecret,
		"payment_intent": pi.ID,
		"montant_total":  float64(totalCents) / 100,
		"items":          details,
	})
}

// StripeWebhook gère tous les événements Stripe
func StripeWebhook(w http.ResponseWriter, r *http.Request) {
	const maxBodyBytes = int64(65536)
	r.Body = http.MaxBytesReader(w, r.Body, maxBodyBytes)

	payload, err := io.ReadAll(r.Body)
	if err != nil {
		w.WriteHeader(http.StatusServiceUnavailable)
		return
	}

	webhookSecret := os.Getenv("STRIPE_WEBHOOK_SECRET")
	var event stripe.Event

	if webhookSecret != "" {
		event, err = stripewebhook.ConstructEventWithOptions(payload, r.Header.Get("Stripe-Signature"), webhookSecret, stripewebhook.ConstructEventOptions{
			IgnoreAPIVersionMismatch: true,
		})
		if err != nil {
			log.Printf("Erreur vérification signature webhook Stripe: %v", err)
			w.WriteHeader(http.StatusBadRequest)
			return
		}
	} else {
		if err := json.Unmarshal(payload, &event); err != nil {
			w.WriteHeader(http.StatusBadRequest)
			return
		}
	}

	switch event.Type {
	case "checkout.session.completed":
		handleCheckoutSessionCompleted(event)
	case "payment_intent.succeeded":
		handlePaymentIntentSucceeded(event)
	case "customer.subscription.updated":
		handleSubscriptionUpdated(event)
	case "customer.subscription.deleted":
		handleSubscriptionDeleted(event)
	case "invoice.payment_failed":
		handleInvoicePaymentFailed(event)
	case "invoice.payment_succeeded":
		handleInvoicePaymentSucceeded(event)
	}

	w.WriteHeader(http.StatusOK)
}

// creerFacture enregistre une facture légale pour un paiement encaissé.
// Idempotent : le numéro est dérivé de l'identifiant Stripe (contrainte UNIQUE
// sur numero_facture) → un webhook rejoué est silencieusement ignoré via
// INSERT IGNORE. TVA 20 % : montant_ht = montant_ttc / 1.20.
func creerFacture(idUtilisateur int, montantTTC float64, typeFacture, service, stripeID string) {
	if idUtilisateur <= 0 || montantTTC <= 0 {
		return
	}
	montantHT := montantTTC / 1.20
	ref := stripeID
	if len(ref) > 38 {
		ref = ref[len(ref)-38:]
	}
	prefix := "FAC"
	switch typeFacture {
	case "abonnement":
		prefix = "ABO"
	case "commande":
		prefix = "CMD"
	case "evenement":
		prefix = "EVT"
	case "publicite":
		prefix = "PUB"
	}
	numero := prefix + "-" + ref
	if _, err := database.DB.Exec(`
		INSERT IGNORE INTO factures (numero_facture, id_utilisateur, montant_ht, montant_ttc, type_facture, service, stripe_payment_id)
		VALUES (?, ?, ?, ?, ?, ?, ?)`,
		numero, idUtilisateur, montantHT, montantTTC, typeFacture, service, stripeID); err != nil {
		log.Printf("[facture] erreur création (%s, user=%d): %v", typeFacture, idUtilisateur, err)
	}
}

func handleCheckoutSessionCompleted(event stripe.Event) {
	var s stripe.CheckoutSession
	if err := json.Unmarshal(event.Data.Raw, &s); err != nil {
		log.Printf("Erreur parsing checkout.session.completed: %v", err)
		return
	}
	if s.Mode != stripe.CheckoutSessionModeSubscription {
		return
	}

	userIDStr := s.Metadata["user_id"]
	idAbonnementStr := s.Metadata["id_abonnement"]
	if userIDStr == "" || idAbonnementStr == "" {
		log.Printf("Metadata manquante dans checkout.session.completed")
		return
	}

	var userId, idAbonnement int
	fmt.Sscanf(userIDStr, "%d", &userId)
	fmt.Sscanf(idAbonnementStr, "%d", &idAbonnement)

	subID := ""
	if s.Subscription != nil {
		subID = s.Subscription.ID
	}

	database.DB.Exec(
		`UPDATE souscriptions SET est_active = FALSE, date_fin = NOW() WHERE id_utilisateur = ? AND est_active = TRUE`,
		userId,
	)
	database.DB.Exec(`
		INSERT INTO souscriptions (id_utilisateur, id_abonnement, date_debut, est_active, stripe_subscription_id, gere_par_admin)
		VALUES (?, ?, NOW(), TRUE, ?, FALSE)
	`, userId, idAbonnement, subID)

	// Facture du paiement initial de l'abonnement (les renouvellements sont
	// facturés par handleInvoicePaymentSucceeded).
	factRef := subID
	if factRef == "" {
		factRef = s.ID
	}
	creerFacture(userId, float64(s.AmountTotal)/100, "abonnement", "Abonnement Pro", factRef)

	log.Printf("Souscription créée: user=%d, abonnement=%d, stripe_sub=%s", userId, idAbonnement, subID)
}

func handlePaymentIntentSucceeded(event stripe.Event) {
	var pi stripe.PaymentIntent
	if err := json.Unmarshal(event.Data.Raw, &pi); err != nil {
		log.Printf("Erreur parsing payment_intent.succeeded: %v", err)
		return
	}

	switch pi.Metadata["type"] {
	case "commande":
		handleCommandePaid(&pi)
	case "evenement":
		handleEvenementPaid(&pi)
	case "catalogue":
		handleCataloguePaid(&pi)
	case "panier":
		handlePanierPaid(&pi)
	}
}

// insertCommandeWithRetry insère une commande + marque l'annonce vendue en transaction.
// Retry jusqu'à 3 fois sur deadlock, idempotent grâce à la contrainte UNIQUE (stripe_payment_intent, id_annonce).
// insertCommandeWithRetry insère une commande idempotente et retourne l'id_commande créé (0 si doublon).
// conteneurDeAnnonce retourne l'id du conteneur désigné sur l'annonce (mode
// conteneur), ou nil (don/main propre). La commande hérite ainsi du point de
// collecte choisi par le vendeur, indispensable au suivi logistique.
func conteneurDeAnnonce(idAnnonce int) *int {
	var idc sql.NullInt64
	if err := database.DB.QueryRow(`SELECT id_conteneur FROM annonces WHERE id_annonce = ?`, idAnnonce).Scan(&idc); err == nil && idc.Valid {
		v := int(idc.Int64)
		return &v
	}
	return nil
}

func insertCommandeWithRetry(piID string, idAnnonce, idAcheteur int, commissionPct, commission float64, dateLimite time.Time, idConteneur *int) (int64, error) {
	for attempt := 0; attempt < 3; attempt++ {
		tx, err := database.DB.Begin()
		if err != nil {
			return 0, fmt.Errorf("begin: %w", err)
		}

		res, err := tx.Exec(`
			INSERT IGNORE INTO commandes (id_annonce, id_acheteur, commission_pct, montant_commission, date_limite_recuperation, stripe_payment_intent, id_conteneur, statut)
			VALUES (?, ?, ?, ?, ?, ?, ?, 'commandee')
		`, idAnnonce, idAcheteur, commissionPct, commission, dateLimite, piID, idConteneur)
		if err != nil {
			tx.Rollback()
			return 0, fmt.Errorf("insert commande: %w", err)
		}

		idCommande, _ := res.LastInsertId()

		_, err = tx.Exec(`UPDATE annonces SET statut = 'vendue' WHERE id_annonce = ? AND statut = 'validee'`, idAnnonce)
		if err != nil {
			tx.Rollback()
			if strings.Contains(err.Error(), "Deadlock") && attempt < 2 {
				time.Sleep(time.Duration(50*(attempt+1)) * time.Millisecond)
				continue
			}
			return 0, fmt.Errorf("update annonce: %w", err)
		}

		if err := tx.Commit(); err != nil {
			return 0, fmt.Errorf("commit: %w", err)
		}
		return idCommande, nil
	}
	return 0, fmt.Errorf("deadlock persistant après 3 tentatives")
}

func handleCommandePaid(pi *stripe.PaymentIntent) {
	var idAnnonce, idAcheteur int
	var commissionPct float64
	modeRemise := pi.Metadata["mode_remise"]

	fmt.Sscanf(pi.Metadata["id_annonce"], "%d", &idAnnonce)
	fmt.Sscanf(pi.Metadata["id_acheteur"], "%d", &idAcheteur)
	fmt.Sscanf(pi.Metadata["commission_pct"], "%f", &commissionPct)

	var prix float64
	err := database.DB.QueryRow(`SELECT COALESCE(prix, 0) FROM annonces WHERE id_annonce = ?`, idAnnonce).Scan(&prix)
	if err != nil {
		log.Printf("Annonce %d introuvable pour commande Stripe", idAnnonce)
		return
	}

	commission := prix * commissionPct / 100
	dateLimite := time.Now().AddDate(0, 0, 14)

	_ = modeRemise

	if idCommande, err := insertCommandeWithRetry(pi.ID, idAnnonce, idAcheteur, commissionPct, commission, dateLimite, conteneurDeAnnonce(idAnnonce)); err != nil {
		log.Printf("Erreur commande annonce=%d pi=%s: %v", idAnnonce, pi.ID, err)
	} else {
		log.Printf("Commande créée via Stripe: annonce=%d, acheteur=%d, pi=%s", idAnnonce, idAcheteur, pi.ID)
		creerFacture(idAcheteur, float64(pi.Amount)/100, "commande", fmt.Sprintf("Achat annonce #%d", idAnnonce), pi.ID)
		if idCommande > 0 {
			services.AwardScoreForCommande(int(idCommande))
		}
		go notifyVendeurVente(idAnnonce, idAcheteur)
	}
}

func notifyVendeurVente(idAnnonce, idAcheteur int) {
	var emailVendeur, titreAnnonce, prenomAcheteur string
	err := database.DB.QueryRow(`
		SELECT u.email, a.titre, (SELECT prenom FROM utilisateurs WHERE id_utilisateur = ?)
		FROM annonces a
		JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
		WHERE a.id_annonce = ?
	`, idAcheteur, idAnnonce).Scan(&emailVendeur, &titreAnnonce, &prenomAcheteur)
	if err != nil {
		log.Printf("[notifyVendeurVente] erreur récupération: %v", err)
		return
	}
	subject := "Votre annonce a été achetée — " + titreAnnonce
	body := "Bonjour,\n\n" + prenomAcheteur + " vient d'acheter votre annonce \"" + titreAnnonce + "\".\n\nConnectez-vous à UpcycleConnect pour suivre la commande.\n\nL'équipe UpcycleConnect"
	if err := services.SendSimpleEmail(emailVendeur, subject, body); err != nil {
		log.Printf("[notifyVendeurVente] erreur envoi email vendeur: %v", err)
	}
}

func handlePanierPaid(pi *stripe.PaymentIntent) {
	var idAcheteur int
	fmt.Sscanf(pi.Metadata["id_acheteur"], "%d", &idAcheteur)
	itemsMeta := pi.Metadata["items"] // "id_annonce:commission_pct:mode_remise|..."

	if itemsMeta == "" || idAcheteur == 0 {
		log.Printf("Metadata panier manquante: %s", pi.ID)
		return
	}

	// Une seule facture pour l'ensemble du panier (montant total réellement payé).
	creerFacture(idAcheteur, float64(pi.Amount)/100, "commande", "Achat panier", pi.ID)

	for _, entry := range strings.Split(itemsMeta, "|") {
		parts := strings.SplitN(entry, ":", 3)
		if len(parts) != 3 {
			continue
		}
		var idAnnonce int
		var commissionPct float64
		modeRemise := parts[2]
		fmt.Sscanf(parts[0], "%d", &idAnnonce)
		fmt.Sscanf(parts[1], "%f", &commissionPct)

		var prix float64
		err := database.DB.QueryRow(`SELECT COALESCE(prix, 0) FROM annonces WHERE id_annonce = ?`, idAnnonce).Scan(&prix)
		if err != nil {
			log.Printf("Annonce %d introuvable (panier webhook)", idAnnonce)
			continue
		}

		commission := prix * commissionPct / 100
		dateLimite := time.Now().AddDate(0, 0, 14)
		_ = modeRemise

		if idCommande, err := insertCommandeWithRetry(pi.ID, idAnnonce, idAcheteur, commissionPct, commission, dateLimite, conteneurDeAnnonce(idAnnonce)); err != nil {
			log.Printf("Erreur commande panier annonce=%d: %v", idAnnonce, err)
		} else {
			log.Printf("Commande panier créée: annonce=%d, acheteur=%d", idAnnonce, idAcheteur)
			if idCommande > 0 {
				services.AwardScoreForCommande(int(idCommande))
			}
			go notifyVendeurVente(idAnnonce, idAcheteur)
		}
	}
}

func handleEvenementPaid(pi *stripe.PaymentIntent) {
	var idEvenement, idUtilisateur int
	fmt.Sscanf(pi.Metadata["id_evenement"], "%d", &idEvenement)
	fmt.Sscanf(pi.Metadata["id_utilisateur"], "%d", &idUtilisateur)

	prixPaye := float64(pi.Amount) / 100

	_, err := database.DB.Exec(`
		INSERT INTO inscriptions_evenements (id_utilisateur, id_evenement, date_inscription, statut_paiement, stripe_payment, prix_paye)
		VALUES (?, ?, NOW(), 'paye', ?, ?)
		ON DUPLICATE KEY UPDATE statut_paiement = 'paye', stripe_payment = ?, prix_paye = ?
	`, idUtilisateur, idEvenement, pi.ID, prixPaye, pi.ID, prixPaye)
	if err != nil {
		log.Printf("Erreur inscription événement payant: %v", err)
		return
	}

	database.DB.Exec(
		`UPDATE evenements SET nb_places_dispo = GREATEST(0, nb_places_dispo - 1) WHERE id_evenement = ?`,
		idEvenement,
	)

	creerFacture(idUtilisateur, prixPaye, "evenement", fmt.Sprintf("Inscription événement #%d", idEvenement), pi.ID)

	log.Printf("Inscription événement payant: event=%d, user=%d, pi=%s", idEvenement, idUtilisateur, pi.ID)
	services.AwardScoreForEvenement(idUtilisateur, idEvenement)
}

// handleCataloguePaid finalise une réservation de formation payante après paiement
// Stripe. Idempotent grâce à la clé unique (id_catalogue_item, id_utilisateur) :
// un webhook rejoué fait un UPDATE (RowsAffected=2) et ne re-décrémente pas les places.
func handleCataloguePaid(pi *stripe.PaymentIntent) {
	var idItem, idUtilisateur int
	fmt.Sscanf(pi.Metadata["id_catalogue_item"], "%d", &idItem)
	fmt.Sscanf(pi.Metadata["id_utilisateur"], "%d", &idUtilisateur)

	prixPaye := float64(pi.Amount) / 100

	res, err := database.DB.Exec(`
		INSERT INTO catalogue_reservations (id_utilisateur, id_catalogue_item, date_reservation, statut_paiement, stripe_payment, prix_paye)
		VALUES (?, ?, NOW(), 'paye', ?, ?)
		ON DUPLICATE KEY UPDATE statut_paiement = 'paye', stripe_payment = VALUES(stripe_payment), prix_paye = VALUES(prix_paye)
	`, idUtilisateur, idItem, pi.ID, prixPaye)
	if err != nil {
		log.Printf("Erreur réservation formation payante: %v", err)
		return
	}

	// RowsAffected == 1 → insertion réelle (1ère réservation) : on décrémente + planning.
	if aff, _ := res.RowsAffected(); aff == 1 {
		database.DB.Exec(
			`UPDATE catalogue_items SET nb_places_dispo = GREATEST(0, nb_places_dispo - 1) WHERE id_catalogue_item = ?`,
			idItem,
		)
		go AddPlanningFromFormation(idUtilisateur, idItem)
	}

	log.Printf("Réservation formation payante: item=%d, user=%d, pi=%s", idItem, idUtilisateur, pi.ID)
}

func handleSubscriptionUpdated(event stripe.Event) {
	var sub stripe.Subscription
	if err := json.Unmarshal(event.Data.Raw, &sub); err != nil {
		log.Printf("Erreur parsing subscription.updated: %v", err)
		return
	}

	isActive := sub.Status == stripe.SubscriptionStatusActive || sub.Status == stripe.SubscriptionStatusTrialing
	if !isActive {
		database.DB.Exec(
			`UPDATE souscriptions SET est_active = FALSE, date_fin = NOW() WHERE stripe_subscription_id = ?`,
			sub.ID,
		)
		log.Printf("Souscription désactivée: %s (statut: %s)", sub.ID, sub.Status)
	}
}

func handleSubscriptionDeleted(event stripe.Event) {
	var sub stripe.Subscription
	if err := json.Unmarshal(event.Data.Raw, &sub); err != nil {
		log.Printf("Erreur parsing subscription.deleted: %v", err)
		return
	}

	// Abonnement de plan Pro
	database.DB.Exec(
		`UPDATE souscriptions SET est_active = FALSE, date_fin = NOW() WHERE stripe_subscription_id = ?`,
		sub.ID,
	)

	// Publicité — marquer comme expirée si c'est une sub de pub
	if sub.Metadata["type"] == "publicite" {
		services.ExpirerPubliciteStripe(sub.ID) //nolint:errcheck
	}

	log.Printf("Souscription annulée: %s", sub.ID)
}

func handleInvoicePaymentFailed(event stripe.Event) {
	subID := services.ExtractInvoiceSubscriptionID(event.Data.Raw)
	if subID == "" {
		log.Printf("invoice.payment_failed: subscription_id introuvable dans le payload")
		return
	}

	if n, err := services.SuspendrePubliciteStripe(subID); err != nil {
		log.Printf("Erreur suspension pub stripe_sub=%s: %v", subID, err)
	} else if n > 0 {
		log.Printf("[STRIPE] Pub suspendue pour paiement échoué: sub=%s", subID)
	}
}

func handleInvoicePaymentSucceeded(event stripe.Event) {
	var invoice stripe.Invoice
	if err := json.Unmarshal(event.Data.Raw, &invoice); err != nil {
		log.Printf("Erreur parsing invoice.payment_succeeded: %v", err)
		return
	}

	subID := services.ExtractInvoiceSubscriptionID(event.Data.Raw)
	if subID == "" {
		return
	}

	montant := float64(invoice.AmountPaid) / 100
	isCreate := invoice.BillingReason == stripe.InvoiceBillingReasonSubscriptionCreate

	// Abonnement de plan Pro : la facture initiale est déjà émise par
	// checkout.session.completed → ici on ne facture que les renouvellements.
	var uid int
	if database.DB.QueryRow(
		`SELECT id_utilisateur FROM souscriptions WHERE stripe_subscription_id = ? ORDER BY date_debut DESC LIMIT 1`,
		subID,
	).Scan(&uid) == nil && uid > 0 {
		database.DB.Exec(`UPDATE souscriptions SET est_active = TRUE WHERE stripe_subscription_id = ?`, subID)
		if !isCreate {
			creerFacture(uid, montant, "abonnement", "Renouvellement abonnement Pro", invoice.ID)
		}
		log.Printf("Paiement abonnement reçu: sub=%s", subID)
		return
	}

	// Publicité Pro : pas de checkout session → on facture aussi le 1er paiement.
	var pid int
	if database.DB.QueryRow(
		`SELECT id_professionnel FROM publicites WHERE stripe_subscription_id = ? LIMIT 1`,
		subID,
	).Scan(&pid) == nil && pid > 0 {
		services.RéactiverPubliciteStripe(subID) //nolint:errcheck
		creerFacture(pid, montant, "publicite", "Publicité Pro (mensuel)", invoice.ID)
		log.Printf("Paiement publicité reçu: sub=%s", subID)
		return
	}

	log.Printf("Paiement reçu pour souscription inconnue: %s", subID)
}

// AdminSyncStripePlans crée les Products et Prices Stripe pour tous les abonnements
func AdminSyncStripePlans(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "application/json")

	rows, err := database.DB.Query(
		`SELECT id_abonnement, nom, prix_mensuel, prix_annuel, type_cible, COALESCE(description, '') FROM abonnements`,
	)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur base de données"})
		return
	}
	defer rows.Close()

	type PlanResult struct {
		IDAbonnement   int    `json:"id_abonnement"`
		Nom            string `json:"nom"`
		PriceIDMensuel string `json:"price_id_mensuel,omitempty"`
		PriceIDAnnuel  string `json:"price_id_annuel,omitempty"`
	}

	var results []PlanResult

	for rows.Next() {
		var id int
		var nom, typeCible, description string
		var prixMensuel float64
		var prixAnnuel sql.NullFloat64

		if err := rows.Scan(&id, &nom, &prixMensuel, &prixAnnuel, &typeCible, &description); err != nil {
			continue
		}
		if prixMensuel == 0 {
			continue // Freemium — pas de Price Stripe
		}

		prodParams := &stripe.ProductParams{
			Name:        stripe.String(nom + " (" + typeCible + ")"),
			Description: stripe.String(description),
			Metadata: map[string]string{
				"id_abonnement": strconv.Itoa(id),
				"type_cible":    typeCible,
			},
		}
		prod, err := stripeproduct.New(prodParams)
		if err != nil {
			log.Printf("Erreur création produit Stripe pour abonnement %d: %v", id, err)
			continue
		}

		res := PlanResult{IDAbonnement: id, Nom: nom}

		monthlyParams := &stripe.PriceParams{
			Product:    stripe.String(prod.ID),
			UnitAmount: stripe.Int64(int64(prixMensuel * 100)),
			Currency:   stripe.String("eur"),
			Recurring: &stripe.PriceRecurringParams{
				Interval: stripe.String("month"),
			},
			Nickname: stripe.String(nom + " — Mensuel"),
		}
		mp, err := stripeprice.New(monthlyParams)
		if err != nil {
			log.Printf("Erreur prix mensuel Stripe pour abonnement %d: %v", id, err)
			continue
		}
		res.PriceIDMensuel = mp.ID
		database.DB.Exec(`UPDATE abonnements SET stripe_price_id_mensuel = ? WHERE id_abonnement = ?`, mp.ID, id)

		if prixAnnuel.Valid && prixAnnuel.Float64 > 0 {
			annualParams := &stripe.PriceParams{
				Product:    stripe.String(prod.ID),
				UnitAmount: stripe.Int64(int64(prixAnnuel.Float64 * 100)),
				Currency:   stripe.String("eur"),
				Recurring: &stripe.PriceRecurringParams{
					Interval: stripe.String("year"),
				},
				Nickname: stripe.String(nom + " — Annuel"),
			}
			ap, err := stripeprice.New(annualParams)
			if err != nil {
				log.Printf("Erreur prix annuel Stripe pour abonnement %d: %v", id, err)
			} else {
				res.PriceIDAnnuel = ap.ID
				database.DB.Exec(`UPDATE abonnements SET stripe_price_id_annuel = ? WHERE id_abonnement = ?`, ap.ID, id)
			}
		}

		results = append(results, res)
	}

	if results == nil {
		results = []PlanResult{}
	}
	json.NewEncoder(w).Encode(map[string]any{
		"message": "synchronisation Stripe terminée",
		"plans":   results,
	})
}

// GetMaFacturation retourne l'abonnement actif et les factures de l'utilisateur
func GetMaFacturation(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	type AbonnementActif struct {
		IDSouscription int     `json:"id_souscription"`
		NomPlan        string  `json:"nom_plan"`
		PrixMensuel    float64 `json:"prix_mensuel"`
		PrixAnnuel     float64 `json:"prix_annuel,omitempty"`
		DateDebut      string  `json:"date_debut"`
		DateFin        string  `json:"date_fin,omitempty"`
		StripeSubID    string  `json:"stripe_subscription_id,omitempty"`
	}

	type Facture struct {
		IDFacture     int     `json:"id_facture"`
		Numero        string  `json:"numero_facture"`
		MontantTTC    float64 `json:"montant_ttc"`
		TypeFacture   string  `json:"type_facture"`
		DateEmission  string  `json:"date_emission"`
		PdfURL        string  `json:"pdf_url,omitempty"`
	}

	type FacturationResponse struct {
		AbonnementActif *AbonnementActif `json:"abonnement_actif"`
		Factures        []Facture        `json:"factures"`
	}

	resp := FacturationResponse{Factures: []Facture{}}

	// Abonnement actif
	var sub AbonnementActif
	var dateFin sql.NullString
	var stripeSubID sql.NullString
	var prixAnnuel sql.NullFloat64
	err := database.DB.QueryRow(`
		SELECT s.id_souscription, a.nom, a.prix_mensuel, a.prix_annuel, s.date_debut, s.date_fin, s.stripe_subscription_id
		FROM souscriptions s
		JOIN abonnements a ON a.id_abonnement = s.id_abonnement
		WHERE s.id_utilisateur = ? AND s.est_active = TRUE
		ORDER BY s.date_debut DESC LIMIT 1
	`, userId).Scan(&sub.IDSouscription, &sub.NomPlan, &sub.PrixMensuel, &prixAnnuel, &sub.DateDebut, &dateFin, &stripeSubID)
	if err == nil {
		if prixAnnuel.Valid {
			sub.PrixAnnuel = prixAnnuel.Float64
		}
		if dateFin.Valid {
			sub.DateFin = dateFin.String
		}
		if stripeSubID.Valid {
			sub.StripeSubID = stripeSubID.String
		}
		resp.AbonnementActif = &sub
	}

	// Factures
	frows, err := database.DB.Query(`
		SELECT id_facture, numero_facture, montant_ttc, type_facture, date_emission, COALESCE(pdf_url, '')
		FROM factures
		WHERE id_utilisateur = ?
		ORDER BY date_emission DESC
		LIMIT 50
	`, userId)
	if err == nil {
		defer frows.Close()
		for frows.Next() {
			var f Facture
			if err := frows.Scan(&f.IDFacture, &f.Numero, &f.MontantTTC, &f.TypeFacture, &f.DateEmission, &f.PdfURL); err == nil {
				resp.Factures = append(resp.Factures, f)
			}
		}
	}

	json.NewEncoder(w).Encode(resp)
}

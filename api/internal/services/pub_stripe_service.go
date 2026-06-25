package services

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"strconv"

	"strings"

	stripe "github.com/stripe/stripe-go/v82"
	stripecustomer "github.com/stripe/stripe-go/v82/customer"
	stripepaymentmethod "github.com/stripe/stripe-go/v82/paymentmethod"
	stripesub "github.com/stripe/stripe-go/v82/subscription"
)

func init() {
	if key := os.Getenv("STRIPE_SECRET_KEY"); key != "" {
		stripe.Key = key
	}
}

// CreatePubSubscription crée une Stripe Subscription mensuelle pour une publicité validée.
// Si STRIPE_PRICE_PUB_MENSUEL n'est pas défini (dev/test), retourne ("", nil) sans erreur.
func CreatePubSubscription(proID int, pubID int) (string, error) {
	priceID := os.Getenv("STRIPE_PRICE_PUB_MENSUEL")
	if priceID == "" {
		log.Printf("[STRIPE] STRIPE_PRICE_PUB_MENSUEL non défini — pub %d activée sans facturation récurrente", pubID)
		return "", nil
	}

	customerID, err := getOrCreateStripeCustomerForPub(proID)
	if err != nil {
		return "", fmt.Errorf("customer Stripe: %w", err)
	}

	// En mode test uniquement, on attache une carte de test au client pour que
	// l'abonnement se paie réellement (sinon il reste "incomplete"). En production,
	// la carte est saisie par le professionnel via Stripe — ce bloc ne s'exécute pas.
	ensureTestPaymentMethod(customerID)

	params := &stripe.SubscriptionParams{
		Customer: stripe.String(customerID),
		Items: []*stripe.SubscriptionItemsParams{
			{Price: stripe.String(priceID)},
		},
		Metadata: map[string]string{
			"type":   "publicite",
			"pub_id": strconv.Itoa(pubID),
			"pro_id": strconv.Itoa(proID),
		},
	}
	sub, err := stripesub.New(params)
	if err != nil {
		return "", fmt.Errorf("Stripe subscription: %w", err)
	}

	log.Printf("[STRIPE] Subscription pub créée: sub=%s pub=%d pro=%d", sub.ID, pubID, proID)
	return sub.ID, nil
}

// ensureTestPaymentMethod attache une carte de test au client et la définit par
// défaut, pour que les abonnements se paient automatiquement en environnement de
// test. No-op si la clé Stripe n'est pas une clé de test (production).
func ensureTestPaymentMethod(customerID string) {
	if !strings.HasPrefix(os.Getenv("STRIPE_SECRET_KEY"), "sk_test") {
		return
	}
	pm, err := stripepaymentmethod.Attach("pm_card_visa", &stripe.PaymentMethodAttachParams{
		Customer: stripe.String(customerID),
	})
	if err != nil {
		log.Printf("[STRIPE][test] attache carte de test: %v", err)
		return
	}
	if _, err := stripecustomer.Update(customerID, &stripe.CustomerParams{
		InvoiceSettings: &stripe.CustomerInvoiceSettingsParams{
			DefaultPaymentMethod: stripe.String(pm.ID),
		},
	}); err != nil {
		log.Printf("[STRIPE][test] définition carte par défaut: %v", err)
	}
}

// CancelPubSubscription annule immédiatement une Stripe Subscription de publicité.
// No-op si subscriptionID est vide.
func CancelPubSubscription(subscriptionID string) error {
	if subscriptionID == "" {
		return nil
	}
	_, err := stripesub.Cancel(subscriptionID, nil)
	if err != nil {
		return fmt.Errorf("annulation Stripe sub %s: %w", subscriptionID, err)
	}
	log.Printf("[STRIPE] Subscription pub annulée: %s", subscriptionID)
	return nil
}

// SuspendrePubliciteStripe suspend une pub dont le paiement Stripe a échoué.
// Retourne le nombre de lignes modifiées.
func SuspendrePubliciteStripe(stripeSubID string) (int64, error) {
	if stripeSubID == "" {
		return 0, nil
	}
	res, err := database.DB.Exec(
		`UPDATE publicites SET statut = 'suspendue' WHERE stripe_subscription_id = ? AND statut = 'active'`,
		stripeSubID,
	)
	if err != nil {
		return 0, err
	}
	n, _ := res.RowsAffected()
	if n > 0 {
		log.Printf("[STRIPE] Pub suspendue pour paiement échoué: sub=%s", stripeSubID)
	}
	return n, nil
}

// RéactiverPubliciteStripe réactive une pub suspendue après un paiement réussi (retry Stripe).
// Retourne le nombre de lignes modifiées.
func RéactiverPubliciteStripe(stripeSubID string) (int64, error) {
	if stripeSubID == "" {
		return 0, nil
	}
	res, err := database.DB.Exec(
		`UPDATE publicites SET statut = 'active' WHERE stripe_subscription_id = ? AND statut = 'suspendue'`,
		stripeSubID,
	)
	if err != nil {
		return 0, err
	}
	n, _ := res.RowsAffected()
	if n > 0 {
		log.Printf("[STRIPE] Pub réactivée après paiement reçu: sub=%s", stripeSubID)
	}
	return n, nil
}

// ExpirerPubliciteStripe marque une pub comme expirée suite à suppression définitive de la subscription.
func ExpirerPubliciteStripe(stripeSubID string) error {
	if stripeSubID == "" {
		return nil
	}
	_, err := database.DB.Exec(
		`UPDATE publicites SET statut = 'expiree' WHERE stripe_subscription_id = ? AND statut IN ('active','suspendue')`,
		stripeSubID,
	)
	return err
}

// ExtractInvoiceSubscriptionID extrait le stripe_subscription_id depuis un payload JSON d'invoice Stripe.
// Compatible avec l'API v82 (parent.subscription_details.subscription.id) et l'ancienne API (subscription).
// Fonction pure — aucune dépendance DB ou réseau, utilisable dans les tests unitaires.
func ExtractInvoiceSubscriptionID(raw []byte) string {
	var payload struct {
		Parent struct {
			SubscriptionDetails struct {
				Subscription json.RawMessage `json:"subscription"`
			} `json:"subscription_details"`
		} `json:"parent"`
		Subscription json.RawMessage `json:"subscription"` // legacy
	}
	if err := json.Unmarshal(raw, &payload); err != nil {
		return ""
	}
	if id := rawSubID(payload.Parent.SubscriptionDetails.Subscription); id != "" {
		return id
	}
	return rawSubID(payload.Subscription)
}

// rawSubID extrait un id d'abonnement depuis un champ Stripe qui peut être soit
// une chaîne ("sub_..."), soit un objet développé ({"id":"sub_..."}).
func rawSubID(raw json.RawMessage) string {
	if len(raw) == 0 {
		return ""
	}
	var s string
	if json.Unmarshal(raw, &s) == nil && s != "" {
		return s
	}
	var obj struct {
		ID string `json:"id"`
	}
	if json.Unmarshal(raw, &obj) == nil {
		return obj.ID
	}
	return ""
}

// getOrCreateStripeCustomerForPub trouve ou crée le Stripe Customer d'un pro.
// Logique identique à getOrCreateStripeCustomer dans handlers/stripe.go,
// isolée ici pour que le service soit autonome.
func getOrCreateStripeCustomerForPub(proID int) (string, error) {
	var stripeCustomerID sql.NullString
	var email, nom, prenom string
	err := database.DB.QueryRow(
		`SELECT stripe_customer_id, email, nom, prenom FROM utilisateurs WHERE id_utilisateur = ?`,
		proID,
	).Scan(&stripeCustomerID, &email, &nom, &prenom)
	if err != nil {
		return "", fmt.Errorf("utilisateur introuvable: %w", err)
	}

	if stripeCustomerID.Valid && stripeCustomerID.String != "" {
		return stripeCustomerID.String, nil
	}

	c, err := stripecustomer.New(&stripe.CustomerParams{
		Email: stripe.String(email),
		Name:  stripe.String(prenom + " " + nom),
		Metadata: map[string]string{"user_id": strconv.Itoa(proID)},
	})
	if err != nil {
		return "", fmt.Errorf("création customer Stripe: %w", err)
	}

	_, err = database.DB.Exec(
		`UPDATE utilisateurs SET stripe_customer_id = ? WHERE id_utilisateur = ?`,
		c.ID, proID,
	)
	if err != nil {
		log.Printf("[STRIPE] Erreur sauvegarde stripe_customer_id user=%d: %v", proID, err)
	}

	return c.ID, nil
}

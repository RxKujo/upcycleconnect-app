package services

import (
	"os"
	"testing"
)

// TestCreatePubSubscription_NoPriceID vérifie que la validation admin n'est pas bloquée
// quand STRIPE_PRICE_PUB_MENSUEL n'est pas configuré (mode dev/test).
// Couvre : création de subscription au moment de la validation admin.
func TestCreatePubSubscription_NoPriceID(t *testing.T) {
	os.Unsetenv("STRIPE_PRICE_PUB_MENSUEL")
	subID, err := CreatePubSubscription(1, 42)
	if err != nil {
		t.Fatalf("attendu nil error quand price ID absent, got: %v", err)
	}
	if subID != "" {
		t.Fatalf("attendu subID vide quand price ID absent, got: %q", subID)
	}
}

// TestCancelPubSubscription_EmptyID vérifie que CancelPubSubscription avec un ID vide est un no-op.
func TestCancelPubSubscription_EmptyID(t *testing.T) {
	err := CancelPubSubscription("")
	if err != nil {
		t.Fatalf("attendu nil pour ID vide, got: %v", err)
	}
}

// TestSuspendrePubliciteStripe_EmptySubID vérifie le no-op pour subID vide.
// Couvre partiellement : passage de la pub en statut suspendue suite à un échec webhook.
func TestSuspendrePubliciteStripe_EmptySubID(t *testing.T) {
	n, err := SuspendrePubliciteStripe("")
	if err != nil {
		t.Fatalf("attendu nil error pour subID vide, got: %v", err)
	}
	if n != 0 {
		t.Fatalf("attendu 0 lignes pour subID vide, got: %d", n)
	}
}

// TestExtractInvoiceSubscriptionID_NewFormat vérifie l'extraction du sub_id depuis le format Stripe v82.
// Couvre : parsing du webhook invoice.payment_failed → identification de la pub à suspendre.
func TestExtractInvoiceSubscriptionID_NewFormat(t *testing.T) {
	raw := []byte(`{
		"parent": {
			"subscription_details": {
				"subscription": { "id": "sub_abc123" }
			}
		}
	}`)
	got := ExtractInvoiceSubscriptionID(raw)
	if got != "sub_abc123" {
		t.Errorf("format v82: attendu sub_abc123, got %q", got)
	}
}

// TestExtractInvoiceSubscriptionID_LegacyFormat vérifie la compatibilité avec l'ancienne API Stripe.
func TestExtractInvoiceSubscriptionID_LegacyFormat(t *testing.T) {
	raw := []byte(`{"subscription": "sub_legacy456"}`)
	got := ExtractInvoiceSubscriptionID(raw)
	if got != "sub_legacy456" {
		t.Errorf("format legacy: attendu sub_legacy456, got %q", got)
	}
}

// TestExtractInvoiceSubscriptionID_EmptyPayload vérifie qu'un payload vide retourne "".
func TestExtractInvoiceSubscriptionID_EmptyPayload(t *testing.T) {
	got := ExtractInvoiceSubscriptionID([]byte(`{}`))
	if got != "" {
		t.Errorf("payload vide: attendu vide, got %q", got)
	}
}

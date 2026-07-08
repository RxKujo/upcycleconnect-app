package handlers

import (
	"api/pkg/database"
	"database/sql"
	"errors"
	"net/http"
)

// ErrAchatPropre est retourné quand l'acheteur tente d'acheter sa propre annonce.
var ErrAchatPropre = errors.New("vous ne pouvez pas acheter votre propre annonce")

// AnnoncePurchaseDetail — infos de paiement/commande, partagées par stripe.go et commandes_buyer.go.
type AnnoncePurchaseDetail struct {
	IDVendeur     int
	Titre         string
	Prix          float64
	RoleVendeur   string
	CommissionPct float64
	Commission    float64
	Total         float64
}

// ValidateAnnonceForPurchase — vérifie qu'une annonce "vente" est dispo et que
// l'acheteur n'en est pas le vendeur ; retourne les détails ou une erreur HTTP.
func ValidateAnnonceForPurchase(idAnnonce, buyerID int) (*AnnoncePurchaseDetail, int, error) {
	var d AnnoncePurchaseDetail
	var prix sql.NullFloat64
	var statut string

	err := database.DB.QueryRow(`
		SELECT a.id_particulier, a.titre, a.prix, a.statut, u.role
		FROM annonces a
		JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
		WHERE a.id_annonce = ? AND a.type_annonce = 'vente'
	`, idAnnonce).Scan(&d.IDVendeur, &d.Titre, &prix, &statut, &d.RoleVendeur)
	if err != nil {
		return nil, http.StatusNotFound, errors.New("annonce introuvable")
	}
	if d.IDVendeur == buyerID {
		return nil, http.StatusBadRequest, ErrAchatPropre
	}
	if statut != "validee" {
		return nil, http.StatusBadRequest, errors.New("annonce non disponible")
	}
	if !prix.Valid || prix.Float64 == 0 {
		return nil, http.StatusBadRequest, errors.New("cette annonce n'a pas de prix défini")
	}

	d.Prix = prix.Float64
	d.CommissionPct = commissionRate(d.RoleVendeur)
	d.Commission = d.Prix * d.CommissionPct / 100
	d.Total = d.Prix + d.Commission
	return &d, 0, nil
}

// commissionRate — taux selon le rôle : 5 % pro, 10 % particulier.
func commissionRate(roleVendeur string) float64 {
	if roleVendeur == "professionnel" {
		return 5.0
	}
	return 10.0
}

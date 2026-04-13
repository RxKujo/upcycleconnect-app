package models

import "time"

type Commande struct {
	IDCommande             int        `json:"id_commande"`
	IDAnnonce              int        `json:"id_annonce"`
	IDAcheteur             int        `json:"id_acheteur"`
	IDConteneur            *int       `json:"id_conteneur,omitempty"`
	CommissionPct          float64    `json:"commission_pct"`
	MontantCommission      float64    `json:"montant_commission"`
	DateLimiteRecuperation *time.Time `json:"date_limite_recuperation,omitempty"`
	StripePaymentIntent    *string    `json:"stripe_payment_intent,omitempty"`
	DateCommande           time.Time  `json:"date_commande"`
	Statut                 string     `json:"statut"`

	TitreAnnonce   *string `json:"titre_annonce,omitempty"`
	AcheteurNom    *string `json:"acheteur_nom,omitempty"`
	AcheteurPrenom *string `json:"acheteur_prenom,omitempty"`
	ModeRemise     *string `json:"mode_remise,omitempty"`
}

type UpdateCommandeStatutRequest struct {
	Statut string `json:"statut"`
}

type CreateCommandeRequest struct {
	IDAnnonce    int  `json:"id_annonce"`
	IDConteneur  *int `json:"id_conteneur,omitempty"`
}

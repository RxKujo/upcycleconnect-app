package models

import "time"

type Annonce struct {
	IDAnnonce      int        `json:"id_annonce"`
	IDParticulier  int        `json:"id_particulier"`
	Titre          string     `json:"titre"`
	Description    string     `json:"description"`
	TypeAnnonce    string     `json:"type_annonce"`
	Prix           *float64   `json:"prix,omitempty"`
	ModeRemise     string     `json:"mode_remise"`
	Statut         string     `json:"statut"`
	MotifRefus     *string    `json:"motif_refus,omitempty"`
	MotifRetrait   *string    `json:"motif_retrait,omitempty"`
	DateCreation   time.Time  `json:"date_creation"`
	ValidePar      *int       `json:"valide_par,omitempty"`
    
	CategorieObjet *string    `json:"categorie_objet,omitempty"`
	MateriauObjet  *string    `json:"materiau_objet,omitempty"`
	EtatObjet      *string    `json:"etat_objet,omitempty"`
}

type AnnonceValidationRequest struct {
	MotifRefus *string `json:"motif_refus"`
}

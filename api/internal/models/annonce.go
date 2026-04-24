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

	Objets         []ObjetAnnonce `json:"objets,omitempty"`
}

type ObjetAnnonce struct {
	IDObjet    int          `json:"id_objet"`
	IDAnnonce  int          `json:"id_annonce"`
	Categorie  string       `json:"categorie"`
	Materiau   string       `json:"materiau"`
	Etat       string       `json:"etat"`
	PoidsKg    *float64     `json:"poids_kg,omitempty"`
	Photos     []PhotoObjet `json:"photos,omitempty"`
}

type PhotoObjet struct {
	IDPhoto   int    `json:"id_photo"`
	IDObjet   int    `json:"id_objet"`
	URL       string `json:"url"`
	Ordre     int    `json:"ordre"`
}

type AnnonceValidationRequest struct {
	MotifRefus *string `json:"motif_refus"`
}

type CreateAnnonceRequest struct {
	Titre       string               `json:"titre"`
	Description string               `json:"description"`
	TypeAnnonce string               `json:"type_annonce"`
	Prix        *float64             `json:"prix,omitempty"`
	ModeRemise  string               `json:"mode_remise"`
	Objets      []CreateObjetRequest `json:"objets"`
}

type CreateObjetRequest struct {
	Categorie string   `json:"categorie"`
	Materiau  string   `json:"materiau"`
	Etat      string   `json:"etat"`
	PoidsKg   *float64 `json:"poids_kg,omitempty"`
	Photos    []string `json:"photos"`
}

type CancelAnnonceRequest struct {
	MotifRetrait string `json:"motif_retrait"`
}

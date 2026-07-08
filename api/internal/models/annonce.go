// Fichier annonce.go : structures d'une annonce (objets à vendre/donner), de ses
// objets/photos rattachés et des requêtes de création, modification et validation.

package models

import "time"

// Annonce : mise en vente ou don d'un ou plusieurs objets par un particulier.
type Annonce struct {
	IDAnnonce      int        `json:"id_annonce"`
	IDParticulier  int        `json:"id_particulier"`
	Titre          string     `json:"titre"`
	Description    string     `json:"description"`
	TypeAnnonce    string     `json:"type_annonce"`
	Prix           *float64   `json:"prix,omitempty"`
	ModeRemise     string     `json:"mode_remise"`
	IDConteneur    *int       `json:"id_conteneur,omitempty"`
	AdresseRemise  *string    `json:"adresse_remise,omitempty"`
	Statut         string     `json:"statut"`
	MotifRefus     *string    `json:"motif_refus,omitempty"`
	MotifRetrait   *string    `json:"motif_retrait,omitempty"`
	DateCreation   time.Time  `json:"date_creation"`
	ValidePar      *int       `json:"valide_par,omitempty"`

	CategorieObjet *string    `json:"categorie_objet,omitempty"`
	MateriauObjet  *string    `json:"materiau_objet,omitempty"`
	EtatObjet      *string    `json:"etat_objet,omitempty"`

	Conteneur      *ConteneurInfo `json:"conteneur,omitempty"`

	Objets         []ObjetAnnonce `json:"objets,omitempty"`
}

// ConteneurInfo : infos d'affichage du point de collecte lié à une annonce.
type ConteneurInfo struct {
	IDConteneur int      `json:"id_conteneur"`
	Ref         string   `json:"conteneur_ref"`
	Adresse     string   `json:"adresse"`
	Ville       string   `json:"ville"`
	CodePostal  *string  `json:"code_postal,omitempty"`
	Latitude    *float64 `json:"latitude,omitempty"`
	Longitude   *float64 `json:"longitude,omitempty"`
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
	IDConteneur *int                 `json:"id_conteneur,omitempty"`
	AdresseRemise *string            `json:"adresse_remise,omitempty"`
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

type UpdateAnnonceRequest struct {
	Titre       string   `json:"titre"`
	Description string   `json:"description"`
	Prix        *float64 `json:"prix,omitempty"`
	ModeRemise  string   `json:"mode_remise"`
}

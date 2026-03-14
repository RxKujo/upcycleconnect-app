package models

import "time"

type CategoriePrestation struct {
	IDCategorie  int       `json:"id_categorie"`
	Nom          string    `json:"nom"`
	Description  string    `json:"description"`
	DateCreation time.Time `json:"date_creation"`
}

type Prestation struct {
	IDPrestation int       `json:"id_prestation"`
	IDCategorie  int       `json:"id_categorie"`
	Titre        string    `json:"titre"`
	Description  string    `json:"description"`
	Prix         float64   `json:"prix"`
	Statut       string    `json:"statut"`
	DateCreation time.Time `json:"date_creation"`
}

type CreatePrestationRequest struct {
	IDCategorie  int     `json:"id_categorie" binding:"required"`
	Titre        string  `json:"titre" binding:"required"`
	Description  string  `json:"description" binding:"required"`
	Prix         float64 `json:"prix" binding:"required"`
}

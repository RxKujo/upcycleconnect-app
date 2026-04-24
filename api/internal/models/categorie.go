package models

import "time"

type CategoriePrestation struct {
	IDCategorie  int       `json:"id_categorie"`
	Nom          string    `json:"nom"`
	Description  string    `json:"description"`
	DateCreation time.Time `json:"date_creation"`
}

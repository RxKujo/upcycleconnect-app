// Fichier categorie.go : catégories de prestations (spécialités) et catégories
// d'objets d'annonces.

package models

import "time"

// CategoriePrestation : spécialité d'un artisan / intervenant.
type CategoriePrestation struct {
	IDCategorie  int       `json:"id_categorie"`
	Nom          string    `json:"nom"`
	Description  string    `json:"description"`
	DateCreation time.Time `json:"date_creation"`
}

// CategorieObjet : liste fermée des catégories d'objets d'annonces,
// gérée par les admins, distincte des spécialités artisans (CategoriePrestation).
type CategorieObjet struct {
	IDCategorieObjet int       `json:"id_categorie_objet"`
	Nom              string    `json:"nom"`
	Actif            bool      `json:"actif"`
	DateCreation     time.Time `json:"date_creation"`
}

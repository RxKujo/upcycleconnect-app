// Fichier materiau.go : référentiel des matériaux (métal, bois, textile…).

package models

// Materiau : type de matériau d'un objet, activable/désactivable par l'admin.
type Materiau struct {
	IDMateriau int     `json:"id_materiau" db:"id_materiau"`
	Code       string  `json:"code" db:"code"`
	Libelle    string  `json:"libelle" db:"libelle"`
	Icone      *string `json:"icone" db:"icone"`
	Actif      bool    `json:"actif" db:"actif"`
}

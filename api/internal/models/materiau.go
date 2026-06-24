package models

type Materiau struct {
	IDMateriau int     `json:"id_materiau" db:"id_materiau"`
	Code       string  `json:"code" db:"code"`
	Libelle    string  `json:"libelle" db:"libelle"`
	Icone      *string `json:"icone" db:"icone"`
	Actif      bool    `json:"actif" db:"actif"`
}

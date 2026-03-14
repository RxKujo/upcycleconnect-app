package models

import "time"

type Utilisateur struct {
	IDUtilisateur   int       `json:"id_utilisateur"`
	Nom             string    `json:"nom"`
	Prenom          string    `json:"prenom"`
	Email           string    `json:"email"`
	MotDePasseHash  string    `json:"-"`
	Telephone       *string   `json:"telephone,omitempty"`
	Ville           *string   `json:"ville,omitempty"`
	Role            string    `json:"role"`
	EstBanni        bool      `json:"est_banni"`
	DateFinBan      *time.Time `json:"date_fin_ban,omitempty"`
	NomEntreprise   *string   `json:"nom_entreprise,omitempty"`
	NumeroSiret     *string   `json:"numero_siret,omitempty"`
	DateCreation    time.Time `json:"date_creation"`
}

type RegisterRequest struct {
	Nom            string  `json:"nom" binding:"required"`
	Prenom         string  `json:"prenom" binding:"required"`
	Email          string  `json:"email" binding:"required,email"`
	MotDePasse     string  `json:"mot_de_passe" binding:"required,min=6"`
	Telephone      *string `json:"telephone"`
	Ville          *string `json:"ville"`
	Role           string  `json:"role" binding:"required"`
	NomEntreprise  *string `json:"nom_entreprise"`
	NumeroSiret    *string `json:"numero_siret"`
}

type LoginRequest struct {
	Email      string `json:"email" binding:"required,email"`
	MotDePasse string `json:"mot_de_passe" binding:"required"`
}

type LoginResponse struct {
	Token string `json:"token"`
}

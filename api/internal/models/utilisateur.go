// Fichier utilisateur.go : structure d'un utilisateur (tous rôles) et payloads
// d'inscription / connexion.

package models

import "time"

// Utilisateur : compte de la plateforme (particulier, professionnel, salarié ou
// admin) ; MotDePasseHash n'est jamais sérialisé en JSON.
type Utilisateur struct {
	IDUtilisateur   int       `json:"id_utilisateur"`
	Nom             string    `json:"nom"`
	Prenom          string    `json:"prenom"`
	Email           string    `json:"email"`
	MotDePasseHash  string    `json:"-"`
	Telephone       *string   `json:"telephone,omitempty"`
	Ville           *string   `json:"ville,omitempty"`
	AdresseComplete *string   `json:"adresse_complete,omitempty"`
	PhotoProfilURL  *string   `json:"photo_profil_url,omitempty"`
	Role            string    `json:"role"`
	EstBanni        bool      `json:"est_banni"`
	DateFinBan      *time.Time `json:"date_fin_ban,omitempty"`
	NomEntreprise   *string   `json:"nom_entreprise,omitempty"`
	NumeroSiret     *string   `json:"numero_siret,omitempty"`
	SiretVerifie    *bool     `json:"siret_verifie,omitempty"`
	NotifPushActive  *bool     `json:"notif_push_active,omitempty"`
	NotifEmailActive *bool     `json:"notif_email_active,omitempty"`
	UpcyclingScore  int       `json:"upcycling_score"`
	EstCertifie     bool      `json:"est_certifie"`
	NiveauScore     string    `json:"niveau_score,omitempty"`
	IDSiteUC        *int      `json:"id_site_uc,omitempty"`
	NomSite         *string   `json:"nom_site,omitempty"`
	DateCreation    time.Time `json:"date_creation"`
}

type RegisterRequest struct {
	Nom             string  `json:"nom" binding:"required"`
	Prenom          string  `json:"prenom" binding:"required"`
	Email           string  `json:"email" binding:"required,email"`
	MotDePasse      string  `json:"mot_de_passe" binding:"required,min=6"`
	Telephone       *string `json:"telephone"`
	Ville           *string `json:"ville"`
	AdresseComplete *string `json:"adresse_complete"`
	CodePostal      *string `json:"code_postal"`
	Role            string  `json:"role" binding:"required"`
	NomEntreprise   *string `json:"nom_entreprise"`
	NumeroSiret     *string `json:"numero_siret"`
	CaptchaToken    string  `json:"captcha_token"`
}

type LoginRequest struct {
	Email      string `json:"email" binding:"required,email"`
	MotDePasse string `json:"mot_de_passe" binding:"required"`
}

type LoginResponse struct {
	Token string `json:"token"`
}

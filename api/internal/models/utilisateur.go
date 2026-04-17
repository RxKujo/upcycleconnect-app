package models

import "time"

type Utilisateur struct {
	IDUtilisateur    int        `json:"id_utilisateur"`
	Nom              string     `json:"nom"`
	Prenom           string     `json:"prenom"`
	Email            string     `json:"email"`
	MotDePasseHash   string     `json:"-"`
	Telephone        *string    `json:"telephone,omitempty"`
	Ville            *string    `json:"ville,omitempty"`
	AdresseComplete  *string    `json:"adresse_complete,omitempty"`
	PhotoProfilURL   *string    `json:"photo_profil_url,omitempty"`
	Role             string     `json:"role"`
	EstBanni         bool       `json:"est_banni"`
	DateFinBan       *time.Time `json:"date_fin_ban,omitempty"`
	NomEntreprise    *string    `json:"nom_entreprise,omitempty"`
	NumeroSiret      *string    `json:"numero_siret,omitempty"`
	SiretVerifie     bool       `json:"siret_verifie"`
	UpcyclingScore   int        `json:"upcycling_score"`
	EstCertifie      bool       `json:"est_certifie"`
	NotifPushActive  bool       `json:"notif_push_active"`
	NotifEmailActive bool       `json:"notif_email_active"`
	DateCreation     time.Time  `json:"date_creation"`
	DeletedAt        *time.Time `json:"deleted_at,omitempty"`
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

type RegisterParticulierRequest struct {
	Nom              string `json:"nom" binding:"required"`
	Prenom           string `json:"prenom" binding:"required"`
	Email            string `json:"email" binding:"required,email"`
	MotDePasse       string `json:"mot_de_passe" binding:"required,min=8"`
	Telephone        *string `json:"telephone"`
	Ville            string `json:"ville" binding:"required"`
	AdresseComplete  *string `json:"adresse_complete"`
	CaptchaToken     *string `json:"captcha_token"`
}

type RegisterParticulierResponse struct {
	Message       string `json:"message"`
	IDUtilisateur int64  `json:"id_utilisateur"`
	Token         string `json:"token"`
}

type RegisterProfessionnelRequest struct {
	Nom             string  `json:"nom" binding:"required"`
	Prenom          string  `json:"prenom" binding:"required"`
	Email           string  `json:"email" binding:"required,email"`
	MotDePasse      string  `json:"mot_de_passe" binding:"required,min=8"`
	Telephone       *string `json:"telephone"`
	Ville           string  `json:"ville" binding:"required"`
	AdresseComplete *string `json:"adresse_complete"`
	NomEntreprise   string  `json:"nom_entreprise" binding:"required"`
	NumeroSiret     string  `json:"numero_siret" binding:"required"`
	CaptchaToken    string  `json:"captcha_token" binding:"required"`
}

type RegisterProfessionnelResponse struct {
	Message       string `json:"message"`
	IDUtilisateur int64  `json:"id_utilisateur"`
	Token         string `json:"token"`
}

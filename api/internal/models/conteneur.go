package models

import "time"

type Conteneur struct {
	IDConteneur  int      `json:"id_conteneur" db:"id_conteneur"`
	ConteneurRef string   `json:"conteneur_ref" db:"conteneur_ref"`
	Adresse      string   `json:"adresse" db:"adresse"`
	Ville        string   `json:"ville" db:"ville"`
	CodePostal   *string  `json:"code_postal" db:"code_postal"`
	Latitude     *float64 `json:"latitude" db:"latitude"`
	Longitude    *float64 `json:"longitude" db:"longitude"`
	Capacite     int      `json:"capacite" db:"capacite"`
	Statut       string   `json:"statut" db:"statut"`
}

type CreateConteneurRequest struct {
	ConteneurRef string  `json:"conteneur_ref" binding:"required"`
	Adresse      string  `json:"adresse" binding:"required"`
	Ville        string  `json:"ville" binding:"required"`
	CodePostal   *string `json:"code_postal"`
	Capacite     int     `json:"capacite" binding:"required"`
}

type CommandeConteneur struct {
	IDCommande             int        `json:"id_commande" db:"id_commande"`
	IDAnnonce              int        `json:"id_annonce" db:"id_annonce"`
	IDAcheteur             int        `json:"id_acheteur" db:"id_acheteur"`
	IDConteneur            *int       `json:"id_conteneur" db:"id_conteneur"`
	CommissionPct          float64    `json:"commission_pct" db:"commission_pct"`
	MontantCommission      float64    `json:"montant_commission" db:"montant_commission"`
	DateLimiteRecuperation *time.Time `json:"date_limite_recuperation" db:"date_limite_recuperation"`
	DateCommande           time.Time  `json:"date_commande" db:"date_commande"`
	Statut                 string     `json:"statut" db:"statut"`
}

type CodeBarre struct {
	IDCodeBarre     int        `json:"id_code_barre" db:"id_code_barre"`
	IDCommande      int        `json:"id_commande" db:"id_commande"`
	CodeValeur      string     `json:"code_valeur" db:"code_valeur"`
	TypeCode        string     `json:"type_code" db:"type_code"`
	DateCreation    time.Time  `json:"date_creation" db:"date_creation"`
	DateUtilisation *time.Time `json:"date_utilisation" db:"date_utilisation"`
	PdfUrl          *string    `json:"pdf_url" db:"pdf_url"`
}

type CreateCodeBarreRequest struct {
	IDCommande int    `json:"id_commande" binding:"required"`
	CodeValeur string `json:"code_valeur" binding:"required"`
	TypeCode   string `json:"type_code" binding:"required"`
	PdfUrl     string `json:"pdf_url"`
}

type TicketIncident struct {
	IDTicket       int        `json:"id_ticket" db:"id_ticket"`
	GlpiTicketID   *string    `json:"glpi_ticket_id" db:"glpi_ticket_id"`
	IDSignaleur    int        `json:"id_signaleur" db:"id_signaleur"`
	IDConteneur    *int       `json:"id_conteneur" db:"id_conteneur"`
	Sujet          string     `json:"sujet" db:"sujet"`
	Description    string     `json:"description" db:"description"`
	Statut         string     `json:"statut" db:"statut"`
	DateCreation   time.Time  `json:"date_creation" db:"date_creation"`
	DateResolution *time.Time `json:"date_resolution" db:"date_resolution"`
}

// Fichier materiel.go : structures de l'inventaire de matériel prêté pour les
// ateliers/conférences et de leurs réservations.

package models

import "time"

// PhotoMateriel : une photo de la galerie d'un matériel.
type PhotoMateriel struct {
	IDPhoto int    `json:"id_photo" db:"id_photo"`
	URL     string `json:"url_photo" db:"url_photo"`
}

// ReservationMateriel : réservation d'un matériel (optionnellement pour un
// événement). Considérée "active" tant que date_retour est NULL.
type ReservationMateriel struct {
	IDReservation   int        `json:"id_reservation" db:"id_reservation"`
	IDMateriel      int        `json:"id_materiel" db:"id_materiel"`
	IDSalarie       int        `json:"id_salarie" db:"id_salarie"`
	IDEvenement     *int       `json:"id_evenement" db:"id_evenement"`
	TitreEvenement  *string    `json:"titre_evenement,omitempty"`
	DateReservation time.Time  `json:"date_reservation" db:"date_reservation"`
	DateRetour      *time.Time `json:"date_retour" db:"date_retour"`
}

// Materiel : objet d'inventaire mis à disposition pour les ateliers/conférences.
type Materiel struct {
	IDMateriel    int                  `json:"id_materiel" db:"id_materiel"`
	Nom           string               `json:"nom" db:"nom"`
	Description   *string              `json:"description" db:"description"`
	Etat          string               `json:"etat" db:"etat"` // neuf | bon | use | a_reparer
	EstDisponible bool                 `json:"est_disponible" db:"est_disponible"`
	IDSite        *int                 `json:"id_site" db:"id_site"`
	EstReserve    bool                 `json:"est_reserve"`
	Reservation   *ReservationMateriel `json:"reservation,omitempty"`
	Photos        []PhotoMateriel      `json:"photos"`
}

// CreateMaterielRequest : payload de création/mise à jour. Images = data URLs
// base64, décodées côté API et poussées sur le stockage (S3/MinIO).
type CreateMaterielRequest struct {
	Nom           string   `json:"nom"`
	Description   *string  `json:"description"`
	Etat          string   `json:"etat"`
	EstDisponible *bool    `json:"est_disponible"`
	IDSite        *int     `json:"id_site"`
	Images        []string `json:"images"`
}

// ReserverMaterielRequest : payload de réservation d'un matériel.
type ReserverMaterielRequest struct {
	IDEvenement *int `json:"id_evenement"`
}

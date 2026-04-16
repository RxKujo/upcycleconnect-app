package models

import "time"

type Evenement struct {
	IDEvenement    int       `json:"id_evenement"`
	IDCreateur     int       `json:"id_createur"`
	Titre          string    `json:"titre"`
	Description    string    `json:"description"`
	TypeEvenement  string    `json:"type_evenement"`
	Format         string    `json:"format"`
	Lieu           *string   `json:"lieu,omitempty"`
	DateDebut      time.Time `json:"date_debut"`
	DateFin        time.Time `json:"date_fin"`
	NbPlacesTotal  int       `json:"nb_places_total"`
	NbPlacesDispo  int       `json:"nb_places_dispo"`
	Prix           float64   `json:"prix"`
	Statut         string    `json:"statut"`
	ValidePar      *int      `json:"valide_par,omitempty"`
	DateCreation   time.Time `json:"date_creation"`
	IsRegistered   bool      `json:"is_registered,omitempty"`
}

type CreateEvenementRequest struct {
	IDCreateur     int       `json:"id_createur" binding:"required"`
	Titre          string    `json:"titre" binding:"required"`
	Description    string    `json:"description" binding:"required"`
	TypeEvenement  string    `json:"type_evenement" binding:"required"`
	Format         string    `json:"format" binding:"required"`
	Lieu           *string   `json:"lieu"`
	DateDebut      CustomTime `json:"date_debut" binding:"required"`
	DateFin        CustomTime `json:"date_fin" binding:"required"`
	NbPlacesTotal  int       `json:"nb_places_total" binding:"required"`
	Prix           float64   `json:"prix" binding:"required"`
}

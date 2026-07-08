// Fichier evenement.go : structures des événements/formations, de leurs séances
// (créneaux) et des animateurs, côté modèle et côté payload de création.

package models

import "time"

// AnimateurInfo : identité minimale d'un animateur d'événement/séance.
type AnimateurInfo struct {
	IDUtilisateur int    `json:"id_utilisateur"`
	Nom           string `json:"nom"`
	Prenom        string `json:"prenom"`
}

// Seance = un créneau d'un événement (une formation peut en compter plusieurs).
// Chaque séance a ses propres date/heures, format, lieu et animateurs.
type Seance struct {
	IDSeance   int             `json:"id_seance"`
	Titre      *string         `json:"titre,omitempty"`
	Format     string          `json:"format"`
	Lieu       *string         `json:"lieu,omitempty"`
	DateDebut  string          `json:"date_debut"`
	DateFin    string          `json:"date_fin"`
	Ordre      int             `json:"ordre"`
	Animateurs []AnimateurInfo `json:"animateurs"`
}

// SeanceInput = payload d'une séance reçu du front lors de la création/édition.
type SeanceInput struct {
	Titre      string `json:"titre"`
	Format     string `json:"format"`
	Lieu       string `json:"lieu"`
	DateDebut  string `json:"date_debut"`
	DateFin    string `json:"date_fin"`
	Animateurs []int  `json:"animateurs"`
}

// Evenement : événement ou formation (potentiellement multi-séances).
type Evenement struct {
	IDEvenement   int             `json:"id_evenement"`
	IDCreateur    int             `json:"id_createur"`
	Titre         string          `json:"titre"`
	Description   string          `json:"description"`
	TypeEvenement string          `json:"type_evenement"`
	Format        string          `json:"format"`
	Lieu          *string         `json:"lieu,omitempty"`
	DateDebut     time.Time       `json:"date_debut"`
	DateFin       time.Time       `json:"date_fin"`
	NbPlacesTotal int             `json:"nb_places_total"`
	NbPlacesDispo int             `json:"nb_places_dispo"`
	Prix          float64         `json:"prix"`
	Statut        string          `json:"statut"`
	ValidePar     *int            `json:"valide_par,omitempty"`
	DateCreation  time.Time       `json:"date_creation"`
	Animateurs    []AnimateurInfo `json:"animateurs,omitempty"`
	Seances       []Seance        `json:"seances,omitempty"`
	NbInscrits    int             `json:"nb_inscrits"`
}

type CreateEvenementRequest struct {
	IDCreateur    int       `json:"id_createur"`
	Titre         string    `json:"titre"`
	Description   string    `json:"description"`
	TypeEvenement string    `json:"type_evenement"`
	Format        string    `json:"format"`
	Lieu          *string   `json:"lieu"`
	DateDebut     time.Time `json:"date_debut"`
	DateFin       time.Time `json:"date_fin"`
	NbPlacesTotal int       `json:"nb_places_total"`
	Prix          float64   `json:"prix"`
	Animateurs    []int     `json:"animateurs"`
	Seances       []SeanceInput `json:"seances"`
}

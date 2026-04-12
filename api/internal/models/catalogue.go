package models

import "time"

type CatalogueItem struct {
    IDCatalogueItem int        `json:"id_catalogue_item"`
    IDCreateur      int        `json:"id_createur"`
    Titre           string     `json:"titre"`
    Description     string     `json:"description"`
    Categorie       string     `json:"categorie"`
    Format          string     `json:"format"`
    Lieu            *string    `json:"lieu,omitempty"`
    DateDebut       time.Time  `json:"date_debut"`
    DateFin         time.Time  `json:"date_fin"`
    NbPlacesTotal   int        `json:"nb_places_total"`
    NbPlacesDispo   int        `json:"nb_places_dispo"`
    Prix            float64    `json:"prix"`
    Statut          string     `json:"statut"`
    ValidePar       *int       `json:"valide_par,omitempty"`
    DateCreation    time.Time  `json:"date_creation"`
}

type CreateCatalogueItemRequest struct {
    IDCreateur     int      `json:"id_createur"`
    Titre          string   `json:"titre" binding:"required"`
    Description    string   `json:"description" binding:"required"`
    Categorie      string   `json:"categorie" binding:"required"`
    Format         string   `json:"format" binding:"required"`
    Lieu           *string  `json:"lieu"`
    DateDebut      time.Time `json:"date_debut" binding:"required"`
    DateFin        time.Time `json:"date_fin" binding:"required"`
    NbPlacesTotal  int      `json:"nb_places_total" binding:"required"`
    Prix           float64  `json:"prix" binding:"required"`
}

type UpdateCatalogueItemRequest struct {
    Titre          string   `json:"titre" binding:"required"`
    Description    string   `json:"description" binding:"required"`
    Categorie      string   `json:"categorie" binding:"required"`
    Format         string   `json:"format" binding:"required"`
    Lieu           *string  `json:"lieu"`
    DateDebut      time.Time `json:"date_debut" binding:"required"`
    DateFin        time.Time `json:"date_fin" binding:"required"`
    NbPlacesTotal  int      `json:"nb_places_total" binding:"required"`
    Prix           float64  `json:"prix" binding:"required"`
}

type Reservation struct {
    IDReservation    int      `json:"id_reservation"`
    IDUtilisateur    int      `json:"id_utilisateur"`
    IDCatalogueItem  int      `json:"id_catalogue_item"`
    DateReservation  time.Time `json:"date_reservation"`
    StatutPaiement   string   `json:"statut_paiement"`
    StripePayment    *string  `json:"stripe_payment,omitempty"`
    PrixPaye         *float64 `json:"prix_paye,omitempty"`
}

type CreateReservationRequest struct {
    StripePayment *string `json:"stripe_payment"`
}

type PlanningItem struct {
    IDCatalogueItem int      `json:"id_catalogue_item"`
    Titre           string   `json:"titre"`
    Categorie       string   `json:"categorie"`
    Format          string   `json:"format"`
    Lieu            *string  `json:"lieu,omitempty"`
    DateDebut       time.Time `json:"date_debut"`
    DateFin         time.Time `json:"date_fin"`
    Prix            float64  `json:"prix"`
    IDReservation   int      `json:"id_reservation"`
    DateReservation time.Time `json:"date_reservation"`
    StatutPaiement  string   `json:"statut_paiement"`
}

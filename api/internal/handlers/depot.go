package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
)

// GetPublicConteneursAvecGeo — carte publique des points de collecte (création d'annonce, remise « conteneur »).
func GetPublicConteneursAvecGeo(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query(`
		SELECT id_conteneur, conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut
		FROM conteneurs WHERE statut = 'actif'`)
	if err != nil {
		jsonError(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	type ConteneurGeo struct {
		IDConteneur int      `json:"id_conteneur"`
		Ref         string   `json:"conteneur_ref"`
		Adresse     string   `json:"adresse"`
		Ville       string   `json:"ville"`
		CodePostal  *string  `json:"code_postal"`
		Latitude    *float64 `json:"latitude"`
		Longitude   *float64 `json:"longitude"`
		Capacite    int      `json:"capacite"`
		Statut      string   `json:"statut"`
	}

	conteneurs := []ConteneurGeo{}
	for rows.Next() {
		var c ConteneurGeo
		var cp sql.NullString
		var lat, lng sql.NullFloat64
		if err := rows.Scan(&c.IDConteneur, &c.Ref, &c.Adresse, &c.Ville, &cp, &lat, &lng, &c.Capacite, &c.Statut); err != nil {
			continue
		}
		if cp.Valid {
			c.CodePostal = &cp.String
		}
		if lat.Valid {
			c.Latitude = &lat.Float64
		}
		if lng.Valid {
			c.Longitude = &lng.Float64
		}
		conteneurs = append(conteneurs, c)
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(conteneurs)
}

package services

import (
	"api/pkg/database"
	"time"
)

// ─── Impact écologique ────────────────────────────────────────────────────────
// Règle CO₂ : 50 % du poids des déchets évités. Isolée ici pour être testée sans DB.
// ─────────────────────────────────────────────────────────────────────────────

const co2FacteurPct = 0.5

// CO2EviteKg : kg de CO₂ évités pour une masse de déchets.
func CO2EviteKg(poidsDechetKg float64) float64 {
	return poidsDechetKg * co2FacteurPct
}

// ImpactEcologique : les 3 métriques du dashboard Essential Pro.
type ImpactEcologique struct {
	NbObjetsRecuperes  int     `json:"nb_objets_recuperes"`
	PoidsDechetKg      float64 `json:"poids_dechet_kg"`
	CO2EviteKg         float64 `json:"co2_evite_kg"`
}

// GetImpactEcologique : impact depuis une date. Seules les commandes 'recuperee' comptent.
func GetImpactEcologique(proID int, depuis time.Time) (ImpactEcologique, error) {
	const q = `
		SELECT
		    COUNT(oa.id_objet)        AS nb_objets,
		    COALESCE(SUM(oa.poids_kg), 0) AS poids_total
		FROM commandes c
		JOIN annonces a  ON a.id_annonce  = c.id_annonce
		JOIN objets_annonces oa ON oa.id_annonce = a.id_annonce
		WHERE c.id_acheteur = ?
		  AND c.statut      = 'recuperee'
		  AND c.date_commande >= ?`

	row := database.DB.QueryRow(q, proID, depuis)
	var imp ImpactEcologique
	if err := row.Scan(&imp.NbObjetsRecuperes, &imp.PoidsDechetKg); err != nil {
		return ImpactEcologique{}, err
	}
	imp.CO2EviteKg = CO2EviteKg(imp.PoidsDechetKg)
	return imp, nil
}

// StatMateriau : nombre d'annonces disponibles par matériau autour du pro.
type StatMateriau struct {
	Materiau   string `json:"materiau"`
	Libelle    string `json:"libelle"`
	NbAnnonces int    `json:"nb_annonces"`
}

// GetStatsMateriaux : annonces validées par matériau dans rayonKm autour de (lat, lon).
// Distance calculée en SQL via Haversine.
func GetStatsMateriaux(lat, lon float64, rayonKm int) ([]StatMateriau, error) {
	const q = `
		SELECT oa.materiau, COALESCE(m.libelle, oa.materiau) AS libelle,
		       COUNT(DISTINCT a.id_annonce) AS nb_annonces
		FROM annonces a
		JOIN objets_annonces oa ON oa.id_annonce = a.id_annonce
		JOIN utilisateurs u     ON u.id_utilisateur = a.id_particulier
		LEFT JOIN materiaux m   ON m.code = oa.materiau
		WHERE a.statut = 'validee'
		  AND u.latitude_entreprise  IS NOT NULL
		  AND u.longitude_entreprise IS NOT NULL
		  AND (
		    6371 * ACOS(
		      COS(RADIANS(?)) * COS(RADIANS(u.latitude_entreprise)) *
		      COS(RADIANS(u.longitude_entreprise) - RADIANS(?)) +
		      SIN(RADIANS(?)) * SIN(RADIANS(u.latitude_entreprise))
		    )
		  ) <= ?
		GROUP BY oa.materiau, m.libelle
		ORDER BY nb_annonces DESC`

	rows, err := database.DB.Query(q, lat, lon, lat, rayonKm)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var stats []StatMateriau
	for rows.Next() {
		var s StatMateriau
		if err := rows.Scan(&s.Materiau, &s.Libelle, &s.NbAnnonces); err != nil {
			return nil, err
		}
		stats = append(stats, s)
	}
	return stats, rows.Err()
}

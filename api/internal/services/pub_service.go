package services

import (
	"api/pkg/database"
	"database/sql"
	"time"
)

// ─── Publicités — Weighted Round-Robin (WRR) ──────────────────────────────────
// Deficit-based WRR. État persisté dans `publicites_rotation`. Poids forcé à 1
// pour TOUS (cahier des charges : « tous la même visibilité ») : rotation
// strictement équitable et déterministe.
// ─────────────────────────────────────────────────────────────────────────────

// PubliciteAffichage : structure renvoyée à la présentation.
type PubliciteAffichage struct {
	IDPublicite     int    `json:"id_publicite"`
	IDProfessionnel int    `json:"id_professionnel"`
	NomEntreprise   string `json:"nom_entreprise"`
	Titre           string `json:"titre"`
	VisuelURL       string `json:"visuel_url"`
	URLCible        string `json:"url_cible"`
}

type pubCandidat struct {
	id            int
	poids         int
	scoreRotation int64
	nbAffichages  int64
}

// PickPublicitesWRR sélectionne jusqu'à n pubs actives (WRR), maj de l'état en transaction.
func PickPublicitesWRR(n int) ([]PubliciteAffichage, error) {
	if n <= 0 {
		return nil, nil
	}

	tx, err := database.DB.Begin()
	if err != nil {
		return nil, err
	}
	defer tx.Rollback() //nolint:errcheck

	now := time.Now()

	// Pubs actives + état de rotation. Poids forcé à 1 pour TOUS (visibilité égale).
	rows, err := tx.Query(`
		SELECT p.id_publicite, 1,
		       COALESCE(pr.score_rotation, 0), COALESCE(pr.nb_affichages, 0),
		       p.titre, COALESCE(p.visuel_url,''), COALESCE(p.url_cible,''),
		       p.id_professionnel, COALESCE(u.nom_entreprise,'')
		FROM publicites p
		LEFT JOIN publicites_rotation pr ON pr.id_publicite = p.id_publicite
		JOIN utilisateurs u ON u.id_utilisateur = p.id_professionnel
		WHERE p.statut = 'active'
		  AND (p.date_debut IS NULL OR p.date_debut <= ?)
		  AND (p.date_fin  IS NULL OR p.date_fin  >= ?)
		FOR UPDATE`, now, now)
	if err != nil {
		return nil, err
	}

	type fullCandidat struct {
		pubCandidat
		pub PubliciteAffichage
	}
	var candidats []fullCandidat
	for rows.Next() {
		var fc fullCandidat
		if err := rows.Scan(
			&fc.id, &fc.poids, &fc.scoreRotation, &fc.nbAffichages,
			&fc.pub.Titre, &fc.pub.VisuelURL, &fc.pub.URLCible,
			&fc.pub.IDProfessionnel, &fc.pub.NomEntreprise,
		); err != nil {
			rows.Close()
			return nil, err
		}
		fc.pub.IDPublicite = fc.id
		candidats = append(candidats, fc)
	}
	rows.Close()

	if len(candidats) == 0 {
		return nil, tx.Commit()
	}

	// Lignes de rotation pour les nouveaux entrants.
	for _, c := range candidats {
		_, err := tx.Exec(`
			INSERT IGNORE INTO publicites_rotation (id_publicite, score_rotation, nb_affichages)
			VALUES (?, 0, 0)`, c.id)
		if err != nil {
			return nil, err
		}
	}

	// Sélection de n pubs par WRR.
	var selection []PubliciteAffichage
	picked := make(map[int]bool)

	for len(selection) < n && len(picked) < len(candidats) {
		// score += poids pour tout candidat non encore sélectionné ce tour.
		for i := range candidats {
			if !picked[candidats[i].id] {
				candidats[i].scoreRotation += int64(candidats[i].poids)
			}
		}
		// Candidat au score le plus élevé.
		best := -1
		for i, c := range candidats {
			if picked[c.id] {
				continue
			}
			if best == -1 || c.scoreRotation > candidats[best].scoreRotation {
				best = i
			}
		}
		if best == -1 {
			break
		}

		candidats[best].scoreRotation = 0
		candidats[best].nbAffichages++
		picked[candidats[best].id] = true
		selection = append(selection, candidats[best].pub)
	}

	// Persister l'état de rotation.
	for _, c := range candidats {
		if _, err := tx.Exec(`
			UPDATE publicites_rotation
			SET score_rotation = ?, nb_affichages = ?, derniere_vue = ?
			WHERE id_publicite = ?`,
			c.scoreRotation, c.nbAffichages, now, c.id); err != nil {
			return nil, err
		}
		// Incrémenter nb_vues sur la table principale (pubs sélectionnées).
		if picked[c.id] {
			if _, err := tx.Exec(`UPDATE publicites SET nb_vues = nb_vues + 1 WHERE id_publicite = ?`, c.id); err != nil {
				return nil, err
			}
		}
	}

	return selection, tx.Commit()
}

// EnregistrerClicPublicite incrémente le compteur de clics.
func EnregistrerClicPublicite(pubID int) error {
	_, err := database.DB.Exec(
		`UPDATE publicites SET nb_clics = nb_clics + 1 WHERE id_publicite = ?`, pubID)
	return err
}

// PublicitePro : publicité vue côté professionnel.
type PublicitePro struct {
	IDPublicite int     `json:"id_publicite"`
	Titre       string  `json:"titre"`
	VisuelURL   *string `json:"visuel_url"`
	URLCible    *string `json:"url_cible"`
	DateDebut   *string `json:"date_debut"`
	DateFin     *string `json:"date_fin"`
	Statut      string  `json:"statut"`
	NbClics     int     `json:"nb_clics"`
	NbVues      int     `json:"nb_vues"`
	CoutMensuel float64 `json:"cout_mensuel"`
	MotifRefus  *string `json:"motif_refus,omitempty"`
}

// GetPublicitesPro retourne les publicités d'un professionnel.
func GetPublicitesPro(proID int) ([]PublicitePro, error) {
	rows, err := database.DB.Query(`
		SELECT id_publicite, titre,
		       visuel_url, url_cible,
		       DATE_FORMAT(date_debut,'%Y-%m-%dT%H:%i:%s'),
		       DATE_FORMAT(date_fin,'%Y-%m-%dT%H:%i:%s'),
		       statut, nb_clics, nb_vues,
		       COALESCE(cout_mensuel, 100.00), motif_refus
		FROM publicites
		WHERE id_professionnel = ?
		ORDER BY id_publicite DESC`, proID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var pubs []PublicitePro
	for rows.Next() {
		var p PublicitePro
		var visuel, url, debut, fin, motif sql.NullString
		if err := rows.Scan(&p.IDPublicite, &p.Titre,
			&visuel, &url, &debut, &fin,
			&p.Statut, &p.NbClics, &p.NbVues, &p.CoutMensuel, &motif); err != nil {
			return nil, err
		}
		if visuel.Valid {
			p.VisuelURL = &visuel.String
		}
		if url.Valid {
			p.URLCible = &url.String
		}
		if debut.Valid {
			p.DateDebut = &debut.String
		}
		if fin.Valid {
			p.DateFin = &fin.String
		}
		if motif.Valid {
			p.MotifRefus = &motif.String
		}
		pubs = append(pubs, p)
	}
	return pubs, rows.Err()
}

// CountPublicitesActivesPro : nombre de pubs non refusées/expirées d'un pro.
func CountPublicitesActivesPro(proID int) (int, error) {
	var n int
	err := database.DB.QueryRow(`
		SELECT COUNT(*) FROM publicites
		WHERE id_professionnel = ?
		  AND statut NOT IN ('refusee','expiree')`, proID).Scan(&n)
	return n, err
}

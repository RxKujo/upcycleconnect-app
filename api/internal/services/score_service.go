package services

import (
	"api/pkg/database"
	"log"
	"math"
)

// =====================================================================
// Upcycling Score — calcul, attribution (idempotente) et certification.
//
// Modèle hybride : un socle de points fixe par action + un bonus
// proportionnel au poids des objets (déchets évités), pondéré par matériau.
//
// Le score d'un utilisateur est la somme de son historique (ledger) :
//   upcycling_score = SUM(historique_score.points)
// ce qui rend le calcul ré-exécutable et auditable.
// =====================================================================

// Points de base par type d'action.
const (
	PointsBaseVente       = 20 // vendeur, annonce de type vente
	PointsBaseDon         = 20 // vendeur, annonce de type don (socle)
	BonusDon              = 30 // bonus écolo supplémentaire pour un don
	PointsBaseAchat       = 15 // acheteur / receveur
	PointsParticipationEv = 25 // participation à un événement / atelier
)

// FacteursMateriau : points par kg selon l'intensité ressources/CO₂ du matériau.
var FacteursMateriau = map[string]float64{
	"metal":        8,
	"electronique": 10,
	"textile":      6,
	"plastique":    5,
	"bois":         4,
	"verre":        3,
	"autre":        3,
}

func facteurMateriau(materiau string) float64 {
	if f, ok := FacteursMateriau[materiau]; ok {
		return f
	}
	return FacteursMateriau["autre"]
}

// Palier représente un niveau du barème (table paliers_score).
type Palier struct {
	IDPalier             int    `json:"id_palier"`
	Nom                  string `json:"nom"`
	SeuilMin             int    `json:"seuil_min"`
	Ordre                int    `json:"ordre"`
	Couleur              string `json:"couleur"`
	ConfereCertification bool   `json:"confere_certification"`
	MiseEnAvant          bool   `json:"mise_en_avant"`
}

// GetPaliers retourne les paliers triés par seuil croissant.
func GetPaliers() ([]Palier, error) {
	rows, err := database.DB.Query(`
		SELECT id_palier, nom, seuil_min, ordre, couleur, confere_certification, mise_en_avant
		FROM paliers_score ORDER BY seuil_min ASC`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var paliers []Palier
	for rows.Next() {
		var p Palier
		if err := rows.Scan(&p.IDPalier, &p.Nom, &p.SeuilMin, &p.Ordre, &p.Couleur, &p.ConfereCertification, &p.MiseEnAvant); err == nil {
			paliers = append(paliers, p)
		}
	}
	return paliers, nil
}

// NiveauPourScore retourne le palier le plus élevé dont le seuil est atteint.
func NiveauPourScore(paliers []Palier, score int) Palier {
	var courant Palier
	for _, p := range paliers {
		if score >= p.SeuilMin {
			courant = p
		}
	}
	return courant
}

// prochainPalier retourne le premier palier non encore atteint (ou nil).
func prochainPalier(paliers []Palier, score int) *Palier {
	for i := range paliers {
		if score < paliers[i].SeuilMin {
			return &paliers[i]
		}
	}
	return nil
}

// estCertifiePourScore : vrai si le score atteint un palier conférant la certification.
func estCertifiePourScore(paliers []Palier, score int) bool {
	for _, p := range paliers {
		if p.ConfereCertification && score >= p.SeuilMin {
			return true
		}
	}
	return false
}

// insertLedger insère une ligne de gain. L'INSERT IGNORE + clé unique
// (utilisateur, motif, ref) garantit l'idempotence : aucun double crédit.
func insertLedger(idUtilisateur, points int, poidsKg float64, motif, refType string, refID int) {
	_, err := database.DB.Exec(`
		INSERT IGNORE INTO historique_score (id_utilisateur, points, poids_kg, motif, ref_type, ref_id)
		VALUES (?, ?, ?, ?, ?, ?)`,
		idUtilisateur, points, poidsKg, motif, refType, refID)
	if err != nil {
		log.Printf("[ERROR] insertLedger | user=%d motif=%s ref=%d/%s : %v", idUtilisateur, motif, refID, refType, err)
	}
}

// RecalcUser recalcule le score agrégé d'un utilisateur depuis le ledger
// et met à jour upcycling_score + est_certifie en conséquence.
func RecalcUser(idUtilisateur int) {
	var score int
	if err := database.DB.QueryRow(
		`SELECT COALESCE(SUM(points), 0) FROM historique_score WHERE id_utilisateur = ?`, idUtilisateur,
	).Scan(&score); err != nil {
		log.Printf("[ERROR] RecalcUser | sum user=%d : %v", idUtilisateur, err)
		return
	}

	paliers, err := GetPaliers()
	if err != nil {
		log.Printf("[ERROR] RecalcUser | paliers : %v", err)
	}
	certifie := estCertifiePourScore(paliers, score)

	if _, err := database.DB.Exec(
		`UPDATE utilisateurs SET upcycling_score = ?, est_certifie = ? WHERE id_utilisateur = ?`,
		score, certifie, idUtilisateur,
	); err != nil {
		log.Printf("[ERROR] RecalcUser | update user=%d : %v", idUtilisateur, err)
	}
}

// AwardScoreForCommande crédite vendeur et acheteur lorsqu'une commande
// est finalisée (statut 'recuperee'). Idempotent : sans effet si déjà crédité.
func AwardScoreForCommande(idCommande int) {
	var idAnnonce, idAcheteur int
	if err := database.DB.QueryRow(
		`SELECT id_annonce, id_acheteur FROM commandes WHERE id_commande = ?`, idCommande,
	).Scan(&idAnnonce, &idAcheteur); err != nil {
		log.Printf("[ERROR] AwardScoreForCommande | commande=%d : %v", idCommande, err)
		return
	}

	var idVendeur int
	var typeAnnonce string
	if err := database.DB.QueryRow(
		`SELECT id_particulier, type_annonce FROM annonces WHERE id_annonce = ?`, idAnnonce,
	).Scan(&idVendeur, &typeAnnonce); err != nil {
		log.Printf("[ERROR] AwardScoreForCommande | annonce=%d : %v", idAnnonce, err)
		return
	}

	// Bonus poids = somme(poids_kg * facteur matériau) sur tous les objets.
	rows, err := database.DB.Query(
		`SELECT materiau, COALESCE(poids_kg, 0) FROM objets_annonces WHERE id_annonce = ?`, idAnnonce)
	var totalPoids, bonus float64
	if err == nil {
		for rows.Next() {
			var materiau string
			var poids float64
			if rows.Scan(&materiau, &poids) == nil {
				totalPoids += poids
				bonus += poids * facteurMateriau(materiau)
			}
		}
		rows.Close()
	}

	// Vendeur / donneur
	vBase := PointsBaseVente
	motifVendeur := "vente_vendeur"
	if typeAnnonce == "don" {
		vBase = PointsBaseDon + BonusDon
		motifVendeur = "don_vendeur"
	}
	vPoints := vBase + int(math.Round(bonus))
	insertLedger(idVendeur, vPoints, totalPoids, motifVendeur, "commande", idCommande)

	// Acheteur / receveur (demi-bonus poids)
	aPoints := PointsBaseAchat + int(math.Round(bonus/2))
	insertLedger(idAcheteur, aPoints, totalPoids, "achat_acheteur", "commande", idCommande)

	RecalcUser(idVendeur)
	RecalcUser(idAcheteur)
}

// AwardScoreForEvenement crédite la participation à un événement / atelier.
func AwardScoreForEvenement(idUtilisateur, idEvenement int) {
	insertLedger(idUtilisateur, PointsParticipationEv, 0, "participation_evenement", "evenement", idEvenement)
	RecalcUser(idUtilisateur)
}

// ScoreDetail : vue complète du score d'un utilisateur (pour la page profil).
type ScoreDetail struct {
	Score           int       `json:"score"`
	DechetsEvitesKg float64   `json:"dechets_evites_kg"`
	NbTransactions  int       `json:"nb_transactions"`
	EstCertifie     bool      `json:"est_certifie"`
	NiveauActuel    Palier    `json:"niveau_actuel"`
	ProchainPalier  *Palier   `json:"prochain_palier,omitempty"`
	PointsManquants int       `json:"points_manquants"`
	Progression     int       `json:"progression_pct"` // progression vers le prochain palier (0-100)
	Paliers         []Palier  `json:"paliers"`
}

// GetUserScoreDetail assemble le détail du score pour l'affichage profil.
func GetUserScoreDetail(idUtilisateur int) (ScoreDetail, error) {
	var d ScoreDetail

	if err := database.DB.QueryRow(`
		SELECT COALESCE(SUM(points), 0), COALESCE(SUM(poids_kg), 0),
		       COALESCE(SUM(motif IN ('vente_vendeur','don_vendeur','achat_acheteur')), 0)
		FROM historique_score WHERE id_utilisateur = ?`, idUtilisateur,
	).Scan(&d.Score, &d.DechetsEvitesKg, &d.NbTransactions); err != nil {
		return d, err
	}

	paliers, err := GetPaliers()
	if err != nil {
		return d, err
	}
	d.Paliers = paliers
	d.NiveauActuel = NiveauPourScore(paliers, d.Score)
	d.EstCertifie = estCertifiePourScore(paliers, d.Score)

	if next := prochainPalier(paliers, d.Score); next != nil {
		d.ProchainPalier = next
		d.PointsManquants = next.SeuilMin - d.Score
		base := d.NiveauActuel.SeuilMin
		span := next.SeuilMin - base
		if span > 0 {
			d.Progression = int(math.Round(float64(d.Score-base) / float64(span) * 100))
		}
	} else {
		d.Progression = 100
	}
	return d, nil
}

// RecomputeAllScores rebâtit le ledger depuis les transactions réelles
// (commandes 'recuperee' + inscriptions événements) puis recalcule tous
// les scores. Idempotent grâce aux INSERT IGNORE.
func RecomputeAllScores() (int, error) {
	// Commandes finalisées
	cmdRows, err := database.DB.Query(`SELECT id_commande FROM commandes WHERE statut = 'recuperee'`)
	if err != nil {
		return 0, err
	}
	var commandeIDs []int
	for cmdRows.Next() {
		var id int
		if cmdRows.Scan(&id) == nil {
			commandeIDs = append(commandeIDs, id)
		}
	}
	cmdRows.Close()
	for _, id := range commandeIDs {
		AwardScoreForCommande(id)
	}

	// Inscriptions événements
	evRows, err := database.DB.Query(`SELECT id_utilisateur, id_evenement FROM inscriptions_evenements`)
	if err == nil {
		type insc struct{ u, e int }
		var inscriptions []insc
		for evRows.Next() {
			var i insc
			if evRows.Scan(&i.u, &i.e) == nil {
				inscriptions = append(inscriptions, i)
			}
		}
		evRows.Close()
		for _, i := range inscriptions {
			AwardScoreForEvenement(i.u, i.e)
		}
	}

	// Recalcule tous les utilisateurs (couvre aussi ceux dont le ledger est vide).
	userRows, err := database.DB.Query(`SELECT id_utilisateur FROM utilisateurs`)
	if err != nil {
		return len(commandeIDs), err
	}
	var userIDs []int
	for userRows.Next() {
		var id int
		if userRows.Scan(&id) == nil {
			userIDs = append(userIDs, id)
		}
	}
	userRows.Close()
	for _, id := range userIDs {
		RecalcUser(id)
	}

	return len(userIDs), nil
}

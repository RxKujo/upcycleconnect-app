package services

import (
	"api/pkg/database"
	"log"
)

// ─── Badges Expert Pro ────────────────────────────────────────────────────────
//
// Les seuils sont lus depuis la table `badges` (modifiables sans redéploiement).
// La logique de calcul ne connaît pas les valeurs en dur.
// ─────────────────────────────────────────────────────────────────────────────

// BadgeDef est la définition d'un badge telle que stockée en base.
type BadgeDef struct {
	IDBadge      int    `json:"id_badge"`
	Nom          string `json:"nom"`
	Description  string `json:"description"`
	SeuilObjets  int    `json:"seuil_objets"`
	TypeMateriau string `json:"type_materiau"`
	Niveau       string `json:"niveau"`
	IconeURL     string `json:"icone_url"`
}

// BadgeUtilisateur représente un badge obtenu par un pro.
type BadgeUtilisateur struct {
	BadgeDef
	DateObtention string `json:"date_obtention"`
}

// GetAllBadges retourne le référentiel complet (pour affichage public du profil).
func GetAllBadges() ([]BadgeDef, error) {
	rows, err := database.DB.Query(`
		SELECT id_badge, nom, COALESCE(description,''), seuil_objets,
		       type_materiau, niveau, COALESCE(icone_url,'')
		FROM badges
		ORDER BY type_materiau, seuil_objets`)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var badges []BadgeDef
	for rows.Next() {
		var b BadgeDef
		if err := rows.Scan(&b.IDBadge, &b.Nom, &b.Description,
			&b.SeuilObjets, &b.TypeMateriau, &b.Niveau, &b.IconeURL); err != nil {
			return nil, err
		}
		badges = append(badges, b)
	}
	return badges, rows.Err()
}

// GetUserBadges retourne les badges déjà obtenus par un utilisateur.
func GetUserBadges(userID int) ([]BadgeUtilisateur, error) {
	rows, err := database.DB.Query(`
		SELECT b.id_badge, b.nom, COALESCE(b.description,''), b.seuil_objets,
		       b.type_materiau, b.niveau, COALESCE(b.icone_url,''),
		       bu.date_obtention
		FROM badges_utilisateurs bu
		JOIN badges b ON b.id_badge = bu.id_badge
		WHERE bu.id_utilisateur = ?
		ORDER BY bu.date_obtention DESC`, userID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var result []BadgeUtilisateur
	for rows.Next() {
		var bu BadgeUtilisateur
		if err := rows.Scan(&bu.IDBadge, &bu.Nom, &bu.Description,
			&bu.SeuilObjets, &bu.TypeMateriau, &bu.Niveau, &bu.IconeURL,
			&bu.DateObtention); err != nil {
			return nil, err
		}
		result = append(result, bu)
	}
	return result, rows.Err()
}

// comptesObjets regroupe les deux valeurs nécessaires au calcul des badges.
type comptesObjets struct {
	total        int            // tous matériaux confondus
	parMateriau  map[string]int // par type de matériau
}

// compterObjetsRecuperes compte les objets effectivement récupérés par un pro.
func compterObjetsRecuperes(userID int) (comptesObjets, error) {
	const q = `
		SELECT oa.materiau, COUNT(*) AS cnt
		FROM commandes c
		JOIN annonces a  ON a.id_annonce  = c.id_annonce
		JOIN objets_annonces oa ON oa.id_annonce = a.id_annonce
		WHERE c.id_acheteur = ?
		  AND c.statut IN ('recuperee')
		GROUP BY oa.materiau`

	rows, err := database.DB.Query(q, userID)
	if err != nil {
		return comptesObjets{}, err
	}
	defer rows.Close()

	res := comptesObjets{parMateriau: make(map[string]int)}
	for rows.Next() {
		var mat string
		var cnt int
		if err := rows.Scan(&mat, &cnt); err != nil {
			return comptesObjets{}, err
		}
		res.parMateriau[mat] += cnt
		res.total += cnt
	}
	return res, rows.Err()
}

// ComputeAndAwardBadges calcule les badges dus à l'utilisateur et les insère
// (INSERT IGNORE = idempotent). Retourne les badges nouvellement attribués.
func ComputeAndAwardBadges(userID int) ([]BadgeDef, error) {
	comptes, err := compterObjetsRecuperes(userID)
	if err != nil {
		return nil, err
	}

	defs, err := GetAllBadges()
	if err != nil {
		return nil, err
	}

	var nouveaux []BadgeDef
	for _, b := range defs {
		atteint := false
		switch b.TypeMateriau {
		case "tous":
			atteint = comptes.total >= b.SeuilObjets
		default:
			atteint = comptes.parMateriau[b.TypeMateriau] >= b.SeuilObjets
		}
		if !atteint {
			continue
		}

		res, err := database.DB.Exec(`
			INSERT IGNORE INTO badges_utilisateurs (id_utilisateur, id_badge)
			VALUES (?, ?)`, userID, b.IDBadge)
		if err != nil {
			log.Printf("[BADGE] Erreur attribution badge %d à user %d : %v", b.IDBadge, userID, err)
			continue
		}
		if rows, _ := res.RowsAffected(); rows > 0 {
			nouveaux = append(nouveaux, b)
		}
	}
	return nouveaux, nil
}

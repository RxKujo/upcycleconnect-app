package middleware

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
)

// ─── Middleware de plan d'abonnement ──────────────────────────────────────────
//
// Différenciation Essential Pro / Expert Pro gérée ici, PAS dans les handlers.
// Les handlers appellent RequireEssentialPro ou RequireExpertPro en début de
// fonction ; si le guard renvoie false la réponse est déjà écrite.
// ─────────────────────────────────────────────────────────────────────────────

// PlanInfo contient les flags du plan actif de l'utilisateur.
type PlanInfo struct {
	IDAbonnement     int    `json:"id_abonnement"`
	Nom              string `json:"nom"`
	NbAlertesMax     *int   `json:"nb_alertes_max"`     // NULL = illimité (Expert Pro)
	RayonAlertMaxKm  *int   `json:"rayon_alerte_max_km"` // NULL = modulable (Expert Pro)
	DashboardAnnuel  bool   `json:"dashboard_annuel"`
	BadgesActives    bool   `json:"badges_actives"`
	EstProFessionnel bool   `json:"est_professionnel"` // vrai si role=professionnel avec un plan actif
}

// GetUserPlanInfo retourne le plan actif du professionnel.
// Renvoie une PlanInfo vide (EstProFessionnel=false) si aucun abonnement actif.
func GetUserPlanInfo(userID int) (*PlanInfo, error) {
	const q = `
		SELECT a.id_abonnement, a.nom,
		       a.nb_alertes_max, a.rayon_alerte_max_km,
		       a.dashboard_annuel, a.badges_actives
		FROM souscriptions s
		JOIN abonnements a ON a.id_abonnement = s.id_abonnement
		WHERE s.id_utilisateur = ?
		  AND s.est_active = TRUE
		  AND (s.date_fin IS NULL OR s.date_fin > NOW())
		  AND a.type_cible = 'professionnel'
		ORDER BY s.date_debut DESC
		LIMIT 1`

	var info PlanInfo
	var nbMax, rayonMax sql.NullInt64
	err := database.DB.QueryRow(q, userID).Scan(
		&info.IDAbonnement, &info.Nom,
		&nbMax, &rayonMax,
		&info.DashboardAnnuel, &info.BadgesActives,
	)
	if err == sql.ErrNoRows {
		return &PlanInfo{EstProFessionnel: false}, nil
	}
	if err != nil {
		return nil, err
	}
	if nbMax.Valid {
		v := int(nbMax.Int64)
		info.NbAlertesMax = &v
	}
	if rayonMax.Valid {
		v := int(rayonMax.Int64)
		info.RayonAlertMaxKm = &v
	}
	info.EstProFessionnel = true
	return &info, nil
}

// IsEssentialPro retourne vrai si le plan est au moins Essential Pro
// (tout plan professionnel actif suffit — Essential est le niveau de base).
func (p *PlanInfo) IsEssentialPro() bool {
	return p.EstProFessionnel
}

// IsExpertPro retourne vrai si le plan inclut les fonctionnalités Expert.
func (p *PlanInfo) IsExpertPro() bool {
	return p.EstProFessionnel && p.DashboardAnnuel && p.BadgesActives
}

// RequireEssentialPro vérifie que l'utilisateur a un plan Essential Pro actif.
// Écrit la réponse HTTP et retourne false si non autorisé.
func RequireEssentialPro(userID int, w http.ResponseWriter) (*PlanInfo, bool) {
	plan, err := GetUserPlanInfo(userID)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return nil, false
	}
	if !plan.IsEssentialPro() {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "abonnement Essential Pro ou Expert Pro requis"})
		return nil, false
	}
	return plan, true
}

// RequireExpertPro vérifie que l'utilisateur a un plan Expert Pro actif.
func RequireExpertPro(userID int, w http.ResponseWriter) (*PlanInfo, bool) {
	plan, err := GetUserPlanInfo(userID)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return nil, false
	}
	if !plan.IsExpertPro() {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "abonnement Expert Pro requis"})
		return nil, false
	}
	return plan, true
}

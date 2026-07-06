package handlers

import (
	"api/internal/middleware"
	"api/internal/services"
	"api/pkg/database"
	"encoding/json"
	"math"
	"net/http"
	"strconv"
	"time"
)

// ─── Alertes matériaux ────────────────────────────────────────────────────────

type alerteResponse struct {
	IDAlerte        int    `json:"id_alerte"`
	Materiau        string `json:"materiau"`
	RayonKm         int    `json:"rayon_km"`
	EstActive       bool   `json:"est_active"`
	DateCreation    string `json:"date_creation"`
}

const msgErrServeurAlerte = "erreur serveur"

// GetAlertesPro liste les alertes du professionnel.
func GetAlertesPro(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.AlertesActives },
		"alertes non incluses dans votre abonnement")
	if !ok {
		return
	}

	rows, err := database.DB.Query(`
		SELECT id_alerte, materiau, rayon_km, est_active, date_creation
		FROM alertes_materiaux
		WHERE id_professionnel = ?
		ORDER BY id_alerte DESC`, userID)
	if err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var alertes []alerteResponse
	for rows.Next() {
		var a alerteResponse
		var dc time.Time
		if err := rows.Scan(&a.IDAlerte, &a.Materiau, &a.RayonKm, &a.EstActive, &dc); err != nil {
			jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
			return
		}
		a.DateCreation = dc.Format(time.RFC3339)
		alertes = append(alertes, a)
	}
	jsonOK(w, alertes, http.StatusOK)
}

// CreateAlertePro crée une alerte matériau en respectant les contraintes du plan.
func CreateAlertePro(w http.ResponseWriter, r *http.Request, userID int) {
	plan, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.AlertesActives },
		"alertes non incluses dans votre abonnement")
	if !ok {
		return
	}

	var req struct {
		Materiau string `json:"materiau"`
		RayonKm  int    `json:"rayon_km"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	if !materiauActif(req.Materiau) {
		jsonErr(w, "matériau invalide", http.StatusBadRequest)
		return
	}

	// Rayon : Essential fixe à 10 km, Expert libre (mais minimum 1)
	rayon := req.RayonKm
	if plan.RayonAlertMaxKm != nil {
		// Essential Pro : rayon fixe 10 km, on ignore la valeur fournie
		rayon = *plan.RayonAlertMaxKm
	}
	if rayon < 1 {
		rayon = 1
	}

	// Empêcher le doublon : même matériau déjà actif pour ce pro
	var existing int
	if err := database.DB.QueryRow(`
		SELECT COUNT(*) FROM alertes_materiaux
		WHERE id_professionnel = ? AND materiau = ? AND est_active = TRUE`,
		userID, req.Materiau).Scan(&existing); err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}
	if existing > 0 {
		jsonErr(w, "vous avez déjà une alerte active pour ce matériau", http.StatusConflict)
		return
	}

	// Vérifier la limite du nombre d'alertes (nil = illimité pour Expert Pro)
	if plan.NbAlertesMax != nil {
		var count int
		if err := database.DB.QueryRow(`
			SELECT COUNT(*) FROM alertes_materiaux
			WHERE id_professionnel = ? AND est_active = TRUE`, userID).Scan(&count); err != nil {
			jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
			return
		}
		if count >= *plan.NbAlertesMax {
			jsonErr(w, "limite d'alertes atteinte pour votre plan (3 max)", http.StatusForbidden)
			return
		}
	}

	res, err := database.DB.Exec(`
		INSERT INTO alertes_materiaux (id_professionnel, materiau, rayon_km)
		VALUES (?, ?, ?)`, userID, req.Materiau, rayon)
	if err != nil {
		jsonErr(w, "impossible de créer l'alerte", http.StatusInternalServerError)
		return
	}
	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "alerte créée", "id_alerte": id, "rayon_km": rayon}, http.StatusCreated)
}

// DeleteAlertePro supprime une alerte appartenant au pro.
func DeleteAlertePro(w http.ResponseWriter, r *http.Request, alerteID string, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.AlertesActives },
		"alertes non incluses dans votre abonnement")
	if !ok {
		return
	}

	res, err := database.DB.Exec(`
		DELETE FROM alertes_materiaux
		WHERE id_alerte = ? AND id_professionnel = ?`, alerteID, userID)
	if err != nil {
		jsonErr(w, msgErrServeurAlerte, http.StatusInternalServerError)
		return
	}
	if n, _ := res.RowsAffected(); n == 0 {
		jsonErr(w, "alerte introuvable", http.StatusNotFound)
		return
	}
	jsonOK(w, map[string]string{"message": "alerte supprimée"}, http.StatusOK)
}

// ─── Envoi des alertes (appelé par le worker lors d'une nouvelle annonce) ─────

// SendAlertesMateriau déclenche l'envoi des alertes correspondant à une annonce.
// Appelé depuis le worker ou le handler de validation d'annonce.
func SendAlertesMateriau(annonceID int, materiau string, villeAnnonce string) {
	// Charger les pros ayant une alerte active pour ce matériau — 1 ligne par pro (rayon max)
	rows, err := database.DB.Query(`
		SELECT am.id_professionnel, u.email, u.onesignal_player_id,
		       COALESCE(u.latitude_entreprise, 0), COALESCE(u.longitude_entreprise, 0),
		       MAX(am.rayon_km) AS rayon_km
		FROM alertes_materiaux am
		JOIN utilisateurs u ON u.id_utilisateur = am.id_professionnel
		WHERE am.materiau = ? AND am.est_active = TRUE
		GROUP BY am.id_professionnel, u.email, u.onesignal_player_id,
		         u.latitude_entreprise, u.longitude_entreprise`, materiau)
	if err != nil {
		logError("SendAlertesMateriau", "query: %v", err)
		return
	}
	defer rows.Close()

	// Récupérer les coordonnées de l'annonce (via le particulier)
	var annLat, annLon float64
	database.DB.QueryRow(`
		SELECT COALESCE(u.latitude_entreprise, 0), COALESCE(u.longitude_entreprise, 0)
		FROM annonces a
		JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
		WHERE a.id_annonce = ?`, annonceID).Scan(&annLat, &annLon)

	for rows.Next() {
		var proID int
		var email, playerID string
		var proLat, proLon float64
		var rayonKm int
		rows.Scan(&proID, &email, &playerID, &proLat, &proLon, &rayonKm)

		if !dansLeRayon(proLat, proLon, annLat, annLon, float64(rayonKm)) {
			continue
		}

		// Email (tous les plans)
		sujet := "Nouvelle annonce de matériau : " + materiau
		corps := "Une annonce de " + materiau + " est disponible près de " + villeAnnonce + ".\n" +
			"Connectez-vous sur UpcycleConnect pour la consulter."
		if err := services.SendSimpleEmail(email, sujet, corps); err != nil {
			logError("SendAlertesMateriau", "email pro %d: %v", proID, err)
		}

		// Push OneSignal : uniquement si le plan inclut les alertes push.
		plan, err := middleware.GetUserPlanInfo(proID)
		if err == nil && plan.AlertesPush && playerID != "" {
			services.NotifierAlerteMateriauPush(playerID, materiau, 1, villeAnnonce)
		}
	}
}

// dansLeRayon retourne vrai si la distance Haversine entre deux points est ≤ rayonKm.
func dansLeRayon(lat1, lon1, lat2, lon2, rayonKm float64) bool {
	if lat1 == 0 && lon1 == 0 {
		return false
	}
	const R = 6371.0
	dlat := (lat2 - lat1) * math.Pi / 180
	dlon := (lon2 - lon1) * math.Pi / 180
	a := math.Sin(dlat/2)*math.Sin(dlat/2) +
		math.Cos(lat1*math.Pi/180)*math.Cos(lat2*math.Pi/180)*
			math.Sin(dlon/2)*math.Sin(dlon/2)
	c := 2 * math.Atan2(math.Sqrt(a), math.Sqrt(1-a))
	return R*c <= rayonKm
}

// ConvertirIDAlerte convertit un string en int pour le routing.
func ConvertirIDAlerte(s string) (int, bool) {
	v, err := strconv.Atoi(s)
	return v, err == nil
}

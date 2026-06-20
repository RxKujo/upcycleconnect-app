package handlers

import (
	"api/internal/middleware"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"fmt"
	"net/http"
	"time"

	"github.com/jung-kurt/gofpdf"
)

const (
	msgErrProfilPro  = "impossible de charger le profil pro"
	msgErrImpact     = "erreur calcul impact"
	msgErrStatsMat   = "erreur stats matériaux"
)

// ─── Dashboard Essential Pro ──────────────────────────────────────────────────

type dashboardEssentialResponse struct {
	Periode         string                    `json:"periode"`
	Impact          services.ImpactEcologique `json:"impact_ecologique"`
	StatsMateriaux  []services.StatMateriau   `json:"stats_materiaux"`
}

// GetDashboardEssential retourne les métriques du mois courant.
func GetDashboardEssential(w http.ResponseWriter, r *http.Request, userID int) {
	plan, ok := middleware.RequireEssentialPro(userID, w)
	if !ok {
		return
	}
	_ = plan

	pro, err := proCoords(userID)
	if err != nil {
		jsonErr(w, msgErrProfilPro, http.StatusInternalServerError)
		return
	}

	depuis := debutMoisCourant()
	impact, err := services.GetImpactEcologique(userID, depuis)
	if err != nil {
		jsonErr(w, msgErrImpact, http.StatusInternalServerError)
		return
	}

	stats, err := services.GetStatsMateriaux(pro.lat, pro.lon, 10)
	if err != nil {
		jsonErr(w, msgErrStatsMat, http.StatusInternalServerError)
		return
	}

	jsonOK(w, dashboardEssentialResponse{
		Periode:        depuis.Format("2006-01"),
		Impact:         impact,
		StatsMateriaux: stats,
	}, http.StatusOK)
}

// ─── Dashboard Expert Pro ─────────────────────────────────────────────────────

type dashboardExpertResponse struct {
	Annee          int                        `json:"annee"`
	Impact         services.ImpactEcologique  `json:"impact_ecologique"`
	StatsMateriaux []services.StatMateriau    `json:"stats_materiaux"`
	Badges         []services.BadgeUtilisateur `json:"badges"`
}

// GetDashboardExpert retourne les métriques de l'année courante + badges.
func GetDashboardExpert(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireExpertPro(userID, w)
	if !ok {
		return
	}

	pro, err := proCoords(userID)
	if err != nil {
		jsonErr(w, msgErrProfilPro, http.StatusInternalServerError)
		return
	}

	annee := time.Now().Year()
	depuis := time.Date(annee, 1, 1, 0, 0, 0, 0, time.UTC)

	impact, err := services.GetImpactEcologique(userID, depuis)
	if err != nil {
		jsonErr(w, msgErrImpact, http.StatusInternalServerError)
		return
	}

	stats, err := services.GetStatsMateriaux(pro.lat, pro.lon, 10)
	if err != nil {
		jsonErr(w, msgErrStatsMat, http.StatusInternalServerError)
		return
	}

	// Recalculer et attribuer les badges avant de les lire.
	if _, err := services.ComputeAndAwardBadges(userID); err != nil {
		logError("GetDashboardExpert", "badge award: %v", err)
	}

	badges, err := services.GetUserBadges(userID)
	if err != nil {
		jsonErr(w, "erreur chargement badges", http.StatusInternalServerError)
		return
	}

	jsonOK(w, dashboardExpertResponse{
		Annee:          annee,
		Impact:         impact,
		StatsMateriaux: stats,
		Badges:         badges,
	}, http.StatusOK)
}

// ─── Export PDF annuel (Expert Pro) ──────────────────────────────────────────

// ExportDashboardPDF génère et envoie un PDF du rapport annuel Expert Pro.
func ExportDashboardPDF(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequireExpertPro(userID, w)
	if !ok {
		return
	}

	pro, err := proCoords(userID)
	if err != nil {
		jsonErr(w, msgErrProfilPro, http.StatusInternalServerError)
		return
	}

	annee := time.Now().Year()
	depuis := time.Date(annee, 1, 1, 0, 0, 0, 0, time.UTC)

	impact, err := services.GetImpactEcologique(userID, depuis)
	if err != nil {
		jsonErr(w, msgErrImpact, http.StatusInternalServerError)
		return
	}

	stats, err := services.GetStatsMateriaux(pro.lat, pro.lon, 10)
	if err != nil {
		jsonErr(w, msgErrStatsMat, http.StatusInternalServerError)
		return
	}

	badges, err := services.GetUserBadges(userID)
	if err != nil {
		jsonErr(w, "erreur badges", http.StatusInternalServerError)
		return
	}

	pdf := buildDashboardPDF(annee, pro.nom, impact, stats, badges)

	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="rapport-upcycleconnect-%d.pdf"`, annee))
	w.WriteHeader(http.StatusOK)
	if err := pdf.Output(w); err != nil {
		logError("ExportDashboardPDF", "output: %v", err)
	}
}

func buildDashboardPDF(annee int, nomEntreprise string,
	impact services.ImpactEcologique,
	stats []services.StatMateriau,
	badges []services.BadgeUtilisateur) *gofpdf.Fpdf {

	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.AddPage()
	pdf.SetFont("Arial", "B", 18)
	pdf.Cell(0, 12, fmt.Sprintf("Rapport annuel %d — %s", annee, nomEntreprise))
	pdf.Ln(16)

	// Impact écologique
	pdf.SetFont("Arial", "B", 13)
	pdf.Cell(0, 8, "Impact écologique")
	pdf.Ln(10)
	pdf.SetFont("Arial", "", 11)
	pdf.Cell(0, 7, fmt.Sprintf("Objets récupérés  : %d", impact.NbObjetsRecuperes))
	pdf.Ln(7)
	pdf.Cell(0, 7, fmt.Sprintf("Poids déchets évités : %.2f kg", impact.PoidsDechetKg))
	pdf.Ln(7)
	pdf.Cell(0, 7, fmt.Sprintf("CO₂ évité         : %.2f kg", impact.CO2EviteKg))
	pdf.Ln(12)

	// Stats matériaux
	pdf.SetFont("Arial", "B", 13)
	pdf.Cell(0, 8, "Annonces disponibles par matériau (rayon 10 km)")
	pdf.Ln(10)
	pdf.SetFont("Arial", "", 11)
	for _, s := range stats {
		pdf.Cell(0, 7, fmt.Sprintf("  %-20s %d annonce(s)", s.Materiau, s.NbAnnonces))
		pdf.Ln(7)
	}
	pdf.Ln(6)

	// Badges
	if len(badges) > 0 {
		pdf.SetFont("Arial", "B", 13)
		pdf.Cell(0, 8, "Badges obtenus")
		pdf.Ln(10)
		pdf.SetFont("Arial", "", 11)
		for _, b := range badges {
			pdf.Cell(0, 7, fmt.Sprintf("  🏅 %s (obtenu le %s)", b.Nom, b.DateObtention[:10]))
			pdf.Ln(7)
		}
	}

	pdf.Ln(12)
	pdf.SetFont("Arial", "I", 9)
	pdf.Cell(0, 6, fmt.Sprintf("Généré par UpcycleConnect le %s", time.Now().Format("02/01/2006")))
	return pdf
}

// ─── Helpers internes ─────────────────────────────────────────────────────────

type proProfile struct {
	lat float64
	lon float64
	nom string
}

func proCoords(userID int) (proProfile, error) {
	var p proProfile
	var lat, lon sql.NullFloat64
	var nom sql.NullString
	err := database.DB.QueryRow(`
		SELECT latitude_entreprise, longitude_entreprise, COALESCE(nom_entreprise, CONCAT(prenom,' ',nom))
		FROM utilisateurs WHERE id_utilisateur = ?`, userID).
		Scan(&lat, &lon, &nom)
	if err != nil {
		return proProfile{}, err
	}
	if lat.Valid { p.lat = lat.Float64 }
	if lon.Valid { p.lon = lon.Float64 }
	if nom.Valid { p.nom = nom.String }
	return p, nil
}

func debutMoisCourant() time.Time {
	now := time.Now()
	return time.Date(now.Year(), now.Month(), 1, 0, 0, 0, 0, time.UTC)
}

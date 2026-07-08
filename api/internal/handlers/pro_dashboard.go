package handlers

// Dashboard Pro : impact écologique, stats matériaux (Essential/Expert), export PDF annuel.

import (
	"api/internal/middleware"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"fmt"
	"net/http"
	"time"
	"unicode"
	"unicode/utf8"

	"github.com/jung-kurt/gofpdf"
)

// capitalize : première lettre en majuscule.
func capitalize(s string) string {
	if s == "" {
		return s
	}
	r := []rune(s)
	r[0] = unicode.ToUpper(r[0])
	return string(r)
}

// fixMojibake répare le double-encodage UTF-8 (ex. "é" stocké "Ã©").
// Sans danger : n'agit que si chaque rune tient sur un octet ET que le résultat
// est de l'UTF-8 valide différent de l'original ; sinon retourne tel quel.
func fixMojibake(s string) string {
	b := make([]byte, 0, len(s))
	for _, r := range s {
		if r > 0xFF {
			return s // runes hors Latin-1 : pas du mojibake
		}
		b = append(b, byte(r))
	}
	if decoded := string(b); utf8.Valid(b) && decoded != s {
		return decoded
	}
	return s
}

// ─── Palette rapport (sobre / corporate) ─────────────────────────────────────
var (
	colInk    = [3]int{34, 34, 34}    // texte principal
	colMuted  = [3]int{135, 135, 135} // texte secondaire
	colLine   = [3]int{224, 224, 224} // filets
	colAccent = [3]int{36, 79, 38}    // vert forêt (#244F26)
)

func setText(pdf *gofpdf.Fpdf, c [3]int) { pdf.SetTextColor(c[0], c[1], c[2]) }
func setLine(pdf *gofpdf.Fpdf, c [3]int) { pdf.SetDrawColor(c[0], c[1], c[2]) }

// sectionTitle : titre de section + filet fin.
func sectionTitle(pdf *gofpdf.Fpdf, x, y, w float64, title string) {
	setText(pdf, colInk)
	pdf.SetFont("dv", "B", 12)
	pdf.SetXY(x, y)
	pdf.CellFormat(w, 7, title, "", 0, "L", false, 0, "")
	setLine(pdf, colLine)
	pdf.SetLineWidth(0.3)
	pdf.Line(x, y+9, x+w, y+9)
}

// drawMetric : grand chiffre coloré + libellé en dessous.
func drawMetric(pdf *gofpdf.Fpdf, x, y, w float64, value, label string) {
	setText(pdf, colAccent)
	pdf.SetFont("dv", "B", 22)
	pdf.SetXY(x, y)
	pdf.CellFormat(w, 11, value, "", 0, "L", false, 0, "")
	setText(pdf, colMuted)
	pdf.SetFont("dv", "", 8.5)
	pdf.SetXY(x, y+12)
	pdf.CellFormat(w, 6, label, "", 0, "L", false, 0, "")
}

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

// GetDashboardEssential : métriques du mois courant.
func GetDashboardEssential(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.DashboardMensuel },
		"tableau de bord non inclus dans votre abonnement")
	if !ok {
		return
	}

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

// GetDashboardExpert : métriques de l'année courante + badges.
func GetDashboardExpert(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.DashboardAnnuel },
		"tableau de bord annuel non inclus dans votre abonnement")
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

	// Recalculer les badges avant de les lire.
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

// ExportDashboardPDF : génère et envoie le PDF du rapport annuel Expert Pro.
func ExportDashboardPDF(w http.ResponseWriter, r *http.Request, userID int) {
	_, ok := middleware.RequirePlanFeature(userID, w,
		func(p *middleware.PlanInfo) bool { return p.ExportPDF },
		"export PDF non inclus dans votre abonnement")
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

// buildDashboardPDF : met en page le rapport annuel d'impact (A4, gofpdf).
func buildDashboardPDF(annee int, nomEntreprise string,
	impact services.ImpactEcologique,
	stats []services.StatMateriau,
	badges []services.BadgeUtilisateur) *gofpdf.Fpdf {

	const (
		pageW    = 210.0
		pageH    = 297.0
		margin   = 15.0
		contentW = pageW - 2*margin // 180
	)

	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.SetMargins(margin, margin, margin)
	pdf.SetAutoPageBreak(true, 8)
	pdf.AddUTF8Font("dv", "", "fonts/DejaVuSans.ttf")
	pdf.AddUTF8Font("dv", "B", "fonts/DejaVuSans-Bold.ttf")
	pdf.AddPage()

	// ── En-tête ──────────────────────────────────────────────────────────────
	setText(pdf, colMuted)
	pdf.SetFont("dv", "B", 9)
	pdf.SetXY(margin, 18)
	pdf.CellFormat(contentW, 5, "UPCYCLECONNECT  ·  RAPPORT ANNUEL D'IMPACT", "", 0, "L", false, 0, "")

	setText(pdf, colInk)
	pdf.SetFont("dv", "B", 24)
	pdf.SetXY(margin, 24)
	pdf.CellFormat(contentW, 12, fixMojibake(nomEntreprise), "", 0, "L", false, 0, "")

	setText(pdf, colMuted)
	pdf.SetFont("dv", "", 11)
	pdf.SetXY(margin, 37)
	pdf.CellFormat(contentW, 6, fmt.Sprintf("Année %d  ·  Espace Expert Pro", annee), "", 0, "L", false, 0, "")

	setLine(pdf, colAccent)
	pdf.SetLineWidth(0.8)
	pdf.Line(margin, 47, margin+contentW, 47)

	y := 58.0

	// ── Impact écologique ────────────────────────────────────────────────────
	sectionTitle(pdf, margin, y, contentW, "Impact écologique")
	y += 16

	colW := contentW / 3
	drawMetric(pdf, margin, y, colW,
		fmt.Sprintf("%d", impact.NbObjetsRecuperes), "Objets récupérés")
	drawMetric(pdf, margin+colW, y, colW,
		fmt.Sprintf("%.0f kg", impact.PoidsDechetKg), "Déchets évités")
	drawMetric(pdf, margin+2*colW, y, colW,
		fmt.Sprintf("%.1f kg", impact.CO2EviteKg), "CO₂ évité")
	y += 30

	// ── Annonces disponibles par matériau ────────────────────────────────────
	sectionTitle(pdf, margin, y, contentW, "Annonces disponibles par matériau")
	setText(pdf, colMuted)
	pdf.SetFont("dv", "", 9)
	pdf.SetXY(margin, y+10)
	pdf.CellFormat(contentW, 6, "Dans un rayon de 10 km autour de votre établissement", "", 0, "L", false, 0, "")
	y += 20

	rowH := 9.0
	if len(stats) == 0 {
		setText(pdf, colMuted)
		pdf.SetFont("dv", "", 11)
		pdf.SetXY(margin, y)
		pdf.CellFormat(contentW, rowH, "Aucune annonce disponible pour le moment.", "", 0, "L", false, 0, "")
		y += rowH
	}
	for _, s := range stats {
		setText(pdf, colInk)
		pdf.SetFont("dv", "", 11)
		pdf.SetXY(margin, y)
		pdf.CellFormat(contentW/2, rowH, capitalize(fixMojibake(s.Materiau)), "", 0, "L", false, 0, "")
		pdf.SetFont("dv", "B", 11)
		pdf.SetXY(margin, y)
		pdf.CellFormat(contentW, rowH, fmt.Sprintf("%d annonce(s)", s.NbAnnonces), "", 0, "R", false, 0, "")
		setLine(pdf, colLine)
		pdf.SetLineWidth(0.2)
		pdf.Line(margin, y+rowH, margin+contentW, y+rowH)
		y += rowH + 2
	}
	y += 12

	// ── Badges obtenus ───────────────────────────────────────────────────────
	if len(badges) > 0 {
		sectionTitle(pdf, margin, y, contentW, "Badges obtenus")
		y += 16
		for _, b := range badges {
			setText(pdf, colAccent)
			pdf.SetFont("dv", "B", 11)
			pdf.SetXY(margin, y)
			pdf.CellFormat(6, rowH, "•", "", 0, "L", false, 0, "")
			setText(pdf, colInk)
			pdf.SetXY(margin+6, y)
			pdf.CellFormat(contentW-6, rowH, fixMojibake(b.Nom), "", 0, "L", false, 0, "")
			setText(pdf, colMuted)
			pdf.SetFont("dv", "", 9)
			pdf.SetXY(margin, y)
			pdf.CellFormat(contentW, rowH, "obtenu le "+b.DateObtention[:10], "", 0, "R", false, 0, "")
			y += rowH + 1
		}
	}

	// ── Pied de page ─────────────────────────────────────────────────────────
	setLine(pdf, colLine)
	pdf.SetLineWidth(0.3)
	pdf.Line(margin, pageH-18, margin+contentW, pageH-18)
	setText(pdf, colMuted)
	pdf.SetFont("dv", "", 8)
	pdf.SetXY(margin, pageH-16)
	pdf.CellFormat(contentW, 6,
		"Généré par UpcycleConnect le "+time.Now().Format("02/01/2006"),
		"", 0, "C", false, 0, "")
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

package handlers

// Pilotage financier — back-office admin.
// Réutilise les tables factures (légal), souscriptions et transactions (temps-réel).
// Export CSV natif Go, export PDF via gofpdf (déjà disponible dans le projet).

import (
	"bytes"
	"api/pkg/database"
	"database/sql"
	"fmt"
	"net/http"
	"strconv"
	"strings"
	"time"

	"github.com/jung-kurt/gofpdf"
)

// ─── Revenus par source / mois ────────────────────────────────────────────────

type RevenuLigne struct {
	Mois           string  `json:"mois"`
	TypeSource     string  `json:"type_source"`
	NbTransactions int     `json:"nb_transactions"`
	TotalHT        float64 `json:"total_ht"`
	TotalTTC       float64 `json:"total_ttc"`
}

// GetRevenusSynthese agrège les revenus par type_facture et par mois.
// Renvoie un tableau plat (consommé directement par la vue), filtrable par
// année (défaut : année courante), type de source et mois.
func GetRevenusSynthese(w http.ResponseWriter, r *http.Request) {
	annee := r.URL.Query().Get("annee")
	if annee == "" {
		annee = strconv.Itoa(time.Now().Year())
	}
	typeSource := r.URL.Query().Get("type")
	mois := r.URL.Query().Get("mois")

	query := `
		SELECT DATE_FORMAT(f.date_emission, '%Y-%m') AS mois,
		       f.type_facture,
		       COUNT(*)                              AS nb_transactions,
		       SUM(f.montant_ht)                     AS total_ht,
		       SUM(f.montant_ttc)                    AS total_ttc
		FROM factures f
		WHERE YEAR(f.date_emission) = ?`
	args := []interface{}{annee}
	if typeSource != "" {
		query += " AND f.type_facture = ?"
		args = append(args, typeSource)
	}
	if mois != "" {
		query += " AND MONTH(f.date_emission) = ?"
		args = append(args, mois)
	}
	query += " GROUP BY mois, f.type_facture ORDER BY mois ASC, f.type_facture ASC"

	rows, err := database.DB.Query(query, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []RevenuLigne{}
	for rows.Next() {
		var l RevenuLigne
		if rows.Scan(&l.Mois, &l.TypeSource, &l.NbTransactions, &l.TotalHT, &l.TotalTTC) == nil {
			out = append(out, l)
		}
	}

	jsonOK(w, out, http.StatusOK)
}

// ─── Liste des factures ───────────────────────────────────────────────────────

type FactureLine struct {
	IDFacture      int     `json:"id_facture"`
	NumeroFacture  string  `json:"numero_facture"`
	IDUtilisateur  int     `json:"id_utilisateur"`
	NomUtilisateur string  `json:"nom_utilisateur"`
	EmailUser      string  `json:"email"`
	MontantHT      float64 `json:"montant_ht"`
	MontantTTC     float64 `json:"montant_ttc"`
	TypeFacture    string  `json:"type_facture"`
	Service        *string `json:"service,omitempty"`
	DateEmission   string  `json:"date_emission"`
	StripeID       *string `json:"stripe_payment_id,omitempty"`
}

func GetFactures(w http.ResponseWriter, r *http.Request) {
	q := r.URL.Query()
	typeF := q.Get("type")
	mois := q.Get("mois")
	annee := q.Get("annee")
	userID := q.Get("user_id")

	base := `
		SELECT f.id_facture, f.numero_facture, f.id_utilisateur,
		       CONCAT(u.prenom,' ',u.nom), u.email,
		       f.montant_ht, f.montant_ttc,
		       f.type_facture, f.service,
		       DATE_FORMAT(f.date_emission,'%Y-%m-%dT%H:%i:%s'),
		       f.stripe_payment_id
		FROM factures f
		JOIN utilisateurs u ON u.id_utilisateur = f.id_utilisateur
		WHERE 1=1`
	args := []interface{}{}

	if typeF != "" {
		base += " AND f.type_facture = ?"
		args = append(args, typeF)
	}
	if mois != "" {
		base += " AND DATE_FORMAT(f.date_emission,'%Y-%m') = ?"
		args = append(args, mois)
	} else if annee != "" {
		base += " AND YEAR(f.date_emission) = ?"
		args = append(args, annee)
	}
	if userID != "" {
		base += " AND f.id_utilisateur = ?"
		args = append(args, userID)
	}
	base += " ORDER BY f.date_emission DESC LIMIT 1000"

	rows, err := database.DB.Query(base, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	out := []FactureLine{}
	for rows.Next() {
		var f FactureLine
		var service, stripeID sql.NullString
		if err := rows.Scan(&f.IDFacture, &f.NumeroFacture, &f.IDUtilisateur,
			&f.NomUtilisateur, &f.EmailUser,
			&f.MontantHT, &f.MontantTTC,
			&f.TypeFacture, &service,
			&f.DateEmission, &stripeID); err == nil {
			if service.Valid {
				f.Service = &service.String
			}
			if stripeID.Valid {
				f.StripeID = &stripeID.String
			}
			out = append(out, f)
		}
	}
	jsonOK(w, out, http.StatusOK)
}

// ─── Export CSV ───────────────────────────────────────────────────────────────

func ExportFacturesCSV(w http.ResponseWriter, r *http.Request) {
	annee := r.URL.Query().Get("annee")
	mois := r.URL.Query().Get("mois")
	typeF := r.URL.Query().Get("type")

	base := `
		SELECT f.numero_facture, CONCAT(u.prenom,' ',u.nom), u.email,
		       f.montant_ht, f.montant_ttc, f.type_facture,
		       COALESCE(f.service,''),
		       DATE_FORMAT(f.date_emission,'%Y-%m-%d'),
		       COALESCE(f.stripe_payment_id,'')
		FROM factures f
		JOIN utilisateurs u ON u.id_utilisateur = f.id_utilisateur
		WHERE 1=1`
	args := []interface{}{}
	if typeF != "" {
		base += " AND f.type_facture = ?"
		args = append(args, typeF)
	}
	if mois != "" {
		base += " AND DATE_FORMAT(f.date_emission,'%Y-%m') = ?"
		args = append(args, mois)
	} else if annee != "" {
		base += " AND YEAR(f.date_emission) = ?"
		args = append(args, annee)
	}
	base += " ORDER BY f.date_emission DESC"

	rows, err := database.DB.Query(base, args...)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	w.Header().Set("Content-Type", "text/csv; charset=utf-8")
	w.Header().Set("Content-Disposition", "attachment; filename=\"factures.csv\"")

	// BOM UTF-8 pour Excel
	fmt.Fprint(w, "\xEF\xBB\xBF")
	fmt.Fprintln(w, "Numero,Nom,Email,HT,TTC,Type,Service,Date,Stripe")

	for rows.Next() {
		var (
			numero, nom, email, typeFacture, service, dateEmission, stripeID string
			montantHT, montantTTC                                             float64
		)
		if rows.Scan(&numero, &nom, &email, &montantHT, &montantTTC,
			&typeFacture, &service, &dateEmission, &stripeID) == nil {
			line := strings.Join([]string{
				csvEscape(numero), csvEscape(nom), csvEscape(email),
				fmt.Sprintf("%.2f", montantHT),
				fmt.Sprintf("%.2f", montantTTC),
				csvEscape(typeFacture), csvEscape(service),
				dateEmission, csvEscape(stripeID),
			}, ",")
			fmt.Fprintln(w, line)
		}
	}
}

func csvEscape(s string) string {
	if strings.ContainsAny(s, ",\"\n") {
		return "\"" + strings.ReplaceAll(s, "\"", "\"\"") + "\""
	}
	return s
}

// ─── Export PDF ───────────────────────────────────────────────────────────────

type financeLigne struct {
	Mois     string
	Type     string
	TotalHT  float64
	TotalTTC float64
}

func ExportFacturesPDF(w http.ResponseWriter, r *http.Request) {
	annee := r.URL.Query().Get("annee")
	if annee == "" {
		annee = strconv.Itoa(time.Now().Year())
	}

	rows, err := database.DB.Query(`
		SELECT DATE_FORMAT(date_emission,'%Y-%m'), type_facture,
		       SUM(montant_ht), SUM(montant_ttc)
		FROM factures
		WHERE YEAR(date_emission) = ?
		GROUP BY DATE_FORMAT(date_emission,'%Y-%m'), type_facture
		ORDER BY 1, 2`, annee)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var lignes []financeLigne
	var grandTotalHT, grandTotalTTC float64
	for rows.Next() {
		var l financeLigne
		if rows.Scan(&l.Mois, &l.Type, &l.TotalHT, &l.TotalTTC) == nil {
			lignes = append(lignes, l)
			grandTotalHT += l.TotalHT
			grandTotalTTC += l.TotalTTC
		}
	}

	pdfBytes, genErr := buildFinancePDF(annee, lignes, grandTotalHT, grandTotalTTC)
	if genErr != nil {
		// Fallback texte lisible si PDF impossible
		w.Header().Set("Content-Type", "text/plain; charset=utf-8")
		w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="revenus_%s.txt"`, annee))
		fmt.Fprintf(w, "PILOTAGE FINANCIER - EXERCICE %s\n\n", annee)
		for _, l := range lignes {
			fmt.Fprintf(w, "%-10s %-20s HT: %10.2f EUR  TTC: %10.2f EUR\n",
				l.Mois, l.Type, l.TotalHT, l.TotalTTC)
		}
		fmt.Fprintf(w, "\nTOTAL HT: %.2f EUR  TTC: %.2f EUR\n", grandTotalHT, grandTotalTTC)
		return
	}

	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", fmt.Sprintf(`attachment; filename="revenus_%s.pdf"`, annee))
	w.Write(pdfBytes)
}

func buildFinancePDF(annee string, lignes []financeLigne, grandTotalHT, grandTotalTTC float64) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.AddPage()
	tr := pdf.UnicodeTranslatorFromDescriptor("")

	pdf.SetFont("Arial", "B", 16)
	pdf.CellFormat(190, 12, tr("Pilotage Financier — Exercice "+annee), "0", 1, "L", false, 0, "")
	pdf.SetFont("Arial", "I", 10)
	pdf.CellFormat(190, 8, tr("Généré le "+time.Now().Format("02/01/2006")), "0", 1, "L", false, 0, "")
	pdf.Ln(6)

	// En-têtes tableau
	pdf.SetFont("Arial", "B", 10)
	pdf.SetFillColor(216, 201, 155)
	pdf.CellFormat(45, 9, "Mois", "1", 0, "C", true, 0, "")
	pdf.CellFormat(65, 9, "Type", "1", 0, "C", true, 0, "")
	pdf.CellFormat(40, 9, "Total HT", "1", 0, "C", true, 0, "")
	pdf.CellFormat(40, 9, "Total TTC", "1", 1, "C", true, 0, "")

	pdf.SetFont("Arial", "", 9)
	pdf.SetFillColor(245, 240, 225)
	for _, l := range lignes {
		pdf.CellFormat(45, 8, l.Mois, "1", 0, "L", false, 0, "")
		pdf.CellFormat(65, 8, tr(l.Type), "1", 0, "L", false, 0, "")
		pdf.CellFormat(40, 8, fmt.Sprintf("%.2f EUR", l.TotalHT), "1", 0, "R", false, 0, "")
		pdf.CellFormat(40, 8, fmt.Sprintf("%.2f EUR", l.TotalTTC), "1", 1, "R", false, 0, "")
	}

	// Ligne total
	pdf.SetFont("Arial", "B", 10)
	pdf.SetFillColor(36, 79, 38)
	pdf.SetTextColor(245, 240, 225)
	pdf.CellFormat(110, 9, tr("TOTAL"), "1", 0, "R", true, 0, "")
	pdf.CellFormat(40, 9, fmt.Sprintf("%.2f EUR", grandTotalHT), "1", 0, "R", true, 0, "")
	pdf.CellFormat(40, 9, fmt.Sprintf("%.2f EUR", grandTotalTTC), "1", 1, "R", true, 0, "")

	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}

// ─── Dashboard financier temps-réel ──────────────────────────────────────────

func GetFinanceDashboard(w http.ResponseWriter, r *http.Request) {
	annee := r.URL.Query().Get("annee")
	if annee == "" {
		annee = strconv.Itoa(time.Now().Year())
	}
	moisCourant := time.Now().Format("2006-01")

	var totalHTMois, totalTTCMois, totalHTAnnee float64
	var nbTransactions, nbAbonnementsActifs int

	database.DB.QueryRow(`
		SELECT COALESCE(SUM(montant_ht),0), COALESCE(SUM(montant_ttc),0)
		FROM factures WHERE DATE_FORMAT(date_emission,'%Y-%m') = ?`, moisCourant).
		Scan(&totalHTMois, &totalTTCMois)
	database.DB.QueryRow(`
		SELECT COALESCE(SUM(montant_ht),0), COUNT(*)
		FROM factures WHERE YEAR(date_emission) = ?`, annee).
		Scan(&totalHTAnnee, &nbTransactions)
	database.DB.QueryRow(`SELECT COUNT(*) FROM souscriptions WHERE est_active = 1`).
		Scan(&nbAbonnementsActifs)

	jsonOK(w, map[string]interface{}{
		"total_ht_mois":         totalHTMois,
		"total_ttc_mois":        totalTTCMois,
		"total_ht_annee":        totalHTAnnee,
		"nb_transactions":       nbTransactions,
		"nb_abonnements_actifs": nbAbonnementsActifs,
	}, http.StatusOK)
}

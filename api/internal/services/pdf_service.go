// pdf_service.go : génération PDF — export RGPD et billet d'événement (QR code).

package services

import (
	"api/internal/models"
	"bytes"
	"fmt"
	"net/url"

	"github.com/boombuler/barcode/qr"
	"github.com/jung-kurt/gofpdf"
	"github.com/jung-kurt/gofpdf/contrib/barcode"
)

// TicketData regroupe les infos de billetterie hors event/user.
type TicketData struct {
	Ref            string  // numéro de billet (ex. UC-E12-00007)
	StatutPaiement string  // gratuit | paye
	PrixPaye       float64 // montant payé (0 si gratuit)
}

// ExportSection : une catégorie de données sous forme de tableau (export RGPD).
type ExportSection struct {
	Titre   string
	Headers []string
	Largeur []float64 // largeur relative de chaque colonne (somme = 1)
	Rows    [][]string
	Vide    string // message affiché si aucune donnée
}

// UserExportData : données personnelles pour l'export RGPD (accès + portabilité).
type UserExportData struct {
	User     models.Utilisateur
	Genere   string
	Profil   [][2]string
	Sections []ExportSection
}

// GenerateUserDataPDF produit le PDF d'export RGPD (profil + sections).
func GenerateUserDataPDF(data UserExportData) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.SetMargins(15, 15, 15)
	pdf.SetAutoPageBreak(true, 15)
	pdf.AddPage()
	tr := pdf.UnicodeTranslatorFromDescriptor("")

	coffee := []int{18, 3, 9}
	cream := []int{245, 240, 225}
	wheat := []int{216, 201, 155}
	cherry := []int{164, 36, 59}
	const W = 180.0

	// ── Bandeau de marque ──
	pdf.SetFillColor(coffee[0], coffee[1], coffee[2])
	pdf.Rect(0, 0, 210, 30, "F")
	pdf.SetY(8)
	pdf.SetTextColor(cream[0], cream[1], cream[2])
	pdf.SetFont("Arial", "B", 20)
	pdf.CellFormat(0, 9, tr("UPCYCLECONNECT"), "0", 1, "C", false, 0, "")
	pdf.SetFont("Arial", "", 10)
	pdf.CellFormat(0, 5, tr("Export de mes données personnelles (RGPD)"), "0", 1, "C", false, 0, "")
	pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
	pdf.SetY(38)
	pdf.SetFont("Arial", "I", 9)
	pdf.CellFormat(0, 5, tr("Généré le "+data.Genere), "0", 1, "L", false, 0, "")
	pdf.Ln(4)

	titreSection := func(t string) {
		pdf.SetFillColor(cherry[0], cherry[1], cherry[2])
		pdf.SetTextColor(cream[0], cream[1], cream[2])
		pdf.SetFont("Arial", "B", 12)
		pdf.CellFormat(W, 9, tr("  "+t), "0", 1, "L", true, 0, "")
		pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
		pdf.Ln(2)
	}

	// ── Profil ──
	titreSection("Profil")
	for _, kv := range data.Profil {
		val := kv[1]
		if val == "" {
			val = "—"
		}
		pdf.SetFont("Arial", "B", 10)
		pdf.CellFormat(55, 7, tr(kv[0]), "0", 0, "L", false, 0, "")
		pdf.SetFont("Arial", "", 10)
		pdf.MultiCell(W-55, 7, tr(val), "0", "L", false)
	}
	pdf.Ln(4)

	// ── Sections tabulaires ──
	for _, sec := range data.Sections {
		titreSection(sec.Titre)
		if len(sec.Rows) == 0 {
			pdf.SetFont("Arial", "I", 9)
			pdf.SetTextColor(120, 120, 120)
			pdf.CellFormat(W, 7, tr(sec.Vide), "0", 1, "L", false, 0, "")
			pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
			pdf.Ln(4)
			continue
		}
		// En-têtes
		pdf.SetFillColor(wheat[0], wheat[1], wheat[2])
		pdf.SetFont("Arial", "B", 9)
		for i, h := range sec.Headers {
			pdf.CellFormat(sec.Largeur[i]*W, 7, tr(h), "1", 0, "L", true, 0, "")
		}
		pdf.Ln(-1)
		// Lignes
		pdf.SetFont("Arial", "", 9)
		for _, row := range sec.Rows {
			for i, c := range row {
				if i >= len(sec.Largeur) {
					break
				}
				pdf.CellFormat(sec.Largeur[i]*W, 6, tr(tronque(c, int(sec.Largeur[i]*95))), "1", 0, "L", false, 0, "")
			}
			pdf.Ln(-1)
		}
		pdf.Ln(4)
	}

	pdf.Ln(2)
	pdf.SetFont("Arial", "I", 8)
	pdf.SetTextColor(90, 90, 90)
	pdf.MultiCell(W, 5, tr("Document généré conformément à votre droit d'accès et de portabilité (RGPD, art. 15 et 20). Pour toute demande de rectification ou de suppression, contactez l'équipe UpcycleConnect."), "0", "L", false)

	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}

// tronque coupe une chaîne trop longue pour une cellule.
func tronque(s string, max int) string {
	r := []rune(s)
	if len(r) <= max {
		return s
	}
	if max <= 1 {
		return string(r[:max])
	}
	return string(r[:max-1]) + "…"
}

// GenerateTicketPDF produit le billet d'un participant (infos, tarif, QR du n° de billet).
func GenerateTicketPDF(user models.Utilisateur, event models.Evenement, t TicketData) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A5", "")
	pdf.SetMargins(12, 12, 12)
	pdf.AddPage()
	tr := pdf.UnicodeTranslatorFromDescriptor("") // gère les accents (UTF-8 -> cp1252)

	coffee := []int{18, 3, 9}
	cream := []int{245, 240, 225}
	cherry := []int{164, 36, 59}

	// Bandeau marque
	pdf.SetFillColor(coffee[0], coffee[1], coffee[2])
	pdf.Rect(0, 0, 148, 26, "F")
	pdf.SetY(6)
	pdf.SetTextColor(cream[0], cream[1], cream[2])
	pdf.SetFont("Arial", "B", 20)
	pdf.CellFormat(0, 9, tr("UPCYCLECONNECT"), "0", 1, "C", false, 0, "")
	pdf.SetFont("Arial", "", 9)
	pdf.CellFormat(0, 5, tr("BILLET D'ENTRÉE"), "0", 1, "C", false, 0, "")

	pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
	pdf.SetY(32)

	// Numéro de billet
	pdf.SetFont("Arial", "", 9)
	pdf.CellFormat(0, 5, tr("N° de billet"), "0", 1, "C", false, 0, "")
	pdf.SetFont("Arial", "B", 15)
	pdf.SetTextColor(cherry[0], cherry[1], cherry[2])
	pdf.CellFormat(0, 8, tr(t.Ref), "0", 1, "C", false, 0, "")
	pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
	pdf.Ln(2)

	// Titre événement
	pdf.SetFont("Arial", "B", 15)
	pdf.MultiCell(0, 8, tr(event.Titre), "0", "C", false)
	pdf.Ln(3)

	// Séparateur
	y := pdf.GetY()
	pdf.SetDrawColor(coffee[0], coffee[1], coffee[2])
	pdf.Line(12, y, 136, y)
	pdf.Ln(5)

	row := func(label, value string) {
		pdf.SetFont("Arial", "", 11)
		pdf.CellFormat(42, 7, tr(label), "0", 0, "L", false, 0, "")
		pdf.SetFont("Arial", "B", 11)
		pdf.MultiCell(0, 7, tr(value), "0", "L", false)
	}

	row("Participant", fmt.Sprintf("%s %s", user.Prenom, user.Nom))
	row("Email", user.Email)
	if user.Telephone != nil && *user.Telephone != "" {
		row("Téléphone", *user.Telephone)
	}
	if user.AdresseComplete != nil && *user.AdresseComplete != "" {
		adr := *user.AdresseComplete
		if user.Ville != nil && *user.Ville != "" {
			adr += ", " + *user.Ville
		}
		row("Adresse", adr)
	} else if user.Ville != nil && *user.Ville != "" {
		row("Ville", *user.Ville)
	}

	pdf.Ln(2)
	row("Date", event.DateDebut.Format("02/01/2006"))
	row("Horaire", fmt.Sprintf("%s - %s", event.DateDebut.Format("15h04"), event.DateFin.Format("15h04")))
	lieu := "En ligne"
	if event.Lieu != nil && *event.Lieu != "" {
		lieu = *event.Lieu
	}
	row("Lieu", lieu)
	tarif := "Gratuit"
	if t.StatutPaiement == "paye" {
		tarif = fmt.Sprintf("Payé - %.2f EUR", t.PrixPaye)
	}
	row("Tarif", tarif)

	// Lien itinéraire (présentiel), cliquable dans le PDF.
	if event.Lieu != nil && *event.Lieu != "" {
		mapsURL := "https://www.google.com/maps/dir/?api=1&destination=" + url.QueryEscape(*event.Lieu)
		pdf.Ln(1)
		pdf.SetFont("Arial", "B", 10)
		pdf.SetTextColor(24, 96, 125) // teal
		pdf.CellFormat(0, 7, tr("Itinéraire Google Maps →"), "0", 1, "L", false, 0, mapsURL)
		pdf.SetTextColor(coffee[0], coffee[1], coffee[2])
	}

	// QR code (encode le n° de billet)
	pdf.Ln(5)
	if t.Ref != "" {
		key := barcode.RegisterQR(pdf, t.Ref, qr.M, qr.Unicode)
		qrSize := 34.0
		barcode.Barcode(pdf, key, (148-qrSize)/2, pdf.GetY(), qrSize, qrSize, false)
		pdf.SetY(pdf.GetY() + qrSize + 3)
	}
	pdf.SetFont("Arial", "I", 8)
	pdf.SetTextColor(90, 90, 90)
	pdf.CellFormat(0, 5, tr("Présentez ce QR code à l'entrée (imprimé ou sur smartphone)."), "0", 1, "C", false, 0, "")

	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}

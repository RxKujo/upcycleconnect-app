package services

import (
	"api/internal/models"
	"bytes"
	"fmt"

	"github.com/jung-kurt/gofpdf"
)

func GenerateUserDataPDF(user models.Utilisateur, genere string) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A4", "")
	pdf.AddPage()
	tr := pdf.UnicodeTranslatorFromDescriptor("")

	pdf.SetFont("Arial", "B", 18)
	pdf.CellFormat(190, 12, tr("Export de mes données — UpcycleConnect"), "0", 1, "L", false, 0, "")

	pdf.SetFont("Arial", "I", 10)
	pdf.CellFormat(190, 8, tr("Généré le "+genere), "0", 1, "L", false, 0, "")
	pdf.Ln(6)

	champ := func(label, valeur string) {
		if valeur == "" {
			valeur = "—"
		}
		pdf.SetFont("Arial", "B", 11)
		pdf.CellFormat(50, 9, tr(label), "0", 0, "L", false, 0, "")
		pdf.SetFont("Arial", "", 11)
		pdf.MultiCell(140, 9, tr(valeur), "0", "L", false)
	}

	champ("Identifiant", fmt.Sprintf("%d", user.IDUtilisateur))
	champ("Nom", user.Nom)
	champ("Prénom", user.Prenom)
	champ("Email", user.Email)
	if user.Telephone != nil {
		champ("Téléphone", *user.Telephone)
	} else {
		champ("Téléphone", "")
	}
	if user.Ville != nil {
		champ("Ville", *user.Ville)
	} else {
		champ("Ville", "")
	}
	champ("Rôle", user.Role)
	champ("Inscrit le", user.DateCreation.Format("02/01/2006"))

	pdf.Ln(8)
	pdf.SetFont("Arial", "I", 9)
	pdf.MultiCell(190, 6, tr("Document généré conformément à votre droit d'accès et de portabilité (RGPD). Pour toute demande de rectification ou de suppression, contactez l'équipe UpcycleConnect."), "0", "L", false)

	var buf bytes.Buffer
	if err := pdf.Output(&buf); err != nil {
		return nil, err
	}
	return buf.Bytes(), nil
}

func GenerateTicketPDF(user models.Utilisateur, event models.Evenement) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A5", "")
	pdf.AddPage()

	pdf.SetFont("Arial", "B", 20)
	pdf.CellFormat(130, 15, "BILLET D'ENTREE", "0", 1, "C", false, 0, "")
	pdf.Ln(5)

	pdf.SetFont("Arial", "B", 16)
	pdf.CellFormat(130, 10, event.Titre, "0", 1, "C", false, 0, "")
	pdf.Ln(5)

	pdf.SetFont("Arial", "", 12)
	pdf.CellFormat(40, 10, "Participant:", "0", 0, "L", false, 0, "")
	pdf.SetFont("Arial", "B", 12)
	pdf.CellFormat(90, 10, fmt.Sprintf("%s %s", user.Prenom, user.Nom), "0", 1, "R", false, 0, "")

	pdf.SetFont("Arial", "", 12)
	pdf.CellFormat(40, 10, "Date:", "0", 0, "L", false, 0, "")
	pdf.SetFont("Arial", "B", 12)
	pdf.CellFormat(90, 10, event.DateDebut.Format("02/01/2006 15:04"), "0", 1, "R", false, 0, "")

	var lieuStr string
	if event.Lieu != nil {
		lieuStr = *event.Lieu
	} else {
		lieuStr = "En ligne"
	}
	pdf.SetFont("Arial", "", 12)
	pdf.CellFormat(40, 10, "Lieu:", "0", 0, "L", false, 0, "")
	pdf.SetFont("Arial", "B", 12)
	
	pdf.MultiCell(90, 10, lieuStr, "0", "R", false)

	pdf.Ln(10)

	pdf.SetFont("Arial", "I", 10)
	pdf.CellFormat(130, 10, "Merci de presenter ce billet a l'entree (imprime ou sur smartphone).", "0", 1, "C", false, 0, "")

	var buf bytes.Buffer
	err := pdf.Output(&buf)
	if err != nil {
		return nil, err
	}

	return buf.Bytes(), nil
}

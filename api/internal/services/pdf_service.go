package services

import (
	"api/internal/models"
	"bytes"
	"fmt"

	"github.com/jung-kurt/gofpdf"
)

// GenerateTicketPDF crée un billet PDF en mémoire et retourne les octets.
func GenerateTicketPDF(user models.Utilisateur, event models.Evenement) ([]byte, error) {
	pdf := gofpdf.New("P", "mm", "A5", "")
	pdf.AddPage()

	// Titre Billet
	pdf.SetFont("Arial", "B", 20)
	pdf.CellFormat(130, 15, "BILLET D'ENTREE", "0", 1, "C", false, 0, "")
	pdf.Ln(5)

	// Nom de l'événement
	pdf.SetFont("Arial", "B", 16)
	pdf.CellFormat(130, 10, event.Titre, "0", 1, "C", false, 0, "")
	pdf.Ln(5)

	// Détails
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
	// Mutliline for lieu in case it's long
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

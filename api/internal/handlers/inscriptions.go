package handlers

// inscriptions.go — inscriptions aux événements : réservation (crédit du score +
// billet PDF par email) et téléchargement du billet.

import (
	"api/internal/middleware"
	"api/internal/models"
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
)

// InscrireEvenement — décrémente les places, crée inscription + créneau planning
// (transaction), crédite le score et envoie le billet PDF.
func InscrireEvenement(w http.ResponseWriter, r *http.Request, id string) {
	userId, _, ok := middleware.AuthRequired(w, r)
	if !ok {
		return
	}

	var event models.Evenement
	var lieu sql.NullString
	err := database.DB.QueryRow("SELECT id_evenement, titre, date_debut, date_fin, nb_places_dispo, prix, lieu FROM evenements WHERE id_evenement = ?", id).
		Scan(&event.IDEvenement, &event.Titre, &event.DateDebut, &event.DateFin, &event.NbPlacesDispo, &event.Prix, &lieu)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "événement non trouvé"})
		return
	}
	if lieu.Valid {
		event.Lieu = &lieu.String
	}

	if event.NbPlacesDispo <= 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "plus de places disponibles"})
		return
	}

	var exists bool
	database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM inscriptions_evenements WHERE id_evenement = ? AND id_utilisateur = ?)", event.IDEvenement, userId).Scan(&exists)
	if exists {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusConflict)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "déjà inscrit à cet événement"})
		return
	}

	var user models.Utilisateur
	err = database.DB.QueryRow("SELECT id_utilisateur, nom, prenom, email FROM utilisateurs WHERE id_utilisateur = ?", userId).
		Scan(&user.IDUtilisateur, &user.Nom, &user.Prenom, &user.Email)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	_, err = tx.Exec("UPDATE evenements SET nb_places_dispo = nb_places_dispo - 1 WHERE id_evenement = ? AND nb_places_dispo > 0", event.IDEvenement)
	if err != nil {
		return
	}

	statutPaiement := "gratuit"
	if event.Prix > 0 {
		statutPaiement = "paye"
	}
	resIns, err := tx.Exec("INSERT INTO inscriptions_evenements (id_utilisateur, id_evenement, statut_paiement, prix_paye) VALUES (?, ?, ?, ?)",
		userId, event.IDEvenement, statutPaiement, event.Prix)
	if err != nil {
		return
	}
	idInscription, _ := resIns.LastInsertId()

	_, err = tx.Exec("INSERT INTO planning_utilisateurs (id_utilisateur, titre_creneau, date_debut, date_fin, type_creneau, id_evenement) VALUES (?, ?, ?, ?, 'evenement', ?)",
		userId, event.Titre, event.DateDebut, event.DateFin, event.IDEvenement)
	if err != nil {
		return
	}

	if err = tx.Commit(); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	// Crédit de l'Upcycling Score.
	services.AwardScoreForEvenement(userId, event.IDEvenement)

	ref := fmt.Sprintf("UC-E%d-%05d", event.IDEvenement, idInscription)
	pdfBytes, err := services.GenerateTicketPDF(user, event, services.TicketData{
		Ref:            ref,
		StatutPaiement: statutPaiement,
		PrixPaye:       event.Prix,
	})
	if err == nil {
		subject := fmt.Sprintf("Confirmation d'inscription : %s", event.Titre)
		body := fmt.Sprintf("Félicitations %s,\n\nVotre inscription à l'événement \"%s\" est confirmée.\n\nVous trouverez votre billet en pièce jointe.", user.Prenom, event.Titre)
		services.SendEmailWithAttachment(user.Email, subject, body, "billet_evenement.pdf", pdfBytes)
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message": "inscription réussie",
		"id_evenement": event.IDEvenement,
	})
}

// GetTicketPDF — régénère le billet PDF d'un événement où l'utilisateur est inscrit.
func GetTicketPDF(w http.ResponseWriter, r *http.Request, id string) {
	userId, _, ok := middleware.AuthRequired(w, r)
	if !ok {
		return
	}

	var exists bool
	database.DB.QueryRow("SELECT EXISTS(SELECT 1 FROM inscriptions_evenements WHERE id_evenement = ? AND id_utilisateur = ?)", id, userId).Scan(&exists)
	if !exists {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "vous n'êtes pas inscrit à cet événement"})
		return
	}

	var event models.Evenement
	var lieu sql.NullString
	err := database.DB.QueryRow("SELECT id_evenement, titre, date_debut, date_fin, nb_places_dispo, prix, lieu FROM evenements WHERE id_evenement = ?", id).
		Scan(&event.IDEvenement, &event.Titre, &event.DateDebut, &event.DateFin, &event.NbPlacesDispo, &event.Prix, &lieu)
	if err != nil {
		w.WriteHeader(http.StatusNotFound)
		return
	}
	if lieu.Valid {
		event.Lieu = &lieu.String
	}

	var user models.Utilisateur
	var tel, ville, adresse sql.NullString
	err = database.DB.QueryRow("SELECT id_utilisateur, nom, prenom, email, telephone, ville, adresse_complete FROM utilisateurs WHERE id_utilisateur = ?", userId).
		Scan(&user.IDUtilisateur, &user.Nom, &user.Prenom, &user.Email, &tel, &ville, &adresse)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	if tel.Valid {
		user.Telephone = &tel.String
	}
	if ville.Valid {
		user.Ville = &ville.String
	}
	if adresse.Valid {
		user.AdresseComplete = &adresse.String
	}

	// Numéro de billet + tarif.
	var idInscription int
	var statutPaiement string
	var prixPaye sql.NullFloat64
	database.DB.QueryRow("SELECT id_inscription, statut_paiement, COALESCE(prix_paye, 0) FROM inscriptions_evenements WHERE id_evenement = ? AND id_utilisateur = ?", id, userId).
		Scan(&idInscription, &statutPaiement, &prixPaye)
	ref := fmt.Sprintf("UC-E%d-%05d", event.IDEvenement, idInscription)

	pdfBytes, err := services.GenerateTicketPDF(user, event, services.TicketData{
		Ref:            ref,
		StatutPaiement: statutPaiement,
		PrixPaye:       prixPaye.Float64,
	})
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/pdf")
	w.Header().Set("Content-Disposition", fmt.Sprintf("attachment; filename=\"billet-%s.pdf\"", ref))
	w.WriteHeader(http.StatusOK)
	w.Write(pdfBytes)
}

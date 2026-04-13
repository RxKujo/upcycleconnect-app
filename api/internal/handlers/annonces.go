package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"fmt"
	"net/http"
)

func GetAnnonces(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_annonce, id_particulier, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par FROM annonces ORDER BY date_creation DESC")
	if err != nil {
		fmt.Printf("GetAnnonces Query err: %v\n", err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var annonces []models.Annonce
	for rows.Next() {
		var a models.Annonce
		var prix sql.NullFloat64
		var motifRefus, motifRetrait sql.NullString
		var validePar sql.NullInt64

		if err := rows.Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar); err == nil {
			if prix.Valid {
				p := prix.Float64
				a.Prix = &p
			}
			if motifRefus.Valid {
				mr := motifRefus.String
				a.MotifRefus = &mr
			}
			if motifRetrait.Valid {
				mt := motifRetrait.String
				a.MotifRetrait = &mt
			}
			if validePar.Valid {
				v := int(validePar.Int64)
				a.ValidePar = &v
			}
			annonces = append(annonces, a)
		} else {
			fmt.Printf("GetAnnonces Scan err: %v\n", err)
		}
	}
	if annonces == nil {
		annonces = []models.Annonce{}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(annonces)
}

func GetAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	var a models.Annonce
	var prix sql.NullFloat64
	var motifRefus, motifRetrait sql.NullString
	var validePar sql.NullInt64

	err := database.DB.QueryRow("SELECT id_annonce, id_particulier, titre, description, type_annonce, prix, mode_remise, statut, motif_refus, motif_retrait, date_creation, valide_par FROM annonces WHERE id_annonce = ?", id).
		Scan(&a.IDAnnonce, &a.IDParticulier, &a.Titre, &a.Description, &a.TypeAnnonce, &prix, &a.ModeRemise, &a.Statut, &motifRefus, &motifRetrait, &a.DateCreation, &validePar)
	if err != nil {
		fmt.Printf("GetAnnonce Scan err: %v\n", err)
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "annonce non trouvée"})
		return
	}

	if prix.Valid {
		p := prix.Float64
		a.Prix = &p
	}
	if motifRefus.Valid {
		mr := motifRefus.String
		a.MotifRefus = &mr
	}
	if motifRetrait.Valid {
		mt := motifRetrait.String
		a.MotifRetrait = &mt
	}
	if validePar.Valid {
		v := int(validePar.Int64)
		a.ValidePar = &v
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(a)
}

func ValiderAnnonce(w http.ResponseWriter, r *http.Request, id string, adminId int) {
	tx, err := database.DB.Begin()
	if err != nil {
		http.Error(w, "DB error", http.StatusInternalServerError)
		return
	}

	// Récupérer le user et type de remise avant l'update
	var idParticulier int
	var titre, modeRemise string
	err = tx.QueryRow("SELECT id_particulier, titre, mode_remise FROM annonces WHERE id_annonce = ?", id).Scan(&idParticulier, &titre, &modeRemise)
	if err != nil {
		tx.Rollback()
		http.Error(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	_, err = tx.Exec("UPDATE annonces SET statut = 'validee', valide_par = ?, motif_refus = NULL WHERE id_annonce = ?", adminId, id)
	if err != nil {
		tx.Rollback()
		http.Error(w, "erreur maj", http.StatusInternalServerError)
		return
	}

	// Notification OneSignal simulée
	sujetNotif := "Votre annonce a été validée !"
	contenuNotif := fmt.Sprintf("Excellente nouvelle, votre annonce \"%s\" est maintenant en ligne.", titre)
	if modeRemise == "conteneur" {
		contenuNotif += " Un code-barre vous sera transmis pour le dépôt."
	}
	_, _ = tx.Exec("INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')",
		idParticulier, sujetNotif, contenuNotif)

	tx.Commit()

	requiresBarcode := modeRemise == "conteneur"

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":          "annonce validée",
		"requires_barcode": requiresBarcode,
	})
}

func RefuserAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	var req models.AnnonceValidationRequest
	_ = json.NewDecoder(r.Body).Decode(&req)

	tx, _ := database.DB.Begin()
	var idParticulier int
	var titre string
	err := tx.QueryRow("SELECT id_particulier, titre FROM annonces WHERE id_annonce = ?", id).Scan(&idParticulier, &titre)
	if err != nil {
		tx.Rollback()
		http.Error(w, "annonce introuvable", http.StatusNotFound)
		return
	}

	motif := "Non conforme aux règles de la plateforme."
	if req.MotifRefus != nil && *req.MotifRefus != "" {
		motif = *req.MotifRefus
	}

	_, err = tx.Exec("UPDATE annonces SET statut = 'refusee', motif_refus = ? WHERE id_annonce = ?", motif, id)
	if err != nil {
		tx.Rollback()
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	// Notification
	sujetNotif := "Votre annonce n'a pas été validée"
	contenuNotif := fmt.Sprintf("Votre annonce \"%s\" a été refusée pour le motif suivant : %s", titre, motif)
	_, _ = tx.Exec("INSERT INTO notifications (id_destinataire, type_notif, sujet, contenu, contexte) VALUES (?, 'push', ?, ?, 'annonce')",
		idParticulier, sujetNotif, contenuNotif)

	tx.Commit()

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce refusée"})
}

func AttenteAnnonce(w http.ResponseWriter, r *http.Request, id string) {
	_, err := database.DB.Exec("UPDATE annonces SET statut = 'en_attente', valide_par = NULL, motif_refus = NULL WHERE id_annonce = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce remise en attente"})
}

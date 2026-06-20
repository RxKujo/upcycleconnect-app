package workers

import (
	"api/internal/services"
	"api/pkg/database"
	"fmt"
	"log"
	"time"
)

func StartRappelWorker() {
	ticker := time.NewTicker(1 * time.Hour)
	go func() {
		for {
			select {
			case <-ticker.C:
				processRappels()
			}
		}
	}()
	log.Println("[WORKER] RappelWorker démarré (fréquence: 1h)")
}

func processRappels() {
	
	query := `
		SELECT id_evenement, titre, date_debut 
		FROM evenements 
		WHERE statut = 'valide' 
		  AND rappel_envoye = FALSE 
		  AND date_debut <= DATE_ADD(NOW(), INTERVAL 48 HOUR)
		  AND date_debut > NOW()
	`
	rows, err := database.DB.Query(query)
	if err != nil {
		log.Printf("[WORKER] Erreur query evenements: %v", err)
		return
	}
	defer rows.Close()

	for rows.Next() {
		var id int
		var titre string
		var dateDebut time.Time
		if err := rows.Scan(&id, &titre, &dateDebut); err != nil {
			continue
		}

		log.Printf("[WORKER] Envoi des rappels pour l'événement #%d: %s", id, titre)

		if err := envoyerRappelsEvenement(id, titre, dateDebut); err != nil {
			log.Printf("[WORKER] Erreur rappels pour #%d: %v", id, err)
			continue
		}

		_, err = database.DB.Exec("UPDATE evenements SET rappel_envoye = TRUE WHERE id_evenement = ?", id)
		if err != nil {
			log.Printf("[WORKER] Erreur update rappel_envoye pour #%d: %v", id, err)
		}
	}
}

func envoyerRappelsEvenement(evenementID int, titre string, dateDebut time.Time) error {
	rows, err := database.DB.Query(`
		SELECT u.email, u.prenom, COALESCE(u.onesignal_player_id,''), u.role
		FROM inscriptions_evenements i
		JOIN utilisateurs u ON i.id_utilisateur = u.id_utilisateur
		WHERE i.id_evenement = ?`, evenementID)
	if err != nil {
		return err
	}
	defer rows.Close()

	dateStr := dateDebut.Format("02/01/2006 à 15:04")
	for rows.Next() {
		var email, prenom, playerID, role string
		if err := rows.Scan(&email, &prenom, &playerID, &role); err != nil {
			continue
		}
		subject := fmt.Sprintf("Rappel : Votre événement \"%s\" approche !", titre)
		body := fmt.Sprintf("Bonjour %s,\n\nCeci est un rappel pour votre participation à l'événement \"%s\" qui aura lieu le %s.\n\nÀ très bientôt !\nL'équipe UpcycleConnect",
			prenom, titre, dateStr)
		services.SendSimpleEmail(email, subject, body) //nolint:errcheck

		if role == "professionnel" && playerID != "" {
			services.NotifierRappelEvenement(playerID, titre, dateStr) //nolint:errcheck
		}
	}
	return rows.Err()
}

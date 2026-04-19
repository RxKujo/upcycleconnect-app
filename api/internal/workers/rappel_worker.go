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

		userRows, err := database.DB.Query(`
			SELECT u.email, u.prenom 
			FROM inscriptions_evenements i
			JOIN utilisateurs u ON i.id_utilisateur = u.id_utilisateur
			WHERE i.id_evenement = ?`, id)
		if err != nil {
			log.Printf("[WORKER] Erreur query inscrits pour #%d: %v", id, err)
			continue
		}

		for userRows.Next() {
			var email, prenom string
			if err := userRows.Scan(&email, &prenom); err == nil {
				subject := fmt.Sprintf("Rappel : Votre événement \"%s\" approche !", titre)
				body := fmt.Sprintf("Bonjour %s,\n\nCeci est un rappel pour votre participation à l'événement \"%s\" qui aura lieu le %s.\n\nÀ très bientôt !\nL'équipe UpcycleConnect", 
					prenom, titre, dateDebut.Format("02/01/2006 à 15:04"))
				
				services.SendSimpleEmail(email, subject, body)
			}
		}
		userRows.Close()

		_, err = database.DB.Exec("UPDATE evenements SET rappel_envoye = TRUE WHERE id_evenement = ?", id)
		if err != nil {
			log.Printf("[WORKER] Erreur update rappel_envoye pour #%d: %v", id, err)
		}
	}
}

package workers

import (
	"api/internal/services"
	"api/pkg/database"
	"database/sql"
	"fmt"
	"log"
	"time"
)

func StartRappelWorker() {
	// Passage immédiat au démarrage, puis toutes les heures.
	go func() {
		processRappels()
		processConteneursExpires()
		ticker := time.NewTicker(1 * time.Hour)
		for range ticker.C {
			processRappels()
			processConteneursExpires()
		}
	}()
	log.Println("[WORKER] RappelWorker démarré (fréquence: 1h)")
}

// processConteneursExpires bascule en 'expiree' les commandes en_conteneur dont
// le délai de récupération (7 j) est dépassé, et ouvre un ticket support pour
// que l'objet non récupéré soit traité.
func processConteneursExpires() {
	rows, err := database.DB.Query(`
		SELECT c.id_commande, c.id_acheteur, c.id_conteneur, a.titre
		FROM commandes c
		JOIN annonces a ON a.id_annonce = c.id_annonce
		WHERE c.statut = 'en_conteneur'
		  AND c.date_limite_recuperation IS NOT NULL
		  AND c.date_limite_recuperation < NOW()`)
	if err != nil {
		log.Printf("[WORKER] Erreur query conteneurs expirés: %v", err)
		return
	}
	defer rows.Close()

	type expiree struct {
		commandeID, acheteurID, conteneurID int
		titre                               string
	}
	var lot []expiree
	for rows.Next() {
		var e expiree
		var conteneurID sql.NullInt64
		if err := rows.Scan(&e.commandeID, &e.acheteurID, &conteneurID, &e.titre); err != nil {
			continue
		}
		if conteneurID.Valid {
			e.conteneurID = int(conteneurID.Int64)
		}
		lot = append(lot, e)
	}

	for _, e := range lot {
		if _, err := database.DB.Exec(
			`UPDATE commandes SET statut = 'expiree' WHERE id_commande = ?`, e.commandeID); err != nil {
			log.Printf("[WORKER] Erreur bascule expiree commande #%d: %v", e.commandeID, err)
			continue
		}

		sujet := fmt.Sprintf("Objet non récupéré — commande #%d", e.commandeID)
		desc := fmt.Sprintf("Délai de récupération (7 jours) dépassé pour « %s ». Objet à traiter par le support.", e.titre)
		var conteneurArg interface{}
		if e.conteneurID > 0 {
			conteneurArg = e.conteneurID
		}
		if _, err := database.DB.Exec(`
			INSERT INTO tickets_incidents (id_signaleur, id_conteneur, sujet, description, statut)
			VALUES (?, ?, ?, ?, 'ouvert')`, e.acheteurID, conteneurArg, sujet, desc); err != nil {
			log.Printf("[WORKER] Erreur création ticket pour commande #%d: %v", e.commandeID, err)
		}
		log.Printf("[WORKER] Commande #%d expirée (objet: %s)", e.commandeID, e.titre)
	}
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

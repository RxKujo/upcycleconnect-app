package services

import (
	"fmt"
	"log"
)

// SendEmailWithAttachment simule l'envoi d'un email avec une pièce jointe (le billet PDF).
// Dans une version de production, on utiliserait net/smtp ou un service tierce.
func SendEmailWithAttachment(to string, subject string, body string, attachmentName string, attachmentContent []byte) error {
	// Simulation de l'envoi
	log.Printf("[EMAIL SERVICE] Vers: %s | Sujet: %s", to, subject)
	log.Printf("[EMAIL SERVICE] Corps: %s", body)
	log.Printf("[EMAIL SERVICE] PJ: %s (%d octets)", attachmentName, len(attachmentContent))
	
	// En production, on implémenterait ici la logique de mail multipart/mixed
	fmt.Printf("Email envoyé avec succès à %s\n", to)
	
	return nil
}

// SendSimpleEmail envoie un email texte simple.
func SendSimpleEmail(to string, subject string, body string) error {
	log.Printf("[EMAIL SERVICE] Vers: %s | Sujet: %s", to, subject)
	log.Printf("[EMAIL SERVICE] Corps: %s", body)
	
	fmt.Printf("Email simple envoyé avec succès à %s\n", to)
	
	return nil
}

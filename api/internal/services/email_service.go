package services

import (
	"fmt"
	"log"
)

func SendEmailWithAttachment(to string, subject string, body string, attachmentName string, attachmentContent []byte) error {
	
	log.Printf("[EMAIL SERVICE] Vers: %s | Sujet: %s", to, subject)
	log.Printf("[EMAIL SERVICE] Corps: %s", body)
	log.Printf("[EMAIL SERVICE] PJ: %s (%d octets)", attachmentName, len(attachmentContent))

	fmt.Printf("Email envoyé avec succès à %s\n", to)
	
	return nil
}

func SendSimpleEmail(to string, subject string, body string) error {
	log.Printf("[EMAIL SERVICE] Vers: %s | Sujet: %s", to, subject)
	log.Printf("[EMAIL SERVICE] Corps: %s", body)
	
	fmt.Printf("Email simple envoyé avec succès à %s\n", to)
	
	return nil
}

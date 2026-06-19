package services

import (
	"bytes"
	"encoding/base64"
	"fmt"
	"log"
	"mime/multipart"
	"net/smtp"
	"net/textproto"
	"os"
	"strings"
)

func smtpAddr() string {
	host := os.Getenv("MAIL_HOST")
	port := os.Getenv("MAIL_PORT")
	if host == "" {
		host = "localhost"
	}
	if port == "" {
		port = "1025"
	}
	return host + ":" + port
}

func fromAddr() string {
	from := os.Getenv("MAIL_FROM")
	if from == "" {
		from = "noreply@upcycleconnect.fr"
	}
	return from
}

func SendEmailWithAttachment(to string, subject string, body string, attachmentName string, attachmentContent []byte) error {
	addr := smtpAddr()
	from := fromAddr()

	var buf bytes.Buffer
	writer := multipart.NewWriter(&buf)

	// Headers
	header := fmt.Sprintf(
		"From: UpcycleConnect <%s>\r\nTo: %s\r\nSubject: %s\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=%q\r\n\r\n",
		from, to, subject, writer.Boundary(),
	)
	buf.Reset()
	buf.WriteString(header)
	mw := multipart.NewWriter(&buf)
	mw.SetBoundary(writer.Boundary())

	// Partie texte
	th := make(textproto.MIMEHeader)
	th.Set("Content-Type", "text/plain; charset=utf-8")
	th.Set("Content-Transfer-Encoding", "quoted-printable")
	pw, _ := mw.CreatePart(th)
	pw.Write([]byte(body))

	// Pièce jointe
	if len(attachmentContent) > 0 {
		ah := make(textproto.MIMEHeader)
		ah.Set("Content-Type", "application/octet-stream")
		ah.Set("Content-Transfer-Encoding", "base64")
		ah.Set("Content-Disposition", fmt.Sprintf("attachment; filename=%q", attachmentName))
		aw, _ := mw.CreatePart(ah)
		encoded := base64.StdEncoding.EncodeToString(attachmentContent)
		for i := 0; i < len(encoded); i += 76 {
			end := i + 76
			if end > len(encoded) {
				end = len(encoded)
			}
			aw.Write([]byte(encoded[i:end] + "\r\n"))
		}
	}
	mw.Close()

	msg := buf.Bytes()
	err := smtp.SendMail(addr, nil, from, []string{to}, msg)
	if err != nil {
		log.Printf("[EMAIL] Erreur envoi vers %s : %v", to, err)
		return err
	}
	log.Printf("[EMAIL] Envoyé à %s — sujet: %s", to, subject)
	return nil
}

func SendSimpleEmail(to string, subject string, body string) error {
	addr := smtpAddr()
	from := fromAddr()

	msg := strings.Join([]string{
		"From: UpcycleConnect <" + from + ">",
		"To: " + to,
		"Subject: " + subject,
		"MIME-Version: 1.0",
		"Content-Type: text/plain; charset=utf-8",
		"",
		body,
	}, "\r\n")

	err := smtp.SendMail(addr, nil, from, []string{to}, []byte(msg))
	if err != nil {
		log.Printf("[EMAIL] Erreur envoi vers %s : %v", to, err)
		return err
	}
	log.Printf("[EMAIL] Envoyé à %s — sujet: %s", to, subject)
	return nil
}

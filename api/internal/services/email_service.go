// email_service.go : envoi SMTP. Mode (simple/STARTTLS/TLS) et auth pilotés par
// les variables MAIL_* : bascule Mailpit (dev) → relais réel sans changer le code.

package services

import (
	"bytes"
	"crypto/tls"
	"encoding/base64"
	"fmt"
	"log"
	"mime/multipart"
	"net/smtp"
	"net/textproto"
	"os"
	"strings"
)

// ─── Configuration SMTP (variables d'environnement MAIL_*) ────────────────────

func mailHost() string {
	if h := os.Getenv("MAIL_HOST"); h != "" {
		return h
	}
	return "localhost"
}

func mailPort() string {
	if p := os.Getenv("MAIL_PORT"); p != "" {
		return p
	}
	return "1025"
}

func smtpAddr() string {
	return mailHost() + ":" + mailPort()
}

func fromAddr() string {
	if from := os.Getenv("MAIL_FROM"); from != "" {
		return from
	}
	return "noreply@upcycleconnect.fr"
}

// sendRaw envoie un message brut (headers + corps) via SMTP. 3 modes selon
// MAIL_ENCRYPTION :
//   - "" / "none"      : SMTP simple sans auth (Mailpit)
//   - "starttls"/"tls" : STARTTLS (port 587)
//   - "ssl"            : TLS implicite (SMTPS, port 465)
// Auth activée dès que MAIL_USERNAME est renseigné.
func sendRaw(from, to string, msg []byte) error {
	host := mailHost()
	addr := smtpAddr()
	user := os.Getenv("MAIL_USERNAME")
	pass := os.Getenv("MAIL_PASSWORD")
	enc := strings.ToLower(strings.TrimSpace(os.Getenv("MAIL_ENCRYPTION")))

	var auth smtp.Auth
	if user != "" {
		auth = smtp.PlainAuth("", user, pass, host)
	}

	var err error
	switch enc {
	case "ssl":
		err = sendImplicitTLS(addr, host, auth, from, to, msg)
	case "tls", "starttls":
		err = sendStartTLS(addr, host, auth, from, to, msg)
	default:
		// SMTP simple (sans chiffrement).
		err = smtp.SendMail(addr, auth, from, []string{to}, msg)
	}

	if err != nil {
		log.Printf("[EMAIL] Erreur envoi vers %s : %v", to, err)
		return err
	}
	log.Printf("[EMAIL] Envoyé à %s (mode=%q)", to, enc)
	return nil
}

// sendStartTLS ouvre une connexion SMTP puis passe en TLS via STARTTLS.
func sendStartTLS(addr, host string, auth smtp.Auth, from, to string, msg []byte) error {
	c, err := smtp.Dial(addr)
	if err != nil {
		return err
	}
	defer c.Close()
	if err = c.StartTLS(&tls.Config{ServerName: host}); err != nil {
		return err
	}
	return deliver(c, auth, from, to, msg)
}

// sendImplicitTLS ouvre directement une connexion TLS (SMTPS, port 465).
func sendImplicitTLS(addr, host string, auth smtp.Auth, from, to string, msg []byte) error {
	conn, err := tls.Dial("tcp", addr, &tls.Config{ServerName: host})
	if err != nil {
		return err
	}
	c, err := smtp.NewClient(conn, host)
	if err != nil {
		return err
	}
	defer c.Close()
	return deliver(c, auth, from, to, msg)
}

// deliver joue le dialogue SMTP (auth, MAIL FROM, RCPT TO, DATA) sur un client connecté.
func deliver(c *smtp.Client, auth smtp.Auth, from, to string, msg []byte) error {
	if auth != nil {
		if ok, _ := c.Extension("AUTH"); ok {
			if err := c.Auth(auth); err != nil {
				return err
			}
		}
	}
	if err := c.Mail(from); err != nil {
		return err
	}
	if err := c.Rcpt(to); err != nil {
		return err
	}
	wc, err := c.Data()
	if err != nil {
		return err
	}
	if _, err = wc.Write(msg); err != nil {
		return err
	}
	if err = wc.Close(); err != nil {
		return err
	}
	return c.Quit()
}

// ─── Envoi d'emails (API publique) ────────────────────────────────────────────

// SendEmailWithAttachment : email texte + pièce jointe base64 (ex. export PDF RGPD).
func SendEmailWithAttachment(to string, subject string, body string, attachmentName string, attachmentContent []byte) error {
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

	return sendRaw(from, to, buf.Bytes())
}

// SendSimpleEmail : email texte simple.
func SendSimpleEmail(to string, subject string, body string) error {
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

	return sendRaw(from, to, []byte(msg))
}

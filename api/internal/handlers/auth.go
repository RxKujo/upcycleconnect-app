package handlers

import (
	"api/internal/models"
	"api/internal/services"
	"api/pkg/database"
	"crypto/rand"
	"encoding/hex"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

func Register(w http.ResponseWriter, r *http.Request) {
	var req models.RegisterRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	// Normalisation
	req.Nom = strings.TrimSpace(req.Nom)
	req.Prenom = strings.TrimSpace(req.Prenom)
	req.Email = strings.ToLower(strings.TrimSpace(req.Email))
	req.Role = strings.TrimSpace(req.Role)

	// Validation des entrées
	if req.Nom == "" || req.Prenom == "" {
		jsonErr(w, "le nom et le prénom sont obligatoires", http.StatusBadRequest)
		return
	}
	if !isValidEmail(req.Email) {
		jsonErr(w, "adresse email invalide", http.StatusBadRequest)
		return
	}
	if len(req.MotDePasse) < 8 {
		jsonErr(w, "le mot de passe doit contenir au moins 8 caractères", http.StatusBadRequest)
		return
	}
	if req.Role != "particulier" && req.Role != "professionnel" {
		jsonErr(w, "rôle invalide", http.StatusBadRequest)
		return
	}
	if req.Role == "professionnel" && (req.NomEntreprise == nil || strings.TrimSpace(*req.NomEntreprise) == "") {
		jsonErr(w, "le nom de l'entreprise est obligatoire pour un compte professionnel", http.StatusBadRequest)
		return
	}

	// reCAPTCHA : les formulaires d'inscription (particulier et pro) envoient un
	// token à vérifier. On ne contrôle que si le secret est configuré (sinon désactivé).
	if secret := os.Getenv("RECAPTCHA_SECRET_KEY"); secret != "" {
		if !verifyRecaptcha(secret, req.CaptchaToken) {
			jsonErr(w, "échec de la vérification anti-robot, merci de recommencer", http.StatusBadRequest)
			return
		}
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), bcrypt.DefaultCost)
	if err != nil {
		jsonErr(w, "erreur de hashage", http.StatusInternalServerError)
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, code_postal, role, nom_entreprise, numero_siret)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`

	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.AdresseComplete, req.CodePostal, req.Role, req.NomEntreprise, req.NumeroSiret)
	if err != nil {
		if strings.Contains(err.Error(), "Duplicate entry") || strings.Contains(err.Error(), "UNIQUE constraint") {
			jsonErr(w, "un compte existe déjà avec cette adresse email", http.StatusConflict)
		} else {
			jsonErr(w, "impossible de créer l'utilisateur", http.StatusInternalServerError)
		}
		return
	}

	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "utilisateur créé avec succès", "id": id}, http.StatusCreated)
}

// verifyRecaptcha valide un token reCAPTCHA auprès de Google (siteverify).
// Retourne false en cas de token vide, d'erreur réseau ou de réponse négative.
func verifyRecaptcha(secret, token string) bool {
	if token == "" {
		return false
	}
	resp, err := http.PostForm("https://www.google.com/recaptcha/api/siteverify", url.Values{
		"secret":   {secret},
		"response": {token},
	})
	if err != nil {
		logError("verifyRecaptcha", "appel siteverify: %v", err)
		return false
	}
	defer resp.Body.Close()

	var out struct {
		Success bool `json:"success"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&out); err != nil {
		logError("verifyRecaptcha", "decode reponse: %v", err)
		return false
	}
	return out.Success
}

func Login(w http.ResponseWriter, r *http.Request) {
	var req models.LoginRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}

	req.Email = strings.ToLower(strings.TrimSpace(req.Email))
	if req.Email == "" || req.MotDePasse == "" {
		jsonErr(w, "email et mot de passe requis", http.StatusBadRequest)
		return
	}

	var user models.Utilisateur
	query := `SELECT id_utilisateur, mot_de_passe_hash, role, est_banni, date_fin_ban FROM utilisateurs WHERE email = ?`
	err := database.DB.QueryRow(query, req.Email).Scan(&user.IDUtilisateur, &user.MotDePasseHash, &user.Role, &user.EstBanni, &user.DateFinBan)
	if err != nil {
		jsonErr(w, "identifiants incorrects", http.StatusUnauthorized)
		return
	}

	if user.EstBanni {
		if user.DateFinBan == nil || user.DateFinBan.After(time.Now()) {
			jsonErr(w, "ce compte est actuellement banni", http.StatusForbidden)
			return
		}
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.MotDePasseHash), []byte(req.MotDePasse)); err != nil {
		jsonErr(w, "identifiants incorrects", http.StatusUnauthorized)
		return
	}

	secret := os.Getenv("JWT_SECRET")
	if secret == "" {
		jsonErr(w, "configuration serveur invalide", http.StatusInternalServerError)
		return
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
		"id":   user.IDUtilisateur,
		"role": user.Role,
		"exp":  time.Now().Add(time.Hour * 72).Unix(),
	})

	tokenString, err := token.SignedString([]byte(secret))
	if err != nil {
		jsonErr(w, "impossible de générer le token", http.StatusInternalServerError)
		return
	}

	jsonOK(w, models.LoginResponse{Token: tokenString}, http.StatusOK)
}

func ForgotPassword(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Email string `json:"email"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Email == "" {
		jsonErr(w, "email requis", http.StatusBadRequest)
		return
	}

	var userId int
	err := database.DB.QueryRow("SELECT id_utilisateur FROM utilisateurs WHERE email = ?", strings.ToLower(strings.TrimSpace(req.Email))).Scan(&userId)
	if err != nil {
		// Ne pas révéler si l'email existe
		jsonOK(w, map[string]string{"message": "si un compte existe, un email de réinitialisation sera envoyé"}, http.StatusOK)
		return
	}

	email := strings.ToLower(strings.TrimSpace(req.Email))

	// Invalider les anciens tokens (schéma Laravel : email, token, created_at)
	database.DB.Exec("DELETE FROM password_reset_tokens WHERE email = ?", email)

	// Token aléatoire cryptographique (32 octets → 64 caractères hex), non prédictible.
	rawToken := make([]byte, 32)
	if _, err := rand.Read(rawToken); err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	token := hex.EncodeToString(rawToken)

	_, err = database.DB.Exec(
		"INSERT INTO password_reset_tokens (email, token, created_at) VALUES (?, ?, NOW())",
		email, token,
	)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}

	appURL := os.Getenv("APP_URL")
	if appURL == "" {
		appURL = "http://localhost:8000"
	}
	resetURL := fmt.Sprintf("%s/reset-password?token=%s", appURL, token)
	emailBody := fmt.Sprintf("Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe UpcycleConnect.\n\nCliquez sur ce lien (valable 1 heure) :\n%s\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\nL'équipe UpcycleConnect", resetURL)
	services.SendSimpleEmail(req.Email, "Réinitialisation de votre mot de passe", emailBody)

	jsonOK(w, map[string]string{"message": "si un compte existe, un email de réinitialisation sera envoyé"}, http.StatusOK)
}

func ResetPassword(w http.ResponseWriter, r *http.Request) {
	var req struct {
		Token       string `json:"token"`
		NewPassword string `json:"new_password"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil || req.Token == "" || len(req.NewPassword) < 8 {
		jsonErr(w, "token et mot de passe (min 8 caractères) requis", http.StatusBadRequest)
		return
	}

	// Schéma Laravel : email, token, created_at — expire après 1h
	var emailAddr string
	var createdAt time.Time
	err := database.DB.QueryRow(
		"SELECT email, created_at FROM password_reset_tokens WHERE token = ?",
		req.Token,
	).Scan(&emailAddr, &createdAt)
	if err != nil || time.Now().After(createdAt.Add(time.Hour)) {
		jsonErr(w, "token invalide ou expiré", http.StatusBadRequest)
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.NewPassword), bcrypt.DefaultCost)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}

	database.DB.Exec("UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE email = ?", string(hash), emailAddr)
	database.DB.Exec("DELETE FROM password_reset_tokens WHERE token = ?", req.Token)

	jsonOK(w, map[string]string{"message": "mot de passe mis à jour"}, http.StatusOK)
}

// ChangePassword permet à un utilisateur connecté de modifier son mot de passe
// en fournissant son mot de passe actuel (vérifié) et le nouveau.
func ChangePassword(w http.ResponseWriter, r *http.Request, userId int) {
	var req struct {
		AncienMotDePasse  string `json:"ancien_mot_de_passe"`
		NouveauMotDePasse string `json:"nouveau_mot_de_passe"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		jsonErr(w, "données invalides", http.StatusBadRequest)
		return
	}
	if req.AncienMotDePasse == "" || len(req.NouveauMotDePasse) < 8 {
		jsonErr(w, "mot de passe actuel requis et nouveau mot de passe (min 8 caractères)", http.StatusBadRequest)
		return
	}
	if req.AncienMotDePasse == req.NouveauMotDePasse {
		jsonErr(w, "le nouveau mot de passe doit être différent de l'ancien", http.StatusBadRequest)
		return
	}

	var hashActuel string
	if err := database.DB.QueryRow("SELECT mot_de_passe_hash FROM utilisateurs WHERE id_utilisateur = ?", userId).Scan(&hashActuel); err != nil {
		jsonErr(w, "utilisateur introuvable", http.StatusNotFound)
		return
	}
	if err := bcrypt.CompareHashAndPassword([]byte(hashActuel), []byte(req.AncienMotDePasse)); err != nil {
		jsonErr(w, "mot de passe actuel incorrect", http.StatusBadRequest)
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.NouveauMotDePasse), bcrypt.DefaultCost)
	if err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}
	if _, err := database.DB.Exec("UPDATE utilisateurs SET mot_de_passe_hash = ? WHERE id_utilisateur = ?", string(hash), userId); err != nil {
		jsonErr(w, "erreur serveur", http.StatusInternalServerError)
		return
	}

	jsonOK(w, map[string]string{"message": "mot de passe modifié"}, http.StatusOK)
}

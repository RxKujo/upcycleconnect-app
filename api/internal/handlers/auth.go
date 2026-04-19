package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"net/url"
	"os"
	"regexp"
	"strings"
	"time"
	"unicode"

	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

func Register(w http.ResponseWriter, r *http.Request) {
	var req models.RegisterRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), bcrypt.DefaultCost)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur de hashage"})
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, role, nom_entreprise, numero_siret) 
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`
	
	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.Role, req.NomEntreprise, req.NumeroSiret)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer l'utilisateur"})
		return
	}

	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "utilisateur créé avec succès", "id": id})
}

func Login(w http.ResponseWriter, r *http.Request) {
	var req models.LoginRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	var user models.Utilisateur
	query := `SELECT id_utilisateur, mot_de_passe_hash, role, est_banni, date_fin_ban FROM utilisateurs WHERE email = ?`
	err = database.DB.QueryRow(query, req.Email).Scan(&user.IDUtilisateur, &user.MotDePasseHash, &user.Role, &user.EstBanni, &user.DateFinBan)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "identifiants incorrects"})
		return
	}

	if user.EstBanni {
		if user.DateFinBan == nil || user.DateFinBan.After(time.Now()) {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusForbidden)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "ce compte est actuellement banni"})
			return
		}
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.MotDePasseHash), []byte(req.MotDePasse)); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusUnauthorized)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "identifiants incorrects"})
		return
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
		"id":   user.IDUtilisateur,
		"role": user.Role,
		"exp":  time.Now().Add(time.Hour * 72).Unix(),
	})

	tokenString, err := token.SignedString([]byte(os.Getenv("JWT_SECRET")))
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de générer le token"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(models.LoginResponse{Token: tokenString})
}

func isValidEmail(email string) bool {
	emailRegex := regexp.MustCompile(`^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$`)
	return emailRegex.MatchString(email)
}

func isValidSiretFormat(siret string) bool {
	if len(siret) != 14 {
		return false
	}
	for _, c := range siret {
		if !unicode.IsDigit(c) {
			return false
		}
	}
	return true
}

func verifySiretAPI(siret string) (bool, error) {
	apiURL := fmt.Sprintf("https://recherche-entreprises.api.gouv.fr/search?q=%s&mtm_campaign=upcycleconnect", siret)
	client := &http.Client{Timeout: 10 * time.Second}
	resp, err := client.Get(apiURL)
	if err != nil {
		return false, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return false, fmt.Errorf("API SIRENE: statut %d", resp.StatusCode)
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return false, err
	}

	var result struct {
		TotalResults int `json:"total_results"`
	}
	if err := json.Unmarshal(body, &result); err != nil {
		return false, err
	}

	return result.TotalResults > 0, nil
}

func verifyRecaptcha(token string) (bool, error) {
	secret := os.Getenv("RECAPTCHA_SECRET_KEY")
	if secret == "" {
		
		return true, nil
	}

	resp, err := http.PostForm("https://www.google.com/recaptcha/api/siteverify", url.Values{
		"secret":   {secret},
		"response": {token},
	})
	if err != nil {
		return false, err
	}
	defer resp.Body.Close()

	var result struct {
		Success bool `json:"success"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&result); err != nil {
		return false, err
	}

	return result.Success, nil
}

func RegisterProfessionnel(w http.ResponseWriter, r *http.Request) {
	var req models.RegisterProfessionnelRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	if req.Nom == "" || req.Prenom == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "nom et prénom sont obligatoires"})
		return
	}

	if req.Email == "" || !isValidEmail(req.Email) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "format email invalide"})
		return
	}

	if req.MotDePasse == "" || len(req.MotDePasse) < 8 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "mot de passe doit contenir au minimum 8 caractères"})
		return
	}

	if req.Ville == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ville est obligatoire"})
		return
	}

	if req.NomEntreprise == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "nom de l'entreprise est obligatoire"})
		return
	}

	req.NumeroSiret = strings.ReplaceAll(req.NumeroSiret, " ", "")

	if !isValidSiretFormat(req.NumeroSiret) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "numéro SIRET invalide (14 chiffres requis)"})
		return
	}

	if req.CaptchaToken == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "veuillez valider le captcha"})
		return
	}

	captchaOk, err := verifyRecaptcha(req.CaptchaToken)
	if err != nil || !captchaOk {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "échec de vérification captcha"})
		return
	}

	var emailCount int
	err = database.DB.QueryRow(`SELECT COUNT(*) FROM utilisateurs WHERE email = ?`, req.Email).Scan(&emailCount)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	if emailCount > 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "email déjà utilisé"})
		return
	}

	var siretCount int
	err = database.DB.QueryRow(`SELECT COUNT(*) FROM utilisateurs WHERE numero_siret = ?`, req.NumeroSiret).Scan(&siretCount)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	if siretCount > 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ce numéro SIRET est déjà enregistré"})
		return
	}

	siretVerifie := false
	siretExists, err := verifySiretAPI(req.NumeroSiret)
	if err == nil && siretExists {
		siretVerifie = true
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), 10)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, nom_entreprise, numero_siret, siret_verifie, role, est_banni, date_creation)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`

	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.AdresseComplete, req.NomEntreprise, req.NumeroSiret, siretVerifie, "professionnel", false)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	idUtilisateur, err := res.LastInsertId()
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
		"id":   idUtilisateur,
		"role": "professionnel",
		"exp":  time.Now().Add(time.Hour * 72).Unix(),
	})

	tokenString, err := token.SignedString([]byte(os.Getenv("JWT_SECRET")))
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(models.RegisterProfessionnelResponse{
		Message:       "compte professionnel créé avec succès",
		IDUtilisateur: idUtilisateur,
		Token:         tokenString,
	})
}

func RegisterParticulier(w http.ResponseWriter, r *http.Request) {
	var req models.RegisterParticulierRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	if req.Nom == "" || req.Prenom == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "nom et prenom sont obligatoires"})
		return
	}

	if req.Email == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "email est obligatoire"})
		return
	}

	if !isValidEmail(req.Email) {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "format email invalide"})
		return
	}

	if req.MotDePasse == "" || len(req.MotDePasse) < 8 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "mot de passe doit contenir au minimum 8 caractères"})
		return
	}

	if req.Ville == "" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ville est obligatoire"})
		return
	}

	var emailCount int
	checkEmailQuery := `SELECT COUNT(*) FROM utilisateurs WHERE email = ?`
	err = database.DB.QueryRow(checkEmailQuery, req.Email).Scan(&emailCount)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	if emailCount > 0 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "email déjà utilisé"})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), 10)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, role, est_banni, date_creation)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())`

	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.AdresseComplete, "particulier", false)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	idUtilisateur, err := res.LastInsertId()
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
		"id":   idUtilisateur,
		"role": "particulier",
		"exp":  time.Now().Add(time.Hour * 72).Unix(),
	})

	tokenString, err := token.SignedString([]byte(os.Getenv("JWT_SECRET")))
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(models.RegisterParticulierResponse{
		Message:       "compte créé avec succès",
		IDUtilisateur: idUtilisateur,
		Token:         tokenString,
	})
}

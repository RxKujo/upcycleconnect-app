package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
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

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), bcrypt.DefaultCost)
	if err != nil {
		jsonErr(w, "erreur de hashage", http.StatusInternalServerError)
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, code_postal, role, nom_entreprise, numero_siret)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`

	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.AdresseComplete, req.CodePostal, req.Role, req.NomEntreprise, req.NumeroSiret)
	if err != nil {
		jsonErr(w, "impossible de créer l'utilisateur", http.StatusInternalServerError)
		return
	}

	id, _ := res.LastInsertId()
	jsonOK(w, map[string]interface{}{"message": "utilisateur créé avec succès", "id": id}, http.StatusCreated)
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

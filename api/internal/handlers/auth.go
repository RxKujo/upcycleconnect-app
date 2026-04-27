package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"os"
	"time"

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

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, code_postal, role, nom_entreprise, numero_siret)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`

	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.AdresseComplete, req.CodePostal, req.Role, req.NomEntreprise, req.NumeroSiret)
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

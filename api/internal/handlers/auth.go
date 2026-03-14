package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"database/sql"
	"net/http"
	"os"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
)

func Register(c *gin.Context) {
	var req models.RegisterRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides", "details": err.Error()})
		return
	}

	hash, err := bcrypt.GenerateFromPassword([]byte(req.MotDePasse), bcrypt.DefaultCost)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur de hashage"})
		return
	}

	query := `INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, role, nom_entreprise, numero_siret) 
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`
	
	res, err := database.DB.Exec(query, req.Nom, req.Prenom, req.Email, string(hash), req.Telephone, req.Ville, req.Role, req.NomEntreprise, req.NumeroSiret)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de créer l'utilisateur"})
		return
	}

	id, _ := res.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{"message": "utilisateur créé avec succès", "id": id})
}

func Login(c *gin.Context) {
	var req models.LoginRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides", "details": err.Error()})
		return
	}

	var user models.Utilisateur
	query := `SELECT id_utilisateur, mot_de_passe_hash, role, est_banni, date_fin_ban FROM utilisateurs WHERE email = ?`
	err := database.DB.QueryRow(query, req.Email).Scan(&user.IDUtilisateur, &user.MotDePasseHash, &user.Role, &user.EstBanni, &user.DateFinBan)
	if err != nil {
		if err == sql.ErrNoRows {
			c.JSON(http.StatusUnauthorized, gin.H{"erreur": "identifiants incorrects"})
		} else {
			c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur base de données"})
		}
		return
	}

	if user.EstBanni {
		if user.DateFinBan == nil || user.DateFinBan.After(time.Now()) {
			c.JSON(http.StatusForbidden, gin.H{"erreur": "ce compte est actuellement banni"})
			return
		}
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.MotDePasseHash), []byte(req.MotDePasse)); err != nil {
		c.JSON(http.StatusUnauthorized, gin.H{"erreur": "identifiants incorrects"})
		return
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, jwt.MapClaims{
		"id":   user.IDUtilisateur,
		"role": user.Role,
		"exp":  time.Now().Add(time.Hour * 72).Unix(),
	})

	tokenString, err := token.SignedString([]byte(os.Getenv("JWT_SECRET")))
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de générer le token"})
		return
	}

	c.JSON(http.StatusOK, models.LoginResponse{Token: tokenString})
}

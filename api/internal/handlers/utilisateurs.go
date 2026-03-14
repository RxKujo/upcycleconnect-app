package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"net/http"

	"github.com/gin-gonic/gin"
)

func GetMe(c *gin.Context) {
	id, _ := c.Get("utilisateur_id")
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"erreur": "utilisateur non trouvé"})
		return
	}
	c.JSON(http.StatusOK, u)
}

func UpdateMe(c *gin.Context) {
	id, _ := c.Get("utilisateur_id")
	var req struct {
		Telephone *string `json:"telephone"`
		Ville     *string `json:"ville"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET telephone = COALESCE(?, telephone), ville = COALESCE(?, ville) WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, req.Telephone, req.Ville, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur lors de la mise à jour"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "profil mis à jour avec succès"})
}

func GetAllUtilisateurs(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id_utilisateur, nom, prenom, email, role, est_banni FROM utilisateurs")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var utilisateurs []map[string]interface{}
	for rows.Next() {
		var id int
		var nom, prenom, email, role string
		var estBanni bool
		if err := rows.Scan(&id, &nom, &prenom, &email, &role, &estBanni); err == nil {
			utilisateurs = append(utilisateurs, gin.H{
				"id_utilisateur": id, "nom": nom, "prenom": prenom, "email": email, "role": role, "est_banni": estBanni,
			})
		}
	}
	c.JSON(http.StatusOK, utilisateurs)
}

func GetUtilisateur(c *gin.Context) {
	id := c.Param("id")
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"erreur": "utilisateur non trouvé"})
		return
	}
	c.JSON(http.StatusOK, u)
}

func BanUtilisateur(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		DateFinBan string `json:"date_fin_ban"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET est_banni = true, date_fin_ban = ? WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, req.DateFinBan, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de bannir l'utilisateur"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "utilisateur banni"})
}

func UnbanUtilisateur(c *gin.Context) {
	id := c.Param("id")
	query := `UPDATE utilisateurs SET est_banni = false, date_fin_ban = NULL WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de débannir l'utilisateur"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "utilisateur débanni"})
}

package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"net/http"

	"github.com/gin-gonic/gin"
)

func GetPrestations(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id_prestation, id_categorie, titre, description, prix, statut, date_creation FROM prestations")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var prestations []models.Prestation
	for rows.Next() {
		var p models.Prestation
		if err := rows.Scan(&p.IDPrestation, &p.IDCategorie, &p.Titre, &p.Description, &p.Prix, &p.Statut, &p.DateCreation); err == nil {
			prestations = append(prestations, p)
		}
	}
	if prestations == nil {
		prestations = []models.Prestation{}
	}
	c.JSON(http.StatusOK, prestations)
}

func GetPrestation(c *gin.Context) {
	id := c.Param("id")
	var p models.Prestation
	err := database.DB.QueryRow("SELECT id_prestation, id_categorie, titre, description, prix, statut, date_creation FROM prestations WHERE id_prestation = ?", id).
		Scan(&p.IDPrestation, &p.IDCategorie, &p.Titre, &p.Description, &p.Prix, &p.Statut, &p.DateCreation)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"erreur": "prestation non trouvée"})
		return
	}
	c.JSON(http.StatusOK, p)
}

func CreatePrestation(c *gin.Context) {
	var req models.CreatePrestationRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	res, err := database.DB.Exec("INSERT INTO prestations (id_categorie, titre, description, prix, statut) VALUES (?, ?, ?, ?, 'validee')",
		req.IDCategorie, req.Titre, req.Description, req.Prix)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de créer la prestation"})
		return
	}
	id, _ := res.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{"message": "prestation créée avec succès", "id": id})
}

func ValiderPrestation(c *gin.Context) {
	id := c.Param("id")
	_, err := database.DB.Exec("UPDATE prestations SET statut = 'validee' WHERE id_prestation = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur lors de la validation"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "prestation validée"})
}

func RefuserPrestation(c *gin.Context) {
	id := c.Param("id")
	_, err := database.DB.Exec("UPDATE prestations SET statut = 'refusee' WHERE id_prestation = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur lors du refus"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "prestation refusée"})
}

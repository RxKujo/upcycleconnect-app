package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"net/http"

	"github.com/gin-gonic/gin"
)

func GetCategories(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id_categorie, nom, description, date_creation FROM categories_prestations")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var categories []models.CategoriePrestation
	for rows.Next() {
		var cat models.CategoriePrestation
		if err := rows.Scan(&cat.IDCategorie, &cat.Nom, &cat.Description, &cat.DateCreation); err == nil {
			categories = append(categories, cat)
		}
	}
	
	if categories == nil {
		categories = []models.CategoriePrestation{}
	}
	c.JSON(http.StatusOK, categories)
}

func CreateCategorie(c *gin.Context) {
	var req struct {
		Nom         string `json:"nom" binding:"required"`
		Description string `json:"description" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	res, err := database.DB.Exec("INSERT INTO categories_prestations (nom, description) VALUES (?, ?)", req.Nom, req.Description)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de créer la catégorie"})
		return
	}
	id, _ := res.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{"message": "catégorie créée avec succès", "id": id})
}

func UpdateCategorie(c *gin.Context) {
	id := c.Param("id")
	var req struct {
		Nom         string `json:"nom" binding:"required"`
		Description string `json:"description" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	_, err := database.DB.Exec("UPDATE categories_prestations SET nom = ?, description = ? WHERE id_categorie = ?", req.Nom, req.Description, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de modifier la catégorie"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "catégorie modifiée avec succès"})
}

func DeleteCategorie(c *gin.Context) {
	id := c.Param("id")
	_, err := database.DB.Exec("DELETE FROM categories_prestations WHERE id_categorie = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de supprimer la catégorie (peut-être des prestations associées)"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "catégorie supprimée avec succès"})
}

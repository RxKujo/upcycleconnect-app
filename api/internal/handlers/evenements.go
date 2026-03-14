package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"net/http"

	"github.com/gin-gonic/gin"
)

func GetEvenements(c *gin.Context) {
	rows, err := database.DB.Query("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements")
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var evenements []models.Evenement
	for rows.Next() {
		var e models.Evenement
		if err := rows.Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation); err == nil {
			evenements = append(evenements, e)
		}
	}
	if evenements == nil {
		evenements = []models.Evenement{}
	}
	c.JSON(http.StatusOK, evenements)
}

func GetEvenement(c *gin.Context) {
	id := c.Param("id")
	var e models.Evenement
	err := database.DB.QueryRow("SELECT id_evenement, id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM evenements WHERE id_evenement = ?", id).
		Scan(&e.IDEvenement, &e.IDCreateur, &e.Titre, &e.Description, &e.TypeEvenement, &e.Format, &e.Lieu, &e.DateDebut, &e.DateFin, &e.NbPlacesTotal, &e.NbPlacesDispo, &e.Prix, &e.Statut, &e.ValidePar, &e.DateCreation)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"erreur": "événement non trouvé"})
		return
	}
	c.JSON(http.StatusOK, e)
}

func CreateEvenement(c *gin.Context) {
	var req models.CreateEvenementRequest
	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"erreur": "données invalides"})
		return
	}

	adminId, _ := c.Get("utilisateur_id")

	query := `INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par)
	          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'valide', ?)`
	res, err := database.DB.Exec(query, req.IDCreateur, req.Titre, req.Description, req.TypeEvenement, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix, adminId)
	
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "impossible de créer l'événement"})
		return
	}
	id, _ := res.LastInsertId()
	c.JSON(http.StatusCreated, gin.H{"message": "événement créé", "id": id})
}

func ValiderEvenement(c *gin.Context) {
	id := c.Param("id")
	adminId, _ := c.Get("utilisateur_id")
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'valide', valide_par = ? WHERE id_evenement = ?", adminId, id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur lors de la validation"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "événement validé"})
}

func RefuserEvenement(c *gin.Context) {
	id := c.Param("id")
	_, err := database.DB.Exec("UPDATE evenements SET statut = 'refuse' WHERE id_evenement = ?", id)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"erreur": "erreur lors du refus"})
		return
	}
	c.JSON(http.StatusOK, gin.H{"message": "événement refusé"})
}

package routes

import (
	"api/internal/handlers"
	"api/internal/middleware"

	"github.com/gin-gonic/gin"
)

func SetupRouter() *gin.Engine {
	r := gin.Default()
	r.Use(middleware.CORS())

	v1 := r.Group("/api/v1")
	{
		auth := v1.Group("/auth")
		{
			auth.POST("/register", handlers.Register)
			auth.POST("/login", handlers.Login)
		}

		authRequired := v1.Group("/")
		authRequired.Use(middleware.AuthRequired())
		{
			utilisateurs := authRequired.Group("/utilisateurs")
			{
				utilisateurs.GET("/me", handlers.GetMe)
				utilisateurs.PUT("/me", handlers.UpdateMe)
			}

			admin := authRequired.Group("/admin")
			admin.Use(middleware.AdminRequired())
			{
				admin.GET("/utilisateurs", handlers.GetAllUtilisateurs)
				admin.GET("/utilisateurs/:id", handlers.GetUtilisateur)
				admin.PUT("/utilisateurs/:id/ban", handlers.BanUtilisateur)
				admin.PUT("/utilisateurs/:id/unban", handlers.UnbanUtilisateur)

				admin.GET("/categories", handlers.GetCategories)
				admin.POST("/categories", handlers.CreateCategorie)
				admin.PUT("/categories/:id", handlers.UpdateCategorie)
				admin.DELETE("/categories/:id", handlers.DeleteCategorie)

				admin.GET("/prestations", handlers.GetPrestations)
				admin.GET("/prestations/:id", handlers.GetPrestation)
				admin.POST("/prestations", handlers.CreatePrestation)
				admin.PUT("/prestations/:id/valider", handlers.ValiderPrestation)
				admin.PUT("/prestations/:id/refuser", handlers.RefuserPrestation)

				admin.GET("/evenements", handlers.GetEvenements)
				admin.GET("/evenements/:id", handlers.GetEvenement)
				admin.POST("/evenements", handlers.CreateEvenement)
				admin.PUT("/evenements/:id/valider", handlers.ValiderEvenement)
				admin.PUT("/evenements/:id/refuser", handlers.RefuserEvenement)
			}
		}
	}

	return r
}

package main

import (
	"api/internal/routes"
	"api/pkg/database"
	"log"
	"os"

	"github.com/joho/godotenv"
)

func main() {
	err := godotenv.Load()
	if err != nil {
		log.Println("Aucun fichier .env trouvé, utilisation des variables d'environnement existantes")
	}

	if err := database.InitDB(); err != nil {
		log.Fatalf("Échec de la connexion à la base de données: %v", err)
	}
	defer database.DB.Close()

	router := routes.SetupRouter()

	port := os.Getenv("API_PORT")
	if port == "" {
		port = "8080"
	}
	log.Printf("Serveur en écoute sur le port %s", port)
	
	if err := router.Run(":" + port); err != nil {
		log.Fatalf("Erreur au démarrage du serveur: %v", err)
	}
}

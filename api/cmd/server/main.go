package main

import (
	"api/internal/router"
	"api/pkg/database"
	"log"
	"net/http"
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

	r := router.New()

	port := os.Getenv("API_PORT")
	if port == "" {
		port = "8888"
	}
	log.Printf("Serveur en écoute sur le port %s", port)

	if err := http.ListenAndServe(":"+port, r); err != nil {
		log.Fatalf("Erreur au démarrage du serveur: %v", err)
	}
}

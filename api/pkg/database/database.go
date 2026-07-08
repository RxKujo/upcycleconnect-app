// Package database gère la connexion MySQL partagée par toute l'API via la
// variable globale DB, configurée à partir des variables d'environnement DB_*.
package database

import (
	"database/sql"
	"fmt"
	"log"
	"os"

	_ "github.com/go-sql-driver/mysql"
)

// DB est le pool de connexions partagé, initialisé par InitDB.
var DB *sql.DB

// InitDB ouvre le pool de connexions MySQL et vérifie sa disponibilité (Ping).
func InitDB() error {
	user := os.Getenv("DB_USER")
	password := os.Getenv("DB_PASSWORD")
	host := os.Getenv("DB_HOST")
	port := os.Getenv("DB_PORT")
	dbName := os.Getenv("DB_NAME")

	dsn := fmt.Sprintf("%s:%s@tcp(%s:%s)/%s?parseTime=true&charset=utf8mb4&collation=utf8mb4_unicode_ci", user, password, host, port, dbName)

	var err error
	DB, err = sql.Open("mysql", dsn)
	if err != nil {
		return fmt.Errorf("erreur lors de l'ouverture de la base de données: %v", err)
	}

	if err = DB.Ping(); err != nil {
		return fmt.Errorf("erreur lors du ping de la base de données: %v", err)
	}

	log.Println("Connexion à la base de données réussie")
	return nil
}

package handlers

import (
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

type AdminStats struct {
	TotalUtilisateurs   int     `json:"total_utilisateurs"`
	TotalPros           int     `json:"total_pros"`
	TotalAnnonces       int     `json:"total_annonces"`
	AnnoncesEnAttente   int     `json:"annonces_en_attente"`
	TotalEvenements     int     `json:"total_evenements"`
	EvenementsEnAttente  int     `json:"evenements_en_attente"`
	PublicitesEnAttente  int     `json:"publicites_en_attente"`
	TotalCommandes       int     `json:"total_commandes"`
	CATotal             float64 `json:"ca_total"`
	TotalInscriptions   int     `json:"total_inscriptions"`
	TotalFormations     int     `json:"total_formations"`
	Signalements        int     `json:"signalements"`
	AbonnementsActifs   int     `json:"abonnements_actifs"`
}

func GetAdminStats(w http.ResponseWriter, r *http.Request) {
	var s AdminStats
	database.DB.QueryRow("SELECT COUNT(*) FROM utilisateurs").Scan(&s.TotalUtilisateurs)
	database.DB.QueryRow("SELECT COUNT(*) FROM utilisateurs WHERE role = 'professionnel'").Scan(&s.TotalPros)
	database.DB.QueryRow("SELECT COUNT(*) FROM annonces").Scan(&s.TotalAnnonces)
	database.DB.QueryRow("SELECT COUNT(*) FROM annonces WHERE statut = 'en_attente'").Scan(&s.AnnoncesEnAttente)
	database.DB.QueryRow("SELECT COUNT(*) FROM evenements").Scan(&s.TotalEvenements)
	database.DB.QueryRow("SELECT COUNT(*) FROM evenements WHERE statut = 'en_attente'").Scan(&s.EvenementsEnAttente)
	database.DB.QueryRow("SELECT COUNT(*) FROM publicites WHERE statut = 'en_attente'").Scan(&s.PublicitesEnAttente)
	database.DB.QueryRow("SELECT COUNT(*) FROM commandes").Scan(&s.TotalCommandes)
	database.DB.QueryRow("SELECT COALESCE(SUM(a.prix), 0) FROM commandes c JOIN annonces a ON a.id_annonce = c.id_annonce WHERE c.statut != 'annulee'").Scan(&s.CATotal)
	database.DB.QueryRow("SELECT COUNT(*) FROM inscriptions_evenements").Scan(&s.TotalInscriptions)
	database.DB.QueryRow("SELECT COUNT(*) FROM catalogue_items WHERE statut = 'publie'").Scan(&s.TotalFormations)
	database.DB.QueryRow("SELECT COUNT(*) FROM signalements_forum WHERE statut = 'en_cours'").Scan(&s.Signalements)
	database.DB.QueryRow("SELECT COUNT(*) FROM souscriptions WHERE est_active = 1").Scan(&s.AbonnementsActifs)

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(s)
}

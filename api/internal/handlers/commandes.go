package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

func GetCommandes(w http.ResponseWriter, r *http.Request) {
	query := `
		SELECT c.id_commande, c.id_annonce, c.id_acheteur, c.id_conteneur, c.commission_pct, c.montant_commission, c.date_limite_recuperation, c.stripe_payment_intent, c.date_commande, c.statut,
			   a.titre as titre_annonce, a.mode_remise,
			   u.nom as acheteur_nom, u.prenom as acheteur_prenom
		FROM commandes c
		JOIN annonces a ON c.id_annonce = a.id_annonce
		JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
		ORDER BY c.date_commande DESC
	`

	rows, err := database.DB.Query(query)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var commandes []models.Commande
	for rows.Next() {
		var c models.Commande
		if err := rows.Scan(&c.IDCommande, &c.IDAnnonce, &c.IDAcheteur, &c.IDConteneur, &c.CommissionPct, &c.MontantCommission, &c.DateLimiteRecuperation, &c.StripePaymentIntent, &c.DateCommande, &c.Statut, &c.TitreAnnonce, &c.ModeRemise, &c.AcheteurNom, &c.AcheteurPrenom); err == nil {
			commandes = append(commandes, c)
		}
	}
	if commandes == nil {
		commandes = []models.Commande{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(commandes)
}

func GetCommande(w http.ResponseWriter, r *http.Request, id string) {
	query := `
		SELECT c.id_commande, c.id_annonce, c.id_acheteur, c.id_conteneur, c.commission_pct, c.montant_commission, c.date_limite_recuperation, c.stripe_payment_intent, c.date_commande, c.statut,
			   a.titre as titre_annonce, a.mode_remise,
			   u.nom as acheteur_nom, u.prenom as acheteur_prenom
		FROM commandes c
		JOIN annonces a ON c.id_annonce = a.id_annonce
		JOIN utilisateurs u ON c.id_acheteur = u.id_utilisateur
		WHERE c.id_commande = ?
	`

	var c models.Commande
	err := database.DB.QueryRow(query, id).Scan(&c.IDCommande, &c.IDAnnonce, &c.IDAcheteur, &c.IDConteneur, &c.CommissionPct, &c.MontantCommission, &c.DateLimiteRecuperation, &c.StripePaymentIntent, &c.DateCommande, &c.Statut, &c.TitreAnnonce, &c.ModeRemise, &c.AcheteurNom, &c.AcheteurPrenom)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "commande introuvable"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(c)
}

func UpdateCommandeStatut(w http.ResponseWriter, r *http.Request, id string) {
	var req models.UpdateCommandeStatutRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	_, err := database.DB.Exec("UPDATE commandes SET statut = ? WHERE id_commande = ?", req.Statut, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la mise à jour"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "statut de la commande mis à jour"})
}

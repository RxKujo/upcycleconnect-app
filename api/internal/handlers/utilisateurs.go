package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
)

func GetMe(w http.ResponseWriter, r *http.Request, id int) {
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouvé"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

func UpdateMe(w http.ResponseWriter, r *http.Request, id int) {
	var req struct {
		Telephone *string `json:"telephone"`
		Ville     *string `json:"ville"`
	}

	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET telephone = COALESCE(?, telephone), ville = COALESCE(?, ville) WHERE id_utilisateur = ?`
	_, err = database.DB.Exec(query, req.Telephone, req.Ville, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la mise à jour"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "profil mis à jour avec succès"})
}

func GetAllUtilisateurs(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_utilisateur, nom, prenom, email, role, est_banni FROM utilisateurs")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var utilisateurs []map[string]interface{}
	for rows.Next() {
		var id int
		var nom, prenom, email, role string
		var estBanni bool
		if err := rows.Scan(&id, &nom, &prenom, &email, &role, &estBanni); err == nil {
			utilisateurs = append(utilisateurs, map[string]interface{}{
				"id_utilisateur": id, "nom": nom, "prenom": prenom, "email": email, "role": role, "est_banni": estBanni,
			})
		}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(utilisateurs)
}

func GetUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	var u models.Utilisateur
	query := `SELECT id_utilisateur, nom, prenom, email, telephone, ville, role, est_banni, date_fin_ban, nom_entreprise, numero_siret, date_creation 
	          FROM utilisateurs WHERE id_utilisateur = ?`
	err := database.DB.QueryRow(query, id).Scan(
		&u.IDUtilisateur, &u.Nom, &u.Prenom, &u.Email, &u.Telephone, &u.Ville, &u.Role, &u.EstBanni, &u.DateFinBan, &u.NomEntreprise, &u.NumeroSiret, &u.DateCreation)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "utilisateur non trouvé"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(u)
}

func BanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	var req struct {
		DateFinBan string `json:"date_fin_ban"`
	}
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `UPDATE utilisateurs SET est_banni = true, date_fin_ban = ? WHERE id_utilisateur = ?`
	_, err = database.DB.Exec(query, req.DateFinBan, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de bannir l'utilisateur"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur banni"})
}

func UnbanUtilisateur(w http.ResponseWriter, r *http.Request, id string) {
	query := `UPDATE utilisateurs SET est_banni = false, date_fin_ban = NULL WHERE id_utilisateur = ?`
	_, err := database.DB.Exec(query, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de débannir l'utilisateur"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "utilisateur débanni"})
}

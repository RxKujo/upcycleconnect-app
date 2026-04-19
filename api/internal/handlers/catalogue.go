package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"strconv"
)

func GetCatalogueItems(w http.ResponseWriter, r *http.Request, role string) {
	query := `
		SELECT a.id_annonce, a.id_particulier, a.titre, a.description, a.type_annonce, a.prix, a.mode_remise, a.statut, a.motif_refus, a.motif_retrait, a.date_creation, a.valide_par,
		o.categorie, o.materiau, o.etat
		FROM annonces a
		LEFT JOIN objets_annonces o ON o.id_annonce = a.id_annonce
	`

	if role != "admin" {
		query += " WHERE a.statut = 'validee'"
	}
	
	query += " ORDER BY a.date_creation DESC"

	rows, err := database.DB.Query(query)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var items []models.Annonce
	for rows.Next() {
		var item models.Annonce
		if err := rows.Scan(
			&item.IDAnnonce, &item.IDParticulier, &item.Titre, &item.Description, &item.TypeAnnonce, &item.Prix, &item.ModeRemise, &item.Statut, &item.MotifRefus, &item.MotifRetrait, &item.DateCreation, &item.ValidePar,
			&item.CategorieObjet, &item.MateriauObjet, &item.EtatObjet,
		); err == nil {
			items = append(items, item)
		}
	}
	if items == nil {
		items = []models.Annonce{}
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(items)
}

func GetCatalogueItem(w http.ResponseWriter, r *http.Request, id string, role string) {
	query := `
		SELECT a.id_annonce, a.id_particulier, a.titre, a.description, a.type_annonce, a.prix, a.mode_remise, a.statut, a.motif_refus, a.motif_retrait, a.date_creation, a.valide_par,
		o.categorie, o.materiau, o.etat
		FROM annonces a
		LEFT JOIN objets_annonces o ON o.id_annonce = a.id_annonce
		WHERE a.id_annonce = ?
	`
	if role != "admin" {
		query += " AND a.statut = 'validee'"
	}

	var item models.Annonce
	err := database.DB.QueryRow(query, id).Scan(
		&item.IDAnnonce, &item.IDParticulier, &item.Titre, &item.Description, &item.TypeAnnonce, &item.Prix, &item.ModeRemise, &item.Statut, &item.MotifRefus, &item.MotifRetrait, &item.DateCreation, &item.ValidePar,
		&item.CategorieObjet, &item.MateriauObjet, &item.EtatObjet,
	)
	
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "élément non trouvé"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(item)
}

func ValiderCatalogueItem(w http.ResponseWriter, r *http.Request, id string, adminId int, role string) {
	if role != "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
		return
	}

	_, err := database.DB.Exec("UPDATE annonces SET statut = 'validee', valide_par = ?, motif_refus = NULL WHERE id_annonce = ?", adminId, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la validation"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce validée"})
}

func RefuserCatalogueItem(w http.ResponseWriter, r *http.Request, id string, adminId int, role string) {
	if role != "admin" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
		return
	}

	var req models.AnnonceValidationRequest
	_ = json.NewDecoder(r.Body).Decode(&req)

	_, err := database.DB.Exec("UPDATE annonces SET statut = 'refusee', valide_par = ?, motif_refus = ? WHERE id_annonce = ?", adminId, req.MotifRefus, id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors du refus"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce refusée"})
}

func DeleteCatalogueItem(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	if role != "admin" {
		
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
		return
	}

	_, err := database.DB.Exec("DELETE FROM annonces WHERE id_annonce = ?", id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de supprimer l'élément"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "annonce supprimée"})
}

func GetUtilisateurPlanning(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
	requestedId, err := strconv.Atoi(id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "identifiant invalide"})
		return
	}
	if role != "admin" && requestedId != userId {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode([]interface{}{})
}

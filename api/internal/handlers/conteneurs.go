package handlers

import (
	"api/internal/models"
	"api/pkg/database"
	"encoding/json"
	"net/http"
	"time"
)

func GetAllConteneurs(w http.ResponseWriter, r *http.Request) {
	rows, err := database.DB.Query("SELECT id_conteneur, conteneur_ref, adresse, ville, code_postal, capacite, statut FROM conteneurs")
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	var conteneurs []models.Conteneur
	for rows.Next() {
		var c models.Conteneur
		if err := rows.Scan(&c.IDConteneur, &c.ConteneurRef, &c.Adresse, &c.Ville, &c.CodePostal, &c.Capacite, &c.Statut); err == nil {
			conteneurs = append(conteneurs, c)
		}
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(conteneurs)
}

func CreateConteneur(w http.ResponseWriter, r *http.Request) {
	var req models.CreateConteneurRequest
	err := json.NewDecoder(r.Body).Decode(&req)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	query := `INSERT INTO conteneurs (conteneur_ref, adresse, ville, code_postal, capacite, statut) VALUES (?, ?, ?, ?, ?, 'actif')`
	result, err := database.DB.Exec(query, req.ConteneurRef, req.Adresse, req.Ville, req.CodePostal, req.Capacite)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer le conteneur"})
		return
	}

	id, _ := result.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{"message": "conteneur créé", "id_conteneur": id})
}

func GetConteneurDetails(w http.ResponseWriter, r *http.Request, id string) {

	var commandes []models.CommandeConteneur
	rowsCmd, _ := database.DB.Query("SELECT id_commande, id_annonce, id_acheteur, id_conteneur, statut FROM commandes WHERE id_conteneur = ?", id)
	for rowsCmd != nil && rowsCmd.Next() {
		var c models.CommandeConteneur
		rowsCmd.Scan(&c.IDCommande, &c.IDAnnonce, &c.IDAcheteur, &c.IDConteneur, &c.Statut)
		commandes = append(commandes, c)
	}
	if rowsCmd != nil {
		rowsCmd.Close()
	}

	var tickets []models.TicketIncident
	rowsTck, _ := database.DB.Query("SELECT id_ticket, sujet, description, statut, date_creation FROM tickets_incidents WHERE id_conteneur = ?", id)
	for rowsTck != nil && rowsTck.Next() {
		var t models.TicketIncident
		rowsTck.Scan(&t.IDTicket, &t.Sujet, &t.Description, &t.Statut, &t.DateCreation)
		tickets = append(tickets, t)
	}
	if rowsTck != nil {
		rowsTck.Close()
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"commandes": commandes,
		"tickets":   tickets,
	})
}

func CreateCodeBarre(w http.ResponseWriter, r *http.Request) {
	var req models.CreateCodeBarreRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		return
	}

	query := `INSERT INTO codes_barres (id_commande, code_valeur, type_code, pdf_url, date_creation) VALUES (?, ?, ?, ?, ?)`
	_, err := database.DB.Exec(query, req.IDCommande, req.CodeValeur, req.TypeCode, req.PdfUrl, time.Now())
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur insertion"})
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]string{"message": "code création succès"})
}

func ScanBarcodeAndUpdateCommande(w http.ResponseWriter, r *http.Request) {
	var req struct {
		CodeValeur string `json:"code_valeur"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		http.Error(w, "Bad request", http.StatusBadRequest)
		return
	}

	var idCodeBarre, idCommande int
	var typeCode string
	err := database.DB.QueryRow("SELECT id_code_barre, id_commande, type_code FROM codes_barres WHERE code_valeur = ? AND date_utilisation IS NULL", req.CodeValeur).Scan(&idCodeBarre, &idCommande, &typeCode)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "code barre invalide ou déjà utilisé"})
		return
	}

	database.DB.Exec("UPDATE codes_barres SET date_utilisation = ? WHERE id_code_barre = ?", time.Now(), idCodeBarre)

	var newStatut string
	if typeCode == "depot_particulier" {
		newStatut = "en_conteneur"
	} else if typeCode == "recuperation_pro" {
		newStatut = "recuperee"
	}

	database.DB.Exec("UPDATE commandes SET statut = ? WHERE id_commande = ?", newStatut, idCommande)

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "commande mise à jour", "nouveau_statut": newStatut})
}

func ResolveTicket(w http.ResponseWriter, r *http.Request, id string) {
	query := `UPDATE tickets_incidents SET statut = 'resolu', date_resolution = ? WHERE id_ticket = ?`
	_, err := database.DB.Exec(query, time.Now(), id)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(map[string]string{"message": "ticket résolu"})
}

package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"strconv"
	"strings"
	"time"
)

type CreateSujetRequest struct {
	Titre     string `json:"titre"`
	Categorie string `json:"categorie"`
	Contenu   string `json:"contenu"`
}

type CreateMessageRequest struct {
	Contenu          string `json:"contenu"`
	IDParentMessage  *int   `json:"id_parent_message,omitempty"`
}

func CreateForumSujet(w http.ResponseWriter, r *http.Request, userId int) {
	var req CreateSujetRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	req.Titre = strings.TrimSpace(req.Titre)
	req.Contenu = strings.TrimSpace(req.Contenu)
	req.Categorie = strings.TrimSpace(req.Categorie)

	if len(req.Titre) < 5 || len(req.Contenu) < 5 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "le titre et le contenu doivent faire au moins 5 caractères"})
		return
	}

	tx, err := database.DB.Begin()
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	res, err := tx.Exec("INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (?, ?, NULLIF(?, ''), 'ouvert')",
		userId, req.Titre, req.Categorie)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer le sujet"})
		return
	}
	sujetID, _ := res.LastInsertId()

	_, err = tx.Exec("INSERT INTO forum_messages (id_sujet, id_auteur, contenu) VALUES (?, ?, ?)",
		sujetID, userId, req.Contenu)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer le message initial"})
		return
	}

	if err := tx.Commit(); err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		return
	}

	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":  "sujet créé",
		"id_sujet": sujetID,
	})
}

func CreateForumMessage(w http.ResponseWriter, r *http.Request, sujetID string, userId int) {
	var req CreateMessageRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}

	req.Contenu = strings.TrimSpace(req.Contenu)
	if len(req.Contenu) < 2 {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "message trop court"})
		return
	}

	var statut string
	err := database.DB.QueryRow("SELECT statut FROM forum_sujets WHERE id_sujet = ?", sujetID).Scan(&statut)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusNotFound)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "sujet non trouvé"})
		return
	}
	if statut != "ouvert" {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusForbidden)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "ce sujet est fermé"})
		return
	}

	sujetIDInt, err := strconv.Atoi(sujetID)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "id sujet invalide"})
		return
	}

	if req.IDParentMessage != nil {
		var parentSujet int
		errP := database.DB.QueryRow("SELECT id_sujet FROM forum_messages WHERE id_message = ?", *req.IDParentMessage).Scan(&parentSujet)
		if errP != nil {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "message parent introuvable"})
			return
		}
		if parentSujet != sujetIDInt {
			w.Header().Set("Content-Type", "application/json")
			w.WriteHeader(http.StatusBadRequest)
			json.NewEncoder(w).Encode(map[string]string{"erreur": "le message parent n'appartient pas à ce sujet"})
			return
		}
	}

	var lastContenu string
	var lastDate sql.NullTime
	_ = database.DB.QueryRow(`SELECT contenu, date_publication FROM forum_messages
		WHERE id_sujet = ? AND id_auteur = ? ORDER BY date_publication DESC LIMIT 1`,
		sujetID, userId).Scan(&lastContenu, &lastDate)
	if lastContenu == req.Contenu && lastDate.Valid && time.Since(lastDate.Time) < 5*time.Minute {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusConflict)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "message en double : vous venez de publier ce contenu"})
		return
	}

	res, err := database.DB.Exec("INSERT INTO forum_messages (id_sujet, id_auteur, contenu, id_parent_message) VALUES (?, ?, ?, ?)",
		sujetID, userId, req.Contenu, req.IDParentMessage)
	if err != nil {
		w.Header().Set("Content-Type", "application/json")
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible d'envoyer le message"})
		return
	}

	id, _ := res.LastInsertId()
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusCreated)
	json.NewEncoder(w).Encode(map[string]interface{}{
		"message":    "message ajouté",
		"id_message": id,
	})
}

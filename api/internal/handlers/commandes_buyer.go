package handlers

import (
	"api/pkg/database"
	"database/sql"
	"encoding/json"
	"net/http"
	"time"
)

type CheckoutItem struct {
	IDAnnonce int `json:"id_annonce"`
}

type CheckoutRequest struct {
	Items []CheckoutItem `json:"items"`
}

type CheckoutCreated struct {
	IDCommande int     `json:"id_commande"`
	IDAnnonce  int     `json:"id_annonce"`
	Titre      string  `json:"titre"`
	Prix       float64 `json:"prix"`
}

type CheckoutResponse struct {
	Created []CheckoutCreated `json:"created"`
	Failed  []map[string]any  `json:"failed"`
	Total   float64           `json:"total"`
}

func CheckoutPanier(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	var req CheckoutRequest
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
		return
	}
	if len(req.Items) == 0 {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "panier vide"})
		return
	}

	resp := CheckoutResponse{
		Created: []CheckoutCreated{},
		Failed:  []map[string]any{},
	}

	for _, item := range req.Items {
		var titre, statut, typeAnnonce string
		var prix sql.NullFloat64
		var idVendeur int
		err := database.DB.QueryRow(`
			SELECT id_particulier, titre, COALESCE(prix, 0), statut, type_annonce
			FROM annonces WHERE id_annonce = ?
		`, item.IDAnnonce).Scan(&idVendeur, &titre, &prix, &statut, &typeAnnonce)
		if err != nil {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "annonce introuvable",
			})
			continue
		}
		if idVendeur == userId {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "vous ne pouvez pas commander votre propre annonce",
			})
			continue
		}
		if statut != "validee" {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "annonce non disponible (statut: " + statut + ")",
			})
			continue
		}
		if typeAnnonce != "vente" && typeAnnonce != "don" {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "type d'annonce non commandable",
			})
			continue
		}

		tx, err := database.DB.Begin()
		if err != nil {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "erreur transaction",
			})
			continue
		}

		commission := 0.0
		dateLimite := time.Now().AddDate(0, 0, 14)
		res, err := tx.Exec(`
			INSERT INTO commandes (id_annonce, id_acheteur, commission_pct, montant_commission, date_limite_recuperation, statut)
			VALUES (?, ?, ?, ?, ?, 'commandee')
		`, item.IDAnnonce, userId, commission, commission, dateLimite)
		if err != nil {
			tx.Rollback()
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "création commande impossible",
			})
			continue
		}

		_, err = tx.Exec(`UPDATE annonces SET statut = 'vendue' WHERE id_annonce = ? AND statut = 'validee'`, item.IDAnnonce)
		if err != nil {
			tx.Rollback()
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "mise à jour annonce impossible",
			})
			continue
		}

		if err := tx.Commit(); err != nil {
			resp.Failed = append(resp.Failed, map[string]any{
				"id_annonce": item.IDAnnonce,
				"erreur":     "commit impossible",
			})
			continue
		}

		idCmd, _ := res.LastInsertId()
		resp.Created = append(resp.Created, CheckoutCreated{
			IDCommande: int(idCmd),
			IDAnnonce:  item.IDAnnonce,
			Titre:      titre,
			Prix:       prix.Float64,
		})
		resp.Total += prix.Float64
	}

	if len(resp.Created) == 0 {
		w.WriteHeader(http.StatusBadRequest)
	} else {
		w.WriteHeader(http.StatusCreated)
	}
	json.NewEncoder(w).Encode(resp)
}

type MaCommande struct {
	IDCommande     int     `json:"id_commande"`
	IDAnnonce      int     `json:"id_annonce"`
	Titre          string  `json:"titre"`
	TypeAnnonce    string  `json:"type_annonce"`
	Prix           float64 `json:"prix"`
	ModeRemise     string  `json:"mode_remise"`
	Statut         string  `json:"statut"`
	DateCommande   string  `json:"date_commande"`
	DateLimite     string  `json:"date_limite_recuperation,omitempty"`
	VendeurPrenom  string  `json:"vendeur_prenom"`
	VendeurNom     string  `json:"vendeur_nom_initiale"`
}

func GetMesCommandes(w http.ResponseWriter, r *http.Request, userId int) {
	w.Header().Set("Content-Type", "application/json")

	rows, err := database.DB.Query(`
		SELECT c.id_commande, c.id_annonce, a.titre, a.type_annonce, COALESCE(a.prix, 0), a.mode_remise,
		       c.statut, c.date_commande, c.date_limite_recuperation,
		       u.prenom, u.nom
		FROM commandes c
		JOIN annonces a ON a.id_annonce = c.id_annonce
		JOIN utilisateurs u ON u.id_utilisateur = a.id_particulier
		WHERE c.id_acheteur = ?
		ORDER BY c.date_commande DESC
	`, userId)
	if err != nil {
		w.WriteHeader(http.StatusInternalServerError)
		json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
		return
	}
	defer rows.Close()

	commandes := []MaCommande{}
	for rows.Next() {
		var c MaCommande
		var dateCmd time.Time
		var dateLim sql.NullTime
		var nomVendeur string
		if err := rows.Scan(&c.IDCommande, &c.IDAnnonce, &c.Titre, &c.TypeAnnonce, &c.Prix, &c.ModeRemise,
			&c.Statut, &dateCmd, &dateLim, &c.VendeurPrenom, &nomVendeur); err == nil {
			c.DateCommande = dateCmd.Format("2006-01-02T15:04:05Z")
			if dateLim.Valid {
				c.DateLimite = dateLim.Time.Format("2006-01-02T15:04:05Z")
			}
			if len(nomVendeur) > 0 {
				c.VendeurNom = string([]rune(nomVendeur)[:1]) + "."
			}
			commandes = append(commandes, c)
		}
	}

	w.WriteHeader(http.StatusOK)
	json.NewEncoder(w).Encode(commandes)
}

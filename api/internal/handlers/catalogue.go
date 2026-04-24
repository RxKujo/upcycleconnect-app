package handlers

import (
    "api/internal/models"
    "api/pkg/database"
    "encoding/json"
    "net/http"
    "strconv"
    "strings"
    "time"
)

func GetCatalogueItems(w http.ResponseWriter, r *http.Request, role string) {
    queryParts := []string{"statut = 'publie'"}
    args := []interface{}{}

    if role == "admin" {
        queryParts = []string{}
    }

    if categorie := strings.TrimSpace(r.URL.Query().Get("categorie")); categorie != "" {
        queryParts = append(queryParts, "categorie = ?")
        args = append(args, categorie)
    }
    if format := strings.TrimSpace(r.URL.Query().Get("format")); format != "" {
        queryParts = append(queryParts, "format = ?")
        args = append(args, format)
    }
    if lieu := strings.TrimSpace(r.URL.Query().Get("lieu")); lieu != "" {
        queryParts = append(queryParts, "lieu LIKE ?")
        args = append(args, "%"+lieu+"%")
    }
    if minPrice := strings.TrimSpace(r.URL.Query().Get("min_price")); minPrice != "" {
        if value, err := strconv.ParseFloat(minPrice, 64); err == nil {
            queryParts = append(queryParts, "prix >= ?")
            args = append(args, value)
        }
    }
    if maxPrice := strings.TrimSpace(r.URL.Query().Get("max_price")); maxPrice != "" {
        if value, err := strconv.ParseFloat(maxPrice, 64); err == nil {
            queryParts = append(queryParts, "prix <= ?")
            args = append(args, value)
        }
    }
    if dateFilter := strings.TrimSpace(r.URL.Query().Get("date")); dateFilter != "" {
        if dateValue, err := time.Parse("2006-01-02", dateFilter); err == nil {
            queryParts = append(queryParts, "date_debut >= ?")
            args = append(args, dateValue)
        }
    }

    query := `SELECT id_catalogue_item, id_createur, titre, description, categorie, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM catalogue_items`
    if len(queryParts) > 0 {
        query += " WHERE " + strings.Join(queryParts, " AND ")
    }
    query += " ORDER BY date_debut ASC"

    rows, err := database.DB.Query(query, args...)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
        return
    }
    defer rows.Close()

    var items []models.CatalogueItem
    for rows.Next() {
        var item models.CatalogueItem
        if err := rows.Scan(&item.IDCatalogueItem, &item.IDCreateur, &item.Titre, &item.Description, &item.Categorie, &item.Format, &item.Lieu, &item.DateDebut, &item.DateFin, &item.NbPlacesTotal, &item.NbPlacesDispo, &item.Prix, &item.Statut, &item.ValidePar, &item.DateCreation); err == nil {
            items = append(items, item)
        }
    }
    if items == nil {
        items = []models.CatalogueItem{}
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(items)
}

func GetCatalogueItem(w http.ResponseWriter, r *http.Request, id string, role string) {
    query := `SELECT id_catalogue_item, id_createur, titre, description, categorie, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par, date_creation FROM catalogue_items WHERE id_catalogue_item = ?`
    if role != "admin" {
        query += " AND statut = 'publie'"
    }

    var item models.CatalogueItem
    err := database.DB.QueryRow(query, id).Scan(&item.IDCatalogueItem, &item.IDCreateur, &item.Titre, &item.Description, &item.Categorie, &item.Format, &item.Lieu, &item.DateDebut, &item.DateFin, &item.NbPlacesTotal, &item.NbPlacesDispo, &item.Prix, &item.Statut, &item.ValidePar, &item.DateCreation)
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

func CreateCatalogueItem(w http.ResponseWriter, r *http.Request, userId int, role string) {
    if role != "admin" && role != "salarie" {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusForbidden)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
        return
    }

    var req models.CreateCatalogueItemRequest
    if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusBadRequest)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
        return
    }

    creator := req.IDCreateur
    if creator == 0 {
        creator = userId
    }
    statut := "publie"
    if role == "salarie" {
        statut = "en_attente"
    }

    query := `INSERT INTO catalogue_items (id_createur, titre, description, categorie, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)`
    res, err := database.DB.Exec(query, creator, req.Titre, req.Description, req.Categorie, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, req.NbPlacesTotal, req.Prix, statut)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer l'élément"})
        return
    }
    id, _ := res.LastInsertId()

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusCreated)
    json.NewEncoder(w).Encode(map[string]interface{}{"message": "élément créé", "id": id})
}

func UpdateCatalogueItem(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
    var existing models.CatalogueItem
    err := database.DB.QueryRow("SELECT id_catalogue_item, id_createur, nb_places_total, nb_places_dispo, statut FROM catalogue_items WHERE id_catalogue_item = ?", id).
        Scan(&existing.IDCatalogueItem, &existing.IDCreateur, &existing.NbPlacesTotal, &existing.NbPlacesDispo, &existing.Statut)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusNotFound)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "élément non trouvé"})
        return
    }

    if role != "admin" {
        if existing.IDCreateur != userId {
            w.Header().Set("Content-Type", "application/json")
            w.WriteHeader(http.StatusForbidden)
            json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
            return
        }
        if existing.Statut == "publie" {
            w.Header().Set("Content-Type", "application/json")
            w.WriteHeader(http.StatusForbidden)
            json.NewEncoder(w).Encode(map[string]string{"erreur": "modification impossible"})
            return
        }
    }

    var req models.UpdateCatalogueItemRequest
    if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusBadRequest)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
        return
    }

    nbPlacesDispo := existing.NbPlacesDispo + (req.NbPlacesTotal - existing.NbPlacesTotal)
    if nbPlacesDispo < 0 {
        nbPlacesDispo = 0
    }

    query := `UPDATE catalogue_items SET titre = ?, description = ?, categorie = ?, format = ?, lieu = ?, date_debut = ?, date_fin = ?, nb_places_total = ?, nb_places_dispo = ?, prix = ? WHERE id_catalogue_item = ?`
    _, err = database.DB.Exec(query, req.Titre, req.Description, req.Categorie, req.Format, req.Lieu, req.DateDebut, req.DateFin, req.NbPlacesTotal, nbPlacesDispo, req.Prix, id)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de mettre à jour l'élément"})
        return
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(map[string]string{"message": "élément mis à jour"})
}

func DeleteCatalogueItem(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
    var existing models.CatalogueItem
    err := database.DB.QueryRow("SELECT id_catalogue_item, id_createur, statut FROM catalogue_items WHERE id_catalogue_item = ?", id).
        Scan(&existing.IDCatalogueItem, &existing.IDCreateur, &existing.Statut)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusNotFound)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "élément non trouvé"})
        return
    }

    if role != "admin" && existing.IDCreateur != userId {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusForbidden)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
        return
    }

    _, err = database.DB.Exec("UPDATE catalogue_items SET statut = 'annule', nb_places_dispo = 0 WHERE id_catalogue_item = ?", id)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de supprimer l'élément"})
        return
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(map[string]string{"message": "élément annulé"})
}

func ValiderCatalogueItem(w http.ResponseWriter, r *http.Request, id string, adminId int, role string) {
    if role != "admin" {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusForbidden)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
        return
    }

    _, err := database.DB.Exec("UPDATE catalogue_items SET statut = 'publie', valide_par = ? WHERE id_catalogue_item = ?", adminId, id)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la validation"})
        return
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(map[string]string{"message": "élément publié"})
}

func ReserverCatalogueItem(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
    if role != "particulier" && role != "professionnel" && role != "salarie" {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusForbidden)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
        return
    }

    var req models.CreateReservationRequest
    if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusBadRequest)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "données invalides"})
        return
    }

    var item models.CatalogueItem
    err := database.DB.QueryRow("SELECT id_catalogue_item, prix, nb_places_dispo, statut FROM catalogue_items WHERE id_catalogue_item = ?", id).
        Scan(&item.IDCatalogueItem, &item.Prix, &item.NbPlacesDispo, &item.Statut)
    if err != nil || item.Statut != "publie" {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusNotFound)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "élément introuvable"})
        return
    }
    if item.NbPlacesDispo <= 0 {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusBadRequest)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "aucune place disponible"})
        return
    }
    if item.Prix > 0 && (req.StripePayment == nil || strings.TrimSpace(*req.StripePayment) == "") {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusBadRequest)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "paiement Stripe requis"})
        return
    }

    var exists int
    err = database.DB.QueryRow("SELECT COUNT(1) FROM catalogue_reservations WHERE id_catalogue_item = ? AND id_utilisateur = ?", id, userId).Scan(&exists)
    if err == nil && exists > 0 {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusConflict)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "réservation déjà existante"})
        return
    }

    tx, err := database.DB.Begin()
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
        return
    }

    statusPaiement := "gratuit"
    if item.Prix > 0 {
        statusPaiement = "paye"
    }

    _, err = tx.Exec("INSERT INTO catalogue_reservations (id_utilisateur, id_catalogue_item, statut_paiement, stripe_payment, prix_paye) VALUES (?, ?, ?, ?, ?)", userId, id, statusPaiement, req.StripePayment, item.Prix)
    if err != nil {
        tx.Rollback()
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "impossible de créer la réservation"})
        return
    }

    _, err = tx.Exec("UPDATE catalogue_items SET nb_places_dispo = nb_places_dispo - 1 WHERE id_catalogue_item = ?", id)
    if err != nil {
        tx.Rollback()
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur lors de la réservation"})
        return
    }

    if err := tx.Commit(); err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
        return
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusCreated)
    json.NewEncoder(w).Encode(map[string]string{"message": "réservation enregistrée"})
}

func GetCatalogueReservations(w http.ResponseWriter, r *http.Request, id string, userId int, role string) {
    var creator int
    err := database.DB.QueryRow("SELECT id_createur FROM catalogue_items WHERE id_catalogue_item = ?", id).Scan(&creator)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusNotFound)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "élément introuvable"})
        return
    }
    if role != "admin" && creator != userId {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusForbidden)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "accès refusé"})
        return
    }

    rows, err := database.DB.Query("SELECT id_reservation, id_utilisateur, id_catalogue_item, date_reservation, statut_paiement, stripe_payment, prix_paye FROM catalogue_reservations WHERE id_catalogue_item = ?", id)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
        return
    }
    defer rows.Close()

    var reservations []models.Reservation
    for rows.Next() {
        var reservation models.Reservation
        if err := rows.Scan(&reservation.IDReservation, &reservation.IDUtilisateur, &reservation.IDCatalogueItem, &reservation.DateReservation, &reservation.StatutPaiement, &reservation.StripePayment, &reservation.PrixPaye); err == nil {
            reservations = append(reservations, reservation)
        }
    }
    if reservations == nil {
        reservations = []models.Reservation{}
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(reservations)
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

    rows, err := database.DB.Query(`SELECT c.id_catalogue_item, c.titre, c.categorie, c.format, c.lieu, c.date_debut, c.date_fin, c.prix, r.id_reservation, r.date_reservation, r.statut_paiement
        FROM catalogue_reservations r
        JOIN catalogue_items c ON r.id_catalogue_item = c.id_catalogue_item
        WHERE r.id_utilisateur = ?
        ORDER BY r.date_reservation DESC`, requestedId)
    if err != nil {
        w.Header().Set("Content-Type", "application/json")
        w.WriteHeader(http.StatusInternalServerError)
        json.NewEncoder(w).Encode(map[string]string{"erreur": "erreur serveur"})
        return
    }
    defer rows.Close()

    var planning []models.PlanningItem
    for rows.Next() {
        var item models.PlanningItem
        if err := rows.Scan(&item.IDCatalogueItem, &item.Titre, &item.Categorie, &item.Format, &item.Lieu, &item.DateDebut, &item.DateFin, &item.Prix, &item.IDReservation, &item.DateReservation, &item.StatutPaiement); err == nil {
            planning = append(planning, item)
        }
    }
    if planning == nil {
        planning = []models.PlanningItem{}
    }

    w.Header().Set("Content-Type", "application/json")
    w.WriteHeader(http.StatusOK)
    json.NewEncoder(w).Encode(planning)
}

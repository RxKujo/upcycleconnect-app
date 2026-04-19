-- =============================================
-- CATALOGUE_ITEMS
-- =============================================
CREATE TABLE catalogue_items (
    id_catalogue_item INT AUTO_INCREMENT PRIMARY KEY,
    id_createur INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    categorie ENUM('formation','atelier','evenement','conseil') NOT NULL,
    format ENUM('presentiel','distanciel') NOT NULL,
    lieu VARCHAR(300),
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    nb_places_total INT NOT NULL,
    nb_places_dispo INT NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut ENUM('brouillon','en_attente','publie','annule') NOT NULL DEFAULT 'brouillon',
    valide_par INT NULL,
    date_creation DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_createur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (valide_par) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- CATALOGUE_RESERVATIONS
-- =============================================
CREATE TABLE catalogue_reservations (
    id_reservation INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_catalogue_item INT NOT NULL,
    date_reservation DATETIME NOT NULL DEFAULT NOW(),
    statut_paiement ENUM('gratuit','paye','annule') NOT NULL DEFAULT 'gratuit',
    stripe_payment VARCHAR(255) NULL,
    prix_paye DECIMAL(10,2) NULL,
    UNIQUE KEY unique_reservation (id_catalogue_item, id_utilisateur),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_catalogue_item) REFERENCES catalogue_items(id_catalogue_item)
);

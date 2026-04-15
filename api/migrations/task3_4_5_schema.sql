-- UpcycleConnect - Schema migrations for Tasks 3, 4, 5
-- Run this SQL against the upcycleconnect database before testing

-- Task 4 & 5: Add missing columns to utilisateurs
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS adresse_complete VARCHAR(500) NULL AFTER ville;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS photo_profil_url VARCHAR(500) NULL AFTER adresse_complete;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS upcycling_score INT DEFAULT 0 AFTER numero_siret;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS est_certifie TINYINT(1) DEFAULT 0 AFTER upcycling_score;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS notif_push_active TINYINT(1) DEFAULT 1 AFTER est_certifie;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS notif_email_active TINYINT(1) DEFAULT 1 AFTER notif_push_active;
ALTER TABLE utilisateurs ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL AFTER date_creation;

-- Task 3: Ensure objets_annonces table exists
CREATE TABLE IF NOT EXISTS objets_annonces (
    id_objet INT AUTO_INCREMENT PRIMARY KEY,
    id_annonce INT NOT NULL,
    categorie VARCHAR(200) NOT NULL,
    materiau ENUM('bois','metal','textile','plastique','verre','electronique','autre') NOT NULL,
    etat ENUM('neuf','bon','use','a_reparer') NOT NULL,
    poids_kg DECIMAL(10,2) NULL,
    FOREIGN KEY (id_annonce) REFERENCES annonces(id_annonce) ON DELETE CASCADE
);

-- Task 3: Ensure photos_objets table exists
CREATE TABLE IF NOT EXISTS photos_objets (
    id_photo INT AUTO_INCREMENT PRIMARY KEY,
    id_objet INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    ordre INT DEFAULT 0,
    FOREIGN KEY (id_objet) REFERENCES objets_annonces(id_objet) ON DELETE CASCADE
);

-- Task 3: Ensure annonce statut supports 'supprimee'
-- MySQL does not support ALTER ENUM directly with IF, so we modify if needed
-- Check current enum values first. If 'supprimee' is missing, run:
-- ALTER TABLE annonces MODIFY COLUMN statut ENUM('en_attente','validee','refusee','annulee','vendue','supprimee') DEFAULT 'en_attente';

-- Task 5: Ensure souscriptions table has gere_par_admin
ALTER TABLE souscriptions ADD COLUMN IF NOT EXISTS gere_par_admin TINYINT(1) DEFAULT 0;

-- Task 3: Ensure upload directory exists (do this in OS, not SQL)
-- mkdir -p web/public/uploads/photos

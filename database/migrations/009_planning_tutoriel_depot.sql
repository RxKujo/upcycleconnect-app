-- ============================================================
-- Migration 009 : Planning personnel, Tutoriel, Dépôt conteneur
-- ============================================================

-- 1. Extension de planning_utilisateurs (colonnes ajoutées si absentes)
DROP PROCEDURE IF EXISTS add_planning_columns;
DELIMITER //
CREATE PROCEDURE add_planning_columns()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='planning_utilisateurs' AND COLUMN_NAME='id_catalogue_item') THEN
        ALTER TABLE planning_utilisateurs ADD COLUMN id_catalogue_item INT NULL AFTER id_evenement;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='planning_utilisateurs' AND COLUMN_NAME='description') THEN
        ALTER TABLE planning_utilisateurs ADD COLUMN description TEXT NULL AFTER titre_creneau;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='planning_utilisateurs' AND COLUMN_NAME='est_manuel') THEN
        ALTER TABLE planning_utilisateurs ADD COLUMN est_manuel TINYINT(1) NOT NULL DEFAULT 0;
    END IF;
    -- Ajouter 'formation' à l'enum si pas encore présent
    ALTER TABLE planning_utilisateurs MODIFY COLUMN type_creneau ENUM('evenement','formation','reunion','travail','perso') NOT NULL DEFAULT 'perso';
END //
DELIMITER ;
CALL add_planning_columns();
DROP PROCEDURE IF EXISTS add_planning_columns;

-- 2. Tutoriel étapes
CREATE TABLE IF NOT EXISTS tutoriel_etapes (
    id_etape            INT AUTO_INCREMENT PRIMARY KEY,
    titre               VARCHAR(200) NOT NULL,
    contenu             TEXT NOT NULL,
    ordre               INT NOT NULL DEFAULT 0,
    cible_element       VARCHAR(200) NULL,
    position            ENUM('top','bottom','left','right','center') NOT NULL DEFAULT 'center',
    icone               VARCHAR(10) NULL,
    est_actif           TINYINT(1) NOT NULL DEFAULT 1,
    date_creation       DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Suivi tutoriel par utilisateur
CREATE TABLE IF NOT EXISTS utilisateurs_tutoriels (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur      INT NOT NULL,
    date_debut          DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_fin            DATETIME NULL,
    termine             TINYINT(1) NOT NULL DEFAULT 0,
    passe               TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_tuto (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales tutoriel
INSERT IGNORE INTO tutoriel_etapes (titre, contenu, ordre, icone, position) VALUES
('Bienvenue sur UpcycleConnect', 'UpcycleConnect est la plateforme de référence pour donner une seconde vie à vos objets. Donnez, vendez ou échangez des objets upcyclés et gagnez des points pour chaque action éco-responsable !', 1, '🌱', 'center'),
('Votre Profil', 'Accédez à votre profil pour gérer vos informations, consulter vos achats, ventes et réservations de formations.', 2, '👤', 'center'),
('Le Marché', 'Parcourez les annonces de la communauté. Ajoutez des articles à votre panier et payez en toute sécurité avec Stripe.', 3, '🛒', 'center'),
('Déposer une Annonce', 'Publiez vos objets à donner ou vendre. Notre équipe valide chaque annonce avant publication.', 4, '📦', 'center'),
('Formations & Ateliers', 'Inscrivez-vous à nos ateliers et formations pour apprendre les techniques d''upcycling auprès d''experts.', 5, '📚', 'center'),
('Votre Planning', 'Retrouvez tous vos événements et formations dans votre planning personnel. Ajoutez aussi vos propres créneaux !', 6, '📅', 'center'),
('Votre Score Upcycling', 'À chaque achat, don ou participation, vous gagnez des points et progressez vers la certification Upcycler certifié !', 7, '⭐', 'center');

-- 4. Demandes de dépôt en conteneur
CREATE TABLE IF NOT EXISTS demandes_depot (
    id_demande          INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur      INT NOT NULL,
    id_conteneur        INT NULL,
    id_annonce          INT NULL,
    titre               VARCHAR(200) NOT NULL,
    description         TEXT NOT NULL,
    type_objet          VARCHAR(100) NOT NULL,
    quantite            INT NOT NULL DEFAULT 1,
    adresse_retrait     VARCHAR(300) NULL,
    code_postal_retrait VARCHAR(10) NULL,
    ville_retrait       VARCHAR(100) NULL,
    statut              ENUM('en_attente','validee','refusee','code_envoye') NOT NULL DEFAULT 'en_attente',
    code_barre          VARCHAR(64) NULL,
    motif_refus         VARCHAR(500) NULL,
    date_demande        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement     DATETIME NULL,
    id_admin_traitement INT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_conteneur)   REFERENCES conteneurs(id_conteneur)    ON DELETE SET NULL,
    FOREIGN KEY (id_annonce)     REFERENCES annonces(id_annonce)         ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_user (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Langues pour i18n
INSERT IGNORE INTO langue (code_iso, libelle, est_active) VALUES ('fr', 'Français', 1), ('en', 'English', 1);

-- 6. Colonne tuto_vu dans utilisateurs
DROP PROCEDURE IF EXISTS add_tuto_vu;
DELIMITER //
CREATE PROCEDURE add_tuto_vu()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='utilisateurs' AND COLUMN_NAME='tuto_vu') THEN
        ALTER TABLE utilisateurs ADD COLUMN tuto_vu TINYINT(1) NOT NULL DEFAULT 0 AFTER notif_email_active;
    END IF;
END //
DELIMITER ;
CALL add_tuto_vu();
DROP PROCEDURE IF EXISTS add_tuto_vu;

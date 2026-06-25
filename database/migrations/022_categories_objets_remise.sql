-- ─────────────────────────────────────────────────────────────────────────────
-- 022 — Catégories d'objets (liste fermée) + précisions de remise sur les annonces
--
-- 1) Table `categories_objets` : liste fermée, gérée par les admins, qui alimente
--    le menu déroulant « Catégorie » du dépôt d'annonce (fini la saisie libre).
--    Distincte de `categories_prestations` (= spécialités artisans).
-- 2) Colonnes sur `annonces` :
--    - id_conteneur   : conteneur choisi quand mode_remise = 'conteneur'
--    - adresse_remise : adresse de rencontre quand mode_remise = 'main_propre'
--
-- Idempotent (CREATE TABLE IF NOT EXISTS, INSERT IGNORE, procédure pour ALTER).
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories_objets (
    id_categorie_objet INT AUTO_INCREMENT PRIMARY KEY,
    nom                VARCHAR(100) NOT NULL UNIQUE,
    actif              TINYINT(1)   NOT NULL DEFAULT 1,
    date_creation      DATETIME     NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categories_objets (nom) VALUES
('Meubles'),
('Électroménager'),
('Décoration'),
('Vêtements & textile'),
('Vaisselle & cuisine'),
('Bricolage & outils'),
('Jouets & jeux'),
('Livres & papeterie'),
('Luminaires'),
('Électronique'),
('Jardin & extérieur'),
('Matériaux & matières premières'),
('Autre');

DROP PROCEDURE IF EXISTS _add_annonce_remise_cols;
DELIMITER //
CREATE PROCEDURE _add_annonce_remise_cols()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'annonces' AND COLUMN_NAME = 'id_conteneur'
    ) THEN
        ALTER TABLE annonces ADD COLUMN id_conteneur INT NULL AFTER mode_remise;
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'annonces' AND COLUMN_NAME = 'adresse_remise'
    ) THEN
        ALTER TABLE annonces ADD COLUMN adresse_remise VARCHAR(255) NULL AFTER id_conteneur;
    END IF;
END //
DELIMITER ;
CALL _add_annonce_remise_cols();
DROP PROCEDURE IF EXISTS _add_annonce_remise_cols;

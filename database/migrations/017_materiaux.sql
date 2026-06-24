-- ─────────────────────────────────────────────────────────────────────────────
-- 017 — Matériaux dynamiques
-- Remplace l'ENUM figé (bois, metal, …) par une table gérable depuis l'admin.
-- Les colonnes materiau passent en VARCHAR (validation applicative contre la table).
-- ─────────────────────────────────────────────────────────────────────────────

-- Force l'encodage de la connexion (un import en pipe défaut latin1 corromprait les accents)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS materiaux (
    id_materiau   INT AUTO_INCREMENT PRIMARY KEY,
    code          VARCHAR(50)  NOT NULL UNIQUE,   -- identifiant interne stocké dans les annonces/alertes
    libelle       VARCHAR(100) NOT NULL,          -- nom affiché
    icone         VARCHAR(500) NULL,              -- chemin image (uploads/materiaux/xxx)
    actif         TINYINT(1)   NOT NULL DEFAULT 1,
    ordre         INT          NOT NULL DEFAULT 0,
    date_creation DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Reprise des valeurs de l'ENUM existant
INSERT INTO materiaux (code, libelle, ordre) VALUES
    ('bois',         'Bois',         1),
    ('metal',        'Métal',        2),
    ('textile',      'Textile',      3),
    ('plastique',    'Plastique',    4),
    ('verre',        'Verre',        5),
    ('electronique', 'Électronique', 6),
    ('autre',        'Autre',        7)
ON DUPLICATE KEY UPDATE libelle = VALUES(libelle);

-- Les colonnes materiau ne sont plus contraintes par un ENUM
ALTER TABLE objets_annonces  MODIFY COLUMN materiau VARCHAR(50) NOT NULL;
ALTER TABLE alertes_materiaux MODIFY COLUMN materiau VARCHAR(50) NOT NULL;

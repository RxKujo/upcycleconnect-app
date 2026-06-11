-- =====================================================================
-- Migration 004 — Upcycling Score
-- Paliers/niveaux configurables + ledger (historique) des points gagnés.
-- Le score d'un utilisateur = SUM(historique_score.points).
-- Les déchets évités (kg) = SUM(historique_score.poids_kg).
-- La certification est conférée par un palier (confere_certification).
-- =====================================================================
USE upcycleconnect;

-- ---------------------------------------------------------------------
-- Paliers / niveaux (configurables en base)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS paliers_score (
    id_palier             INT AUTO_INCREMENT PRIMARY KEY,
    nom                   VARCHAR(50) NOT NULL,
    seuil_min             INT NOT NULL,                       -- score minimum pour atteindre ce niveau
    ordre                 INT NOT NULL,
    couleur               VARCHAR(20) NOT NULL DEFAULT '#D8C99B',
    confere_certification BOOLEAN NOT NULL DEFAULT FALSE,     -- atteindre ce palier certifie le compte
    mise_en_avant         BOOLEAN NOT NULL DEFAULT FALSE,     -- mise en avant des annonces
    UNIQUE KEY uk_seuil (seuil_min)
);

INSERT IGNORE INTO paliers_score (nom, seuil_min, ordre, couleur, confere_certification, mise_en_avant) VALUES
    ('Graine',  0,    1, '#244F26', FALSE, FALSE),
    ('Bronze',  150,  2, '#A97142', FALSE, FALSE),
    ('Argent',  450,  3, '#9AA3A8', FALSE, FALSE),
    ('Or',      900,  4, '#C9A227', TRUE,  FALSE),
    ('Platine', 1800, 5, '#18607D', TRUE,  TRUE);

-- ---------------------------------------------------------------------
-- Historique des points (ledger) — 1 ligne par gain, idempotent.
-- La clé unique (utilisateur, motif, ref) empêche tout double crédit.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historique_score (
    id_historique  INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    points         INT NOT NULL,
    poids_kg       DECIMAL(8,3) NOT NULL DEFAULT 0.000,       -- déchets évités attribués sur cette ligne
    motif          ENUM('vente_vendeur','don_vendeur','achat_acheteur','participation_evenement','ajustement') NOT NULL,
    ref_type       ENUM('commande','evenement','manuel') NOT NULL,
    ref_id         INT NULL,
    date_creation  DATETIME NOT NULL DEFAULT NOW(),
    UNIQUE KEY uk_ref (id_utilisateur, motif, ref_type, ref_id),
    INDEX idx_utilisateur (id_utilisateur),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
);

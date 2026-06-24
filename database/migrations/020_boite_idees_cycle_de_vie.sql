-- ─────────────────────────────────────────────────────────────────────────────
-- 020 — Boîte à idées : cycle de vie & archivage
-- Ajoute un statut métier (En attente / Réalisé / Non retenu) et un archivage
-- non destructif (archived_at). Idempotent (MySQL 8 ne supporte pas
-- ADD COLUMN IF NOT EXISTS) via procédure de contrôle INFORMATION_SCHEMA.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _add_boite_idees_cycle;
DELIMITER //
CREATE PROCEDURE _add_boite_idees_cycle()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'boite_idees'
          AND COLUMN_NAME  = 'statut'
    ) THEN
        ALTER TABLE boite_idees
            ADD COLUMN statut ENUM('en_attente','realise','non_retenu')
                NOT NULL DEFAULT 'en_attente' AFTER tags;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'boite_idees'
          AND COLUMN_NAME  = 'archived_at'
    ) THEN
        ALTER TABLE boite_idees
            ADD COLUMN archived_at DATETIME NULL DEFAULT NULL AFTER statut;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'boite_idees'
          AND INDEX_NAME   = 'idx_idees_flux'
    ) THEN
        -- Accélère le flux principal (archivées exclues, tri par popularité).
        CREATE INDEX idx_idees_flux ON boite_idees (archived_at, nb_votes);
    END IF;
END //
DELIMITER ;
CALL _add_boite_idees_cycle();
DROP PROCEDURE IF EXISTS _add_boite_idees_cycle;

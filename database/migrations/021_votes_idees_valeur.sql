-- ─────────────────────────────────────────────────────────────────────────────
-- 021 — Vote type Reddit (up / down) sur la boîte à idées
-- Ajoute une colonne `valeur` (+1 = upvote, -1 = downvote) à votes_idees.
-- Les votes existants étaient des « j'aime » → valeur 1 par défaut.
-- nb_votes (boite_idees) devient le score net (somme des valeurs, peut être < 0).
-- Idempotent (procédure INFORMATION_SCHEMA).
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS _add_votes_idees_valeur;
DELIMITER //
CREATE PROCEDURE _add_votes_idees_valeur()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'votes_idees'
          AND COLUMN_NAME  = 'valeur'
    ) THEN
        ALTER TABLE votes_idees
            ADD COLUMN valeur TINYINT NOT NULL DEFAULT 1 AFTER id_utilisateur;
    END IF;
END //
DELIMITER ;
CALL _add_votes_idees_valeur();
DROP PROCEDURE IF EXISTS _add_votes_idees_valeur;

-- ─────────────────────────────────────────────────────────────────────────────
-- 024 — Couleur d'accent des plans d'abonnement
--
-- Ajoute une colonne `couleur` (hex #RRGGBB) à la table abonnements pour permettre
-- à l'admin de personnaliser l'apparence de chaque plan sur la page de gestion.
-- Idempotent : ajout de colonne conditionnel + backfill de couleurs par défaut.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonnements' AND COLUMN_NAME = 'couleur');
SET @sql := IF(@col = 0,
    'ALTER TABLE abonnements ADD COLUMN couleur VARCHAR(7) NULL AFTER description',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill : couleurs par défaut selon le prix (gratuit → gris, sinon dégradé).
UPDATE abonnements SET couleur = '#6b7280' WHERE couleur IS NULL AND prix_mensuel = 0;
UPDATE abonnements SET couleur = '#18607D' WHERE couleur IS NULL AND prix_mensuel > 0 AND prix_mensuel < 40;
UPDATE abonnements SET couleur = '#244F26' WHERE couleur IS NULL AND prix_mensuel >= 40;
UPDATE abonnements SET couleur = '#244F26' WHERE couleur IS NULL;

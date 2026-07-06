-- ─────────────────────────────────────────────────────────────────────────────
-- 025 — Privilèges granulaires des plans d'abonnement
--
-- Passe d'un gating grossier (Essential/Expert codés en dur) à des privilèges
-- individuels réellement vérifiés côté API :
--   dashboard_mensuel   → accès au tableau de bord d'activité 30 jours
--   export_pdf          → export PDF du tableau de bord annuel
--   alertes_actives     → accès aux alertes d'annonces par matériau
--   alertes_push        → alertes/notifs via OneSignal (push), pas seulement email
--   publicites_actives  → accès à la promotion / sponsoring (publicités)
-- (dashboard_annuel, badges_actives, nb_alertes_max, rayon_alerte_max_km existent déjà)
--
-- Backfill conforme au descriptif fonctionnel :
--   Freemium  : aucun de ces privilèges
--   Essential : dashboard 30j + alertes (3 / 10km) + publicités
--   Expert    : tout, alertes illimitées + push
-- Idempotent.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'abonnements' AND COLUMN_NAME = 'dashboard_mensuel');
SET @sql := IF(@has = 0,
    'ALTER TABLE abonnements
        ADD COLUMN dashboard_mensuel  TINYINT(1) NOT NULL DEFAULT 0 AFTER badges_actives,
        ADD COLUMN export_pdf         TINYINT(1) NOT NULL DEFAULT 0 AFTER dashboard_mensuel,
        ADD COLUMN alertes_actives    TINYINT(1) NOT NULL DEFAULT 0 AFTER export_pdf,
        ADD COLUMN alertes_push       TINYINT(1) NOT NULL DEFAULT 0 AFTER alertes_actives,
        ADD COLUMN publicites_actives TINYINT(1) NOT NULL DEFAULT 0 AFTER alertes_push',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expert (plans ayant déjà dashboard annuel ou badges) : tous les privilèges.
UPDATE abonnements
   SET dashboard_mensuel = 1, export_pdf = 1, alertes_actives = 1, alertes_push = 1, publicites_actives = 1
 WHERE dashboard_annuel = 1 OR badges_actives = 1;

-- Essential (plans payants non-Expert) : dashboard 30j, alertes, publicités.
UPDATE abonnements
   SET dashboard_mensuel = 1, alertes_actives = 1, publicites_actives = 1
 WHERE prix_mensuel > 0 AND dashboard_annuel = 0 AND badges_actives = 0;

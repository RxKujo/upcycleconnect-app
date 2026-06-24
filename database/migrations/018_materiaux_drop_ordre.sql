-- ─────────────────────────────────────────────────────────────────────────────
-- 018 — Suppression du champ ordre des matériaux (inutile, tri par libellé)
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE materiaux DROP COLUMN ordre;

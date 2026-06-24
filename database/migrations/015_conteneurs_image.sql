-- ─────────────────────────────────────────────────────────────────────────────
-- 015 — Photo du conteneur
-- URL d'une image illustrant le point de dépôt (façade, repère visuel).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE conteneurs
  ADD COLUMN image_url VARCHAR(500) NULL AFTER longitude;

-- ─────────────────────────────────────────────────────────────────────────────
-- Traductions anglaises (EN) — « fichier de langues » versionné.
-- Alimente la table `translations` (moteur i18n existant : data-i18n côté vues).
-- Idempotent : on supprime les clés listées puis on réinsère.
-- Réappliquer après ajout de nouvelles clés (charset utf8mb4 obligatoire, sinon
-- les caractères comme … et ' sont corrompus) :
--   docker exec -i uc_mysql mysql --default-character-set=utf8mb4 -u<user> -p<pass> upcycleconnect < database/seed_i18n_en.sql
-- ─────────────────────────────────────────────────────────────────────────────

SET @en := (SELECT id_langue FROM langue WHERE code_iso = 'en' LIMIT 1);

-- ── LOT 1 : Marketplace (public/marche/index) + statuts communs ──────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'market.kicker','market.title','market.subtitle','market.search',
  'market.filter.all','market.filter.don','market.filter.vente',
  'market.nophoto','market.empty.title','market.empty.body',
  'status.don','status.vente','status.free'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('market.kicker',      @en, 'Marketplace'),
('market.title',       @en, 'The Marketplace'),
('market.subtitle',    @en, 'Browse the community''s donation and sale listings'),
('market.search',      @en, 'Search a listing…'),
('market.filter.all',  @en, 'All'),
('market.filter.don',  @en, 'Donations'),
('market.filter.vente',@en, 'Sales'),
('market.nophoto',     @en, 'NO PHOTO'),
('market.empty.title', @en, 'No listings'),
('market.empty.body',  @en, 'Listings will appear here once approved by the team.'),
('status.don',         @en, 'Donation'),
('status.vente',       @en, 'Sale'),
('status.free',        @en, 'Free');

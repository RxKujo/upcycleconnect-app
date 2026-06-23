-- ─────────────────────────────────────────────────────────────────────────────
-- 014 — Ajout du statut 'expiree' aux commandes
-- Une commande en_conteneur dont le délai de récupération (7 j) est dépassé
-- bascule en 'expiree' (worker horaire) : elle quitte la liste "en attente"
-- et apparaît dans l'historique du pro.
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE commandes
  MODIFY COLUMN statut
  ENUM('commandee','deposee','en_conteneur','recuperee','annulee','expiree')
  NOT NULL DEFAULT 'commandee';

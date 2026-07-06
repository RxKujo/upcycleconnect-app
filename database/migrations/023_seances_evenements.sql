-- ─────────────────────────────────────────────────────────────────────────────
-- 023 — Séances d'événements (formations multi-jours / multi-créneaux)
--
-- Un événement peut désormais contenir PLUSIEURS séances (comme une vraie
-- formation : Lun 9h-12h, Mar 14h-17h, Jeu 9h-11h…). Chaque séance porte ses
-- propres dates/heures, son format, son lieu et ses animateurs.
--
-- La capacité (nb_places_total) et le prix restent GLOBAUX au niveau de
-- l'événement (inscription globale = on s'inscrit à toute la formation).
--
-- Les colonnes evenements.date_debut / date_fin / format / lieu sont conservées
-- et servent d'« enveloppe » calculée (min début / max fin des séances) pour ne
-- pas casser le catalogue et les listes existantes.
--
-- Idempotent : CREATE TABLE IF NOT EXISTS + backfill conditionnel.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS seances_evenements (
    id_seance    INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT NOT NULL,
    titre        VARCHAR(200) NULL,
    format       ENUM('presentiel','distanciel') NOT NULL DEFAULT 'presentiel',
    lieu         VARCHAR(300) NULL,
    date_debut   DATETIME NOT NULL,
    date_fin     DATETIME NOT NULL,
    ordre        INT NOT NULL DEFAULT 0,
    KEY idx_seances_evenement (id_evenement),
    FOREIGN KEY (id_evenement) REFERENCES evenements(id_evenement) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS animateurs_seances (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_seance  INT NOT NULL,
    id_salarie INT NOT NULL,
    UNIQUE KEY uniq_animateur_seance (id_seance, id_salarie),
    FOREIGN KEY (id_seance)  REFERENCES seances_evenements(id_seance) ON DELETE CASCADE,
    FOREIGN KEY (id_salarie) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Backfill : tout événement existant sans séance obtient une séance unique
--    reprenant ses date/format/lieu (rétro-compatibilité).
INSERT INTO seances_evenements (id_evenement, titre, format, lieu, date_debut, date_fin, ordre)
SELECT e.id_evenement, NULL, e.format, e.lieu, e.date_debut, e.date_fin, 0
FROM evenements e
LEFT JOIN seances_evenements s ON s.id_evenement = e.id_evenement
WHERE s.id_seance IS NULL;

-- ── Backfill : reporter les animateurs de l'événement sur sa 1ʳᵉ séance.
INSERT IGNORE INTO animateurs_seances (id_seance, id_salarie)
SELECT s.id_seance, ae.id_salarie
FROM animateurs_evenements ae
JOIN seances_evenements s
  ON s.id_evenement = ae.id_evenement AND s.ordre = 0;

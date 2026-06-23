-- Migration 013 : Module Salariés & Administration — lot 2
-- Tickets : boîte à idées (votes), multilingue (translations),
--           supervision notifications (log), pilotage financier (transactions)
USE upcycleconnect;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. VOTES IDÉES — suivi individuel des votes pour éviter le double-vote
--    La table boite_idees existe depuis la migration 001 (nb_votes = compteur).
--    votes_idees trace QUI a voté, pour pouvoir toggler et éviter les doublons.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS votes_idees (
    id_vote        INT AUTO_INCREMENT PRIMARY KEY,
    id_idee        INT NOT NULL,
    id_utilisateur INT NOT NULL,
    date_vote      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vote (id_idee, id_utilisateur),
    FOREIGN KEY (id_idee)        REFERENCES boite_idees(id_idee)        ON DELETE CASCADE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT 'Votes individuels sur la boîte à idées — empêche le double-vote';

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. TAGS BOÎTE À IDÉES — catégorisation libre des idées
-- ─────────────────────────────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS _add_boite_idees_tags;
DELIMITER //
CREATE PROCEDURE _add_boite_idees_tags()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'boite_idees'
          AND COLUMN_NAME  = 'tags'
    ) THEN
        ALTER TABLE boite_idees ADD COLUMN tags VARCHAR(300) NULL AFTER contenu;
    END IF;
END //
DELIMITER ;
CALL _add_boite_idees_tags();
DROP PROCEDURE IF EXISTS _add_boite_idees_tags;

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. TRADUCTIONS — interface multilingue no-code
--    La table langue existe depuis la migration 001 (code_iso, libelle, est_active).
--    translations stocke les libellés d'interface clé→valeur par langue.
--    Ajout de conformité accessibilité WCAG 2.1 via le flag RTL.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS translations (
    id_translation INT AUTO_INCREMENT PRIMARY KEY,
    cle            VARCHAR(200) NOT NULL
      COMMENT 'Clé unique de libellé UI, ex: nav.home, btn.save',
    id_langue      INT          NOT NULL,
    valeur         TEXT         NOT NULL,
    date_mise_a_jour DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
      ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_trad (cle, id_langue),
    INDEX idx_cle    (cle),
    INDEX idx_langue (id_langue),
    FOREIGN KEY (id_langue) REFERENCES langue(id_langue) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT 'Traductions des libellés UI — gestion no-code depuis le back-office admin';

-- Ajout du flag RTL sur la table langue (accessibilité WCAG 2.1 — direction du texte)
DROP PROCEDURE IF EXISTS _add_langue_rtl;
DELIMITER //
CREATE PROCEDURE _add_langue_rtl()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = 'langue'
          AND COLUMN_NAME  = 'rtl'
    ) THEN
        ALTER TABLE langue
          ADD COLUMN rtl TINYINT(1) NOT NULL DEFAULT 0
          COMMENT 'Right-to-Left : 1 pour arabe, hébreu — conformité WCAG 2.1 §1.4.8';
    END IF;
END //
DELIMITER ;
CALL _add_langue_rtl();
DROP PROCEDURE IF EXISTS _add_langue_rtl;

-- Seed initial : traductions FR pour les libellés clés
INSERT IGNORE INTO translations (cle, id_langue, valeur)
SELECT cle, l.id_langue, valeur
FROM (
    SELECT 'nav.home'          AS cle, 'Accueil'           AS valeur UNION ALL
    SELECT 'nav.market',              'Marché'                       UNION ALL
    SELECT 'nav.events',              'Événements'                   UNION ALL
    SELECT 'nav.conseils',            'Conseils'                     UNION ALL
    SELECT 'nav.forum',               'Forum'                        UNION ALL
    SELECT 'btn.save',                'Enregistrer'                  UNION ALL
    SELECT 'btn.cancel',              'Annuler'                      UNION ALL
    SELECT 'btn.delete',              'Supprimer'                    UNION ALL
    SELECT 'btn.edit',                'Modifier'                     UNION ALL
    SELECT 'btn.publish',             'Publier'                      UNION ALL
    SELECT 'btn.draft',               'Brouillon'                    UNION ALL
    SELECT 'status.pending',          'En attente'                   UNION ALL
    SELECT 'status.validated',        'Validé'                       UNION ALL
    SELECT 'status.refused',          'Refusé'
) AS seeds
CROSS JOIN langue l
WHERE l.code_iso = 'fr';

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. JOURNAL NOTIFICATIONS — supervision admin des envois push/email
--    Ne remplace pas la table notifications (destinée aux utilisateurs).
--    notifications_envoi_log trace TOUS les envois pour l'audit admin.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications_envoi_log (
    id_log          INT AUTO_INCREMENT PRIMARY KEY,
    type_envoi      ENUM('push','email','groupe_push','groupe_email') NOT NULL,
    id_envoyeur     INT  NULL COMMENT 'Admin déclencheur — NULL si envoi automatique',
    id_destinataire INT  NULL COMMENT 'NULL si envoi groupé (segment)',
    segment         VARCHAR(200) NULL COMMENT 'Critère de segmentation ex: site_id=3',
    titre           VARCHAR(200) NOT NULL,
    contenu         TEXT         NOT NULL,
    nb_destinataires INT NOT NULL DEFAULT 1,
    statut          ENUM('envoye','erreur') NOT NULL DEFAULT 'envoye',
    erreur_detail   TEXT NULL,
    date_envoi      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_envoi   (date_envoi),
    INDEX idx_type_envoi   (type_envoi),
    INDEX idx_destinataire (id_destinataire),
    FOREIGN KEY (id_envoyeur)     REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL,
    FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT 'Journal d''audit des envois de notifications — supervision back-office';

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. TRANSACTIONS — pilotage financier en temps réel
--    Centralize les revenus Stripe (abonnements, commandes, événements, pubs).
--    La table factures existante reste la source de vérité légale/comptable ;
--    transactions sert à l'agrégation temps-réel par source.
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transactions (
    id_transaction    INT AUTO_INCREMENT PRIMARY KEY,
    stripe_event_id   VARCHAR(255) NULL UNIQUE
      COMMENT 'ID de l''événement Stripe (idempotence webhook)',
    stripe_payment_id VARCHAR(255) NULL,
    id_utilisateur    INT          NOT NULL,
    type_source       ENUM('abonnement_particulier','abonnement_pro','commande','evenement','publicite')
                      NOT NULL,
    montant_ht        DECIMAL(10,2) NOT NULL,
    montant_ttc       DECIMAL(10,2) NOT NULL,
    taux_tva          DECIMAL(5,2)  NOT NULL DEFAULT 20.00,
    devise            CHAR(3)       NOT NULL DEFAULT 'EUR',
    statut            ENUM('paye','rembourse','dispute','en_attente') NOT NULL DEFAULT 'paye',
    description       VARCHAR(300) NULL,
    date_transaction  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date_tx   (date_transaction),
    INDEX idx_source_tx (type_source),
    INDEX idx_user_tx   (id_utilisateur),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT 'Pilotage financier — agrégat temps-réel des flux Stripe par source';

-- Index de performance sur factures pour le pilotage financier
DROP PROCEDURE IF EXISTS _add_indexes_013;
DELIMITER //
CREATE PROCEDURE _add_indexes_013()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factures' AND INDEX_NAME='idx_factures_date') THEN
        ALTER TABLE factures ADD INDEX idx_factures_date (date_emission);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factures' AND INDEX_NAME='idx_factures_type') THEN
        ALTER TABLE factures ADD INDEX idx_factures_type (type_facture);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='factures' AND INDEX_NAME='idx_factures_user') THEN
        ALTER TABLE factures ADD INDEX idx_factures_user (id_utilisateur);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='souscriptions' AND INDEX_NAME='idx_souscriptions_active') THEN
        ALTER TABLE souscriptions ADD INDEX idx_souscriptions_active (est_active);
    END IF;
END //
DELIMITER ;
CALL _add_indexes_013();
DROP PROCEDURE IF EXISTS _add_indexes_013;

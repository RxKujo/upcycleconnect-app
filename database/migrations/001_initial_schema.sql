CREATE DATABASE IF NOT EXISTS upcycleconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE upcycleconnect;

-- =============================================
-- LANGUE
-- =============================================
CREATE TABLE langue (
    id_langue  INT AUTO_INCREMENT PRIMARY KEY,
    code_iso   CHAR(5) NOT NULL UNIQUE,
    libelle    VARCHAR(100) NOT NULL,
    est_active BOOLEAN DEFAULT TRUE,
    date_ajout DATETIME NOT NULL DEFAULT NOW()
);

-- =============================================
-- SITE_UC
-- =============================================
CREATE TABLE site_uc (
    id_site     INT AUTO_INCREMENT PRIMARY KEY,
    nom_site    VARCHAR(200) NOT NULL,
    adresse     TEXT,
    ville       VARCHAR(100),
    code_postal VARCHAR(10)
);

-- =============================================
-- UTILISATEUR
-- =============================================
CREATE TABLE utilisateurs (
    id_utilisateur       INT AUTO_INCREMENT PRIMARY KEY,
    nom                  VARCHAR(100) NOT NULL,
    prenom               VARCHAR(100) NOT NULL,
    email                VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe_hash    VARCHAR(255) NOT NULL,
    telephone            VARCHAR(20),
    ville                VARCHAR(100),
    adresse_complete     TEXT,
    photo_profil_url     VARCHAR(500),
    role                 ENUM('particulier','professionnel','salarie','admin') NOT NULL,
    est_banni            BOOLEAN DEFAULT FALSE,
    date_fin_ban         DATETIME NULL,
    notif_push_active    BOOLEAN DEFAULT TRUE,
    onesignal_player_id  VARCHAR(255),
    nom_entreprise       VARCHAR(200) NULL,
    upcycling_score      INT NULL DEFAULT 0,
    est_certifie         BOOLEAN NULL,
    tutoriel_effectue    BOOLEAN NULL,
    numero_siret         CHAR(14) NULL UNIQUE,
    siret_verifie        BOOLEAN NULL,
    latitude_entreprise  DECIMAL(10,7) NULL,
    longitude_entreprise DECIMAL(10,7) NULL,
    poste                VARCHAR(150) NULL,
    id_langue            INT NULL,
    id_site_uc           INT NULL,
    date_creation        DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_langue)  REFERENCES langue(id_langue),
    FOREIGN KEY (id_site_uc) REFERENCES site_uc(id_site)
);

-- =============================================
-- ABONNEMENT
-- =============================================
CREATE TABLE abonnements (
    id_abonnement       INT AUTO_INCREMENT PRIMARY KEY,
    nom                 VARCHAR(100) NOT NULL,
    prix_mensuel        DECIMAL(10,2) NOT NULL,
    type_cible          ENUM('particulier','professionnel') NOT NULL,
    description         TEXT,
    nb_alertes_max      INT NULL,
    rayon_alerte_max_km INT NULL,
    dashboard_annuel    BOOLEAN DEFAULT FALSE,
    badges_actives      BOOLEAN DEFAULT FALSE
);

-- =============================================
-- SOUSCRIPTION
-- =============================================
CREATE TABLE souscriptions (
    id_souscription        INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur         INT NOT NULL,
    id_abonnement          INT NOT NULL,
    date_debut             DATETIME NOT NULL DEFAULT NOW(),
    date_fin               DATETIME NULL,
    est_active             BOOLEAN NOT NULL DEFAULT TRUE,
    stripe_subscription_id VARCHAR(255),
    gere_par_admin         BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_abonnement)  REFERENCES abonnements(id_abonnement)
);

-- =============================================
-- FACTURE
-- =============================================
CREATE TABLE factures (
    id_facture        INT AUTO_INCREMENT PRIMARY KEY,
    numero_facture    VARCHAR(50) NOT NULL UNIQUE,
    id_utilisateur    INT NOT NULL,
    montant_ht        DECIMAL(10,2) NOT NULL,
    montant_ttc       DECIMAL(10,2) NOT NULL,
    type_facture      ENUM('abonnement','commande','evenement','publicite') NOT NULL,
    service           VARCHAR(100),
    date_emission     DATETIME NOT NULL DEFAULT NOW(),
    pdf_url           VARCHAR(500),
    stripe_payment_id VARCHAR(255),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- PUBLICITE
-- =============================================
CREATE TABLE publicites (
    id_publicite     INT AUTO_INCREMENT PRIMARY KEY,
    id_professionnel INT NOT NULL,
    titre            VARCHAR(200) NOT NULL,
    visuel_url       VARCHAR(500),
    url_cible        VARCHAR(500),
    date_debut       DATETIME,
    date_fin         DATETIME,
    statut           ENUM('en_attente','validee','refusee','active','expiree') NOT NULL DEFAULT 'en_attente',
    nb_clics         INT DEFAULT 0,
    nb_vues          INT DEFAULT 0,
    cout_mensuel     DECIMAL(10,2),
    valide_par       INT NULL,
    FOREIGN KEY (id_professionnel) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (valide_par)       REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- ANNONCE
-- =============================================
CREATE TABLE annonces (
    id_annonce     INT AUTO_INCREMENT PRIMARY KEY,
    id_particulier INT NOT NULL,
    titre          VARCHAR(200) NOT NULL,
    description    TEXT NOT NULL,
    type_annonce   ENUM('don','vente') NOT NULL,
    prix           DECIMAL(10,2) NULL,
    mode_remise    ENUM('conteneur','main_propre') NOT NULL,
    statut         ENUM('en_attente','validee','refusee','annulee','vendue') NOT NULL DEFAULT 'en_attente',
    motif_refus    TEXT NULL,
    motif_retrait  TEXT NULL,
    date_creation  DATETIME NOT NULL DEFAULT NOW(),
    valide_par     INT NULL,
    FOREIGN KEY (id_particulier) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (valide_par)     REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- OBJET_ANNONCE
-- =============================================
CREATE TABLE objets_annonces (
    id_objet   INT AUTO_INCREMENT PRIMARY KEY,
    id_annonce INT NOT NULL,
    categorie  VARCHAR(100),
    materiau   ENUM('bois','metal','textile','plastique','verre','electronique','autre') NOT NULL,
    etat       ENUM('neuf','bon','use','a_reparer') NOT NULL,
    poids_kg   DECIMAL(8,3) NULL,
    FOREIGN KEY (id_annonce) REFERENCES annonces(id_annonce) ON DELETE CASCADE
);

-- =============================================
-- PHOTO_OBJET
-- =============================================
CREATE TABLE photos_objets (
    id_photo  INT AUTO_INCREMENT PRIMARY KEY,
    id_objet  INT NOT NULL,
    url_photo VARCHAR(500) NOT NULL,
    ordre     TINYINT DEFAULT 0,
    FOREIGN KEY (id_objet) REFERENCES objets_annonces(id_objet) ON DELETE CASCADE
);

-- =============================================
-- CONTENEUR
-- =============================================
CREATE TABLE conteneurs (
    id_conteneur  INT AUTO_INCREMENT PRIMARY KEY,
    conteneur_ref VARCHAR(50) NOT NULL UNIQUE,
    adresse       TEXT NOT NULL,
    ville         VARCHAR(100) NOT NULL,
    code_postal   VARCHAR(10),
    latitude      DECIMAL(10,7),
    longitude     DECIMAL(10,7),
    capacite      INT NOT NULL DEFAULT 20,
    statut        ENUM('actif','plein','maintenance','hors_service') NOT NULL DEFAULT 'actif'
);

-- =============================================
-- COMMANDE
-- =============================================
CREATE TABLE commandes (
    id_commande              INT AUTO_INCREMENT PRIMARY KEY,
    id_annonce               INT NOT NULL,
    id_acheteur              INT NOT NULL,
    id_conteneur             INT NULL,
    commission_pct           DECIMAL(5,2) NOT NULL,
    montant_commission       DECIMAL(10,2) NOT NULL,
    date_limite_recuperation DATETIME NULL,
    stripe_payment_intent    VARCHAR(255),
    date_commande            DATETIME NOT NULL DEFAULT NOW(),
    statut                   ENUM('commandee','deposee','en_conteneur','recuperee','annulee') NOT NULL DEFAULT 'commandee',
    FOREIGN KEY (id_annonce)    REFERENCES annonces(id_annonce),
    FOREIGN KEY (id_acheteur)   REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_conteneur)  REFERENCES conteneurs(id_conteneur)
);

-- =============================================
-- CODE_BARRE
-- =============================================
CREATE TABLE codes_barres (
    id_code_barre    INT AUTO_INCREMENT PRIMARY KEY,
    id_commande      INT NOT NULL,
    code_valeur      VARCHAR(100) NOT NULL UNIQUE,
    type_code        ENUM('depot_particulier','recuperation_pro') NOT NULL,
    date_creation    DATETIME NOT NULL DEFAULT NOW(),
    date_utilisation DATETIME NULL,
    pdf_url          VARCHAR(500),
    FOREIGN KEY (id_commande) REFERENCES commandes(id_commande)
);

-- =============================================
-- ALERTE_MATERIAU
-- =============================================
CREATE TABLE alertes_materiaux (
    id_alerte        INT AUTO_INCREMENT PRIMARY KEY,
    id_professionnel INT NOT NULL,
    materiau         ENUM('bois','metal','textile','plastique','verre','electronique','autre') NOT NULL,
    rayon_km         INT NOT NULL DEFAULT 10,
    est_active       BOOLEAN DEFAULT TRUE,
    date_creation    DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_professionnel) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
);

-- =============================================
-- TEMPLATE_EVENEMENT
-- =============================================
CREATE TABLE templates_evenements (
    id_template  INT AUTO_INCREMENT PRIMARY KEY,
    nom_template VARCHAR(150) NOT NULL,
    description  TEXT,
    modele       JSON
);

-- =============================================
-- EVENEMENT
-- =============================================
CREATE TABLE evenements (
    id_evenement    INT AUTO_INCREMENT PRIMARY KEY,
    id_createur     INT NOT NULL,
    id_template     INT NULL,
    titre           VARCHAR(200) NOT NULL,
    description     TEXT NOT NULL,
    type_evenement  ENUM('formation','atelier','conference') NOT NULL,
    format          ENUM('presentiel','distanciel') NOT NULL,
    lieu            VARCHAR(300),
    date_debut      DATETIME NOT NULL,
    date_fin        DATETIME NOT NULL,
    nb_places_total INT NOT NULL,
    nb_places_dispo INT NOT NULL,
    prix            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut          ENUM('en_attente','valide','refuse','annule','termine') NOT NULL DEFAULT 'en_attente',
    valide_par      INT NULL,
    rappel_envoye   BOOLEAN DEFAULT FALSE,
    date_creation   DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_createur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_template) REFERENCES templates_evenements(id_template),
    FOREIGN KEY (valide_par)  REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- ANIMATEUR_EVENEMENT
-- =============================================
CREATE TABLE animateurs_evenements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT NOT NULL,
    id_salarie   INT NOT NULL,
    FOREIGN KEY (id_evenement) REFERENCES evenements(id_evenement) ON DELETE CASCADE,
    FOREIGN KEY (id_salarie)   REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- INSCRIPTION_EVENEMENT
-- =============================================
CREATE TABLE inscriptions_evenements (
    id_inscription    INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur    INT NOT NULL,
    id_evenement      INT NOT NULL,
    date_inscription  DATETIME NOT NULL DEFAULT NOW(),
    statut_paiement   ENUM('gratuit','paye','rembourse') NOT NULL DEFAULT 'gratuit',
    stripe_payment    VARCHAR(255) NULL,
    prix_paye         DECIMAL(10,2),
    avis_satisfaction TINYINT NULL COMMENT '1 à 5',
    commentaire_avis  TEXT NULL,
    UNIQUE KEY unique_inscription (id_evenement, id_utilisateur),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_evenement)   REFERENCES evenements(id_evenement)
);

-- =============================================
-- ARTICLE_NEWS
-- =============================================
CREATE TABLE articles_news (
    id_article       INT AUTO_INCREMENT PRIMARY KEY,
    id_auteur        INT NOT NULL,
    titre            VARCHAR(300) NOT NULL,
    contenu          LONGTEXT NOT NULL,
    categorie        VARCHAR(100),
    statut           ENUM('brouillon','publie','archive') NOT NULL DEFAULT 'brouillon',
    date_publication DATETIME NULL,
    FOREIGN KEY (id_auteur) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- PLANNING_UTILISATEUR
-- =============================================
CREATE TABLE planning_utilisateurs (
    id_planning    INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    titre_creneau  VARCHAR(200) NOT NULL,
    date_debut     DATETIME NOT NULL,
    date_fin       DATETIME NOT NULL,
    type_creneau   ENUM('evenement','reunion','travail','perso') NOT NULL DEFAULT 'perso',
    id_evenement   INT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_evenement)   REFERENCES evenements(id_evenement) ON DELETE SET NULL
);

-- =============================================
-- FORUM_SUJET
-- =============================================
CREATE TABLE forum_sujets (
    id_sujet      INT AUTO_INCREMENT PRIMARY KEY,
    id_createur   INT NOT NULL,
    titre         VARCHAR(300) NOT NULL,
    categorie     VARCHAR(100),
    statut        ENUM('ouvert','ferme','signale','supprime') NOT NULL DEFAULT 'ouvert',
    date_creation DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_createur) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- FORUM_MESSAGE
-- =============================================
CREATE TABLE forum_messages (
    id_message        INT AUTO_INCREMENT PRIMARY KEY,
    id_sujet          INT NOT NULL,
    id_auteur         INT NOT NULL,
    contenu           TEXT NOT NULL,
    est_signale       BOOLEAN DEFAULT FALSE,
    date_publication  DATETIME NOT NULL DEFAULT NOW(),
    id_parent_message INT NULL,
    FOREIGN KEY (id_sujet)          REFERENCES forum_sujets(id_sujet) ON DELETE CASCADE,
    FOREIGN KEY (id_auteur)         REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_parent_message) REFERENCES forum_messages(id_message)
);

-- =============================================
-- MOT_BANNI
-- =============================================
CREATE TABLE mots_bannis (
    id_mot     INT AUTO_INCREMENT PRIMARY KEY,
    mot        VARCHAR(100) NOT NULL UNIQUE,
    ajoute_par INT NOT NULL,
    date_ajout DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (ajoute_par) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- SIGNALEMENT_FORUM
-- =============================================
CREATE TABLE signalements_forum (
    id_signalement   INT AUTO_INCREMENT PRIMARY KEY,
    id_message       INT NOT NULL,
    id_signaleur     INT NOT NULL,
    motif            TEXT,
    date_signalement DATETIME NOT NULL DEFAULT NOW(),
    glpi_ticket_id   VARCHAR(100) NULL,
    statut           ENUM('en_cours','traite','rejete') NOT NULL DEFAULT 'en_cours',
    FOREIGN KEY (id_message)   REFERENCES forum_messages(id_message),
    FOREIGN KEY (id_signaleur) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- BOITE_IDEES
-- =============================================
CREATE TABLE boite_idees (
    id_idee          INT AUTO_INCREMENT PRIMARY KEY,
    id_auteur        INT NOT NULL,
    titre            VARCHAR(200) NOT NULL,
    contenu          TEXT NOT NULL,
    date_publication DATETIME NOT NULL DEFAULT NOW(),
    nb_votes         INT DEFAULT 0,
    FOREIGN KEY (id_auteur) REFERENCES utilisateurs(id_utilisateur)
);

-- =============================================
-- NOTIFICATION
-- =============================================
CREATE TABLE notifications (
    id_notif       INT AUTO_INCREMENT PRIMARY KEY,
    id_destinataire INT NOT NULL,
    type_notif     ENUM('push','email') NOT NULL DEFAULT 'push',
    sujet          VARCHAR(200) NOT NULL,
    contenu        TEXT NOT NULL,
    date_envoi     DATETIME NOT NULL DEFAULT NOW(),
    est_lu         BOOLEAN DEFAULT FALSE,
    contexte       ENUM('commande','evenement','annonce','systeme') NOT NULL DEFAULT 'systeme',
    FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
);

-- =============================================
-- BADGE
-- =============================================
CREATE TABLE badges (
    id_badge      INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(150) NOT NULL,
    description   TEXT,
    seuil_objets  INT NOT NULL,
    type_materiau ENUM('tous','bois','metal','textile','plastique','verre','electronique') NOT NULL DEFAULT 'tous',
    icone_url     VARCHAR(500)
);

-- =============================================
-- BADGE_UTILISATEUR
-- =============================================
CREATE TABLE badges_utilisateurs (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_badge       INT NOT NULL,
    date_obtention DATETIME NOT NULL DEFAULT NOW(),
    UNIQUE KEY unique_badge (id_utilisateur, id_badge),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_badge)       REFERENCES badges(id_badge)
);

-- =============================================
-- MATERIEL
-- =============================================
CREATE TABLE materiels (
    id_materiel    INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(200) NOT NULL,
    description    TEXT,
    etat           ENUM('neuf','bon','use','a_reparer') NOT NULL DEFAULT 'bon',
    est_disponible BOOLEAN DEFAULT TRUE,
    photo_url      VARCHAR(500),
    id_site        INT NULL,
    FOREIGN KEY (id_site) REFERENCES site_uc(id_site)
);

-- =============================================
-- RESERVATION_MATERIEL
-- =============================================
CREATE TABLE reservations_materiels (
    id_reservation   INT AUTO_INCREMENT PRIMARY KEY,
    id_materiel      INT NOT NULL,
    id_salarie       INT NOT NULL,
    id_evenement     INT NULL,
    date_reservation DATETIME NOT NULL DEFAULT NOW(),
    date_retour      DATETIME NULL,
    FOREIGN KEY (id_materiel)  REFERENCES materiels(id_materiel),
    FOREIGN KEY (id_salarie)   REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_evenement) REFERENCES evenements(id_evenement) ON DELETE SET NULL
);

-- =============================================
-- TICKET_INCIDENT
-- =============================================
CREATE TABLE tickets_incidents (
    id_ticket       INT AUTO_INCREMENT PRIMARY KEY,
    glpi_ticket_id  VARCHAR(100),
    id_signaleur    INT NOT NULL,
    id_conteneur    INT NULL,
    sujet           VARCHAR(200) NOT NULL,
    description     TEXT NOT NULL,
    statut          ENUM('ouvert','en_cours','resolu','ferme') NOT NULL DEFAULT 'ouvert',
    date_creation   DATETIME NOT NULL DEFAULT NOW(),
    date_resolution DATETIME NULL,
    FOREIGN KEY (id_signaleur) REFERENCES utilisateurs(id_utilisateur),
    FOREIGN KEY (id_conteneur) REFERENCES conteneurs(id_conteneur)
);

-- =============================================
-- CATEGORIE_PRESTATION
-- =============================================
CREATE TABLE categories_prestations (
    id_categorie INT AUTO_INCREMENT PRIMARY KEY,
    nom          VARCHAR(150) NOT NULL,
    description  TEXT,
    date_creation DATETIME NOT NULL DEFAULT NOW()
);

-- =============================================
-- PRESTATION
-- =============================================
CREATE TABLE prestations (
    id_prestation INT AUTO_INCREMENT PRIMARY KEY,
    id_categorie  INT NOT NULL,
    titre         VARCHAR(200) NOT NULL,
    description   TEXT,
    prix          DECIMAL(10,2) NOT NULL,
    statut        ENUM('en_attente', 'validee', 'refusee') NOT NULL DEFAULT 'en_attente',
    date_creation DATETIME NOT NULL DEFAULT NOW(),
    FOREIGN KEY (id_categorie) REFERENCES categories_prestations(id_categorie)
);

-- =============================================
-- SEED DATA
-- =============================================
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, role) 
VALUES ('Administrateur', 'UC', 'admin@upcycleconnect.com', '$2y$10$FcUsEAi0fIgjMBLOrMIDKe0OBggZADA2BQjmXpV0tC9ZSH7.2t88u', 'admin');

INSERT INTO categories_prestations (nom, description) VALUES 
('Couture & Retouche', 'Services de reparation et transformation textile'),
('Menuiserie & Bois', 'Restauration et transformation de meubles en bois'),
('Electronique & High-Tech', 'Reparation et detournement d''objets connectes'),
('Decoration & Design', 'Creation d''objets deco a partir de materiaux de recuperation');

-- =============================================
-- SEED PRESTATIONS
-- =============================================
INSERT INTO prestations (id_categorie, titre, description, prix, statut) VALUES
(1, 'Reparation Veste Denim', 'Reparation de dechirures et remplacement de boutons sur vestes en jean.', 25.00, 'validee'),
(2, 'Restauration Table Basse', 'Ponçage, vernissage et reparation d''une table basse ancienne.', 85.00, 'validee'),
(3, 'Upgrade PC Recyclé', 'Optimisation d''un ordinateur ancien avec des composants de seconde main.', 120.00, 'validee'),
(4, 'Lampe Design Metal', 'Creation d''une lampe unique a partir de pieces metalliques de recuperation.', 45.00, 'en_attente');

-- =============================================
-- SEED EVENEMENTS
-- =============================================
INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut) VALUES
(1, 'Atelier Upcycling Textile', 'Apprenez a transformer vos vieux vetements en accessoires de mode.', 'atelier', 'presentiel', 'Atelier UC - Paris 11', '2026-05-15 14:00:00', '2026-05-15 17:00:00', 10, 10, 15.00, 'valide'),
(1, 'Conference Economie Circulaire', 'Comprendre les enjeux de l''upcycling dans l''industrie moderne.', 'conference', 'distanciel', 'Zoom / En ligne', '2026-06-01 10:00:00', '2026-06-01 12:00:00', 100, 100, 0.00, 'valide'),
(1, 'Formation Reparation Bois', 'Les bases de la menuiserie pour sauver vos meubles abimes.', 'formation', 'presentiel', 'FabLab UC - Lyon', '2026-06-10 09:00:00', '2026-06-10 18:00:00', 6, 6, 45.00, 'en_attente');

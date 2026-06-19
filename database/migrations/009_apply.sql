ALTER TABLE planning_utilisateurs ADD COLUMN id_catalogue_item INT NULL AFTER id_evenement;
ALTER TABLE planning_utilisateurs ADD COLUMN description TEXT NULL AFTER titre_creneau;
ALTER TABLE planning_utilisateurs ADD COLUMN est_manuel TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE planning_utilisateurs MODIFY COLUMN type_creneau ENUM('evenement','formation','reunion','travail','perso') NOT NULL DEFAULT 'perso';

CREATE TABLE IF NOT EXISTS tutoriel_etapes (
    id_etape INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    contenu TEXT NOT NULL,
    ordre INT NOT NULL DEFAULT 0,
    cible_element VARCHAR(200) NULL,
    position ENUM('top','bottom','left','right','center') NOT NULL DEFAULT 'center',
    icone VARCHAR(10) NULL,
    est_actif TINYINT(1) NOT NULL DEFAULT 1,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordre (ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS utilisateurs_tutoriels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    date_debut DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME NULL,
    termine TINYINT(1) NOT NULL DEFAULT 0,
    passe TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    UNIQUE KEY uniq_user_tuto (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tutoriel_etapes (titre, contenu, ordre, icone, position) VALUES
('Bienvenue sur UpcycleConnect', 'UpcycleConnect est la plateforme de reference pour donner une seconde vie a vos objets. Donnez, vendez ou echangez des objets upcycles et gagnez des points !', 1, '', 'center'),
('Votre Profil', 'Gerez vos informations, consultez vos achats, ventes et reservations de formations.', 2, '', 'center'),
('Le Marche', 'Parcourez les annonces de la communaute. Ajoutez des articles au panier et payez avec Stripe.', 3, '', 'center'),
('Deposer une Annonce', 'Publiez vos objets a donner ou vendre. Notre equipe valide chaque annonce avant publication.', 4, '', 'center'),
('Formations et Ateliers', 'Inscrivez-vous a nos ateliers pour apprendre les techniques dupcycling.', 5, '', 'center'),
('Votre Planning', 'Retrouvez tous vos evenements et formations dans votre planning personnel.', 6, '', 'center'),
('Votre Score Upcycling', 'A chaque achat, don ou participation, vous gagnez des points et progressez !', 7, '', 'center');

CREATE TABLE IF NOT EXISTS demandes_depot (
    id_demande INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_conteneur INT NULL,
    id_annonce INT NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    type_objet VARCHAR(100) NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    adresse_retrait VARCHAR(300) NULL,
    code_postal_retrait VARCHAR(10) NULL,
    ville_retrait VARCHAR(100) NULL,
    statut ENUM('en_attente','validee','refusee','code_envoye') NOT NULL DEFAULT 'en_attente',
    code_barre VARCHAR(64) NULL,
    motif_refus VARCHAR(500) NULL,
    date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME NULL,
    id_admin_traitement INT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_conteneur) REFERENCES conteneurs(id_conteneur) ON DELETE SET NULL,
    FOREIGN KEY (id_annonce) REFERENCES annonces(id_annonce) ON DELETE SET NULL,
    INDEX idx_statut (statut),
    INDEX idx_user (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO langue (code_iso, libelle, est_active) VALUES ('fr', 'Francais', 1), ('en', 'English', 1);

ALTER TABLE utilisateurs ADD COLUMN tuto_vu TINYINT(1) NOT NULL DEFAULT 0;

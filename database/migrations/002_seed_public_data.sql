USE upcycleconnect;

-- =============================================
-- SEED : Utilisateurs de test
-- Mot de passe : Test1234! (bcrypt hash)
-- =============================================
INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, role, upcycling_score, est_certifie) VALUES
('Rousseau', 'Sophie', 'sophie.rousseau@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0612345678', 'Paris', '12 rue des Lilas, 75011 Paris', 'particulier', 320, false),
('Martin', 'Lucas', 'lucas.martin@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0698765432', 'Lyon', '5 place Bellecour, 69002 Lyon', 'particulier', 750, true),
('Dubois', 'Camille', 'camille.dubois@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0645678901', 'Marseille', '28 avenue du Prado, 13008 Marseille', 'particulier', 150, false),
('Bernard', 'Emma', 'emma.bernard@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0678901234', 'Bordeaux', '3 cours de l''Intendance, 33000 Bordeaux', 'particulier', 45, false),
('Lefevre', 'Antoine', 'antoine.lefevre@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0656789012', 'Nantes', '15 rue Crébillon, 44000 Nantes', 'particulier', 580, true),
('Moreau', 'Julie', 'julie.moreau@email.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0634567890', 'Toulouse', '8 place du Capitole, 31000 Toulouse', 'particulier', 90, false),
('Petit', 'Thomas', 'thomas.petit@artisan.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0623456789', 'Paris', '42 rue du Faubourg Saint-Antoine, 75012 Paris', 'professionnel', 0, false),
('Garcia', 'Marie', 'marie.garcia@pro.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0687654321', 'Lyon', '10 rue de la République, 69001 Lyon', 'professionnel', 0, false),
('Durand', 'Pierre', 'pierre.durand@uc.com', '$2a$10$N9qo8uLOickgx2ZMRZoMye3Z4MjY0NjM0LjAwMAo', '0611223344', 'Paris', 'UpcycleConnect HQ', 'salarie', 0, false);

-- =============================================
-- Variables pour récupérer les IDs des utilisateurs
-- =============================================
SET @uid_admin   = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'admin@upcycleconnect.com' LIMIT 1);
SET @uid_sophie  = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'sophie.rousseau@email.com' LIMIT 1);
SET @uid_lucas   = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'lucas.martin@email.com' LIMIT 1);
SET @uid_camille = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'camille.dubois@email.com' LIMIT 1);
SET @uid_emma    = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'emma.bernard@email.com' LIMIT 1);
SET @uid_antoine = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'antoine.lefevre@email.com' LIMIT 1);
SET @uid_julie   = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'julie.moreau@email.com' LIMIT 1);
SET @uid_pierre  = (SELECT id_utilisateur FROM utilisateurs WHERE email = 'pierre.durand@uc.com' LIMIT 1);

-- =============================================
-- SEED : Annonces validées (marketplace publique)
-- =============================================
INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_lucas,   'Table basse en bois de palette', 'Belle table basse fabriquée à partir de palettes recyclées. Dimensions 120x60cm, hauteur 40cm. Finition vernis mat. Quelques marques d''usage qui ajoutent du caractère.', 'vente', 45.00, 'main_propre', 'validee', @uid_admin);
SET @ann_table = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_camille, 'Lot de tissus vintage pour couture', 'Collection de 8 coupons de tissus vintage (coton, lin, soie). Parfaits pour projets de couture, patchwork ou customisation. Entre 50cm et 1m chaque coupon.', 'vente', 25.00, 'conteneur', 'validee', @uid_admin);
SET @ann_tissus = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_lucas,   'Vieux vélo de course à restaurer', 'Vélo de course années 80, cadre acier Reynolds. Nécessite restauration complète : pneus, freins, selle. Cadre en bon état, pas de rouille. Idéal projet upcycling.', 'vente', 60.00, 'main_propre', 'validee', @uid_admin);
SET @ann_velo = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_emma,    'Planches de chêne récupérées', 'Lot de 6 planches de chêne massif récupérées d''une ancienne armoire. Dimensions variées, entre 80cm et 1m20. Bois de qualité, prêt à être retravaillé.', 'don', NULL, 'conteneur', 'validee', @uid_admin);
SET @ann_planches = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_antoine, 'Composants électroniques divers', 'Boîte de composants électroniques récupérés : résistances, condensateurs, LEDs, microcontrôleurs Arduino. Parfait pour projets DIY et prototypage.', 'don', NULL, 'main_propre', 'validee', @uid_admin);
SET @ann_elec = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_julie,   'Lot de bocaux en verre anciens', 'Collection de 15 bocaux en verre de différentes tailles (0.5L à 2L). Parfaits pour conserves maison, rangement ou décoration. Quelques-uns avec couvercle céramique.', 'vente', 12.00, 'conteneur', 'validee', @uid_admin);
SET @ann_bocaux = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_lucas,   'Chaises de bistrot à retapisser', 'Lot de 4 chaises de bistrot en bois massif. Structure solide, assises à retapisser. Style années 50, pieds compas. Un projet de restauration accessible.', 'vente', 80.00, 'main_propre', 'validee', @uid_admin);
SET @ann_chaises = LAST_INSERT_ID();

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@uid_emma,    'Boîtes de conserve décorées', 'Set de 10 grandes boîtes de conserve nettoyées et poncées, prêtes pour décoration ou rangement. Certaines déjà peintes avec motifs floraux.', 'don', NULL, 'conteneur', 'validee', @uid_admin);
SET @ann_boites = LAST_INSERT_ID();

-- =============================================
-- SEED : Objets pour chaque annonce
-- =============================================
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_table,    'Mobilier',    'bois',         'use',       15.0);
SET @obj_table = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_tissus,   'Textile',     'textile',      'bon',        2.5);
SET @obj_tissus = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_velo,     'Transport',   'metal',        'a_reparer', 12.0);
SET @obj_velo = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_planches, 'Mobilier',    'bois',         'bon',       20.0);
SET @obj_planches = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_elec,     'Électronique','electronique', 'bon',        1.5);
SET @obj_elec = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_bocaux,   'Vaisselle',   'verre',        'bon',        8.0);
SET @obj_bocaux = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_chaises,  'Mobilier',    'bois',         'use',       25.0);
SET @obj_chaises = LAST_INSERT_ID();
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES (@ann_boites,   'Décoration',  'metal',        'bon',        3.0);
SET @obj_boites = LAST_INSERT_ID();

-- =============================================
-- SEED : Photos pour les objets (URLs placeholder)
-- =============================================
INSERT INTO photos_objets (id_objet, url_photo, ordre) VALUES
(@obj_table,    'photos/table-palette-1.jpg', 1),
(@obj_table,    'photos/table-palette-2.jpg', 2),
(@obj_tissus,   'photos/tissus-vintage-1.jpg', 1),
(@obj_velo,     'photos/velo-course-1.jpg', 1),
(@obj_velo,     'photos/velo-course-2.jpg', 2),
(@obj_planches, 'photos/planches-chene-1.jpg', 1),
(@obj_elec,     'photos/composants-elec-1.jpg', 1),
(@obj_bocaux,   'photos/bocaux-verre-1.jpg', 1),
(@obj_chaises,  'photos/chaises-bistrot-1.jpg', 1),
(@obj_chaises,  'photos/chaises-bistrot-2.jpg', 2),
(@obj_boites,   'photos/boites-conserve-1.jpg', 1);

-- =============================================
-- SEED : Événements supplémentaires (futurs, validés)
-- =============================================
INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut) VALUES
(@uid_admin, 'Atelier Restauration de Meubles', 'Découvrez les techniques de base pour redonner vie à vos meubles anciens. Ponçage, teinture, vernis : repartez avec les clés pour transformer vos trouvailles.', 'atelier', 'presentiel', 'FabLab UC - Paris 11', '2026-05-20 14:00:00', '2026-05-20 17:00:00', 12, 12, 20.00, 'valide'),
(@uid_admin, 'Conférence Zéro Déchet au Quotidien', 'Comment adopter un mode de vie zéro déchet sans frustration ? Témoignages, astuces pratiques et échanges avec des experts du mouvement.', 'conference', 'distanciel', 'Zoom / En ligne', '2026-05-28 18:30:00', '2026-05-28 20:30:00', 200, 200, 0.00, 'valide'),
(@uid_admin, 'Formation Couture Upcycling', 'Transformez vos vieux vêtements en pièces uniques ! Cette formation de 2 jours vous apprend les bases de la couture créative à partir de textiles recyclés.', 'formation', 'presentiel', 'Atelier UC - Lyon', '2026-06-15 09:00:00', '2026-06-16 17:00:00', 8, 8, 75.00, 'valide'),
(@uid_admin, 'Atelier Électronique Récupération', 'Apprenez à récupérer et réutiliser les composants électroniques. Soudure, test, montage : créez vos propres gadgets à partir de matériel recyclé.', 'atelier', 'presentiel', 'FabLab UC - Nantes', '2026-07-05 10:00:00', '2026-07-05 16:00:00', 10, 10, 35.00, 'valide');

-- =============================================
-- SEED : Articles / Conseils
-- =============================================
INSERT INTO articles_news (id_auteur, titre, contenu, categorie, statut, date_publication) VALUES
(@uid_pierre, '5 astuces pour donner une seconde vie à vos meubles',
'Le bois est un matériau noble qui mérite une seconde chance. Voici 5 techniques simples pour transformer vos vieux meubles en pièces uniques.\n\n1. Le ponçage : la base de tout projet. Un bon ponçage révèle la beauté du bois caché sous les couches de peinture.\n\n2. La teinture naturelle : utilisez du café, du thé ou du vinaigre de cidre pour teindre le bois de manière écologique.\n\n3. Le découpage : collez des motifs de papier ou de tissu pour personnaliser vos meubles.\n\n4. Le pochoir : créez des motifs géométriques ou floraux avec des pochoirs et de la peinture.\n\n5. Le rempaillage : apprenez les bases du cannage pour redonner vie à vos chaises.',
'Bois', 'publie', '2026-04-01 10:00:00'),

(@uid_pierre, 'Guide complet du tri des métaux recyclables',
'Les métaux sont infiniment recyclables, ce qui en fait des matériaux précieux pour l''upcycling. Voici comment les identifier et les trier efficacement.\n\nLes métaux ferreux (acier, fonte) sont magnétiques. Utilisez un aimant pour les identifier rapidement.\n\nL''aluminium est léger et ne rouille pas. On le trouve dans les canettes, les cadres de vélo et les ustensiles de cuisine.\n\nLe cuivre a une couleur caractéristique et se trouve dans les câbles électriques et la plomberie.\n\nLe laiton est un alliage de cuivre et de zinc, souvent utilisé pour les poignées de porte et les instruments de musique.',
'Métal', 'publie', '2026-04-05 14:30:00'),

(@uid_pierre, 'Upcycling textile : transformer un jean en sac',
'Vous avez un vieux jean que vous ne portez plus ? Transformez-le en sac tendance en quelques étapes simples.\n\nMatériel nécessaire :\n- Un jean usagé\n- Des ciseaux de couture\n- Du fil solide et une aiguille\n- Éventuellement une machine à coudre\n\nÉtape 1 : Coupez les jambes du jean juste en dessous de la braguette.\n\nÉtape 2 : Retournez le jean et cousez le bas pour fermer le fond du sac.\n\nÉtape 3 : Utilisez les jambes coupées pour créer des anses. Coupez deux bandes de 5cm de large.\n\nÉtape 4 : Fixez les anses à l''intérieur du sac avec des coutures renforcées.\n\nÉtape 5 : Retournez le sac et admirez votre création !',
'Textile', 'publie', '2026-04-10 09:00:00'),

(@uid_pierre, 'Les bienfaits écologiques de l''upcycling en chiffres',
'L''upcycling n''est pas qu''une tendance, c''est un geste concret pour la planète. Voici quelques chiffres qui parlent.\n\nChaque tonne de bois réutilisée évite l''émission de 1,1 tonne de CO2.\n\nRecycler une tonne de métal économise 1,5 tonne de minerai de fer.\n\nLe textile est le 2e secteur le plus polluant au monde. Réutiliser un vêtement pendant 9 mois de plus réduit son empreinte carbone de 20-30%.\n\nEn France, 630 millions de tonnes de déchets sont produites chaque année. L''upcycling peut contribuer à réduire significativement ce volume.\n\nRejoignez le mouvement UpcycleConnect et participez à cette révolution écologique.',
'Écologie', 'publie', '2026-04-12 11:00:00');

-- =============================================
-- SEED : Forum sujets
-- =============================================
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (@uid_lucas, 'Comment bien poncer du bois de palette ?', 'Bois', 'ouvert');
SET @sujet_poncer = LAST_INSERT_ID();
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (@uid_emma, 'Où trouver des tissus de récupération à Paris ?', 'Textile', 'ouvert');
SET @sujet_tissus = LAST_INSERT_ID();
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (@uid_antoine, 'Retour d''expérience : restauration vélo vintage', 'Métal', 'ouvert');
SET @sujet_velo = LAST_INSERT_ID();
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (@uid_julie, 'Idées pour réutiliser des bocaux en verre', 'Verre', 'ouvert');
SET @sujet_bocaux = LAST_INSERT_ID();
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES (@uid_camille, 'Quel vernis écologique utiliser ?', 'Bois', 'ouvert');
SET @sujet_vernis = LAST_INSERT_ID();

-- =============================================
-- SEED : Forum messages
-- =============================================
INSERT INTO forum_messages (id_sujet, id_auteur, contenu) VALUES
(@sujet_poncer, @uid_lucas,   'Bonjour à tous ! Je débute dans le travail du bois de palette et j''aimerais avoir vos conseils pour le ponçage. Quel grain utiliser pour commencer ?'),
(@sujet_poncer, @uid_camille, 'Salut ! Commence toujours par un grain 80 pour enlever les éclats et les marques. Ensuite passe au 120 puis au 180 pour une finition lisse. N''oublie pas de poncer dans le sens du grain !'),
(@sujet_poncer, @uid_antoine, 'Je confirme, grain 80 puis 120 puis 180. Et surtout, investis dans un bon masque anti-poussière. Le bois de palette peut contenir des traitements chimiques.'),
(@sujet_tissus, @uid_emma,    'Je cherche des sources de tissus de récupération sur Paris. Les friperies c''est bien mais les prix montent. Des idées ?'),
(@sujet_tissus, @uid_julie,   'Essaie les ressourceries ! La Petite Rockette dans le 11e a souvent des lots de tissus à prix libre. Sinon les fins de rouleaux chez les grossistes du Sentier.'),
(@sujet_tissus, @uid_lucas,   'Les Emmaüs ont souvent des draps et rideaux en bon état, parfaits pour la couture. Et c''est vraiment pas cher.'),
(@sujet_velo,   @uid_antoine, 'Je viens de terminer la restauration d''un Peugeot PX-10 de 1978. C''était un sacré projet mais le résultat est magnifique. AMA si vous avez des questions !'),
(@sujet_velo,   @uid_camille, 'Superbe projet ! Comment as-tu géré la rouille sur le cadre ? J''ai le même problème sur mon projet actuel.'),
(@sujet_velo,   @uid_antoine, 'Pour la rouille, j''ai utilisé du vinaigre blanc en bain pendant 24h puis un ponçage fin. Ensuite une couche de convertisseur de rouille avant la peinture. Ça marche super bien !'),
(@sujet_bocaux, @uid_julie,   'J''ai accumulé une vingtaine de bocaux Le Parfait. Des idées originales pour les réutiliser au-delà des conserves ?'),
(@sujet_bocaux, @uid_emma,    'Lampes ! Mets des guirlandes LED dedans, c''est magnifique en déco. Sinon des terrariums avec des petites plantes grasses.'),
(@sujet_bocaux, @uid_lucas,   'Moi je les utilise pour ranger mes vis, clous et petits accessoires dans l''atelier. Avec le couvercle vissé sous une étagère, c''est super pratique.'),
(@sujet_vernis, @uid_camille, 'Je cherche un vernis écologique pour finir mes projets bois. Des recommandations ?'),
(@sujet_vernis, @uid_antoine, 'L''huile de lin est un classique et c''est 100% naturel. Sinon regarde les vernis à l''eau de la marque Osmo, ils sont très bien et peu toxiques.'),
(@sujet_vernis, @uid_lucas,   'La cire d''abeille mélangée à de l''essence de térébenthine végétale, c''est ce que j''utilise. Ça donne un rendu mat très naturel.');

-- =============================================
-- SEED : Conteneurs (carte publique)
-- =============================================
INSERT IGNORE INTO conteneurs (conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut) VALUES
('UC-PAR-001', '15 rue du Faubourg Saint-Antoine', 'Paris', '75011', 48.8534, 2.3725, 30, 'actif'),
('UC-PAR-002', '42 boulevard de Belleville', 'Paris', '75020', 48.8711, 2.3770, 20, 'actif'),
('UC-LYO-001', '8 place Bellecour', 'Lyon', '69002', 45.7578, 4.8320, 25, 'actif'),
('UC-MAR-001', '22 avenue du Prado', 'Marseille', '13008', 43.2801, 5.3866, 20, 'actif'),
('UC-NAN-001', '5 rue Crébillon', 'Nantes', '44000', 47.2133, -1.5580, 20, 'actif');

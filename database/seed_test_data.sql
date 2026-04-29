-- Seed de données de test UpcycleConnect
-- Tous les comptes de test ont le mot de passe : Admin123!

SET @hash = '$2a$10$ho.x5/PCZhSMpPugPs0Yu.h4Vk7oOS6clyI8uCEbqqCJNBfX.PnJK';

-- ============================================================
-- 1. Réinitialiser le mot de passe admin@upcycleconnect.com
-- ============================================================
UPDATE utilisateurs SET mot_de_passe_hash = @hash
WHERE email = 'admin@upcycleconnect.com';

-- ============================================================
-- 2. Sites UpcycleConnect
-- ============================================================
INSERT INTO site_uc (nom_site, adresse, ville, code_postal) VALUES
('UC Paris 11', '12 rue de la Roquette', 'Paris', '75011'),
('UC Lyon Croix-Rousse', '34 Grande rue de la Croix-Rousse', 'Lyon', '69004'),
('UC Marseille Belsunce', '8 cours Belsunce', 'Marseille', '13001');

SET @site1 = (SELECT id_site FROM site_uc WHERE nom_site='UC Paris 11');
SET @site2 = (SELECT id_site FROM site_uc WHERE nom_site='UC Lyon Croix-Rousse');
SET @site3 = (SELECT id_site FROM site_uc WHERE nom_site='UC Marseille Belsunce');

-- ============================================================
-- 3. Comptes utilisateurs de test
-- ============================================================
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe_hash, telephone, ville, adresse_complete, code_postal, role, nom_entreprise, numero_siret, upcycling_score, est_certifie, id_site_uc) VALUES
('Martin', 'Sophie', 'sophie.martin@test.com', @hash, '0612345601', 'Paris', '15 rue des Lilas', '75011', 'particulier', NULL, NULL, 120, 0, NULL),
('Dubois', 'Lucas', 'lucas.dubois@test.com', @hash, '0612345602', 'Lyon', '8 rue Garibaldi', '69003', 'particulier', NULL, NULL, 80, 0, NULL),
('Bernard', 'Emma', 'emma.bernard@test.com', @hash, '0612345603', 'Marseille', '22 boulevard Longchamp', '13001', 'particulier', NULL, NULL, 250, 1, NULL),
('Petit', 'Julien', 'julien.petit@test.com', @hash, '0612345604', 'Paris', '5 avenue Parmentier', '75011', 'particulier', NULL, NULL, 50, 0, NULL),
('Rousseau', 'Camille', 'camille.rousseau@test.com', @hash, '0612345605', 'Toulouse', '17 rue Alsace-Lorraine', '31000', 'particulier', NULL, NULL, 30, 0, NULL),
-- Professionnels
('Leclerc', 'Antoine', 'antoine@reparveloparis.fr', @hash, '0623456701', 'Paris', '45 rue Oberkampf', '75011', 'professionnel', 'RéparVélo Paris', '12345678901234', 320, 1, NULL),
('Moreau', 'Marie', 'marie@atelier-couture.fr', @hash, '0623456702', 'Lyon', '12 rue Mercière', '69002', 'professionnel', 'Atelier Couture Lyon', '23456789012345', 410, 1, NULL),
('Garnier', 'Thomas', 'thomas@meublerecup.fr', @hash, '0623456703', 'Marseille', '3 rue Sainte', '13001', 'professionnel', 'Meuble Récup Sud', '34567890123456', 280, 1, NULL),
-- Salariés UC
('Lemoine', 'Claire', 'claire.lemoine@upcycleconnect.com', @hash, '0634567801', 'Paris', 'Site UC Paris 11', '75011', 'salarie', NULL, NULL, 0, 0, NULL),
('Faure', 'Nicolas', 'nicolas.faure@upcycleconnect.com', @hash, '0634567802', 'Lyon', 'Site UC Lyon', '69004', 'salarie', NULL, NULL, 0, 0, NULL);

-- Lier les salariés aux sites (si la colonne id_site_uc accepte)
UPDATE utilisateurs SET id_site_uc = @site1 WHERE email='claire.lemoine@upcycleconnect.com';
UPDATE utilisateurs SET id_site_uc = @site2 WHERE email='nicolas.faure@upcycleconnect.com';

SET @sophie = (SELECT id_utilisateur FROM utilisateurs WHERE email='sophie.martin@test.com');
SET @lucas = (SELECT id_utilisateur FROM utilisateurs WHERE email='lucas.dubois@test.com');
SET @emma = (SELECT id_utilisateur FROM utilisateurs WHERE email='emma.bernard@test.com');
SET @julien = (SELECT id_utilisateur FROM utilisateurs WHERE email='julien.petit@test.com');
SET @camille = (SELECT id_utilisateur FROM utilisateurs WHERE email='camille.rousseau@test.com');
SET @antoine = (SELECT id_utilisateur FROM utilisateurs WHERE email='antoine@reparveloparis.fr');
SET @mariep = (SELECT id_utilisateur FROM utilisateurs WHERE email='marie@atelier-couture.fr');
SET @thomas = (SELECT id_utilisateur FROM utilisateurs WHERE email='thomas@meublerecup.fr');
SET @admin = (SELECT id_utilisateur FROM utilisateurs WHERE email='admin@upcycleconnect.com');

-- ============================================================
-- 4. Matériels (liés aux sites)
-- ============================================================
INSERT INTO materiels (nom, description, etat, est_disponible, id_site) VALUES
('Perceuse Bosch GSB', 'Perceuse-visseuse 18V avec batterie', 'bon', 1, @site1),
('Machine à coudre Singer', 'Machine semi-pro pour ateliers couture', 'bon', 1, @site1),
('Scie sauteuse Makita', 'Scie sauteuse filaire 650W', 'use', 1, @site1),
('Établi mobile', 'Établi pliable avec étau intégré', 'bon', 1, @site2),
('Imprimante 3D Prusa', 'Pour ateliers réparation pièces plastique', 'neuf', 1, @site2),
('Tournevis électrique', 'Set de tournevis sans fil', 'a_reparer', 0, @site2),
('Postes à souder', 'Lot de 3 postes à souder MIG', 'bon', 1, @site3);

-- ============================================================
-- 5. Catalogue items (formations / ateliers à venir)
-- ============================================================
INSERT INTO catalogue_items (id_createur, titre, description, categorie, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par) VALUES
(@antoine, 'Initiation à la réparation de vélo', 'Apprenez à réparer votre vélo : crevaison, freins, dérailleur', 'atelier', 'presentiel', 'UC Paris 11, 12 rue de la Roquette', DATE_ADD(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 7 DAY) + INTERVAL 3 HOUR, 12, 8, 25.00, 'publie', @admin),
(@mariep, 'Customiser ses vêtements - upcycling textile', 'Transformer un vieux jean en sac, customiser un t-shirt', 'atelier', 'presentiel', 'UC Lyon Croix-Rousse', DATE_ADD(NOW(), INTERVAL 14 DAY), DATE_ADD(NOW(), INTERVAL 14 DAY) + INTERVAL 4 HOUR, 10, 10, 35.00, 'publie', @admin),
(@thomas, 'Restauration de meubles anciens', 'Décaper, poncer, vernir : redonnez vie à vos meubles', 'formation', 'presentiel', 'UC Marseille Belsunce', DATE_ADD(NOW(), INTERVAL 21 DAY), DATE_ADD(NOW(), INTERVAL 22 DAY), 8, 5, 80.00, 'publie', @admin),
(@antoine, 'Webinaire : économie circulaire', 'Tour d''horizon des bonnes pratiques d''économie circulaire', 'evenement', 'distanciel', NULL, DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY) + INTERVAL 1 HOUR, 100, 73, 0.00, 'publie', @admin),
(@mariep, 'Conseil personnalisé tri/désencombrement', 'Visite à domicile pour aider à trier et donner ce qui n''est plus utile', 'conseil', 'presentiel', 'À domicile (région lyonnaise)', DATE_ADD(NOW(), INTERVAL 10 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY) + INTERVAL 2 HOUR, 1, 1, 45.00, 'en_attente', NULL);

-- ============================================================
-- 6. Événements supplémentaires
-- ============================================================
INSERT INTO evenements (id_createur, titre, description, type_evenement, format, lieu, date_debut, date_fin, nb_places_total, nb_places_dispo, prix, statut, valide_par) VALUES
(@antoine, 'Atelier crevaison-vélo gratuit', 'Atelier ouvert pour réparer son pneu', 'atelier', 'presentiel', 'Place de la République, Paris', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL 5 DAY) + INTERVAL 2 HOUR, 20, 14, 0.00, 'valide', @admin),
(@mariep, 'Conférence : Mode éthique', 'Comment consommer la mode autrement', 'conference', 'distanciel', NULL, DATE_ADD(NOW(), INTERVAL 12 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY) + INTERVAL 1 HOUR, 200, 187, 0.00, 'valide', @admin),
(@thomas, 'Formation soudure débutant', 'Initiation 2 jours à la soudure MIG', 'formation', 'presentiel', 'UC Marseille Belsunce', DATE_ADD(NOW(), INTERVAL 30 DAY), DATE_ADD(NOW(), INTERVAL 31 DAY), 6, 4, 120.00, 'valide', @admin),
(@antoine, 'Atelier compostage urbain', 'Faire son compost en appartement', 'atelier', 'presentiel', 'UC Paris 11', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 2 DAY) + INTERVAL 2 HOUR, 15, 12, 5.00, 'en_attente', NULL);

-- ============================================================
-- 7. Annonces (dons et ventes)
-- ============================================================
INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
(@sophie, 'Bibliothèque IKEA Billy à donner', 'Bibliothèque blanche, 2m de haut, démontée. À récupérer Paris 11.', 'don', NULL, 'main_propre', 'validee', @admin),
(@lucas, 'Lot de vaisselle vintage', '12 assiettes, 6 verres, héritage grand-mère, parfait état', 'vente', 25.00, 'conteneur', 'validee', @admin),
(@emma, 'Vélo enfant 5-7 ans', 'Vélo bleu, freins refaits, à donner à une famille', 'don', NULL, 'main_propre', 'validee', @admin),
(@julien, 'Console Wii + 8 jeux', 'Console fonctionnelle, 2 manettes, 8 jeux inclus', 'vente', 60.00, 'main_propre', 'validee', @admin),
(@camille, 'Lot de vêtements bébé 0-12 mois', 'Une cinquantaine de pièces, lavées, en très bon état', 'don', NULL, 'conteneur', 'en_attente', NULL),
(@sophie, 'Machine à café Nespresso', 'Fonctionne, juste détartrée. Vendu sans capsules.', 'vente', 30.00, 'conteneur', 'en_attente', NULL),
(@emma, 'Canapé 3 places en cuir', 'Canapé en cuir marron, quelques rayures sur les accoudoirs', 'vente', 150.00, 'main_propre', 'validee', @admin);

-- ============================================================
-- 8. Article de news supplémentaire
-- ============================================================
INSERT INTO articles_news (id_auteur, titre, contenu, categorie, statut, date_publication) VALUES
(@admin, 'Nouveau site UC à Marseille', 'Nous sommes ravis d''annoncer l''ouverture de notre nouvelle antenne à Marseille Belsunce ! Venez découvrir nos ateliers et nos matériels en libre accès.', 'actualites', 'publie', NOW()),
(@admin, '5 astuces pour réparer plutôt que jeter', 'Avant de jeter un objet cassé, posez-vous ces 5 questions...', 'conseils', 'publie', NOW());

-- ============================================================
-- 9. Sujets forum supplémentaires
-- ============================================================
INSERT INTO forum_sujets (id_createur, titre, categorie, statut) VALUES
(@sophie, 'Comment réparer une fermeture éclair cassée ?', 'reparation', 'ouvert'),
(@antoine, 'Ressources pour apprendre la soudure', 'formation', 'ouvert');

SELECT 'Seed terminé' AS resultat;
SELECT COUNT(*) AS total_utilisateurs FROM utilisateurs;
SELECT COUNT(*) AS total_evenements FROM evenements;
SELECT COUNT(*) AS total_annonces FROM annonces;
SELECT COUNT(*) AS total_catalogue FROM catalogue_items;
SELECT COUNT(*) AS total_materiels FROM materiels;
SELECT COUNT(*) AS total_sites FROM site_uc;

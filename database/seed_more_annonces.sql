-- Seed additionnel : plus d'annonces variées pour le marché public
-- À exécuter après seed_test_data.sql
-- Encodage : UTF-8 (utf8mb4)

SET NAMES utf8mb4;
USE upcycleconnect;

SET @sophie  = (SELECT id_utilisateur FROM utilisateurs WHERE email='sophie.martin@test.com');
SET @lucas   = (SELECT id_utilisateur FROM utilisateurs WHERE email='lucas.dubois@test.com');
SET @emma    = (SELECT id_utilisateur FROM utilisateurs WHERE email='emma.bernard@test.com');
SET @julien  = (SELECT id_utilisateur FROM utilisateurs WHERE email='julien.petit@test.com');
SET @camille = (SELECT id_utilisateur FROM utilisateurs WHERE email='camille.rousseau@test.com');
SET @admin   = (SELECT id_utilisateur FROM utilisateurs WHERE email='admin@upcycleconnect.com');

-- ============================================================
-- Insertion des annonces (avec @start = id avant insert pour récupérer les ids créés)
-- ============================================================
SET @start_id = (SELECT IFNULL(MAX(id_annonce),0) FROM annonces);

INSERT INTO annonces (id_particulier, titre, description, type_annonce, prix, mode_remise, statut, valide_par) VALUES
-- BOIS
(@sophie,  'Palettes en bois (lot de 6)',                'Palettes Europe en bon état, idéales pour meubles DIY ou jardinage. À récupérer en pied d''immeuble.', 'don',   NULL,   'main_propre', 'validee', @admin),
(@lucas,   'Étagère en chêne massif',                    'Étagère 3 niveaux, 90×30×120 cm, en chêne massif. Quelques rayures superficielles.',                  'vente', 45.00,  'conteneur',   'validee', @admin),
(@emma,    'Cagettes en bois brut',                      'Lot de 12 cagettes type maraîcher, parfaites pour rangement ou décoration.',                          'don',   NULL,   'main_propre', 'validee', @admin),
(@julien,  'Table basse en pin recyclé',                 'Fabriquée à partir de palettes, traitée et vernie. Dimensions 100×60×40 cm.',                         'vente', 65.00,  'main_propre', 'validee', @admin),
(@camille, 'Planches de coffrage usagées',               'Lot de 20 planches 2m, propres et sèches, idéales pour bricolage rustique.',                          'don',   NULL,   'main_propre', 'validee', @admin),

-- MÉTAL
(@sophie,  'Cadre de vélo VTT vintage',                  'Cadre acier années 90, taille M, sans rouille. Pour pièce ou restauration.',                          'vente', 30.00,  'conteneur',   'validee', @admin),
(@lucas,   'Vis, écrous et boulons (vrac 5 kg)',         'Mélange de visserie acier inox et galvanisée, idéal pour atelier ou récupération.',                   'don',   NULL,   'conteneur',   'validee', @admin),
(@emma,    'Lampe industrielle en métal',                'Lampe d''atelier articulée, finition brossée. Câble électrique à refaire.',                            'vente', 40.00,  'main_propre', 'validee', @admin),
(@julien,  'Tôles ondulées galvanisées',                 'Lot de 8 tôles 2×1 m, légèrement marquées mais étanches. Pour abri ou clôture.',                      'don',   NULL,   'main_propre', 'validee', @admin),
(@camille, 'Cadre de fer forgé pour miroir',             'Très joli cadre ouvragé, sans verre. Patine d''origine conservée.',                                    'vente', 35.00,  'conteneur',   'validee', @admin),

-- TEXTILE
(@sophie,  'Rideaux en lin écru (paire)',                'Rideaux 140×260 cm, lin lavé, jamais utilisés. Tringle non incluse.',                                  'vente', 25.00,  'conteneur',   'validee', @admin),
(@lucas,   'Lot de tissus à motifs (5 kg)',              'Coupons variés : coton, viscose, polyester. Idéal pour couture, patchwork ou customisation.',         'don',   NULL,   'conteneur',   'validee', @admin),
(@emma,    'Manteau en laine bouillie',                  'Taille 40, marron, marque française, état impeccable. Pour silhouette élégante.',                     'vente', 55.00,  'main_propre', 'validee', @admin),
(@julien,  'Draps anciens en lin',                       'Lot de 4 draps brodés main, hérités. Quelques traces d''usage, à laver.',                              'don',   NULL,   'conteneur',   'validee', @admin),
(@camille, 'Chemisiers vintage (lot de 6)',              'Années 80, taille 38-40, motifs variés. Parfaits pour upcycling ou friperie.',                        'vente', 30.00,  'conteneur',   'validee', @admin),

-- ÉLECTRONIQUE
(@sophie,  'Écran PC 24 pouces Dell',                    'Full HD, en parfait état de marche, avec câble HDMI. Boîte d''origine.',                              'vente', 80.00,  'main_propre', 'validee', @admin),
(@lucas,   'Lot de câbles électroniques',                'USB, HDMI, jack, alimentations diverses. Triés et fonctionnels.',                                     'don',   NULL,   'conteneur',   'validee', @admin),
(@emma,    'Imprimante laser HP',                        'Noir et blanc, fonctionne très bien. Toner à mi-niveau. Récupération sur place.',                     'don',   NULL,   'main_propre', 'validee', @admin),
(@julien,  'Tablette graphique Wacom Bamboo',            'Légèrement utilisée, stylet inclus, tous les drivers compatibles. Idéale pour débutant.',             'vente', 45.00,  'main_propre', 'validee', @admin),
(@camille, 'Vieux téléphones portables (lot)',           'Lot de 5 téléphones non fonctionnels, pour récupération de pièces ou recyclage.',                     'don',   NULL,   'conteneur',   'en_attente', NULL),

-- VERRE
(@sophie,  'Bocaux en verre (lot de 30)',                'Bocaux Le Parfait et conserves, propres et stérilisables. Couvercles inclus.',                        'don',   NULL,   'conteneur',   'validee', @admin),
(@lucas,   'Vases en cristal',                           'Lot de 4 vases, hauteurs variées, sans éclat. Très lumineux.',                                        'vente', 20.00,  'main_propre', 'validee', @admin),
(@emma,    'Carafes anciennes (lot de 3)',               'Verre soufflé, deux modèles différents, parfaites pour décoration de table.',                         'vente', 18.00,  'conteneur',   'validee', @admin),
(@julien,  'Plaques de verre épaisses',                  'Lot de 6 plaques 30×40 cm, 5 mm d''épaisseur. Pour bricolage ou serre.',                              'don',   NULL,   'main_propre', 'validee', @admin),

-- PLASTIQUE
(@camille, 'Bidons plastique alimentaire 25L',           'Lot de 4 bidons propres, idéaux pour stockage eau, brassage ou jardin.',                              'don',   NULL,   'main_propre', 'validee', @admin),
(@sophie,  'Bacs de rangement transparents',             'Lot de 8 bacs empilables avec couvercle, environ 30L chacun.',                                        'vente', 22.00,  'main_propre', 'validee', @admin),
(@lucas,   'Pots de fleurs en plastique recyclé',        'Lot de 15 pots, tailles assorties, parfait pour potager ou plantes d''intérieur.',                    'don',   NULL,   'main_propre', 'validee', @admin),

-- AUTRES / MIXTES
(@emma,    'Vélo pliant Decathlon',                      'Vélo pliant Btwin Tilt 500, parfait état, peu utilisé. Idéal trajets urbains.',                       'vente', 110.00, 'main_propre', 'validee', @admin),
(@julien,  'Lot d''outils de jardin',                    'Bêche, râteau, sécateurs, plantoir. Manches en bois, lames en bon état.',                             'don',   NULL,   'main_propre', 'validee', @admin),
(@camille, 'Caisse à outils complète',                   'Caisse métallique avec jeu de tournevis, clés, marteau, mètre, niveau à bulle.',                      'vente', 50.00,  'main_propre', 'validee', @admin),
(@sophie,  'Skateboard cruiser',                         'Planche en érable, roues souples, parfait pour balades urbaines. Léger usage.',                       'vente', 35.00,  'main_propre', 'validee', @admin),
(@lucas,   'Livres de cuisine (lot de 25)',              'Cuisine française, italienne, asiatique. Tous en très bon état.',                                     'don',   NULL,   'conteneur',   'validee', @admin),
(@emma,    'Service à thé en porcelaine',                'Théière + 6 tasses + 6 sous-tasses, motif fleuri. Jamais utilisé, dans son carton.',                  'vente', 28.00,  'conteneur',   'validee', @admin),
(@julien,  'Aquarium 60L avec accessoires',              'Aquarium en verre, pompe, filtre, décors. Sans poissons. À nettoyer avant utilisation.',              'don',   NULL,   'main_propre', 'en_attente', NULL),
(@camille, 'Poussette double',                           'Poussette double Chicco, parfait état, peu servi. Idéale jumeaux ou rapprochés.',                     'vente', 75.00,  'main_propre', 'validee', @admin),
(@sophie,  'Lot de jouets en bois',                      'Cubes, puzzles, jouets à tirer. Marques type Vilac, Janod. Très bon état.',                           'don',   NULL,   'conteneur',   'validee', @admin),
(@lucas,   'Trottinette électrique Xiaomi',              'Modèle M365, bien entretenue, batterie OK (~25 km autonomie). Petite rayure carter.',                'vente', 130.00, 'main_propre', 'validee', @admin);

-- ============================================================
-- Objets associés à chaque annonce nouvellement créée
-- On reprend les ids dans l'ordre d'insertion
-- ============================================================
INSERT INTO objets_annonces (id_annonce, categorie, materiau, etat, poids_kg) VALUES
-- BOIS
(@start_id +  1, 'Mobilier extérieur',  'bois',          'use',       60.000),
(@start_id +  2, 'Mobilier intérieur',  'bois',          'bon',       18.000),
(@start_id +  3, 'Rangement',           'bois',          'bon',        8.000),
(@start_id +  4, 'Mobilier intérieur',  'bois',          'bon',       22.000),
(@start_id +  5, 'Bricolage',           'bois',          'use',       35.000),
-- MÉTAL
(@start_id +  6, 'Sport',               'metal',         'bon',        2.500),
(@start_id +  7, 'Bricolage',           'metal',         'bon',        5.000),
(@start_id +  8, 'Luminaire',           'metal',         'a_reparer',  3.200),
(@start_id +  9, 'Construction',        'metal',         'use',       40.000),
(@start_id + 10, 'Décoration',          'metal',         'bon',        2.800),
-- TEXTILE
(@start_id + 11, 'Décoration',          'textile',       'neuf',       1.800),
(@start_id + 12, 'Couture',             'textile',       'bon',        5.000),
(@start_id + 13, 'Vêtement',            'textile',       'bon',        1.200),
(@start_id + 14, 'Linge de maison',     'textile',       'use',        2.500),
(@start_id + 15, 'Vêtement',            'textile',       'bon',        2.000),
-- ÉLECTRONIQUE
(@start_id + 16, 'Informatique',        'electronique',  'bon',        4.500),
(@start_id + 17, 'Informatique',        'electronique',  'bon',        2.000),
(@start_id + 18, 'Bureautique',         'electronique',  'bon',        9.000),
(@start_id + 19, 'Informatique',        'electronique',  'bon',        0.800),
(@start_id + 20, 'Téléphonie',          'electronique',  'a_reparer',  0.600),
-- VERRE
(@start_id + 21, 'Cuisine',             'verre',         'bon',        8.000),
(@start_id + 22, 'Décoration',          'verre',         'bon',        3.500),
(@start_id + 23, 'Décoration',          'verre',         'bon',        2.200),
(@start_id + 24, 'Bricolage',           'verre',         'bon',        9.500),
-- PLASTIQUE
(@start_id + 25, 'Stockage',            'plastique',     'bon',        4.000),
(@start_id + 26, 'Rangement',           'plastique',     'bon',        3.500),
(@start_id + 27, 'Jardin',              'plastique',     'use',        2.500),
-- AUTRES
(@start_id + 28, 'Sport',               'metal',         'bon',       12.000),
(@start_id + 29, 'Jardin',              'autre',         'bon',        4.500),
(@start_id + 30, 'Bricolage',           'metal',         'bon',        7.500),
(@start_id + 31, 'Sport',               'autre',         'bon',        3.000),
(@start_id + 32, 'Livres',              'autre',         'bon',       12.000),
(@start_id + 33, 'Cuisine',             'verre',         'neuf',       3.800),
(@start_id + 34, 'Animalerie',          'verre',         'use',       18.000),
(@start_id + 35, 'Puériculture',        'autre',         'bon',        9.000),
(@start_id + 36, 'Jouets',              'bois',          'bon',        4.000),
(@start_id + 37, 'Sport',               'metal',         'bon',       13.500);

-- ============================================================
-- Résumé
-- ============================================================
SELECT 'Seed annonces additionnelles terminé' AS resultat;
SELECT COUNT(*) AS total_annonces FROM annonces;
SELECT COUNT(*) AS total_objets   FROM objets_annonces;

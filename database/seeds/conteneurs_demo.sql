-- ─────────────────────────────────────────────────────────────────────────────
-- Données de démo : réseau de conteneurs UpcycleConnect partout en France.
-- 35 points de dépôt — 8 dans Paris, 5 en proche banlieue, le reste réparti
-- dans les grandes villes. Coordonnées GPS réelles (affichage carte correct).
-- Idempotent : INSERT IGNORE sur conteneur_ref (UNIQUE) → relançable sans doublon.
--
-- Exécution (sur le serveur) :
--   docker exec -i uc_mysql mysql -uroot -p<MDP_ROOT> upcycleconnect \
--     < database/seeds/conteneurs_demo.sql
-- ─────────────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO conteneurs
    (conteneur_ref, adresse, ville, code_postal, latitude, longitude, capacite, statut)
VALUES
-- ── Paris intra-muros ────────────────────────────────────────────────────────
('UC-75011-01', '12 Rue de la Roquette',            'Paris',  '75011', 48.8551000, 2.3725000, 30, 'actif'),
('UC-75011-02', '45 Boulevard Voltaire',            'Paris',  '75011', 48.8607000, 2.3790000, 25, 'plein'),
('UC-75001-01', '3 Rue de Rivoli',                  'Paris',  '75001', 48.8558000, 2.3601000, 20, 'actif'),
('UC-75018-01', '8 Rue des Abbesses',               'Paris',  '75018', 48.8845000, 2.3380000, 25, 'actif'),
('UC-75013-01', '60 Avenue d''Italie',              'Paris',  '75013', 48.8270000, 2.3560000, 35, 'actif'),
('UC-75020-01', '15 Rue de Belleville',             'Paris',  '75020', 48.8722000, 2.3770000, 20, 'maintenance'),
('UC-75015-01', '120 Rue de Vaugirard',             'Paris',  '75015', 48.8430000, 2.3110000, 30, 'actif'),
('UC-75005-01', '5 Rue Mouffetard',                 'Paris',  '75005', 48.8430000, 2.3500000, 20, 'actif'),
-- ── Proche banlieue parisienne (Île-de-France) ──────────────────────────────
('UC-92100-01', '24 Avenue Jean Jaurès',            'Boulogne-Billancourt', '92100', 48.8350000, 2.2410000, 25, 'actif'),
('UC-93200-01', '2 Place du Caquet',                'Saint-Denis',          '93200', 48.9362000, 2.3574000, 30, 'actif'),
('UC-93100-01', '18 Rue de Paris',                  'Montreuil',            '93100', 48.8638000, 2.4485000, 20, 'actif'),
('UC-78000-01', '7 Avenue de Saint-Cloud',          'Versailles',           '78000', 48.8049000, 2.1300000, 20, 'actif'),
('UC-92000-01', '30 Rue Maurice Thorez',            'Nanterre',             '92000', 48.8924000, 2.2065000, 25, 'plein'),
-- ── Lyon ────────────────────────────────────────────────────────────────────
('UC-69001-01', '14 Montée de la Grande Côte',      'Lyon',   '69001', 45.7720000, 4.8320000, 30, 'actif'),
('UC-69002-01', '5 Place Bellecour',                'Lyon',   '69002', 45.7578000, 4.8320000, 25, 'actif'),
('UC-69003-01', '88 Cours Lafayette',               'Lyon',   '69003', 45.7620000, 4.8540000, 20, 'maintenance'),
-- ── Marseille ───────────────────────────────────────────────────────────────
('UC-13001-01', '10 La Canebière',                  'Marseille', '13001', 43.2965000, 5.3760000, 35, 'actif'),
('UC-13008-01', '40 Avenue du Prado',               'Marseille', '13008', 43.2720000, 5.3870000, 25, 'actif'),
-- ── Grandes villes ──────────────────────────────────────────────────────────
('UC-31000-01', '1 Place du Capitole',              'Toulouse',          '31000', 43.6045000, 1.4440000, 30, 'actif'),
('UC-33000-01', '20 Cours de l''Intendance',        'Bordeaux',          '33000', 44.8420000, -0.5750000, 30, 'actif'),
('UC-59000-01', '5 Place du Général de Gaulle',     'Lille',             '59000', 50.6370000, 3.0630000, 25, 'actif'),
('UC-44000-01', '12 Rue Crébillon',                 'Nantes',            '44000', 47.2135000, -1.5610000, 25, 'plein'),
('UC-67000-01', '3 Place Kléber',                   'Strasbourg',        '67000', 48.5830000, 7.7455000, 30, 'actif'),
('UC-06000-01', '7 Avenue Jean Médecin',            'Nice',              '06000', 43.7020000, 7.2680000, 25, 'actif'),
('UC-35000-01', '4 Place de la Mairie',             'Rennes',            '35000', 48.1115000, -1.6794000, 20, 'actif'),
('UC-34000-01', '9 Place de la Comédie',            'Montpellier',       '34000', 43.6085000, 3.8800000, 25, 'actif'),
('UC-38000-01', '15 Rue Félix Poulat',              'Grenoble',          '38000', 45.1885000, 5.7245000, 20, 'maintenance'),
('UC-51100-01', '6 Place Drouet d''Erlon',          'Reims',             '51100', 49.2540000, 4.0240000, 20, 'actif'),
('UC-76000-01', '11 Rue du Gros-Horloge',           'Rouen',             '76000', 49.4420000, 1.0930000, 20, 'actif'),
('UC-21000-01', '2 Place de la Libération',         'Dijon',             '21000', 47.3215000, 5.0415000, 20, 'actif'),
('UC-49000-01', '8 Place du Ralliement',            'Angers',            '49000', 47.4710000, -0.5520000, 20, 'actif'),
('UC-63000-01', '5 Place de Jaude',                 'Clermont-Ferrand',  '63000', 45.7760000, 3.0830000, 20, 'actif'),
('UC-37000-01', '14 Rue Nationale',                 'Tours',             '37000', 47.3940000, 0.6890000, 20, 'actif'),
('UC-54000-01', '3 Place Stanislas',                'Nancy',             '54000', 48.6937000, 6.1834000, 20, 'hors_service'),
('UC-29200-01', '1 Rue de Siam',                    'Brest',             '29200', 48.3900000, -4.4860000, 20, 'actif');

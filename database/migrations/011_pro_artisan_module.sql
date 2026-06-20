-- Migration 011 : Module Pro/Artisan (Essential Pro & Expert Pro)
-- Tickets : impact écologique, stats matériaux, alertes, badges, pubs, conteneurs
USE upcycleconnect;

-- ─────────────────────────────────────────────────────────────────────────────
-- 1. BADGES : ajout colonne niveau
--    Permet de distinguer les badges généraux (tous matériaux) des badges
--    par matériau avec deux paliers (intermédiaire=20 objets, avancé=100).
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE badges
  ADD COLUMN niveau ENUM('general','intermediaire','avance') NOT NULL DEFAULT 'general'
    COMMENT 'general=tous matériaux, intermediaire=palier 20, avance=palier 100';

-- ─────────────────────────────────────────────────────────────────────────────
-- 2. PUBLICITÉS : support de l'affichage pondéré
--    Algorithme choisi : round-robin pondéré.
--    Justification : le round-robin pur est équitable en nombre d'affichages
--    mais ne tient pas compte du budget/durée restante. Le round-robin pondéré
--    (poids = 1 par défaut, ajustable par l'admin) permet de moduler la
--    fréquence d'affichage sans complexité supplémentaire côté code.
--    L'état de rotation est isolé dans publicites_rotation pour éviter les
--    mises à jour concurrentes sur la table principale.
-- ─────────────────────────────────────────────────────────────────────────────
ALTER TABLE publicites
  ADD COLUMN poids_affichage INT NOT NULL DEFAULT 1
    COMMENT 'Poids dans le round-robin pondéré (1=équitable, >1=priorité accrue)';

CREATE TABLE IF NOT EXISTS publicites_rotation (
    id_publicite     INT NOT NULL PRIMARY KEY,
    nb_affichages    BIGINT NOT NULL DEFAULT 0
      COMMENT 'Compteur total d'affichages depuis la création',
    score_rotation   BIGINT NOT NULL DEFAULT 0
      COMMENT 'Score courant : incrémenté de poids_affichage à chaque tour non-sélectionné, remis à 0 à la sélection (algorithme Weighted Round-Robin à déficit)',
    derniere_vue     DATETIME NULL,
    FOREIGN KEY (id_publicite) REFERENCES publicites(id_publicite) ON DELETE CASCADE
) COMMENT 'État de rotation pour l'algorithme round-robin pondéré des publicités';

-- ─────────────────────────────────────────────────────────────────────────────
-- 3. SEED : référentiel des badges (seuils en table, non codés en dur)
--    Badges généraux : paliers sur le total d'objets récupérés/achetés.
--    Badges matériau : intermédiaire (20 obj.) et avancé (100 obj.) par type.
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO badges (nom, description, seuil_objets, type_materiau, niveau) VALUES
  -- Badges généraux (tous matériaux confondus)
  ('Eco-Initié',
   'Premier objet récupéré ou acheté sur UpcycleConnect',
   1, 'tous', 'general'),
  ('Sauveteur de Matière',
   '10 objets récupérés ou achetés',
   10, 'tous', 'general'),
  ('Bâtisseur Circulaire',
   '50 objets récupérés ou achetés',
   50, 'tous', 'general'),
  ('Eco-Ninja',
   '100 objets récupérés ou achetés',
   100, 'tous', 'general'),
  ('Champion du Zéro Déchet',
   '250 objets récupérés ou achetés',
   250, 'tous', 'general'),
  ('Maître de la Ressource',
   '500 objets récupérés ou achetés',
   500, 'tous', 'general'),
  ('Légende d''UpcycleConnect',
   '1 000 objets récupérés ou achetés',
   1000, 'tous', 'general'),

  -- Bois
  ('Ami du Bois',          '20 objets en bois récupérés',   20,  'bois', 'intermediaire'),
  ('Gardien de la Forêt',  '100 objets en bois récupérés',  100, 'bois', 'avance'),

  -- Métal
  ('Dompteur de Fer',      '20 objets métal récupérés',     20,  'metal', 'intermediaire'),
  ('Maître de la Forge',   '100 objets métal récupérés',    100, 'metal', 'avance'),

  -- Textile
  ('Tisseur Responsable',  '20 objets textile récupérés',   20,  'textile', 'intermediaire'),
  ('Artisan de la Fibre',  '100 objets textile récupérés',  100, 'textile', 'avance'),

  -- Plastique
  ('Alchimiste du Plastique', '20 objets plastique récupérés', 20,  'plastique', 'intermediaire'),
  ('Expert Polymère',         '100 objets plastique récupérés', 100, 'plastique', 'avance'),

  -- Verre
  ('Passeur de Verre',     '20 objets verre récupérés',     20,  'verre', 'intermediaire'),
  ('Maître de l''Éclat',   '100 objets verre récupérés',    100, 'verre', 'avance'),

  -- Électronique
  ('Sauveur de Circuits',  '20 objets électronique récupérés',  20,  'electronique', 'intermediaire'),
  ('Génie de la Puce',     '100 objets électronique récupérés', 100, 'electronique', 'avance');

-- ─────────────────────────────────────────────────────────────────────────────
-- 4. SEED : plans d'abonnement Essential Pro et Expert Pro
--    Idempotent : INSERT ... WHERE NOT EXISTS (pas de contrainte UNIQUE sur nom).
--    Essential Pro  : alertes email uniquement, max 3, rayon fixe 10 km,
--                     dashboard mensuel, sans badges Expert.
--    Expert Pro     : alertes illimitées (nb_alertes_max=NULL), rayon libre
--                     (rayon_alerte_max_km=NULL), dashboard annuel, badges actifs.
-- ─────────────────────────────────────────────────────────────────────────────
INSERT INTO abonnements
  (nom, prix_mensuel, type_cible, description,
   nb_alertes_max, rayon_alerte_max_km, dashboard_annuel, badges_actives)
SELECT
  'Essential Pro', 29.99, 'professionnel',
  'Dashboard mensuel, impact écologique, stats matériaux, jusqu''à 3 alertes email (rayon fixe 10 km)',
  3, 10, FALSE, FALSE
WHERE NOT EXISTS (SELECT 1 FROM abonnements WHERE nom = 'Essential Pro');

INSERT INTO abonnements
  (nom, prix_mensuel, type_cible, description,
   nb_alertes_max, rayon_alerte_max_km, dashboard_annuel, badges_actives)
SELECT
  'Expert Pro', 59.99, 'professionnel',
  'Tout Essential Pro + dashboard annuel, export PDF, badges publics, alertes illimitées, rayon modulable, email + push OneSignal',
  NULL, NULL, TRUE, TRUE
WHERE NOT EXISTS (SELECT 1 FROM abonnements WHERE nom = 'Expert Pro');

-- Mise à jour prix annuels (10 mois = 2 mois offerts)
UPDATE abonnements
SET prix_annuel = ROUND(prix_mensuel * 10, 2)
WHERE nom IN ('Essential Pro', 'Expert Pro')
  AND (prix_annuel IS NULL OR prix_annuel = 0);

-- ─────────────────────────────────────────────────────────────────────────────
-- 5. INDEX complémentaires pour les nouvelles requêtes dashboard
-- ─────────────────────────────────────────────────────────────────────────────

-- Calcul impact écologique : requêtes par acheteur + période sur commandes/objets
CREATE INDEX IF NOT EXISTS idx_commandes_acheteur_statut
  ON commandes (id_acheteur, statut);

-- Stats matériaux par zone géographique : requêtes sur objets_annonces + annonces
CREATE INDEX IF NOT EXISTS idx_objets_materiau
  ON objets_annonces (materiau);

-- Alertes : recherche par professionnel actif
CREATE INDEX IF NOT EXISTS idx_alertes_pro_active
  ON alertes_materiaux (id_professionnel, est_active);

-- Badges : recherche par matériau + niveau pour calcul automatique
CREATE INDEX IF NOT EXISTS idx_badges_materiau_niveau
  ON badges (type_materiau, niveau, seuil_objets);

-- Publicités actives (pour l'affichage aux particuliers)
CREATE INDEX IF NOT EXISTS idx_pub_statut_dates
  ON publicites (statut, date_debut, date_fin);

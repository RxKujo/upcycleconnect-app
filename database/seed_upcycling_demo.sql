-- =====================================================================
-- Seed de démonstration — Upcycling Score
-- Illustre le calcul des points de TRANSACTION (vendeur + acheteur,
-- avec bonus poids) et un compte CERTIFIÉ.
--
-- À appliquer APRÈS la migration 004, puis lancer un recompute :
--   POST /api/v1/admin/scores/recompute
--
-- Idempotent : ré-exécutable sans effet de bord (INSERT IGNORE + refs fixes).
-- =====================================================================
USE upcycleconnect;

-- Objets avec poids → active le bonus poids (poids_kg × facteur matériau)
-- pour les annonces liées à des commandes finalisées.
INSERT IGNORE INTO objets_annonces (id_objet, id_annonce, categorie, materiau, etat, poids_kg) VALUES
    (101, 3, 'Textile',  'textile', 'bon', 6.000),   -- don   (vendeuse Emma)
    (102, 4, 'Mobilier', 'bois',    'bon', 12.000),  -- vente (vendeur Julien)
    (103, 7, 'Mobilier', 'metal',   'use', 8.000);   -- vente (vendeuse Emma)

-- Commandes finalisées : le recompute crédite vendeur + acheteur.
UPDATE commandes SET statut = 'recuperee' WHERE id_commande IN (3, 4, 5);

-- Ajustement manuel pour certifier un compte de démo (Emma → palier Or ≥ 900).
INSERT IGNORE INTO historique_score (id_utilisateur, points, poids_kg, motif, ref_type, ref_id)
VALUES (4, 800, 0, 'ajustement', 'manuel', 1);

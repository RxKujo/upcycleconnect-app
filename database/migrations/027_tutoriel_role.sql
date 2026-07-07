-- ─────────────────────────────────────────────────────────────────────────────
-- 027 — Tutoriel par rôle
-- Ajoute une colonne `role` aux étapes de tutoriel : NULL = étape commune à
-- tous les rôles ; sinon l'étape n'est montrée qu'au rôle indiqué
-- (particulier | professionnel | salarie).
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE tutoriel_etapes ADD COLUMN role VARCHAR(20) NULL AFTER icone;

-- Exemples d'étapes spécifiques à un rôle (les 7 étapes existantes restent
-- role=NULL, donc communes à tous).
INSERT INTO tutoriel_etapes (titre, contenu, ordre, icone, position, role) VALUES
('Vos publicités', 'En tant que professionnel, mettez en avant votre activité : créez jusqu''à 5 publicités (validées par notre équipe) diffusées équitablement sur les pages du site.', 10, '📣', 'center', 'professionnel'),
('Matériel & inventaire', 'Gérez l''inventaire du matériel mis à disposition pour les ateliers : ajout, état (neuf/usé/à réparer), photos et réservation pour un événement.', 10, '🧰', 'center', 'salarie');

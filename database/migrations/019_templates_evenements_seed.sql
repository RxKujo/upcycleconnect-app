-- ─────────────────────────────────────────────────────────────────────────────
-- 019 — Modèles d'événements (templates)
-- Ajoute un statut actif (désactivation douce) et seed 4 modèles prêts à l'emploi.
-- Le champ `modele` (JSON) pré-remplit le formulaire de création côté salarié :
-- clés consommées : titre, description, type_evenement, format, lieu,
-- nb_places_total, prix.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;

ALTER TABLE templates_evenements
    ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1;

INSERT INTO templates_evenements (nom_template, description, modele) VALUES
(
    'Atelier réparation',
    'Atelier pratique « réparer plutôt que jeter » — petit groupe, présentiel, gratuit.',
    CAST('{"titre":"Atelier réparer plutôt que jeter","description":"Apprenez les gestes de base pour diagnostiquer et réparer vos petits objets du quotidien (électroménager, mobilier, textile). Animé par un expert UpcycleConnect. Apportez un objet à réparer !","type_evenement":"atelier","format":"presentiel","lieu":"","nb_places_total":12,"prix":0}' AS JSON)
),
(
    'Formation upcycling',
    'Formation d''initiation à l''upcycling — demi-journée, présentiel, payante.',
    CAST('{"titre":"Initiation à l''upcycling du bois","description":"Formation d''une demi-journée pour transformer des matériaux de récupération en objets utiles et esthétiques. Théorie + atelier pratique encadré. Matériel fourni.","type_evenement":"formation","format":"presentiel","lieu":"","nb_places_total":10,"prix":35}' AS JSON)
),
(
    'Conférence / webinaire',
    'Conférence en ligne sur l''économie circulaire — distanciel, large audience, gratuit.',
    CAST('{"titre":"Économie circulaire : enjeux et solutions","description":"Conférence en ligne d''une heure pour comprendre les principes de l''économie circulaire et son impact concret. Session de questions-réponses en fin de présentation.","type_evenement":"conference","format":"distanciel","lieu":"","nb_places_total":100,"prix":0}' AS JSON)
),
(
    'Collecte / événement terrain',
    'Grande collecte de quartier sur une journée — présentiel, capacité large, gratuit.',
    CAST('{"titre":"Grande collecte de quartier","description":"Journée de collecte solidaire d''objets réemployables au cœur du quartier. Tri, sensibilisation et stands partenaires. Ouvert à tous, venez déposer vos objets !","type_evenement":"atelier","format":"presentiel","lieu":"","nb_places_total":200,"prix":0}' AS JSON)
);

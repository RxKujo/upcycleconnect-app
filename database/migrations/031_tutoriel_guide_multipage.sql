-- Tutoriel guidé multi-pages : chaque étape pointe un élément réel (cible_element)
-- sur une page donnée (page). Le moteur navigue de page en page et surligne
-- l'élément. Tour réservé aux particuliers (role='particulier'), non-passable.

ALTER TABLE tutoriel_etapes ADD COLUMN page VARCHAR(200) NULL AFTER position;

DELETE FROM tutoriel_etapes;
INSERT INTO tutoriel_etapes (titre, contenu, ordre, cible_element, position, page, role, est_actif) VALUES
('Bienvenue sur UpcycleConnect', 'Ce petit tour guidé te montre l''essentiel en quelques étapes. Suis le fil, ça prend 30 secondes !', 1, NULL, 'center', NULL, 'particulier', 1),
('Ton espace', 'Tout se pilote depuis ce menu, à gauche : tes annonces, tes formations, ton profil.', 2, '.espace-sidebar', 'right', '/particulier/dashboard', 'particulier', 1),
('Ton profil & ton score', 'Ici tu gères tes infos et tu suis ton score upcycling — il monte à chaque don, vente ou atelier.', 3, '.side-nav a[href*="profile"]', 'right', '/particulier/dashboard', 'particulier', 1),
('Déposer un objet', 'Pour donner ou vendre un objet, c''est ce bouton. On y va !', 4, '.side-cta a', 'right', '/particulier/dashboard', 'particulier', 1),
('Créer ton annonce', 'En 3 étapes : description, photos & mode de remise (conteneur sur la carte, ou main propre), puis confirmation.', 5, '.neo-progress-container', 'bottom', '/particulier/annonces/create', 'particulier', 1),
('Le Marché', 'Parcours et recherche les annonces de toute la communauté ici.', 6, '#search-marche', 'bottom', '/annonces', 'particulier', 1),
('La messagerie', 'Contacte les vendeurs et suis tes échanges via cette bulle, en bas à droite. Bonne découverte !', 7, '#ucmsg-bubble', 'left', '/annonces', 'particulier', 1);

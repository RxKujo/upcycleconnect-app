-- Fix double-encoded UTF-8 (les accents apparaissent comme Ã©, Ã , etc.)
-- On convertit chaque colonne UNIQUEMENT si elle contient 'Ã' (sinon CONVERT peut renvoyer NULL et casser une colonne NOT NULL)

-- annonces
UPDATE annonces SET titre = CONVERT(BINARY CONVERT(titre USING latin1) USING utf8mb4) WHERE titre LIKE '%Ã%';
UPDATE annonces SET description = CONVERT(BINARY CONVERT(description USING latin1) USING utf8mb4) WHERE description LIKE '%Ã%';
UPDATE annonces SET motif_refus = CONVERT(BINARY CONVERT(motif_refus USING latin1) USING utf8mb4) WHERE motif_refus LIKE '%Ã%';
UPDATE annonces SET motif_retrait = CONVERT(BINARY CONVERT(motif_retrait USING latin1) USING utf8mb4) WHERE motif_retrait LIKE '%Ã%';

-- catalogue_items
UPDATE catalogue_items SET titre = CONVERT(BINARY CONVERT(titre USING latin1) USING utf8mb4) WHERE titre LIKE '%Ã%';
UPDATE catalogue_items SET description = CONVERT(BINARY CONVERT(description USING latin1) USING utf8mb4) WHERE description LIKE '%Ã%';
UPDATE catalogue_items SET lieu = CONVERT(BINARY CONVERT(lieu USING latin1) USING utf8mb4) WHERE lieu LIKE '%Ã%';

-- evenements
UPDATE evenements SET titre = CONVERT(BINARY CONVERT(titre USING latin1) USING utf8mb4) WHERE titre LIKE '%Ã%';
UPDATE evenements SET description = CONVERT(BINARY CONVERT(description USING latin1) USING utf8mb4) WHERE description LIKE '%Ã%';
UPDATE evenements SET lieu = CONVERT(BINARY CONVERT(lieu USING latin1) USING utf8mb4) WHERE lieu LIKE '%Ã%';

-- utilisateurs
UPDATE utilisateurs SET nom = CONVERT(BINARY CONVERT(nom USING latin1) USING utf8mb4) WHERE nom LIKE '%Ã%';
UPDATE utilisateurs SET prenom = CONVERT(BINARY CONVERT(prenom USING latin1) USING utf8mb4) WHERE prenom LIKE '%Ã%';
UPDATE utilisateurs SET adresse_complete = CONVERT(BINARY CONVERT(adresse_complete USING latin1) USING utf8mb4) WHERE adresse_complete LIKE '%Ã%';
UPDATE utilisateurs SET ville = CONVERT(BINARY CONVERT(ville USING latin1) USING utf8mb4) WHERE ville LIKE '%Ã%';
UPDATE utilisateurs SET nom_entreprise = CONVERT(BINARY CONVERT(nom_entreprise USING latin1) USING utf8mb4) WHERE nom_entreprise LIKE '%Ã%';

-- articles_news
UPDATE articles_news SET titre = CONVERT(BINARY CONVERT(titre USING latin1) USING utf8mb4) WHERE titre LIKE '%Ã%';
UPDATE articles_news SET contenu = CONVERT(BINARY CONVERT(contenu USING latin1) USING utf8mb4) WHERE contenu LIKE '%Ã%';

-- forum_sujets
UPDATE forum_sujets SET titre = CONVERT(BINARY CONVERT(titre USING latin1) USING utf8mb4) WHERE titre LIKE '%Ã%';

-- site_uc
UPDATE site_uc SET nom_site = CONVERT(BINARY CONVERT(nom_site USING latin1) USING utf8mb4) WHERE nom_site LIKE '%Ã%';
UPDATE site_uc SET adresse = CONVERT(BINARY CONVERT(adresse USING latin1) USING utf8mb4) WHERE adresse LIKE '%Ã%';
UPDATE site_uc SET ville = CONVERT(BINARY CONVERT(ville USING latin1) USING utf8mb4) WHERE ville LIKE '%Ã%';

-- materiels
UPDATE materiels SET nom = CONVERT(BINARY CONVERT(nom USING latin1) USING utf8mb4) WHERE nom LIKE '%Ã%';
UPDATE materiels SET description = CONVERT(BINARY CONVERT(description USING latin1) USING utf8mb4) WHERE description LIKE '%Ã%';

SELECT 'Fix terminé' AS resultat;
SELECT id_annonce, titre FROM annonces WHERE id_annonce >= 31;

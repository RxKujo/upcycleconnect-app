-- ─────────────────────────────────────────────────────────────────────────────
-- 016 — Galerie de photos par conteneur
-- Remplace la colonne unique conteneurs.image_url par une table dédiée
-- (plusieurs photos par conteneur, comme photos_objets pour les annonces).
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS photos_conteneurs (
    id_photo      INT AUTO_INCREMENT PRIMARY KEY,
    id_conteneur  INT NOT NULL,
    url_photo     VARCHAR(500) NOT NULL,
    ordre         INT NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photo_conteneur FOREIGN KEY (id_conteneur)
        REFERENCES conteneurs(id_conteneur) ON DELETE CASCADE
);

-- Reprise de l'éventuelle image existante
INSERT INTO photos_conteneurs (id_conteneur, url_photo, ordre)
SELECT id_conteneur, image_url, 0
FROM conteneurs
WHERE image_url IS NOT NULL AND image_url <> '';

ALTER TABLE conteneurs DROP COLUMN image_url;

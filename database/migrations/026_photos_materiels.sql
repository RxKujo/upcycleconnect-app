-- ─────────────────────────────────────────────────────────────────────────────
-- 026 — Galerie de photos par matériel (inventaire salariés)
-- Multi-photos par objet de matériel (comme photos_conteneurs), stockées sur S3.
-- La colonne héritée materiels.photo_url reste tolérée mais n'est plus utilisée.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS photos_materiels (
    id_photo      INT AUTO_INCREMENT PRIMARY KEY,
    id_materiel   INT NOT NULL,
    url_photo     VARCHAR(500) NOT NULL,
    ordre         INT NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photo_materiel FOREIGN KEY (id_materiel)
        REFERENCES materiels(id_materiel) ON DELETE CASCADE
);

-- Reprise de l'éventuelle photo unique existante (photo_url) en première photo.
INSERT INTO photos_materiels (id_materiel, url_photo, ordre)
SELECT id_materiel, photo_url, 0
FROM materiels
WHERE photo_url IS NOT NULL AND photo_url <> '';

-- Migration 010 : indexes de performance (idempotent via information_schema)
DROP PROCEDURE IF EXISTS _add_idx;

DELIMITER //
CREATE PROCEDURE _add_idx(IN tbl VARCHAR(64), IN idx VARCHAR(64), IN cols VARCHAR(255))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name   = tbl
          AND index_name   = idx
        LIMIT 1
    ) THEN
        SET @q = CONCAT('ALTER TABLE `', tbl, '` ADD INDEX `', idx, '` (', cols, ')');
        PREPARE s FROM @q;
        EXECUTE s;
        DEALLOCATE PREPARE s;
    END IF;
END //
DELIMITER ;

-- annonces : statut et id_particulier déjà indexés (MUL) — on ajoute date_creation
CALL _add_idx('annonces',         'idx_annonces_date_creation',       '`date_creation`');
CALL _add_idx('commandes',        'idx_commandes_statut',             '`statut`');
CALL _add_idx('commandes',        'idx_commandes_id_acheteur',        '`id_acheteur`');
CALL _add_idx('evenements',       'idx_evenements_statut',            '`statut`');
CALL _add_idx('evenements',       'idx_evenements_date_debut',        '`date_debut`');
CALL _add_idx('catalogue_items',  'idx_catalogue_items_statut',       '`statut`');
CALL _add_idx('catalogue_items',  'idx_catalogue_items_date_debut',   '`date_debut`');
CALL _add_idx('objets_annonces',  'idx_objets_annonces_id_annonce',   '`id_annonce`');
CALL _add_idx('photos_objets',    'idx_photos_objets_id_objet',       '`id_objet`');

DROP PROCEDURE IF EXISTS _add_idx;

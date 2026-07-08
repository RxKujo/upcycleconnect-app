-- Vente en main propre (espèces) : trace de l'acheteur quand le vendeur déclare
-- son annonce vendue depuis une conversation.

ALTER TABLE annonces
  ADD COLUMN id_acheteur INT NULL AFTER valide_par,
  ADD COLUMN date_vente  DATETIME NULL AFTER id_acheteur,
  ADD CONSTRAINT fk_annonce_acheteur
      FOREIGN KEY (id_acheteur) REFERENCES utilisateurs(id_utilisateur) ON DELETE SET NULL;

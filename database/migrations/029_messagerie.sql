-- Messagerie acheteur ↔ vendeur, liée à une annonce.
-- Une conversation par couple (annonce, acheteur) ; le vendeur est le propriétaire
-- de l'annonce. Sert notamment à coordonner une remise « main propre ».

CREATE TABLE IF NOT EXISTS conversations (
    id_conversation INT AUTO_INCREMENT PRIMARY KEY,
    id_annonce      INT NOT NULL,
    id_acheteur     INT NOT NULL,
    id_vendeur      INT NOT NULL,
    date_creation   DATETIME NOT NULL DEFAULT NOW(),
    UNIQUE KEY uniq_conv (id_annonce, id_acheteur),
    FOREIGN KEY (id_annonce)  REFERENCES annonces(id_annonce)         ON DELETE CASCADE,
    FOREIGN KEY (id_acheteur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_vendeur)  REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id_message      INT AUTO_INCREMENT PRIMARY KEY,
    id_conversation INT NOT NULL,
    id_expediteur   INT NOT NULL,
    contenu         TEXT NOT NULL,
    lu              BOOLEAN NOT NULL DEFAULT FALSE,
    date_envoi      DATETIME NOT NULL DEFAULT NOW(),
    INDEX idx_conv (id_conversation),
    FOREIGN KEY (id_conversation) REFERENCES conversations(id_conversation) ON DELETE CASCADE,
    FOREIGN KEY (id_expediteur)   REFERENCES utilisateurs(id_utilisateur)   ON DELETE CASCADE
);

-- Migration 007 : Tokens de réinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expire_at DATETIME NOT NULL,
    utilise TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expire (expire_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

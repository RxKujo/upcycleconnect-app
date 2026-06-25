-- Migration 007 : Tokens de réinitialisation de mot de passe
-- Schéma aligné sur le code Go (handlers/auth.go) : recherche par email + token,
-- expiration calculée sur created_at (1h). Token aléatoire crypto (64 hex).
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (email),
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migration 008 : Ajout préférence notification email
ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS notif_email_active TINYINT(1) NOT NULL DEFAULT 1
    AFTER notif_push_active;

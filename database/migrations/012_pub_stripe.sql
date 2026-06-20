-- Migration 012 : Facturation Stripe récurrente pour les publicités
-- Exécuter après 011_pro_artisan_module.sql

-- 1. Colonne stripe_subscription_id sur publicites
ALTER TABLE publicites
    ADD COLUMN stripe_subscription_id VARCHAR(255) NULL AFTER cout_mensuel,
    ADD INDEX idx_pub_stripe_sub (stripe_subscription_id);

-- 2. Ajout du statut 'suspendue' (paiement Stripe échoué) à l'ENUM
--    Inclut tous les statuts existants + le nouveau
ALTER TABLE publicites
    MODIFY COLUMN statut ENUM('en_attente','active','validee','refusee','expiree','suspendue')
        NOT NULL DEFAULT 'en_attente';

-- Variable .env requise à ajouter :
--   STRIPE_PRICE_PUB_MENSUEL=price_XXXXXXXXXXXXXXXXXX
-- (créer un Price de 100 € / mois dans le dashboard Stripe et coller l'ID ici)

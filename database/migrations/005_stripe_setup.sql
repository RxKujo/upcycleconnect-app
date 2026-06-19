-- Migration 005 : Stripe integration setup
USE upcycleconnect;

-- Stripe customer ID on users
ALTER TABLE utilisateurs
  ADD COLUMN stripe_customer_id VARCHAR(255) NULL;

-- Annual pricing + Stripe price IDs on plans
ALTER TABLE abonnements
  ADD COLUMN prix_annuel DECIMAL(10,2) NULL,
  ADD COLUMN stripe_price_id_mensuel VARCHAR(255) NULL,
  ADD COLUMN stripe_price_id_annuel VARCHAR(255) NULL;

-- Seed annual prices (2 months free = 10 months billed)
UPDATE abonnements SET prix_annuel = ROUND(prix_mensuel * 10, 2) WHERE prix_mensuel > 0;

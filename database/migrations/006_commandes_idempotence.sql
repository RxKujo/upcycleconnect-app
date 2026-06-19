-- Empêche les doublons si Stripe re-livre un webhook payment_intent.succeeded
ALTER TABLE commandes
  ADD CONSTRAINT uq_commande_pi_annonce UNIQUE (stripe_payment_intent, id_annonce);

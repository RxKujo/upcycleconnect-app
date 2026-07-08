-- ─────────────────────────────────────────────────────────────────────────────
-- 028 — Motif de refus des publicités
-- Persiste le motif saisi par l'admin lors d'un refus (jusque-là reçu mais
-- jamais enregistré), pour que le professionnel sache pourquoi sa pub est refusée.
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE publicites ADD COLUMN motif_refus TEXT NULL AFTER valide_par;

-- ─────────────────────────────────────────────────────────────────────────────
-- Traductions anglaises (EN) — « fichier de langues » versionné.
-- Alimente la table `translations` (moteur i18n existant : data-i18n côté vues).
-- Idempotent : on supprime les clés listées puis on réinsère.
-- Réappliquer après ajout de nouvelles clés (charset utf8mb4 obligatoire, sinon
-- les caractères comme … et ' sont corrompus) :
--   docker exec -i uc_mysql mysql --default-character-set=utf8mb4 -u<user> -p<pass> upcycleconnect < database/seed_i18n_en.sql
-- ─────────────────────────────────────────────────────────────────────────────

SET @en := (SELECT id_langue FROM langue WHERE code_iso = 'en' LIMIT 1);

-- ── LOT 1 : Marketplace (public/marche/index) + statuts communs ──────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'market.kicker','market.title','market.subtitle','market.search',
  'market.filter.all','market.filter.don','market.filter.vente',
  'market.nophoto','market.empty.title','market.empty.body',
  'status.don','status.vente','status.free'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('market.kicker',      @en, 'Marketplace'),
('market.title',       @en, 'The Marketplace'),
('market.subtitle',    @en, 'Browse the community''s donation and sale listings'),
('market.search',      @en, 'Search a listing…'),
('market.filter.all',  @en, 'All'),
('market.filter.don',  @en, 'Donations'),
('market.filter.vente',@en, 'Sales'),
('market.nophoto',     @en, 'NO PHOTO'),
('market.empty.title', @en, 'No listings'),
('market.empty.body',  @en, 'Listings will appear here once approved by the team.'),
('status.don',         @en, 'Donation'),
('status.vente',       @en, 'Sale'),
('status.free',        @en, 'Free');

-- ── LOT 2 : fiche annonce + événements (liste + fiche) + paiement ────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'market.back','market.show.nophoto','market.seller','market.certified','market.score',
  'cart.add','cart.view','market.proonly.title','market.proonly.body','market.specs',
  'market.material','market.state','market.category','market.weight',
  'market.pickup.container','market.pickup.hand','market.pickup.container.note',
  'market.pickup.hand.note','market.directions',
  'events.kicker','events.title','events.subtitle','events.filter.all','events.filter.atelier',
  'events.filter.formation','events.filter.conference','events.filter.presentiel','events.filter.distanciel',
  'events.program','events.date','events.time','events.place','events.full','events.empty.title',
  'events.empty.body','events.description','events.terms','events.format','events.period','events.sessions',
  'events.perperson','events.availability','events.payregister','events.register.free','events.registered',
  'events.ticket','events.soldout','common.processing',
  'pay.title','pay.amount','pay.now','pay.note'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('market.back',                @en, 'Back to marketplace'),
('market.show.nophoto',        @en, 'No photo'),
('market.seller',              @en, 'Seller'),
('market.certified',           @en, 'Certified'),
('market.score',               @en, 'Upcycling score:'),
('cart.add',                   @en, 'Add to cart'),
('cart.view',                  @en, 'View my cart'),
('market.proonly.title',       @en, 'Pickup reserved for professionals and craftspeople.'),
('market.proonly.body',        @en, 'Items dropped off by individuals are collected by pros through UpcycleConnect containers.'),
('market.specs',               @en, 'Specifications'),
('market.material',            @en, 'Material:'),
('market.state',               @en, 'Condition:'),
('market.category',            @en, 'Category:'),
('market.weight',              @en, 'Weight:'),
('market.pickup.container',    @en, 'Collection point'),
('market.pickup.hand',         @en, 'In-person handover'),
('market.pickup.container.note',@en,'Via container — collection point provided after approval.'),
('market.pickup.hand.note',    @en, 'In-person handover — address provided by the seller.'),
('market.directions',          @en, 'Google Maps directions →'),
('events.kicker',              @en, 'Agenda'),
('events.title',               @en, 'Events & training'),
('events.subtitle',            @en, 'Workshops, training and talks organised by the community'),
('events.filter.all',          @en, 'All'),
('events.filter.atelier',      @en, 'Workshops'),
('events.filter.formation',    @en, 'Training'),
('events.filter.conference',   @en, 'Talks'),
('events.filter.presentiel',   @en, 'In person'),
('events.filter.distanciel',   @en, 'Online'),
('events.program',             @en, 'Programme'),
('events.date',                @en, 'Date'),
('events.time',                @en, 'Time'),
('events.place',               @en, 'Location'),
('events.full',                @en, 'Full'),
('events.empty.title',         @en, 'No upcoming events'),
('events.empty.body',          @en, 'Upcoming workshops and training will appear here.'),
('events.description',         @en, 'Description'),
('events.terms',               @en, 'Details'),
('events.format',              @en, 'Format'),
('events.period',              @en, 'Period'),
('events.sessions',            @en, 'Sessions'),
('events.perperson',           @en, 'per participant'),
('events.availability',        @en, 'Availability'),
('events.payregister',         @en, 'Pay & register'),
('events.register.free',       @en, 'Register for free'),
('events.registered',          @en, 'You''re registered'),
('events.ticket',              @en, 'Download my PDF ticket'),
('events.soldout',             @en, 'Event full'),
('common.processing',          @en, 'Processing…'),
('pay.title',                  @en, 'Secure payment'),
('pay.amount',                 @en, 'Amount:'),
('pay.now',                    @en, 'Pay now'),
('pay.note',                   @en, 'Secure payment by Stripe — card details are never stored.');

-- ── LOT 3 : ressources (liste + fiche) + forum (liste + fiche) ───────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'resources.title','resources.subtitle','common.loading','resources.empty.title',
  'resources.empty.body','resources.pdf','resources.all',
  'forum.kicker','forum.title','forum.subtitle','forum.newtopic','forum.newtopic.title',
  'forum.field.title','forum.field.category','forum.field.category.ph','forum.field.message',
  'forum.by','forum.messages','forum.empty.title','forum.empty.body','forum.nomsg','forum.join',
  'forum.login2reply','forum.yourreply','forum.reply.ph','forum.postreply','forum.reply','forum.report'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('resources.title',        @en, 'Educational resources'),
('resources.subtitle',     @en, 'News, tips and advice written by the UpcycleConnect team to help you with reuse and repair.'),
('common.loading',         @en, 'Loading…'),
('resources.empty.title',  @en, 'No resources yet'),
('resources.empty.body',   @en, 'The UpcycleConnect team will publish news and advice here soon.'),
('resources.pdf',          @en, 'Download as PDF'),
('resources.all',          @en, 'All resources'),
('forum.kicker',           @en, 'Community'),
('forum.title',            @en, 'Forum'),
('forum.subtitle',         @en, 'Chat with the community, ask your questions and share your feedback'),
('forum.newtopic',         @en, '+ New topic'),
('forum.newtopic.title',   @en, 'Start a new topic'),
('forum.field.title',      @en, 'Title'),
('forum.field.category',   @en, 'Category (optional)'),
('forum.field.category.ph',@en, 'e.g. repair, training, advice...'),
('forum.field.message',    @en, 'Message'),
('forum.by',               @en, 'By'),
('forum.messages',         @en, 'Messages'),
('forum.empty.title',      @en, 'No topics'),
('forum.empty.body',       @en, 'Be the first to start a discussion!'),
('forum.nomsg',            @en, 'No messages in this topic.'),
('forum.join',             @en, 'Would you like to join this discussion?'),
('forum.login2reply',      @en, 'Log in to reply'),
('forum.yourreply',        @en, 'Your reply'),
('forum.reply.ph',         @en, 'Write your message...'),
('forum.postreply',        @en, 'Post my reply'),
('forum.reply',            @en, 'Reply'),
('forum.report',           @en, 'Report');

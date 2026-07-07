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

-- ── LOT 4 : panier (checkout) ────────────────────────────────────────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'cart.kicker','cart.title','cart.empty.title','cart.empty.body','cart.explore','cart.home',
  'cart.subtotal','cart.commission','cart.total','cart.authwarn.1','cart.authwarn.link',
  'cart.authwarn.2','cart.paycard','cart.clear','cart.continue','cart.tocharge'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('cart.kicker',        @en, 'Purchases'),
('cart.title',         @en, 'My cart'),
('cart.empty.title',   @en, 'Your cart is empty'),
('cart.empty.body',    @en, 'Browse the marketplace and add items to collect or buy. They will appear here.'),
('cart.explore',       @en, 'Explore the marketplace'),
('cart.home',          @en, 'Back to home'),
('cart.subtotal',      @en, 'Items subtotal'),
('cart.commission',    @en, 'UpcycleConnect commission'),
('cart.total',         @en, 'Total to pay'),
('cart.authwarn.1',    @en, 'You must be'),
('cart.authwarn.link', @en, 'logged in'),
('cart.authwarn.2',    @en, 'to place your order.'),
('cart.paycard',       @en, 'Pay by card'),
('cart.clear',         @en, 'Empty cart'),
('cart.continue',      @en, 'Continue shopping'),
('cart.tocharge',      @en, 'Total to charge:');

-- ── LOT 4b : services-pro + a-propos + tutoriels + CGU/RGPD (entetes) ─────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'pro.kicker','pro.title','pro.subtitle','pro.plan.free','pro.plan.essential','pro.plan.expert',
  'pro.month','pro.popular','pro.cta.start','pro.cta.subscribe','pro.cta.createaccount',
  'pro.sponsoring.title','pro.sponsoring.body',
  'pro.feat.1','pro.feat.2','pro.feat.3','pro.feat.4','pro.feat.5','pro.feat.6','pro.feat.7',
  'pro.feat.8','pro.feat.9','pro.feat.10','pro.feat.11','pro.feat.12','pro.feat.13','pro.feat.14','pro.feat.15',
  'about.kicker','about.title','about.p1','about.p2','about.mission','about.mission.body',
  'about.reduce','about.reduce.body','about.connect','about.connect.body','about.train','about.train.body',
  'tuto.title','tuto.subtitle',
  'cgu.title','cgu.updated','cgu.h1','cgu.h2','cgu.h3','cgu.h4','cgu.h5','cgu.h6',
  'rgpd.title','rgpd.updated','rgpd.h1','rgpd.h2','rgpd.h3','rgpd.h4','rgpd.h5','rgpd.h6'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('pro.kicker',           @en, 'Professionals & Craftspeople'),
('pro.title',            @en, 'Pro plans'),
('pro.subtitle',         @en, 'Advanced tools to grow your upcycling business'),
('pro.plan.free',        @en, 'Freemium'),
('pro.plan.essential',   @en, 'Essential Pro'),
('pro.plan.expert',      @en, 'Expert Pro'),
('pro.month',            @en, ' /month'),
('pro.popular',          @en, 'Popular'),
('pro.cta.start',        @en, 'Get started'),
('pro.cta.subscribe',    @en, 'Subscribe'),
('pro.cta.createaccount',@en, 'Create a Pro account'),
('pro.sponsoring.title', @en, 'Promotion & Sponsoring'),
('pro.sponsoring.body',  @en, 'Showcase your products on UpcycleConnect with a fair advertising system. €100 per ad per month, limited to 5 ads per professional.'),
('pro.feat.1',  @en, 'Marketplace access'),
('pro.feat.2',  @en, 'Order items'),
('pro.feat.3',  @en, 'Events catalogue'),
('pro.feat.4',  @en, 'Advice area'),
('pro.feat.5',  @en, 'Everything in the free plan'),
('pro.feat.6',  @en, '30-day activity dashboard'),
('pro.feat.7',  @en, '3 material alerts (10 km radius)'),
('pro.feat.8',  @en, 'Local material statistics'),
('pro.feat.9',  @en, 'Environmental impact'),
('pro.feat.10', @en, 'Everything in Essential +'),
('pro.feat.11', @en, 'Annual dashboard + PDF export'),
('pro.feat.12', @en, 'Unlimited alerts'),
('pro.feat.13', @en, 'Adjustable search radius'),
('pro.feat.14', @en, 'Badge system'),
('pro.feat.15', @en, 'OneSignal push alerts'),
('about.kicker',       @en, 'Our story'),
('about.title',        @en, 'About UpcycleConnect'),
('about.p1',           @en, 'UpcycleConnect is an innovative, eco-friendly company that reduces waste by giving value to recycling.'),
('about.p2',           @en, 'Our platform connects individuals who want to give away or sell items and materials with the craftspeople and professionals who give them a second life. Every item saved from waste helps reduce our environmental footprint.'),
('about.mission',      @en, 'Our mission'),
('about.mission.body', @en, 'Modernise the web architecture for managing recycled-material exchanges while keeping the soul of UpcycleConnect, essential for optimal customer satisfaction.'),
('about.reduce',       @en, 'Reduce'),
('about.reduce.body',  @en, 'Waste, by promoting reuse and upcycling'),
('about.connect',      @en, 'Connect'),
('about.connect.body', @en, 'Individuals and craftspeople around the circular economy'),
('about.train',        @en, 'Train'),
('about.train.body',   @en, 'Through workshops, training and practical advice'),
('tuto.title',         @en, 'Guide & Tutorials'),
('tuto.subtitle',      @en, 'Find the onboarding tutorial steps for UpcycleConnect here'),
('cgu.title',    @en, 'Terms of Use'),
('cgu.updated',  @en, 'Last updated: April 2026'),
('cgu.h1',       @en, '1. Purpose'),
('cgu.h2',       @en, '2. Service description'),
('cgu.h3',       @en, '3. Registration'),
('cgu.h4',       @en, '4. Listings'),
('cgu.h5',       @en, '5. Liability'),
('cgu.h6',       @en, '6. Contact'),
('rgpd.title',   @en, 'Privacy Policy'),
('rgpd.updated', @en, 'In accordance with GDPR — Last updated: April 2026'),
('rgpd.h1',      @en, '1. Data collected'),
('rgpd.h2',      @en, '2. Purpose of processing'),
('rgpd.h3',      @en, '3. Public anonymisation'),
('rgpd.h4',      @en, '4. Retention period'),
('rgpd.h5',      @en, '5. Your rights'),
('rgpd.h6',      @en, '6. Subcontractors');

-- ── LOT 4c : depot (formulaire de depot en conteneur) ────────────────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'depot.title','depot.subtitle','depot.form.title','depot.map.title','depot.map.hint',
  'depot.mydemands','depot.alert.success','depot.alert.error','depot.submit',
  'depot.label.1','depot.label.2','depot.label.3','depot.label.4','depot.label.5','depot.label.6',
  'depot.label.7','depot.label.8',
  'depot.opt.1','depot.opt.2','depot.opt.3','depot.opt.4','depot.opt.5','depot.opt.6','depot.opt.7',
  'depot.opt.8','depot.opt.9','depot.opt.10'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('depot.title',         @en, 'Container drop-off'),
('depot.subtitle',      @en, 'Drop off your items in one of our partner containers'),
('depot.form.title',    @en, 'My drop-off request'),
('depot.map.title',     @en, 'Nearby containers'),
('depot.map.hint',      @en, 'Click a container to select it — or "Around me" for the nearest ones'),
('depot.mydemands',     @en, 'My requests'),
('depot.alert.success', @en, 'Request sent! Our team will process it within 48h.'),
('depot.alert.error',   @en, 'Error while sending your request.'),
('depot.submit',        @en, 'Send my request'),
('depot.label.1', @en, 'Item title *'),
('depot.label.2', @en, 'Item type *'),
('depot.label.3', @en, 'Description *'),
('depot.label.4', @en, 'Quantity'),
('depot.label.5', @en, 'Pickup address'),
('depot.label.6', @en, 'Postal code'),
('depot.label.7', @en, 'City'),
('depot.label.8', @en, 'Selected container'),
('depot.opt.1',  @en, 'Select...'),
('depot.opt.2',  @en, 'Furniture'),
('depot.opt.3',  @en, 'Appliances'),
('depot.opt.4',  @en, 'Clothing'),
('depot.opt.5',  @en, 'Electronics'),
('depot.opt.6',  @en, 'Books / Media'),
('depot.opt.7',  @en, 'Toys'),
('depot.opt.8',  @en, 'Decoration'),
('depot.opt.9',  @en, 'Tools'),
('depot.opt.10', @en, 'Other');

-- ── LOT 5 : espace particulier (nav layout + dashboard) ──────────────────────
DELETE FROM translations WHERE id_langue = @en AND cle IN (
  'nav.myspace','nav.dashboard','nav.mylistings','nav.mytrainings','nav.profile',
  'nav.postlisting','nav.logout',
  'dash.points','dash.managelistings','dash.myregistrations','dash.wasteavoided',
  'dash.kgprofile','dash.recentactivity'
);
INSERT INTO translations (cle, id_langue, valeur) VALUES
('nav.myspace',       @en, 'My space'),
('nav.dashboard',     @en, 'Dashboard'),
('nav.mylistings',    @en, 'My listings'),
('nav.mytrainings',   @en, 'My training'),
('nav.profile',       @en, 'Profile & settings'),
('nav.postlisting',   @en, '+ Post a listing'),
('nav.logout',        @en, 'Log out'),
('dash.points',           @en, 'upcycling points'),
('dash.managelistings',   @en, 'Manage my drop-offs →'),
('dash.myregistrations',  @en, 'View my registrations →'),
('dash.wasteavoided',     @en, 'Waste avoided'),
('dash.kgprofile',        @en, 'kg · view my profile →'),
('dash.recentactivity',   @en, 'Recent activity');

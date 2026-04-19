@extends('layouts.public')

@section('title', 'Accueil')
@section('meta_description', 'UpcycleConnect — Marketplace écologique. Donnez une seconde vie à vos objets. Trouvez des matériaux recyclés.')

@section('content')

<section class="hero-section">
    <div class="hero-inner">
        <div class="hero-left">
            <p class="hero-eyebrow">
                <span class="hero-eyebrow-dot"></span>
                Marketplace écologique
            </p>
            <h1 class="hero-title">
                Donnez une<br>
                <span class="hero-title-accent">seconde vie</span><br>
                à vos objets.
            </h1>
            <p class="hero-subtitle">
                Vendez, donnez ou trouvez des matériaux recyclés dans votre ville.<br>
                Rejoignez une communauté engagée pour une économie circulaire.
            </p>
            <div class="hero-actions">
                <a href="{{ route('annonces.index') }}" class="btn btn-primary btn-lg">
                    Explorer le marché
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8"/></svg>
                </a>
                <a href="{{ route('particulier.register') }}" class="btn btn-secondary btn-lg">Créer un compte</a>
            </div>
            <div class="hero-trust">
                <span class="hero-trust-dot hero-trust-dot-forest"></span>
                <span>Gratuit &middot; Sans engagement &middot; Communauté vérifiée</span>
            </div>
        </div>

        <div class="hero-right">
            <div class="hero-visual">
                <div class="hero-visual-card hero-visual-card-1">
                    <span class="hero-visual-tag hero-visual-tag-forest">Don</span>
                    <div class="hero-visual-img" style="background:linear-gradient(135deg,#D8C99B 0%, #A4243B 100%);"></div>
                    <p class="hero-visual-title">Planches de chêne</p>
                    <p class="hero-visual-meta">Bordeaux &middot; Emma B.</p>
                </div>
                <div class="hero-visual-card hero-visual-card-2">
                    <span class="hero-visual-tag hero-visual-tag-cherry">Vente</span>
                    <div class="hero-visual-img" style="background:linear-gradient(135deg,#244F26 0%, #18607D 100%);"></div>
                    <p class="hero-visual-title">Table en palette</p>
                    <p class="hero-visual-meta">Lyon &middot; Lucas M. &#10003;</p>
                    <p class="hero-visual-price">45&euro;</p>
                </div>
                <div class="hero-visual-card hero-visual-card-3">
                    <span class="hero-visual-tag hero-visual-tag-teal">Atelier</span>
                    <div class="hero-visual-img" style="background:linear-gradient(135deg,#18607D 0%, #D8C99B 100%);"></div>
                    <p class="hero-visual-title">Upcycling textile</p>
                    <p class="hero-visual-meta">15 mai &middot; Paris</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-wheat steps-section">
    <div class="section-inner">
        <div class="section-head">
            <p class="section-eyebrow">Simple &amp; efficace</p>
            <h2 class="section-heading">Comment ça marche</h2>
        </div>

        <div class="steps-grid">
            <div class="step-card">
                <span class="step-num">01</span>
                <h3 class="step-title">Je dépose</h3>
                <p class="step-desc">Publiez vos objets en don ou en vente. Notre équipe valide chaque annonce pour garantir la qualité.</p>
            </div>
            <div class="step-arrow" aria-hidden="true">&rarr;</div>
            <div class="step-card">
                <span class="step-num">02</span>
                <h3 class="step-title">Je rencontre</h3>
                <p class="step-desc">Les artisans et particuliers découvrent vos annonces. Échange en main propre ou via conteneur.</p>
            </div>
            <div class="step-arrow" aria-hidden="true">&rarr;</div>
            <div class="step-card">
                <span class="step-num">03</span>
                <h3 class="step-title">Je gagne</h3>
                <p class="step-desc">Votre Upcycling Score augmente à chaque échange. Débloquez badges et réductions.</p>
            </div>
        </div>
    </div>
</section>

<section class="section-light">
    <div class="section-inner">
        <div class="section-head section-head-row">
            <div>
                <p class="section-eyebrow">Marketplace</p>
                <h2 class="section-heading">Dernières annonces</h2>
            </div>
            <a href="{{ route('annonces.index') }}" class="section-link">Tout voir &rarr;</a>
        </div>

        @if(count($annonces) > 0)
        <div class="cards-grid cards-grid-4">
            @foreach($annonces as $annonce)
            <a href="{{ route('annonces.show', $annonce['id_annonce']) }}" class="product-card">
                <div class="product-card-img">
                    @if(!empty($annonce['objets']) && !empty($annonce['objets'][0]['photos']))
                    <img src="/uploads/{{ $annonce['objets'][0]['photos'][0]['url'] }}" alt="{{ $annonce['titre'] }}">
                    @else
                    <span class="product-card-placeholder">{{ strtoupper(substr($annonce['objets'][0]['materiau'] ?? '?', 0, 1)) }}</span>
                    @endif
                    <span class="product-card-badge {{ $annonce['type_annonce'] === 'don' ? 'badge-forest' : 'badge-cherry' }}">
                        {{ $annonce['type_annonce'] === 'don' ? 'Don' : 'Vente' }}
                    </span>
                </div>
                <div class="product-card-body">
                    <p class="product-card-meta">
                        @if(!empty($annonce['objets']))
                        {{ ucfirst($annonce['objets'][0]['materiau']) }}
                        @endif
                    </p>
                    <h3 class="product-card-title">{{ $annonce['titre'] }}</h3>
                    <p class="product-card-seller">
                        {{ $annonce['vendeur']['prenom'] ?? '' }} {{ $annonce['vendeur']['nom_initiale'] ?? '' }}
                        @if($annonce['vendeur']['certifie'] ?? false)<span class="certified-mark" title="Certifie">&#10003;</span>@endif
                        <span class="sep">&middot;</span> {{ $annonce['ville'] ?? '' }}
                    </p>
                    <div class="product-card-footer">
                        @if($annonce['type_annonce'] === 'don')
                        <span class="product-card-free">Gratuit</span>
                        @else
                        <span class="product-card-price">{{ number_format($annonce['prix'] ?? 0, 2) }}&euro;</span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <p>Aucune annonce pour le moment.</p>
            <a href="{{ route('particulier.register') }}" class="btn btn-secondary btn-lg">Être le premier à déposer</a>
        </div>
        @endif
    </div>
</section>

<section class="section-wheat">
    <div class="section-inner">
        <div class="section-head" style="text-align:center;">
            <p class="section-eyebrow" style="justify-content:center;">Notre impact</p>
            <h2 class="section-heading">La communauté en chiffres</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-block">
                <span class="stat-value">{{ number_format($stats['objets_sauves'] ?? 0) }}<span class="stat-plus">+</span></span>
                <span class="stat-label">Objets sauvés des déchets</span>
            </div>
            <div class="stat-block">
                <span class="stat-value">{{ number_format($stats['membres'] ?? 0) }}<span class="stat-plus">+</span></span>
                <span class="stat-label">Membres actifs</span>
            </div>
            <div class="stat-block">
                <span class="stat-value">{{ $stats['ateliers_an'] ?? 0 }}</span>
                <span class="stat-label">Ateliers programmés cette année</span>
            </div>
            <div class="stat-block">
                <span class="stat-value">48<span class="stat-unit">T</span></span>
                <span class="stat-label">CO&#8322; évité cette année</span>
            </div>
        </div>
    </div>
</section>

<section class="section-light">
    <div class="section-inner">
        <div class="section-head section-head-row">
            <div>
                <p class="section-eyebrow">Agenda</p>
                <h2 class="section-heading">Ateliers &amp; formations</h2>
            </div>
            <a href="{{ route('evenements.index') }}" class="section-link">Voir tout &rarr;</a>
        </div>

        @if(count($evenements) > 0)
        <div class="cards-grid cards-grid-3">
            @foreach($evenements as $event)
            <a href="{{ route('evenements.show', $event['id_evenement']) }}" class="event-card">
                <div class="event-card-header">
                    @php $date = \Carbon\Carbon::parse($event['date_debut']); @endphp
                    <div class="event-card-date">
                        <span class="event-card-day">{{ $date->format('d') }}</span>
                        <span class="event-card-month">{{ strtoupper($date->locale('fr')->isoFormat('MMM')) }}</span>
                    </div>
                    <div class="event-card-tags">
                        @php
                            $typeLabels = [
                                'atelier' => 'Atelier',
                                'formation' => 'Formation',
                                'conference' => 'Conférence',
                            ];
                            $formatLabels = [
                                'presentiel' => 'Présentiel',
                                'distanciel' => 'Distanciel',
                            ];
                        @endphp
                        <span class="event-card-tag event-card-tag-{{ $event['type_evenement'] ?? 'atelier' }}">{{ $typeLabels[$event['type_evenement'] ?? ''] ?? 'Événement' }}</span>
                        <span class="event-card-format">{{ $formatLabels[$event['format'] ?? ''] ?? '' }}</span>
                    </div>
                </div>
                <h3 class="event-card-title">{{ $event['titre'] }}</h3>
                <p class="event-card-desc">{{ \Illuminate\Support\Str::limit($event['description'] ?? '', 110) }}</p>
                <div class="event-card-footer">
                    @if(($event['prix'] ?? 0) > 0)
                    <span class="event-card-price">{{ number_format($event['prix'], 2) }}&euro;</span>
                    @else
                    <span class="event-card-free">Gratuit</span>
                    @endif
                    <span class="event-card-places">{{ $event['nb_places_dispo'] ?? 0 }} places</span>
                </div>
            </a>
            @endforeach
        </div>
        @else
        <div class="empty-state"><p>Aucun événement à venir.</p></div>
        @endif
    </div>
</section>

<section class="pro-section">
    <div class="section-inner">
        <div class="pro-grid">
            <div class="pro-left">
                <p class="pro-eyebrow">Professionnels &amp; Artisans</p>
                <h2 class="pro-title">Boostez votre activité d'upcycling</h2>
                <p class="pro-subtitle">
                    Dashboard avancé, alertes matériaux géolocalisées, badges communautaires. Deux offres adaptées à votre besoin.
                </p>
                <a href="{{ route('services-pro') }}" class="btn btn-primary btn-lg">Découvrir les offres Pro</a>
            </div>
            <div class="pro-cards">
                <div class="pro-card">
                    <p class="pro-card-name">Essential Pro</p>
                    <p class="pro-card-price">15,99<span>&euro;/mois</span></p>
                    <ul>
                        <li>Dashboard 30 jours</li>
                        <li>3 alertes matériaux</li>
                        <li>Statistiques locales</li>
                    </ul>
                </div>
                <div class="pro-card pro-card-featured">
                    <span class="pro-card-badge">Populaire</span>
                    <p class="pro-card-name">Expert Pro</p>
                    <p class="pro-card-price">29,99<span>&euro;/mois</span></p>
                    <ul>
                        <li>Tout Essential +</li>
                        <li>Alertes illimitées</li>
                        <li>Badges communautaires</li>
                        <li>Export PDF annuel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-wheat cta-final">
    <div class="section-inner">
        <h2 class="cta-final-title">Prêt à donner<br>une seconde vie ?</h2>
        <p class="cta-final-sub">Rejoignez la communauté UpcycleConnect dès aujourd'hui. C'est gratuit.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="{{ route('particulier.register') }}" class="btn btn-primary btn-lg">Créer un compte</a>
            <a href="{{ route('annonces.index') }}" class="btn btn-secondary btn-lg">Parcourir le marché</a>
        </div>
    </div>
</section>

@endsection

@section('styles')
/* =============================================
   HERO
============================================= */
.hero-section {
    background: var(--cream);
    border-bottom: var(--border);
    padding: 64px 0 96px;
    position: relative;
    overflow: hidden;
}
.hero-section::before {
    content: '';
    position: absolute;
    top: 10%;
    right: -120px;
    width: 280px;
    height: 280px;
    background: var(--wheat);
    border: var(--border);
    transform: rotate(15deg);
    z-index: 0;
}
.hero-inner {
    position: relative;
    z-index: 1;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 32px;
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 64px;
    align-items: center;
    min-height: 560px;
}
.hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--teal);
    margin-bottom: 24px;
}
.hero-eyebrow-dot {
    width: 10px;
    height: 10px;
    background: var(--cherry);
    border: 2px solid var(--coffee);
    display: inline-block;
}
.hero-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(3.5rem, 7vw, 6rem);
    letter-spacing: 0.04em;
    line-height: 0.95;
    color: var(--coffee);
    margin-bottom: 28px;
}
.hero-title-accent {
    color: var(--cherry);
    position: relative;
    display: inline-block;
}
.hero-title-accent::after {
    content: '';
    position: absolute;
    bottom: 0.08em;
    left: -0.05em;
    right: -0.05em;
    height: 0.18em;
    background: var(--wheat);
    z-index: -1;
}
.hero-subtitle {
    font-size: 1.1rem;
    color: var(--coffee);
    opacity: 0.75;
    line-height: 1.7;
    margin-bottom: 40px;
    max-width: 500px;
}
.hero-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.hero-trust {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    letter-spacing: 0.05em;
    color: var(--coffee);
    opacity: 0.7;
}
.hero-trust-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}
.hero-trust-dot-forest { background: var(--forest); }

/* Hero right — product cards */
.hero-right {
    position: relative;
    height: 520px;
}
.hero-visual {
    position: relative;
    width: 100%;
    height: 100%;
}
.hero-visual-card {
    position: absolute;
    background: var(--cream);
    border: 3px solid var(--coffee);
    box-shadow: 6px 6px 0 var(--coffee);
    padding: 16px;
    width: 220px;
    transition: transform 0.3s ease;
}
.hero-visual-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 9px 9px 0 var(--coffee);
    z-index: 5;
}
.hero-visual-card-1 { top: 0; left: 10%; transform: rotate(-4deg); }
.hero-visual-card-2 { top: 110px; right: 0; transform: rotate(3deg); z-index: 2; }
.hero-visual-card-3 { bottom: 20px; left: 20%; transform: rotate(-2deg); }
.hero-visual-card-1:hover { transform: rotate(-4deg) translate(-3px, -3px); }
.hero-visual-card-2:hover { transform: rotate(3deg) translate(-3px, -3px); }
.hero-visual-card-3:hover { transform: rotate(-2deg) translate(-3px, -3px); }

.hero-visual-img {
    width: 100%;
    height: 120px;
    border: 2px solid var(--coffee);
    margin-bottom: 12px;
}
.hero-visual-tag {
    display: inline-block;
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 10px;
    border: 2px solid var(--coffee);
    margin-bottom: 12px;
}
.hero-visual-tag-forest { background: var(--forest); color: var(--cream); }
.hero-visual-tag-cherry { background: var(--cherry); color: var(--cream); }
.hero-visual-tag-teal { background: var(--teal); color: var(--cream); }
.hero-visual-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.15rem;
    letter-spacing: 0.04em;
    line-height: 1.1;
    margin-bottom: 4px;
}
.hero-visual-meta {
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    color: var(--coffee);
    opacity: 0.6;
}
.hero-visual-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.4rem;
    color: var(--cherry);
    margin-top: 8px;
}

/* =============================================
   SECTIONS communes
============================================= */
.section-light { background: var(--cream); border-bottom: var(--border); }
.section-wheat { background: var(--wheat); border-bottom: var(--border); }
.section-inner { max-width: 1280px; margin: 0 auto; padding: 96px 32px; }
.section-head { margin-bottom: 56px; }
.section-head-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 24px;
    flex-wrap: wrap;
}
.section-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--cherry);
    margin-bottom: 14px;
}
.section-eyebrow::before {
    content: '';
    width: 32px;
    height: 2px;
    background: var(--cherry);
}
.section-heading {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(2.4rem, 4.5vw, 3.5rem);
    letter-spacing: 0.04em;
    line-height: 1;
    color: var(--coffee);
}
.section-link {
    font-family: 'DM Mono', monospace;
    font-size: 0.85rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--coffee);
    padding: 10px 20px;
    border: 2px solid var(--coffee);
    transition: all 0.15s;
}
.section-link:hover { background: var(--coffee); color: var(--cream); }

.cards-grid { display: grid; gap: 24px; }
.cards-grid-4 { grid-template-columns: repeat(4, 1fr); }
.cards-grid-3 { grid-template-columns: repeat(3, 1fr); }

/* =============================================
   STEPS (Comment ça marche)
============================================= */
.steps-grid {
    display: grid;
    grid-template-columns: 1fr auto 1fr auto 1fr;
    gap: 16px;
    align-items: stretch;
}
.step-card {
    background: var(--cream);
    border: var(--border);
    box-shadow: 5px 5px 0 var(--coffee);
    padding: 36px 28px;
    transition: transform 0.15s;
}
.step-card:hover {
    transform: translate(-2px, -2px);
    box-shadow: 7px 7px 0 var(--coffee);
}
.step-num {
    display: block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3.5rem;
    color: var(--cherry);
    letter-spacing: 0.04em;
    line-height: 1;
    margin-bottom: 16px;
}
.step-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    letter-spacing: 0.05em;
    margin-bottom: 12px;
}
.step-desc {
    font-size: 0.95rem;
    color: var(--coffee);
    opacity: 0.75;
    line-height: 1.6;
}
.step-arrow {
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.2rem;
    color: var(--coffee);
    opacity: 0.4;
}

/* =============================================
   PRODUCT CARDS
============================================= */
.product-card {
    display: flex;
    flex-direction: column;
    background: var(--cream);
    border: var(--border);
    box-shadow: 4px 4px 0 var(--coffee);
    transition: all 0.15s;
    overflow: hidden;
}
.product-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 7px 7px 0 var(--coffee);
}
.product-card-img {
    position: relative;
    height: 180px;
    background: var(--wheat);
    border-bottom: var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.product-card-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.product-card-placeholder {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3rem;
    color: var(--coffee);
    opacity: 0.3;
}
.product-card-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 4px 12px;
    border: 2px solid var(--coffee);
}
.badge-forest { background: var(--forest); color: var(--cream); }
.badge-cherry { background: var(--cherry); color: var(--cream); }
.product-card-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.product-card-meta {
    font-family: 'DM Mono', monospace;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--teal);
    margin-bottom: 6px;
}
.product-card-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    letter-spacing: 0.03em;
    line-height: 1.15;
    margin-bottom: 8px;
    color: var(--coffee);
}
.product-card-seller {
    font-size: 0.82rem;
    color: var(--coffee);
    opacity: 0.65;
    margin-bottom: 14px;
}
.product-card-seller .sep { margin: 0 4px; }
.certified-mark { color: var(--forest); font-weight: 700; margin-left: 3px; }
.product-card-footer {
    margin-top: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.product-card-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.6rem;
    color: var(--cherry);
    letter-spacing: 0.03em;
}
.product-card-free {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--forest);
    padding: 5px 12px;
    background: rgba(36, 79, 38, 0.1);
    border: 2px solid var(--forest);
}

/* =============================================
   EVENT CARDS
============================================= */
.event-card {
    display: flex;
    flex-direction: column;
    background: var(--cream);
    border: var(--border);
    box-shadow: 4px 4px 0 var(--coffee);
    padding: 28px;
    transition: all 0.15s;
}
.event-card:hover {
    transform: translate(-3px, -3px);
    box-shadow: 7px 7px 0 var(--coffee);
}
.event-card-header {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    align-items: flex-start;
}
.event-card-date {
    flex-shrink: 0;
    width: 64px;
    padding: 8px 4px;
    text-align: center;
    background: var(--cherry);
    color: var(--cream);
    border: 2px solid var(--coffee);
}
.event-card-day {
    display: block;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.6rem;
    letter-spacing: 0.02em;
    line-height: 1;
}
.event-card-month {
    display: block;
    font-family: 'DM Mono', monospace;
    font-size: 0.65rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-top: 2px;
}
.event-card-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    align-items: center;
}
.event-card-tag {
    font-family: 'DM Mono', monospace;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 3px 10px;
    border: 2px solid var(--coffee);
    background: var(--wheat);
}
.event-card-tag-atelier { background: var(--teal); color: var(--cream); }
.event-card-tag-formation { background: var(--forest); color: var(--cream); }
.event-card-tag-conference { background: var(--wheat); color: var(--coffee); }
.event-card-format {
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    color: var(--coffee);
    opacity: 0.6;
    letter-spacing: 0.04em;
}
.event-card-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.4rem;
    letter-spacing: 0.03em;
    line-height: 1.15;
    margin-bottom: 12px;
}
.event-card-desc {
    font-size: 0.9rem;
    color: var(--coffee);
    opacity: 0.7;
    line-height: 1.6;
    margin-bottom: 20px;
    flex: 1;
}
.event-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 16px;
    border-top: 2px solid rgba(18,3,9,0.1);
}
.event-card-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.4rem;
    color: var(--cherry);
    letter-spacing: 0.03em;
}
.event-card-free {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--forest);
}
.event-card-places {
    font-family: 'DM Mono', monospace;
    font-size: 0.72rem;
    color: var(--coffee);
    opacity: 0.6;
    letter-spacing: 0.04em;
}

/* =============================================
   STATS
============================================= */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}
.stat-block {
    background: var(--cream);
    border: var(--border);
    box-shadow: 5px 5px 0 var(--coffee);
    padding: 32px 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    transition: transform 0.15s;
}
.stat-block:hover {
    transform: translate(-2px, -2px);
    box-shadow: 7px 7px 0 var(--coffee);
}
.stat-value {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(2.5rem, 4vw, 3.5rem);
    letter-spacing: 0.02em;
    line-height: 1;
    color: var(--coffee);
    display: flex;
    align-items: baseline;
    gap: 2px;
}
.stat-plus, .stat-unit {
    color: var(--cherry);
    font-size: 0.7em;
}
.stat-label {
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 500;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--coffee);
    opacity: 0.7;
    line-height: 1.4;
}

/* =============================================
   PRO SECTION (coffee)
============================================= */
.pro-section {
    background: var(--coffee);
    color: var(--cream);
    border-bottom: var(--border);
}
.pro-grid {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 64px;
    align-items: center;
}
.pro-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: 'DM Mono', monospace;
    font-size: 0.78rem;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--wheat);
    margin-bottom: 18px;
}
.pro-eyebrow::before {
    content: '';
    width: 32px;
    height: 2px;
    background: var(--wheat);
}
.pro-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(2.4rem, 4vw, 3.3rem);
    letter-spacing: 0.03em;
    line-height: 1.05;
    color: var(--cream);
    margin-bottom: 24px;
}
.pro-subtitle {
    font-size: 1.05rem;
    color: rgba(245, 240, 225, 0.75);
    line-height: 1.7;
    margin-bottom: 32px;
    max-width: 440px;
}
/* Pro section: inverser le contraste des boutons dans le fond sombre */
.pro-section .btn {
    border-color: var(--cream);
    box-shadow: 5px 5px 0 var(--cream);
}
.pro-section .btn:hover {
    box-shadow: 7px 7px 0 var(--cream);
}
.pro-section .btn:active {
    box-shadow: 2px 2px 0 var(--cream);
}

.pro-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.pro-card {
    background: rgba(245, 240, 225, 0.05);
    border: 2px solid var(--wheat);
    padding: 28px 24px;
    position: relative;
}
.pro-card-featured {
    border-color: var(--cherry);
    background: rgba(164, 36, 59, 0.1);
    box-shadow: 5px 5px 0 var(--cherry);
}
.pro-card-badge {
    position: absolute;
    top: -12px;
    right: 16px;
    font-family: 'DM Mono', monospace;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 4px 12px;
    background: var(--cherry);
    color: var(--cream);
    border: 2px solid var(--cream);
}
.pro-card-name {
    font-family: 'DM Mono', monospace;
    font-size: 0.82rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--wheat);
    margin-bottom: 14px;
}
.pro-card-price {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.4rem;
    letter-spacing: 0.03em;
    line-height: 1;
    color: var(--cream);
    margin-bottom: 20px;
}
.pro-card-price span {
    font-family: 'DM Mono', monospace;
    font-size: 0.68rem;
    color: rgba(245,240,225,0.5);
    margin-left: 4px;
}
.pro-card ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.pro-card li {
    font-size: 0.9rem;
    color: rgba(245, 240, 225, 0.85);
    padding-left: 22px;
    position: relative;
}
.pro-card li::before {
    content: '\2713';
    position: absolute;
    left: 0;
    color: var(--wheat);
    font-weight: 700;
}
.pro-card-featured li::before { color: var(--cherry); }

/* =============================================
   CTA FINAL
============================================= */
.cta-final .section-inner { padding: 96px 32px; text-align: center; }
.cta-final-title {
    font-family: 'Bebas Neue', sans-serif;
    font-size: clamp(3rem, 6vw, 5.5rem);
    letter-spacing: 0.03em;
    line-height: 1;
    color: var(--coffee);
    margin-bottom: 20px;
}
.cta-final-sub {
    font-size: 1.15rem;
    color: var(--coffee);
    opacity: 0.75;
    margin-bottom: 40px;
}

/* =============================================
   EMPTY STATE
============================================= */
.empty-state {
    text-align: center;
    padding: 64px 20px;
    border: var(--border);
    background: var(--cream);
    box-shadow: 4px 4px 0 var(--coffee);
}
.empty-state p { margin-bottom: 24px; opacity: 0.7; }

/* =============================================
   RESPONSIVE
============================================= */
@media (max-width: 1024px) {
    .hero-inner { grid-template-columns: 1fr; gap: 48px; }
    .hero-right { height: 480px; }
    .cards-grid-4 { grid-template-columns: repeat(2, 1fr); }
    .steps-grid { grid-template-columns: 1fr; }
    .step-arrow { display: none; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .pro-grid { grid-template-columns: 1fr; gap: 48px; }
}
@media (max-width: 768px) {
    .hero-section { padding: 40px 0 60px; }
    .hero-inner { padding: 0 20px; min-height: auto; }
    .hero-right { height: 420px; }
    .hero-visual-card { width: 180px; padding: 12px; }
    .hero-visual-card-1 { left: 0; }
    .hero-visual-card-2 { right: 0; top: 80px; }
    .hero-visual-card-3 { left: 10%; bottom: 0; }
    .section-inner { padding: 64px 20px; }
    .cards-grid-4, .cards-grid-3 { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: 1fr; }
    .pro-cards { grid-template-columns: 1fr; }
}
@endsection

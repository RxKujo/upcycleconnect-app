@extends('layouts.public')

{{-- Page d'accueil publique (landing « .lp », néo-brutalisme isolé du reste du site). --}}

@section('pub_slot')@include('partials.pub-slot')@endsection

@section('title', 'Accueil')
@section('meta_description', 'UpcycleConnect — la plateforme française de l\'économie circulaire. Vendez, donnez, échangez vos objets et matériaux, et apprenez l\'upcycling avec une communauté engagée.')

@section('content')
@php
    $featured = $annonces[0] ?? null;
    $imgClasses = ['lp-img--wheat', 'lp-img--teal', 'lp-img--cherry', 'lp-img--forest'];
@endphp

<noscript><style>.lp .reveal{opacity:1!important;transform:none!important}</style></noscript>

<div class="lp">

    {{-- ============ RUBAN HAUT ============ --}}
    <div class="lp-tape" aria-hidden="true">
        <div class="lp-tape__inner" data-tape>
            <span>Réduire les déchets</span><i class="sep"></i>
            <span>Valoriser les matériaux</span><i class="sep"></i>
            <span>Connecter les communautés</span><i class="sep"></i>
            <span>100% récup'</span><i class="sep"></i>
            <span>0% gaspi</span><i class="sep"></i>
        </div>
    </div>

    {{-- ============ HERO ============ --}}
    <header class="lp-hero">
        <div class="lp-wrap lp-hero__grid">
            <div>
                <p class="lp-kicker"><span class="lp-tag" data-i18n="home.hero.kicker">Plateforme française · économie circulaire</span></p>
                <h1 class="lp-hero__title" data-i18n-html="home.hero.title">Donne une <span class="lp-hl lp-hl--cherry">seconde vie</span> à <span class="lp-hl lp-hl--forest">tout.</span></h1>
                <p class="lp-lead" data-i18n="home.hero.lead">Vends, donne ou échange tes objets et matériaux. Apprends l'upcycling avec des passionnés. Rejoins une communauté qui transforme les déchets des uns en trésors des autres.</p>
                <div class="lp-hero__ctas">
                    <a class="btn" href="{{ route('particulier.register') }}" data-i18n="home.hero.cta1">Je m'inscris gratuitement</a>
                    <a class="btn btn--ghost" href="{{ route('annonces.index') }}" data-i18n-html="home.hero.cta2">Explorer le marché &darr;</a>
                </div>
                <p class="lp-hero__note" data-i18n="home.hero.note">// inscription gratuite · sans engagement · sans carte bancaire</p>
            </div>

            <div class="lp-hero__card-zone">
                <div class="lp-sticker lp-sticker--gaspi">0% Gaspi</div>
                <div class="lp-sticker lp-sticker--recup">100% Récup'</div>
                <div class="lp-sticker lp-sticker--star">Coup de cœur</div>

                @if($featured)
                    @php $fMat = $featured['objets'][0]['materiau'] ?? null; @endphp
                    <a class="lp-annonce" data-tilt href="{{ route('annonces.show', $featured['id_annonce']) }}" aria-label="{{ $featured['titre'] }}">
                        <div class="lp-annonce__img lp-img--wheat">
                            @if(!empty($featured['objets'][0]['photos'][0]['url']))
                                <img src="{{ media_url($featured['objets'][0]['photos'][0]['url']) }}" alt="{{ $featured['titre'] }}">
                            @else
                                <span class="lp-annonce__ph">{{ strtoupper(substr($fMat ?? '?', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="lp-annonce__body">
                            <span class="lp-tag">{{ $fMat ? ucfirst($fMat) : 'Récup\'' }} · Matériaux</span>
                            <h3>{{ $featured['titre'] }}</h3>
                            <div class="lp-annonce__meta">
                                <span class="lp-mono">{{ $featured['ville'] ?? 'France' }}</span>
                                @if(($featured['type_annonce'] ?? '') === 'don')
                                    <span class="lp-prix">Don</span>
                                @else
                                    <span class="lp-prix lp-prix--cherry">{{ number_format($featured['prix'] ?? 0, 0, ',', ' ') }}&euro;</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @else
                    <article class="lp-annonce" data-tilt aria-label="Exemple d'annonce">
                        <div class="lp-annonce__img lp-img--wheat"><span class="lp-annonce__ph">B</span></div>
                        <div class="lp-annonce__body">
                            <span class="lp-tag">Bois · Matériaux</span>
                            <h3>Lot de palettes en chêne</h3>
                            <div class="lp-annonce__meta">
                                <span class="lp-mono">Ivry-sur-Seine · 94</span>
                                <span class="lp-prix">Don</span>
                            </div>
                        </div>
                    </article>
                @endif
            </div>
        </div>
    </header>

    {{-- ============ STATS ============ --}}
    <section class="lp-stats" aria-label="Notre impact">
        <div class="lp-wrap lp-stats__grid">
            <div class="lp-stat reveal"><b data-count="{{ (int)($stats['objets_sauves'] ?? 0) }}">0</b><span data-i18n="home.stats.objets">objets sauvés de la benne</span></div>
            <div class="lp-stat reveal"><b data-count="48" data-suffix=" t">0</b><span data-i18n-html="home.stats.co2">de CO&#8322; évité cette année</span></div>
            <div class="lp-stat reveal"><b data-count="{{ (int)($stats['membres'] ?? 0) }}">0</b><span data-i18n="home.stats.membres">membres actifs</span></div>
            <div class="lp-stat reveal"><b data-count="{{ (int)($stats['ateliers_an'] ?? 0) }}">0</b><span data-i18n="home.stats.ateliers">ateliers organisés</span></div>
        </div>
    </section>

    {{-- ============ MARCHÉ ============ --}}
    <section class="lp-marche" id="marche">
        <div class="lp-wrap">
            <div class="lp-sec-head reveal">
                <h2 data-i18n-html="home.market.title">Le marché de la <em>récup'</em></h2>
                <span class="lp-tag" data-i18n="home.market.tag">Vendre · Donner · Échanger</span>
            </div>

            <div class="lp-filtres" role="toolbar" aria-label="Filtrer les annonces">
                <button class="lp-chip on" data-filtre="tout" data-i18n="home.market.all">Tout voir</button>
                <button class="lp-chip" data-filtre="vente" data-i18n="home.market.forsale">À vendre</button>
                <button class="lp-chip" data-filtre="don" data-i18n="home.market.fordonation">À donner</button>
            </div>

            @if(count($annonces) > 0)
            <div class="lp-grille lp-grille--3" data-grille>
                @foreach($annonces as $i => $annonce)
                    @php
                        $type = $annonce['type_annonce'] ?? 'vente';
                        $mat  = $annonce['objets'][0]['materiau'] ?? null;
                        $photo = $annonce['objets'][0]['photos'][0]['url'] ?? null;
                    @endphp
                    <a class="lp-carte reveal" data-type="{{ $type }}" href="{{ route('annonces.show', $annonce['id_annonce']) }}">
                        <span class="lp-badge {{ $type === 'don' ? 'lp-badge--don' : 'lp-badge--vente' }}">{{ $type === 'don' ? 'Don' : 'Vente' }}</span>
                        <div class="lp-carte__img {{ $imgClasses[$i % count($imgClasses)] }}">
                            @if($photo)
                                <img src="{{ media_url($photo) }}" alt="{{ $annonce['titre'] }}">
                            @else
                                <span class="lp-annonce__ph">{{ strtoupper(substr($mat ?? '?', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div class="lp-carte__body">
                            <h3>{{ $annonce['titre'] }}</h3>
                            <p>{{ $mat ? ucfirst($mat) : 'Matériau' }} · {{ $annonce['vendeur']['prenom'] ?? 'Membre' }} {{ $annonce['vendeur']['nom_initiale'] ?? '' }}</p>
                            <div class="lp-carte__foot">
                                @if($type === 'don')
                                    <span class="lp-prix-sm gratuit">Gratuit</span>
                                @else
                                    <span class="lp-prix-sm">{{ number_format($annonce['prix'] ?? 0, 0, ',', ' ') }}&nbsp;&euro;</span>
                                @endif
                                <span class="lp-mono">{{ $annonce['ville'] ?? '' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            @else
            <div class="lp-empty">
                <p data-i18n="home.market.empty">Aucune annonce pour le moment.</p>
                <a class="btn" href="{{ route('particulier.register') }}" data-i18n="home.market.first">Être le premier à déposer</a>
            </div>
            @endif

            <div class="lp-sec-cta">
                <a class="btn btn--ghost" href="{{ route('annonces.index') }}" data-i18n-html="home.market.seeall">Voir tout le marché &rarr;</a>
            </div>
        </div>
    </section>

    {{-- ============ RUBAN MATÉRIAUX ============ --}}
    <div class="lp-tape lp-tape--forest" aria-hidden="true">
        <div class="lp-tape__inner" data-tape>
            <span>Bois</span><i class="sep"></i><span>Métal</span><i class="sep"></i><span>Textile</span><i class="sep"></i>
            <span>Verre</span><i class="sep"></i><span>Mobilier</span><i class="sep"></i><span>Électronique</span><i class="sep"></i>
            <span>Matériaux de chantier</span><i class="sep"></i>
        </div>
    </div>

    {{-- ============ PILIERS / COMMUNAUTÉ ============ --}}
    <section class="lp-piliers" id="communaute">
        <div class="lp-wrap">
            <div class="lp-sec-head reveal">
                <h2 data-i18n-html="home.pillars.title">Bien plus qu'une <em>marketplace</em></h2>
                <span class="lp-tag" data-i18n="home.pillars.tag">Apprendre · Partager · Progresser</span>
            </div>
            <div class="lp-grille lp-grille--2">
                <a class="lp-pilier reveal" href="{{ route('forum.index') }}">
                    <h3>
                        <span class="lp-pic">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M2.678 11.894a1 1 0 0 1 .287.801 10.97 10.97 0 0 1-.398 2c1.395-.323 2.247-.697 2.634-.893a1 1 0 0 1 .71-.074A8.06 8.06 0 0 0 8 14c3.996 0 7-2.807 7-6 0-3.192-3.004-6-7-6S1 4.808 1 8c0 1.468.617 2.83 1.678 3.894zm-.493 3.905a21.682 21.682 0 0 1-.713.129c-.2.032-.352-.176-.273-.362a9.68 9.68 0 0 0 .244-.637l.003-.01c.248-.72.45-1.548.524-2.319C.743 11.37 0 9.76 0 8c0-3.866 3.582-7 8-7s8 3.134 8 7-3.582 7-8 7a9.06 9.06 0 0 1-2.347-.306c-.52.263-1.639.742-3.468 1.105z"/></svg>
                        </span><span data-i18n="home.pillar.forum.title">Le forum d'entraide</span>
                    </h3>
                    <p data-i18n="home.pillar.forum.desc">Une question sur la restauration d'un meuble ? Un doute sur une peinture ? La communauté répond : bricoleurs du dimanche, artisans confirmés et passionnés de récup' s'entraident tous les jours.</p>
                    <span class="lp-tag" data-i18n-html="home.pillar.forum.cta">Rejoindre les discussions &rarr;</span>
                </a>
                <a class="lp-pilier reveal" href="{{ route('evenements.index') }}">
                    <h3>
                        <span class="lp-pic">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.282-.12a.5.5 0 0 0 .025-.917l-7.5-3.5ZM8 8.46 1.758 5.965 8 3.052l6.242 2.913L8 8.46Z"/><path d="M4.176 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466 4.176 9.032Z"/></svg>
                        </span><span data-i18n="home.pillar.training.title">Formations &amp; ateliers</span>
                    </h3>
                    <p data-i18n="home.pillar.training.desc">Des ateliers en ligne et en présentiel pour apprendre à transformer : menuiserie de récup', couture zéro déchet, soudure, rempaillage… Animés par des pros de la plateforme.</p>
                    <span class="lp-tag" data-i18n-html="home.pillar.training.cta">Voir le catalogue &rarr;</span>
                </a>
                <a class="lp-pilier reveal" href="{{ route('ressources.index') }}">
                    <h3>
                        <span class="lp-pic">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M5 10.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5Zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5Zm0-2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5Z"/><path d="M3 0h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-1h1v1a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v1H1V2a2 2 0 0 1 2-2Z"/><path d="M1 5v-.5a.5.5 0 0 1 1 0V5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1Zm0 3v-.5a.5.5 0 0 1 1 0V8h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1Zm0 3v-.5a.5.5 0 0 1 1 0v.5h.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1 0-1H1Z"/></svg>
                        </span><span data-i18n="home.pillar.resources.title">Ressources &amp; articles</span>
                    </h3>
                    <p data-i18n="home.pillar.resources.desc">Guides pratiques, tutos pas-à-pas et idées de projets pour passer à l'action : identifier un bois, chiner malin, réparer plutôt que jeter. Du concret, sans blabla.</p>
                    <span class="lp-tag" data-i18n-html="home.pillar.resources.cta">Lire les guides &rarr;</span>
                </a>
                <a class="lp-pilier reveal" href="{{ route('annonces.index') }}">
                    <h3>
                        <span class="lp-pic">
                            <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M12.166 8.94c-.524 1.062-1.234 2.12-1.96 3.07A31.493 31.493 0 0 1 8 14.58a31.481 31.481 0 0 1-2.206-2.57c-.726-.95-1.436-2.008-1.96-3.07C3.304 7.867 3 6.862 3 6a5 5 0 0 1 10 0c0 .862-.305 1.867-.834 2.94zM8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10z"/><path d="M8 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 1a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/></svg>
                        </span><span data-i18n="home.pillar.local.title">Le réseau local</span>
                    </h3>
                    <p data-i18n="home.pillar.local.desc">Trouve les annonces, ateliers et événements près de chez toi. L'upcycling, c'est d'abord du circuit court : moins de transport, plus de rencontres.</p>
                    <span class="lp-tag" data-i18n-html="home.pillar.local.cta">Explorer ma région &rarr;</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ============ ÉTAPES ============ --}}
    <section class="lp-etapes" id="etapes">
        <div class="lp-wrap">
            <div class="lp-sec-head reveal">
                <h2 data-i18n-html="home.steps.title">Comment ça <em>marche</em> ?</h2>
                <span class="lp-tag" data-i18n="home.steps.tag">3 étapes · 5 minutes</span>
            </div>
            <div class="lp-grille lp-grille--3">
                <article class="lp-etape reveal">
                    <div class="lp-num">1</div>
                    <h3 data-i18n="home.step1.title">Crée ton compte</h3>
                    <p data-i18n="home.step1.desc">Inscription gratuite en 2 minutes. Particulier ou pro, chacun a sa place. Pas de carte bancaire demandée.</p>
                </article>
                <article class="lp-etape reveal">
                    <div class="lp-num">2</div>
                    <h3 data-i18n="home.step2.title">Publie ou chine</h3>
                    <p data-i18n="home.step2.desc">Poste une annonce en quelques clics — vente ou don — ou explore les trouvailles près de chez toi.</p>
                </article>
                <article class="lp-etape reveal">
                    <div class="lp-num">3</div>
                    <h3 data-i18n-html="home.step3.title">Transforme &amp; partage</h3>
                    <p data-i18n="home.step3.desc">Donne une seconde vie à ta trouvaille, montre le résultat à la communauté et inspire les suivants.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ PRO ============ --}}
    <section class="lp-pro" id="pro">
        <div class="lp-wrap">
            <div class="lp-sec-head reveal">
                <h2 data-i18n-html="home.pro.title">Artisans &amp; pros, <em>c'est par ici</em></h2>
                <span class="lp-tag lp-tag--wheat" data-i18n="home.pro.tag">Boutique · Visibilité · Outils</span>
            </div>
            <div class="lp-tickets">
                <article class="lp-ticket lp-ticket--a reveal">
                    <h3 class="lp-plan">Essential Pro</h3>
                    <div class="lp-tarif">15,99&euro;<small data-i18n="home.pro.permonth">/mois</small></div>
                    <span class="lp-mono lp-ticket__sub" data-i18n="home.pro.essential.sub">Pour se lancer sérieusement</span>
                    <ul>
                        <li data-i18n="home.pro.essential.f1">Dashboard 30 jours</li>
                        <li data-i18n="home.pro.essential.f2">3 alertes matériaux géolocalisées</li>
                        <li data-i18n="home.pro.essential.f3">Statistiques locales</li>
                        <li data-i18n="home.pro.essential.f4">Badge « Pro vérifié »</li>
                    </ul>
                    <a class="btn btn--forest" href="{{ route('services-pro') }}" data-i18n="home.pro.cta">Découvrir l'offre</a>
                </article>
                <article class="lp-ticket lp-ticket--b reveal">
                    <span class="lp-tag lp-ticket__pop" data-i18n="home.pro.popular">Le + populaire</span>
                    <h3 class="lp-plan">Expert Pro</h3>
                    <div class="lp-tarif">29,99&euro;<small data-i18n="home.pro.permonth">/mois</small></div>
                    <span class="lp-mono lp-ticket__sub" data-i18n="home.pro.expert.sub">Pour développer son activité</span>
                    <ul>
                        <li data-i18n="home.pro.expert.f1">Tout Essential Pro, et&nbsp;:</li>
                        <li data-i18n="home.pro.expert.f2">Alertes matériaux illimitées</li>
                        <li data-i18n="home.pro.expert.f3">Badges communautaires</li>
                        <li data-i18n="home.pro.expert.f4">Export PDF annuel</li>
                    </ul>
                    <a class="btn" href="{{ route('services-pro') }}" data-i18n="home.pro.cta">Découvrir l'offre</a>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ TÉMOIGNAGES ============ --}}
    <section class="lp-temoins">
        <div class="lp-wrap">
            <div class="lp-sec-head reveal">
                <h2 data-i18n-html="home.reviews.title">Ils ont <em>sauvé</em> des trésors</h2>
                <span class="lp-tag" data-i18n="home.reviews.tag">La communauté témoigne</span>
            </div>
            <div class="lp-grille lp-grille--3">
                <article class="lp-temoin reveal">
                    <q data-i18n="home.review1.text">J'ai meublé tout mon atelier avec des matériaux trouvés ici. Et j'ai rencontré deux clients fidèles au passage.</q>
                    <div class="lp-qui"><span class="lp-avatar">SK</span><div><b>Sarah K.</b><span data-i18n="home.review1.role">Ébéniste · Montreuil</span></div></div>
                </article>
                <article class="lp-temoin reveal">
                    <q data-i18n="home.review2.text">Le forum m'a sauvé : je ne savais pas par où commencer pour restaurer ma commode. Trois réponses en une heure.</q>
                    <div class="lp-qui"><span class="lp-avatar lp-avatar--teal">YB</span><div><b>Yanis B.</b><span data-i18n="home.review2.role">Débutant motivé · Lyon</span></div></div>
                </article>
                <article class="lp-temoin reveal">
                    <q data-i18n="home.review3.text">Plutôt que de payer la déchetterie, je donne mes chutes de chantier. Elles partent en 48h et font des heureux.</q>
                    <div class="lp-qui"><span class="lp-avatar lp-avatar--cherry">ML</span><div><b>Marc L.</b><span data-i18n="home.review3.role">Menuisier · Nantes</span></div></div>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ RUBAN CTA ============ --}}
    <div class="lp-tape lp-tape--cherry" aria-hidden="true">
        <div class="lp-tape__inner" data-tape>
            <span>Rejoins la récup'</span><i class="sep"></i><span>Inscription gratuite</span><i class="sep"></i>
            <span>Rejoins la récup'</span><i class="sep"></i><span>Inscription gratuite</span><i class="sep"></i>
        </div>
    </div>

    {{-- ============ CTA FINAL ============ --}}
    <section class="lp-final" id="rejoindre">
        <div class="lp-wrap">
            <h2 data-i18n-html="home.final.title">Prêt&middot;e à <span class="lp-hl">chiner</span> ?</h2>
            <p data-i18n="home.final.lead">Rejoins les membres qui réduisent les déchets, valorisent les matériaux et connectent les communautés. C'est gratuit, et ça commence maintenant.</p>
            <form class="lp-final__form" data-register-url="{{ route('particulier.register') }}">
                <input type="email" placeholder="ton@email.fr" aria-label="Ton adresse email" data-i18n-ph="home.final.placeholder" required>
                <button class="btn" type="submit" data-i18n="home.final.cta">Créer mon compte</button>
            </form>
            <p class="lp-final__mini" data-i18n="home.final.mini">// gratuit pour les particuliers · offres dédiées pour les pros</p>
        </div>
    </section>

</div>
@endsection

@section('styles')
{{-- === Styles (portée limitée à .lp) === --}}
/* =====================================================
   LANDING PAGE (.lp) — néo-brutalisme, isolé du global
===================================================== */
.lp{
    --b: var(--border);
    --sh: 6px 6px 0 var(--coffee);
    --sh-big: 10px 10px 0 var(--coffee);
    background-image: radial-gradient(var(--coffee) 0.6px, transparent 0.6px);
    background-size: 26px 26px;
}
.lp .lp-wrap{ max-width:1180px; margin:0 auto; padding:0 24px; }
.lp .lp-mono{ font-family:'DM Mono',monospace; }
.lp h1,.lp h2,.lp h3{ font-family:'Bebas Neue',sans-serif; font-weight:400; letter-spacing:.5px; }

/* Boutons (héritent du .btn global, on renforce le style brutal) */
.lp .btn{
    display:inline-flex; align-items:center; gap:.55rem;
    font-family:'Bebas Neue',sans-serif; font-size:1.25rem; letter-spacing:1px;
    padding:.7rem 1.6rem; border:var(--b); box-shadow:var(--sh);
    background:var(--cherry); color:var(--cream); text-transform:none;
}
.lp .btn:hover{ transform:translate(-3px,-3px); box-shadow:9px 9px 0 var(--coffee); }
.lp .btn:active{ transform:translate(3px,3px); box-shadow:2px 2px 0 var(--coffee); }
.lp .btn--ghost{ background:var(--cream); color:var(--coffee); }
.lp .btn--forest{ background:var(--forest); color:var(--cream); }

.lp .lp-tag{
    font-family:'DM Mono',monospace; font-size:.75rem; letter-spacing:.08em;
    text-transform:uppercase; padding:.3rem .7rem; border:2px solid var(--coffee);
    background:var(--wheat); display:inline-block;
}
.lp .lp-tag--wheat{ background:var(--wheat); }

/* ---------- rubans ---------- */
.lp .lp-tape{
    background:var(--coffee); color:var(--cream);
    border-top:var(--b); border-bottom:var(--b);
    overflow:hidden; white-space:nowrap; padding:.55rem 0;
}
.lp .lp-tape--cherry{ background:var(--cherry); }
.lp .lp-tape--forest{ background:var(--forest); }
.lp .lp-tape__inner{ display:inline-flex; align-items:center; gap:2.4rem; animation:lp-scroll 28s linear infinite; will-change:transform; }
.lp .lp-tape span{ font-family:'DM Mono',monospace; font-size:.85rem; letter-spacing:.14em; text-transform:uppercase; }
.lp .lp-tape .sep{ width:7px; height:7px; background:var(--wheat); display:inline-block; transform:rotate(45deg); flex:none; }
@keyframes lp-scroll{ to{ transform:translateX(-50%); } }

/* ---------- hero ---------- */
.lp .lp-hero{ padding:72px 0 96px; position:relative; }
.lp .lp-hero__grid{ display:grid; grid-template-columns:1.25fr 1fr; gap:48px; align-items:center; }
.lp .lp-kicker{ margin-bottom:1.2rem; }
.lp .lp-hero__title{ font-size:clamp(3.2rem,8vw,6.6rem); line-height:.92; text-transform:uppercase; }
.lp .lp-hl{ display:inline-block; padding:0 .18em; border:var(--b); box-shadow:var(--sh); transform:rotate(-1.5deg); }
.lp .lp-hl--cherry{ background:var(--cherry); color:var(--cream); }
.lp .lp-hl--forest{ background:var(--forest); color:var(--cream); transform:rotate(1.2deg); }
.lp .lp-lead{ font-size:1.15rem; max-width:34rem; margin:1.6rem 0 2rem; line-height:1.55; }
.lp .lp-hero__ctas{ display:flex; gap:1rem; flex-wrap:wrap; }
.lp .lp-hero__note{ font-family:'DM Mono',monospace; font-size:.75rem; margin-top:1rem; opacity:.75; }

.lp .lp-hero__card-zone{ position:relative; display:flex; justify-content:center; perspective:900px; }
.lp .lp-annonce{
    width:min(340px,100%); background:var(--cream); border:var(--b); box-shadow:var(--sh-big);
    transform:rotate(2deg); transition:transform .2s ease; will-change:transform; color:var(--coffee); display:block;
}
.lp .lp-annonce__img{ height:200px; border-bottom:var(--b); position:relative; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.lp .lp-annonce__img img{ width:100%; height:100%; object-fit:cover; }
.lp .lp-annonce__ph{ font-family:'Bebas Neue',sans-serif; font-size:4.4rem; color:var(--coffee); opacity:.4; }
.lp .lp-annonce__body{ padding:1rem 1.1rem 1.2rem; }
.lp .lp-annonce__body h3{ font-size:1.6rem; text-transform:uppercase; margin-top:.6rem; }
.lp .lp-annonce__meta{ display:flex; justify-content:space-between; align-items:center; margin-top:.8rem; }
.lp .lp-annonce__meta .lp-mono{ font-size:.75rem; }
.lp .lp-prix{
    font-family:'Bebas Neue',sans-serif; font-size:1.7rem; background:var(--forest);
    color:var(--cream); border:2px solid var(--coffee); padding:.05rem .7rem; transform:rotate(-3deg);
}
.lp .lp-prix--cherry{ background:var(--cherry); }

/* visuels colorés (placeholders sans image) */
.lp .lp-img--wheat{ background:var(--wheat); }
.lp .lp-img--teal{ background:var(--teal); }
.lp .lp-img--cherry{ background:var(--cherry); }
.lp .lp-img--forest{ background:var(--forest); }
.lp .lp-img--teal .lp-annonce__ph,
.lp .lp-img--cherry .lp-annonce__ph,
.lp .lp-img--forest .lp-annonce__ph{ color:var(--cream); opacity:.55; }

.lp .lp-sticker{
    position:absolute; font-family:'Bebas Neue',sans-serif; text-align:center;
    border:var(--b); box-shadow:var(--sh); padding:.5rem 1rem; line-height:1; z-index:5; font-size:1.25rem;
}
.lp .lp-sticker--gaspi{ top:-26px; right:8%; background:var(--cherry); color:var(--cream); transform:rotate(8deg); }
.lp .lp-sticker--recup{ bottom:-20px; left:4%; background:var(--teal); color:var(--cream); transform:rotate(-6deg); }
.lp .lp-sticker--star{ top:34%; left:-14px; background:var(--wheat); transform:rotate(-12deg); font-size:1rem; }

/* ---------- stats ---------- */
.lp .lp-stats{ background:var(--coffee); border-top:var(--b); border-bottom:var(--b); padding:56px 0; }
.lp .lp-stats__grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:24px; }
.lp .lp-stat{ background:var(--cream); border:var(--b); box-shadow:6px 6px 0 var(--cherry); padding:1.4rem 1.2rem; text-align:center; transform:rotate(-1deg); }
.lp .lp-stat:nth-child(2n){ transform:rotate(1.2deg); box-shadow:6px 6px 0 var(--teal); }
.lp .lp-stat:nth-child(3){ box-shadow:6px 6px 0 var(--forest); }
.lp .lp-stat b{ font-family:'Bebas Neue',sans-serif; font-size:3.2rem; display:block; line-height:1; }
.lp .lp-stat span{ font-family:'DM Mono',monospace; font-size:.72rem; text-transform:uppercase; letter-spacing:.1em; }

/* ---------- têtes de section ---------- */
.lp .lp-sec-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:24px; margin-bottom:42px; flex-wrap:wrap; }
.lp .lp-sec-head h2{ font-size:clamp(2.6rem,5.5vw,4.4rem); line-height:.95; text-transform:uppercase; }
.lp .lp-sec-head h2 em{ font-style:normal; color:var(--cherry); }
.lp .lp-sec-head .lp-tag{ transform:rotate(-2deg); }
.lp .lp-sec-cta{ margin-top:38px; text-align:center; }

/* ---------- marché ---------- */
.lp .lp-marche{ padding:96px 0; }
.lp .lp-filtres{ display:flex; gap:.8rem; margin-bottom:34px; flex-wrap:wrap; }
.lp .lp-chip{
    font-family:'DM Mono',monospace; font-size:.8rem; text-transform:uppercase; letter-spacing:.08em;
    padding:.5rem 1.1rem; border:var(--b); background:var(--cream); box-shadow:4px 4px 0 var(--coffee);
    transition:transform .12s ease, box-shadow .12s ease; cursor:pointer; color:var(--coffee);
}
.lp .lp-chip:hover{ transform:translate(-2px,-2px); box-shadow:6px 6px 0 var(--coffee); }
.lp .lp-chip.on{ background:var(--coffee); color:var(--cream); }

.lp .lp-grille{ display:grid; gap:28px; }
.lp .lp-grille--2{ grid-template-columns:repeat(2,1fr); }
.lp .lp-grille--3{ grid-template-columns:repeat(3,1fr); }

.lp .lp-carte{
    background:var(--cream); border:var(--b); box-shadow:var(--sh);
    transition:transform .18s ease, box-shadow .18s ease, opacity .25s ease;
    position:relative; color:var(--coffee); display:block;
}
.lp .lp-carte:hover{ transform:translate(-4px,-4px) rotate(-.6deg); box-shadow:10px 10px 0 var(--coffee); }
.lp .lp-carte.hide{ display:none; }
.lp .lp-carte__img{ height:150px; border-bottom:var(--b); display:flex; align-items:center; justify-content:center; overflow:hidden; }
.lp .lp-carte__img img{ width:100%; height:100%; object-fit:cover; }
.lp .lp-carte__body{ padding:1rem 1.1rem 1.2rem; }
.lp .lp-carte__body h3{ font-size:1.45rem; text-transform:uppercase; }
.lp .lp-carte__body p{ font-size:.9rem; opacity:.8; margin:.35rem 0 .9rem; }
.lp .lp-carte__foot{ display:flex; justify-content:space-between; align-items:center; }
.lp .lp-carte .lp-mono{ font-size:.7rem; opacity:.7; }
.lp .lp-badge{
    position:absolute; top:-12px; right:-10px; font-family:'DM Mono',monospace; font-size:.68rem;
    text-transform:uppercase; letter-spacing:.06em; padding:.3rem .6rem; border:2px solid var(--coffee);
    box-shadow:3px 3px 0 var(--coffee); transform:rotate(4deg); z-index:2;
}
.lp .lp-badge--don{ background:var(--forest); color:var(--cream); }
.lp .lp-badge--vente{ background:var(--cherry); color:var(--cream); }
.lp .lp-prix-sm{ font-family:'Bebas Neue',sans-serif; font-size:1.6rem; }
.lp .lp-prix-sm.gratuit{ color:var(--forest); }

.lp .lp-empty{ text-align:center; padding:56px 20px; border:var(--b); background:var(--cream); box-shadow:var(--sh); }
.lp .lp-empty p{ margin-bottom:20px; opacity:.75; }

/* ---------- piliers ---------- */
.lp .lp-piliers{ padding:96px 0; background:var(--wheat); border-top:var(--b); border-bottom:var(--b); }
.lp .lp-pilier{ background:var(--cream); border:var(--b); box-shadow:var(--sh); padding:1.6rem 1.5rem; position:relative; overflow:hidden; color:var(--coffee); display:block; transition:transform .18s ease, box-shadow .18s ease; }
.lp .lp-pilier:hover{ transform:translate(-4px,-4px); box-shadow:10px 10px 0 var(--coffee); }
.lp .lp-pilier h3{ font-size:1.9rem; text-transform:uppercase; display:flex; align-items:center; gap:.7rem; }
.lp .lp-pic{ width:46px; height:46px; display:inline-flex; align-items:center; justify-content:center; border:var(--b); background:var(--cream); flex:none; transform:rotate(-4deg); }
.lp .lp-pic svg{ width:22px; height:22px; }
.lp .lp-pilier p{ margin-top:.8rem; line-height:1.55; font-size:.98rem; }
.lp .lp-pilier .lp-tag{ margin-top:1rem; }
.lp .lp-pilier::after{ content:""; position:absolute; right:-34px; bottom:-34px; width:110px; height:110px; border:var(--b); transform:rotate(12deg); opacity:.12; background:var(--coffee); }

/* ---------- étapes ---------- */
.lp .lp-etapes{ padding:96px 0; }
.lp .lp-etape{ background:var(--cream); border:var(--b); box-shadow:var(--sh); padding:1.6rem 1.4rem; }
.lp .lp-num{
    font-family:'Bebas Neue',sans-serif; font-size:2.6rem; width:64px; height:64px;
    display:flex; align-items:center; justify-content:center; border:var(--b);
    background:var(--cherry); color:var(--cream); box-shadow:4px 4px 0 var(--coffee);
    transform:rotate(-5deg); margin-bottom:1rem;
}
.lp .lp-etape:nth-child(2) .lp-num{ background:var(--teal); transform:rotate(4deg); }
.lp .lp-etape:nth-child(3) .lp-num{ background:var(--forest); transform:rotate(-3deg); }
.lp .lp-etape h3{ font-size:1.7rem; text-transform:uppercase; }
.lp .lp-etape p{ margin-top:.6rem; line-height:1.55; font-size:.95rem; }

/* ---------- pro / tickets ---------- */
.lp .lp-pro{ padding:96px 0; background:var(--teal); border-top:var(--b); border-bottom:var(--b); }
.lp .lp-pro .lp-sec-head h2{ color:var(--cream); }
.lp .lp-pro .lp-sec-head h2 em{ color:var(--wheat); }
.lp .lp-tickets{ display:grid; grid-template-columns:repeat(2,1fr); gap:32px; max-width:880px; margin:0 auto; }
.lp .lp-ticket{ background:var(--cream); border:var(--b); box-shadow:var(--sh-big); position:relative; padding:2rem 1.8rem 1.8rem; color:var(--coffee); }
.lp .lp-ticket::before,.lp .lp-ticket::after{ content:""; position:absolute; width:22px; height:22px; background:var(--teal); border:var(--b); border-radius:50%; top:50%; transform:translateY(-50%); }
.lp .lp-ticket::before{ left:-14px; }
.lp .lp-ticket::after{ right:-14px; }
.lp .lp-ticket--a{ transform:rotate(-1.2deg); }
.lp .lp-ticket--b{ transform:rotate(1.2deg); }
.lp .lp-plan{ font-size:2.2rem; text-transform:uppercase; }
.lp .lp-tarif{ font-family:'Bebas Neue',sans-serif; font-size:3.6rem; line-height:1; margin:.6rem 0 .2rem; }
.lp .lp-tarif small{ font-size:1.1rem; font-family:'DM Mono',monospace; letter-spacing:0; }
.lp .lp-ticket__sub{ font-size:.75rem; opacity:.7; }
.lp .lp-ticket ul{ list-style:none; margin:1.1rem 0 1.5rem; display:grid; gap:.55rem; padding:0; }
.lp .lp-ticket li{ font-size:.95rem; display:flex; gap:.6rem; align-items:baseline; }
.lp .lp-ticket li::before{ content:"+"; color:var(--forest); font-weight:700; font-family:'DM Mono',monospace; }
.lp .lp-ticket__pop{ position:absolute; top:-14px; right:18px; transform:rotate(3deg); background:var(--cherry); color:var(--cream); }

/* ---------- témoignages ---------- */
.lp .lp-temoins{ padding:96px 0; }
.lp .lp-temoins .lp-grille{ align-items:start; }
.lp .lp-temoin{ background:var(--cream); border:var(--b); box-shadow:var(--sh); padding:1.5rem 1.4rem; position:relative; }
.lp .lp-temoin:nth-child(1){ transform:rotate(-1deg); }
.lp .lp-temoin:nth-child(2){ transform:rotate(.8deg); top:18px; }
.lp .lp-temoin:nth-child(3){ transform:rotate(-.6deg); }
.lp .lp-temoin q{ font-family:'Playfair Display',serif; font-style:italic; font-size:1.08rem; line-height:1.55; quotes:"\00ab\00a0" "\00a0\00bb"; }
.lp .lp-qui{ margin-top:1.1rem; display:flex; align-items:center; gap:.7rem; }
.lp .lp-avatar{ width:44px; height:44px; border:var(--b); display:flex; align-items:center; justify-content:center; font-family:'Bebas Neue',sans-serif; font-size:1.3rem; background:var(--wheat); flex:none; }
.lp .lp-avatar--teal{ background:var(--teal); color:var(--cream); }
.lp .lp-avatar--cherry{ background:var(--cherry); color:var(--cream); }
.lp .lp-qui b{ display:block; font-size:.95rem; }
.lp .lp-qui span{ font-family:'DM Mono',monospace; font-size:.7rem; text-transform:uppercase; opacity:.7; }

/* ---------- CTA final ---------- */
.lp .lp-final{ background:var(--cherry); border-top:var(--b); padding:110px 0; text-align:center; color:var(--cream); overflow:hidden; }
.lp .lp-final h2{ font-size:clamp(3.2rem,9vw,7rem); line-height:.9; text-transform:uppercase; }
.lp .lp-final h2 .lp-hl{ background:var(--cream); color:var(--cherry); }
.lp .lp-final p{ max-width:36rem; margin:1.6rem auto 2.2rem; font-size:1.1rem; line-height:1.55; }
.lp .lp-final__form{ display:flex; gap:0; max-width:520px; margin:0 auto; flex-wrap:wrap; justify-content:center; align-items:stretch; }
.lp .lp-final__form input{
    font-family:'DM Mono',monospace; font-size:.95rem; padding:.95rem 1.1rem; border:var(--b);
    background:var(--cream); color:var(--coffee); min-width:280px; flex:1; box-shadow:var(--sh);
}
.lp .lp-final__form input:focus{ outline:3px dashed var(--coffee); outline-offset:3px; }
.lp .lp-final__form .btn{ background:var(--coffee); margin-left:14px; }
.lp .lp-final__mini{ font-family:'DM Mono',monospace; font-size:.72rem; margin-top:1.1rem; opacity:.85; }

/* ---------- reveal au scroll ---------- */
.lp .reveal{ opacity:0; transform:translateY(26px) rotate(.3deg); }
.lp .reveal.in{ opacity:1; transform:none; transition:opacity .6s ease, transform .6s cubic-bezier(.2,.9,.3,1.2); }

/* ---------- responsive ---------- */
@media (max-width:980px){
    .lp .lp-hero__grid{ grid-template-columns:1fr; gap:64px; }
    .lp .lp-stats__grid{ grid-template-columns:repeat(2,1fr); }
    .lp .lp-grille--3{ grid-template-columns:repeat(2,1fr); }
    .lp .lp-grille--2{ grid-template-columns:1fr; }
}
@media (max-width:640px){
    .lp .lp-grille--3,.lp .lp-grille--2,.lp .lp-tickets,.lp .lp-stats__grid{ grid-template-columns:1fr; }
    .lp .lp-temoin:nth-child(2){ top:0; }
    .lp .lp-final__form .btn{ margin:14px 0 0; }
    .lp .lp-hero{ padding-top:48px; }
}
@media (prefers-reduced-motion:reduce){
    .lp *,.lp *::before,.lp *::after{ animation:none!important; transition:none!important; }
    .lp .reveal{ opacity:1; transform:none; }
}
@endsection

@section('scripts')
{{-- === Scripts : rubans, reveal, compteurs, filtres, tilt, CTA === --}}
<script>
(function(){
    const root = document.querySelector('.lp');
    if(!root) return;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

    // marquees : dupliquer le contenu pour boucler proprement
    root.querySelectorAll('[data-tape]').forEach(t => { t.innerHTML += t.innerHTML; });

    // reveal au scroll
    if('IntersectionObserver' in window){
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => { if(e.isIntersecting){ e.target.classList.add('in'); io.unobserve(e.target); } });
        }, { threshold:.15 });
        root.querySelectorAll('.reveal').forEach(el => io.observe(el));
    } else {
        root.querySelectorAll('.reveal').forEach(el => el.classList.add('in'));
    }

    // compteurs animés
    const fmt = n => n.toLocaleString('fr-FR');
    const animate = (el) => {
        const target = +el.dataset.count, suffix = el.dataset.suffix || '';
        if(reduced){ el.textContent = fmt(target) + suffix; return; }
        const dur = 1400, t0 = performance.now();
        (function tick(now){
            const p = Math.min((now - t0) / dur, 1), ease = 1 - Math.pow(1 - p, 3);
            el.textContent = fmt(Math.round(target * ease)) + suffix;
            if(p < 1) requestAnimationFrame(tick);
        })(t0);
    };
    if('IntersectionObserver' in window){
        const ioS = new IntersectionObserver((entries) => {
            entries.forEach(e => { if(e.isIntersecting){ animate(e.target); ioS.unobserve(e.target); } });
        }, { threshold:.5 });
        root.querySelectorAll('[data-count]').forEach(el => ioS.observe(el));
    } else {
        root.querySelectorAll('[data-count]').forEach(animate);
    }

    // filtres marketplace
    const chips = root.querySelectorAll('.lp-chip');
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chips.forEach(c => c.classList.remove('on'));
            chip.classList.add('on');
            const f = chip.dataset.filtre;
            root.querySelectorAll('[data-grille] .lp-carte').forEach(carte => {
                carte.classList.toggle('hide', f !== 'tout' && carte.dataset.type !== f);
            });
        });
    });

    // tilt 3D de la carte hero
    const tilt = root.querySelector('[data-tilt]');
    if(tilt && !reduced){
        const zone = tilt.parentElement;
        zone.addEventListener('mousemove', e => {
            const r = zone.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - .5;
            const y = (e.clientY - r.top) / r.height - .5;
            tilt.style.transform = `rotate(2deg) rotateY(${x*14}deg) rotateX(${-y*14}deg)`;
        });
        zone.addEventListener('mouseleave', () => { tilt.style.transform = 'rotate(2deg)'; });
    }

    // CTA final : redirige vers l'inscription (avec email prérempli)
    const form = root.querySelector('.lp-final__form');
    if(form){
        form.addEventListener('submit', e => {
            e.preventDefault();
            const email = form.querySelector('input').value.trim();
            const url = form.dataset.registerUrl;
            window.location.href = email ? url + '?email=' + encodeURIComponent(email) : url;
        });
    }
})();
</script>
@endsection

@extends('layouts.public')

@section('title', 'Services Pro')
@section('meta_description', 'Offres professionnelles UpcycleConnect. Essential Pro et Expert Pro pour artisans et professionnels.')

{{-- Page de presentation des offres Pro : comparatif des trois formules
     (Freemium, Essential Pro, Expert Pro) et bloc promotion/sponsoring. --}}

@section('content')
{{-- === Comparatif des offres (3 cartes tarifaires) === --}}
<section class="section section-light">
    <div class="section-inner" style="text-align:center;">
        <p class="section-label" data-i18n="pro.kicker">Professionnels &amp; Artisans</p>
        <h1 class="section-title" data-i18n="pro.title">Offres Pro</h1>
        <p class="section-subtitle" style="margin:0 auto 56px;" data-i18n="pro.subtitle">Des outils avancés pour développer votre activité d'upcycling</p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:28px; text-align:left; align-items:start;">

            {{-- Formule gratuite --}}
            <div class="card" style="display:flex; flex-direction:column;">
                <p class="font-mono" style="font-size:0.82rem; margin-bottom:16px;" data-i18n="pro.plan.free">Freemium</p>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">0&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;" data-i18n="pro.month"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.1">Accès au marché</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.2">Commander des objets</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.3">Catalogue évènements</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.4">Espace conseils</span></li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-secondary btn-block" data-i18n="pro.cta.start">Commencer</a>
            </div>

            {{-- Formule Essential Pro --}}
            <div class="card" style="display:flex; flex-direction:column; border-color:var(--forest);">
                <p class="font-mono" style="font-size:0.82rem; margin-bottom:16px; color:var(--forest);" data-i18n="pro.plan.essential">Essential Pro</p>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">15,99&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;" data-i18n="pro.month"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.5">Tout du plan gratuit</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.6">Dashboard activité 30 jours</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.7">3 alertes matériaux (rayon 10 km)</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.8">Statistiques matériaux locaux</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.9">Impact écologique</span></li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-success btn-block" data-i18n="pro.cta.subscribe">S'abonner</a>
            </div>

            {{-- Formule Expert Pro (mise en avant « Populaire ») --}}
            <div class="card" style="display:flex; flex-direction:column; border-color:var(--cherry); box-shadow:5px 5px 0px var(--cherry);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <p class="font-mono" style="font-size:0.82rem; color:var(--cherry);" data-i18n="pro.plan.expert">Expert Pro</p>
                    <span class="badge badge-cherry" data-i18n="pro.popular">Populaire</span>
                </div>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">29,99&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;" data-i18n="pro.month"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.10">Tout de l'Essential +</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.11">Dashboard annuel + export PDF</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.12">Alertes illimitées</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.13">Rayon de recherche modulable</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.14">Système de badges</span></li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span><span data-i18n="pro.feat.15">Alertes push OneSignal</span></li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-primary btn-block" data-i18n="pro.cta.subscribe">S'abonner</a>
            </div>
        </div>
    </div>
</section>

{{-- === Promotion & sponsoring (mise en avant payante des produits) === --}}
<section class="section section-wheat">
    <div class="section-inner" style="text-align:center;">
        <h2 class="section-title" data-i18n="pro.sponsoring.title">Promotion &amp; Sponsoring</h2>
        <p style="font-size:1.05rem; opacity:0.7; margin-bottom:32px; max-width:600px; margin-left:auto; margin-right:auto;" data-i18n="pro.sponsoring.body">
            Mettez en avant vos produits sur UpcycleConnect avec un système de publicité équitable. 100&euro; par publicité par mois, limité à 5 publicités par professionnel.
        </p>
        <a href="{{ route('professionnel.register') }}" class="btn btn-secondary btn-lg" data-i18n="pro.cta.createaccount">Créer un compte Pro</a>
    </div>
</section>
@endsection

@section('styles')
{{-- === Responsive : empilement des cartes tarifaires sur petits ecrans === --}}
@media (max-width: 1024px) {
    .section-inner [style*="grid-template-columns:repeat(3"] { grid-template-columns: 1fr !important; }
}
@endsection

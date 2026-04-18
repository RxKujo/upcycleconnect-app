@extends('layouts.public')

@section('title', 'Services Pro')
@section('meta_description', 'Offres professionnelles UpcycleConnect. Essential Pro et Expert Pro pour artisans et professionnels.')

@section('content')
<section class="section section-light">
    <div class="section-inner" style="text-align:center;">
        <p class="section-label">Professionnels &amp; Artisans</p>
        <h1 class="section-title">Offres Pro</h1>
        <p class="section-subtitle" style="margin:0 auto 56px;">Des outils avancés pour développer votre activité d'upcycling</p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:28px; text-align:left; align-items:start;">
            {{-- Freemium --}}
            <div class="card" style="display:flex; flex-direction:column;">
                <p class="font-mono" style="font-size:0.82rem; margin-bottom:16px;">Freemium</p>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">0&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Accès au marché</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Commander des objets</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Catalogue évènements</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Espace conseils</li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-secondary btn-block">Commencer</a>
            </div>

            {{-- Essential Pro --}}
            <div class="card" style="display:flex; flex-direction:column; border-color:var(--forest);">
                <p class="font-mono" style="font-size:0.82rem; margin-bottom:16px; color:var(--forest);">Essential Pro</p>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">15,99&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Tout du plan gratuit</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Dashboard activité 30 jours</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>3 alertes matériaux (rayon 10 km)</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Statistiques matériaux locaux</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--forest); font-weight:700;">&#10003;</span>Impact écologique</li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-success btn-block">S'abonner</a>
            </div>

            {{-- Expert Pro --}}
            <div class="card" style="display:flex; flex-direction:column; border-color:var(--cherry); box-shadow:5px 5px 0px var(--cherry);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                    <p class="font-mono" style="font-size:0.82rem; color:var(--cherry);">Expert Pro</p>
                    <span class="badge badge-cherry">Populaire</span>
                </div>
                <p style="font-family:'Bebas Neue',sans-serif; font-size:3.5rem; letter-spacing:0.04em; line-height:1; margin-bottom:28px;">29,99&euro;<span class="font-mono" style="font-size:0.78rem; opacity:0.6;"> /mois</span></p>
                <ul style="display:flex; flex-direction:column; gap:10px; margin-bottom:32px; flex:1;">
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Tout de l'Essential +</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Dashboard annuel + export PDF</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Alertes illimitées</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Rayon de recherche modulable</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Système de badges</li>
                    <li style="font-size:0.95rem; padding-left:24px; position:relative;"><span style="position:absolute; left:0; color:var(--cherry); font-weight:700;">&#10003;</span>Alertes push OneSignal</li>
                </ul>
                <a href="{{ route('professionnel.register') }}" class="btn btn-primary btn-block">S'abonner</a>
            </div>
        </div>
    </div>
</section>

<section class="section section-wheat">
    <div class="section-inner" style="text-align:center;">
        <h2 class="section-title">Promotion &amp; Sponsoring</h2>
        <p style="font-size:1.05rem; opacity:0.7; margin-bottom:32px; max-width:600px; margin-left:auto; margin-right:auto;">
            Mettez en avant vos produits sur UpcycleConnect avec un système de publicité équitable. 100&euro; par publicité par mois, limité à 5 publicités par professionnel.
        </p>
        <a href="{{ route('professionnel.register') }}" class="btn btn-secondary btn-lg">Créer un compte Pro</a>
    </div>
</section>
@endsection

@section('styles')
@media (max-width: 1024px) {
    .section-inner [style*="grid-template-columns:repeat(3"] { grid-template-columns: 1fr !important; }
}
@endsection

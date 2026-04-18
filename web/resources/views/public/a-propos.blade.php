@extends('layouts.public')

@section('title', 'À propos')

@section('content')
<section class="section section-light">
    <div class="section-inner" style="max-width:800px;">
        <p class="section-label">Notre histoire</p>
        <h1 class="section-title">À propos d'UpcycleConnect</h1>

        <p style="font-size:1.1rem; line-height:1.8; margin-bottom:32px;">
            UpcycleConnect est une entreprise innovante et écologique qui permet une réduction des déchets grâce à une valorisation du recyclage.
        </p>

        <p style="font-size:1rem; line-height:1.8; margin-bottom:32px; opacity:0.8;">
            Notre plateforme met en relation les particuliers qui souhaitent donner ou vendre des objets et matériaux avec les artisans et professionnels qui leur donnent une seconde vie. Chaque objet sauvé des déchets contribue à réduire notre impact environnemental.
        </p>

        <h2 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; letter-spacing:0.06em; margin-bottom:16px; margin-top:48px;">Notre mission</h2>
        <p style="font-size:1rem; line-height:1.8; margin-bottom:32px; opacity:0.8;">
            Moderniser l'architecture Web de la gestion des échanges de matériaux recyclés tout en gardant l'âme d'UpcycleConnect, nécessaire pour une satisfaction client optimale.
        </p>

        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:28px; margin-top:48px;">
            <div style="border:var(--border); padding:28px; background:white; box-shadow:var(--shadow-sm);">
                <span style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--cherry); display:block; margin-bottom:8px; line-height:1;">Réduire</span>
                <p style="font-size:0.9rem; opacity:0.7;">Les déchets en favorisant le réemploi et l'upcycling</p>
            </div>
            <div style="border:var(--border); padding:28px; background:white; box-shadow:var(--shadow-sm);">
                <span style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--forest); display:block; margin-bottom:8px; line-height:1;">Connecter</span>
                <p style="font-size:0.9rem; opacity:0.7;">Les particuliers et les artisans autour de l'économie circulaire</p>
            </div>
            <div style="border:var(--border); padding:28px; background:white; box-shadow:var(--shadow-sm);">
                <span style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--teal); display:block; margin-bottom:8px; line-height:1;">Former</span>
                <p style="font-size:0.9rem; opacity:0.7;">Via des ateliers, formations et conseils pratiques</p>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
@media (max-width: 768px) {
    .section-inner [style*="grid-template-columns:repeat(3"] { grid-template-columns: 1fr !important; }
}
@endsection

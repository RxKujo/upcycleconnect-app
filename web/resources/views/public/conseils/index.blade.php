@extends('layouts.public')

@section('title', 'Conseils')
@section('meta_description', 'Conseils et astuces pour l\'upcycling. Articles rédigés par l\'équipe UpcycleConnect.')

@section('content')
<div class="page-container">
    <p class="section-label">Ressources</p>
    <h1 class="page-title">Conseils &amp; astuces</h1>
    <p class="page-subtitle">Articles et guides rédigés par l'équipe UpcycleConnect</p>

    @if(count($articles) > 0)
    <div class="grid-3">
        @foreach($articles as $article)
        <a href="{{ route('conseils.show', $article['id_article']) }}" class="card" style="display:flex; flex-direction:column;">
            @if(!empty($article['categorie']))
            <span class="badge badge-teal" style="align-self:flex-start; margin-bottom:16px;">{{ $article['categorie'] }}</span>
            @endif
            <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; letter-spacing:0.04em; margin-bottom:12px; line-height:1.1;">{{ $article['titre'] }}</h3>
            <p style="font-size:0.88rem; opacity:0.7; margin-bottom:16px; line-height:1.5; flex:1;">{{ Illuminate\Support\Str::limit(strip_tags($article['contenu']), 150) }}</p>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto;">
                <span class="font-mono" style="font-size:0.72rem; opacity:0.5;">
                    {{ $article['auteur_prenom'] ?? '' }} {{ $article['auteur_nom_initiale'] ?? '' }}
                </span>
                @if(!empty($article['date_publication']))
                <span class="font-mono" style="font-size:0.72rem; opacity:0.5;">
                    {{ \Carbon\Carbon::parse($article['date_publication'])->locale('fr')->isoFormat('D MMM Y') }}
                </span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div style="text-align:center; padding:80px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Aucun article</h3>
        <p style="opacity:0.6;">Les articles apparaîtront ici une fois publiés.</p>
    </div>
    @endif
</div>
@endsection

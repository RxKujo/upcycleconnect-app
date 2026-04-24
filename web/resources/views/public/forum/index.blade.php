@extends('layouts.public')

@section('title', 'Forum')
@section('meta_description', 'Forum communautaire UpcycleConnect. Posez vos questions et partagez vos expériences.')

@section('content')
<div class="page-container">
    <p class="section-label">Communauté</p>
    <h1 class="page-title">Forum</h1>
    <p class="page-subtitle">Échangez avec la communauté, posez vos questions et partagez vos retours d'expérience</p>

    <div style="margin-bottom:32px;">
        <a href="{{ route('particulier.login') }}?intent=forum" class="btn btn-primary" data-requires-auth data-auth-title="Connectez-vous pour poster">
            + Nouveau sujet
        </a>
    </div>

    @if(count($sujets) > 0)
    <div style="border:var(--border); box-shadow:var(--shadow);">
        @foreach($sujets as $index => $sujet)
        <a href="{{ route('forum.show', $sujet['id_sujet']) }}" style="display:flex; align-items:center; justify-content:space-between; padding:20px 24px; background:var(--cream); {{ $index < count($sujets) - 1 ? 'border-bottom:var(--border);' : '' }} transition:background 0.15s;" onmouseover="this.style.background='white'" onmouseout="this.style.background='var(--cream)'">
            <div style="flex:1;">
                <div style="display:flex; gap:8px; align-items:center; margin-bottom:6px;">
                    @if(!empty($sujet['categorie']))
                    <span class="badge badge-waiting" style="font-size:0.6rem; padding:2px 8px;">{{ $sujet['categorie'] }}</span>
                    @endif
                    <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.25rem; letter-spacing:0.04em; line-height:1;">{{ $sujet['titre'] }}</h3>
                </div>
                <p class="font-mono" style="font-size:0.72rem; opacity:0.5;">
                    Par {{ $sujet['createur_prenom'] ?? '' }} {{ $sujet['createur_nom_initiale'] ?? '' }}
                    &middot; {{ \Carbon\Carbon::parse($sujet['date_creation'])->locale('fr')->diffForHumans() }}
                </p>
            </div>
            <div style="text-align:center; min-width:80px;">
                <span style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; color:var(--teal); display:block; line-height:1;">{{ $sujet['nb_messages'] ?? 0 }}</span>
                <span class="font-mono" style="font-size:0.65rem; opacity:0.5;">Messages</span>
            </div>
        </a>
        @endforeach
    </div>
    @else
    <div style="text-align:center; padding:80px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Aucun sujet</h3>
        <p style="opacity:0.6;">Soyez le premier à lancer une discussion !</p>
    </div>
    @endif
</div>
@endsection

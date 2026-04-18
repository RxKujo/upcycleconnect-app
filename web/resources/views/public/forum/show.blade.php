@extends('layouts.public')

@section('title', $sujet['titre'] ?? 'Sujet')
@section('og_title', $sujet['titre'] ?? 'Forum')

@section('content')
<div class="page-container" style="max-width:900px;">
    <a href="{{ route('forum.index') }}" style="display:inline-flex; align-items:center; gap:8px; font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--coffee); margin-bottom:32px; opacity:0.6; transition:opacity 0.15s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour au forum
    </a>

    {{-- En-tête du sujet --}}
    <div style="margin-bottom:40px;">
        @if(!empty($sujet['categorie']))
        <span class="badge badge-teal" style="margin-bottom:16px;">{{ $sujet['categorie'] }}</span>
        @endif
        <h1 style="font-family:'Bebas Neue',sans-serif; font-size:clamp(2rem,4vw,3rem); letter-spacing:0.04em; line-height:1; margin-bottom:16px;">{{ $sujet['titre'] }}</h1>
        <p class="font-mono" style="font-size:0.78rem; opacity:0.5;">
            Lancé par {{ $sujet['createur_prenom'] ?? '' }} {{ $sujet['createur_nom_initiale'] ?? '' }}
            &middot; {{ \Carbon\Carbon::parse($sujet['date_creation'])->locale('fr')->isoFormat('D MMMM Y') }}
            &middot; {{ count($sujet['messages'] ?? []) }} messages
        </p>
    </div>

    {{-- Messages --}}
    <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:48px;">
        @forelse(($sujet['messages'] ?? []) as $index => $message)
        <div style="border:var(--border); padding:24px; background:{{ $index === 0 ? 'white' : 'var(--cream)' }}; {{ $index === 0 ? 'box-shadow:var(--shadow-sm);' : '' }}">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-weight:600; font-size:0.95rem;">
                    {{ $message['auteur_prenom'] ?? '' }} {{ $message['auteur_nom_initiale'] ?? '' }}
                </span>
                <span class="font-mono" style="font-size:0.7rem; opacity:0.4;">
                    {{ \Carbon\Carbon::parse($message['date_publication'])->locale('fr')->diffForHumans() }}
                </span>
            </div>
            <p style="font-size:0.95rem; line-height:1.7; white-space:pre-line;">{{ $message['contenu'] }}</p>
        </div>
        @empty
        <p style="text-align:center; opacity:0.6; padding:40px;">Aucun message dans ce sujet.</p>
        @endforelse
    </div>

    {{-- Répondre (mur d'inscription) --}}
    <div style="border:var(--border); padding:32px; background:white; box-shadow:var(--shadow-sm); text-align:center;">
        <p style="font-size:1rem; margin-bottom:16px; opacity:0.7;">Vous souhaitez participer à cette discussion ?</p>
        <a href="{{ route('particulier.login') }}?intent=forum_reponse&sujet={{ $sujet['id_sujet'] }}" class="btn btn-primary" data-requires-auth data-auth-title="Connectez-vous pour répondre">
            Se connecter pour répondre
        </a>
    </div>
</div>
@endsection

@extends('layouts.public')

@section('title', $article['titre'] ?? 'Article')
@section('meta_description', Illuminate\Support\Str::limit(strip_tags($article['contenu'] ?? ''), 160))
@section('og_title', $article['titre'] ?? 'Article')
@section('og_description', Illuminate\Support\Str::limit(strip_tags($article['contenu'] ?? ''), 160))

@section('content')
<div class="page-container" style="max-width:800px;">
    <a href="{{ route('conseils.index') }}" style="display:inline-flex; align-items:center; gap:8px; font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--coffee); margin-bottom:32px; opacity:0.6; transition:opacity 0.15s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour aux conseils
    </a>

    @if(!empty($article['categorie']))
    <span class="badge badge-teal" style="margin-bottom:20px;">{{ $article['categorie'] }}</span>
    @endif

    <h1 style="font-family:'Bebas Neue',sans-serif; font-size:clamp(2.5rem,5vw,3.5rem); letter-spacing:0.04em; line-height:1; margin-bottom:20px;">{{ $article['titre'] }}</h1>

    <div style="display:flex; gap:16px; align-items:center; margin-bottom:40px; padding-bottom:20px; border-bottom:2px solid rgba(18,3,9,0.1);">
        <span style="font-size:0.95rem; opacity:0.7;">Par {{ $article['auteur_prenom'] ?? '' }} {{ $article['auteur_nom_initiale'] ?? '' }}</span>
        @if(!empty($article['date_publication']))
        <span class="font-mono" style="font-size:0.75rem; opacity:0.4;">{{ \Carbon\Carbon::parse($article['date_publication'])->locale('fr')->isoFormat('D MMMM Y') }}</span>
        @endif
    </div>

    <div style="font-size:1.05rem; line-height:1.8; white-space:pre-line;">{{ $article['contenu'] }}</div>

    <div style="margin-top:64px; border-top:2px solid rgba(18,3,9,0.1); padding-top:32px; text-align:center;">
        <a href="{{ route('conseils.index') }}" class="btn btn-secondary">Voir tous les articles</a>
    </div>
</div>
@endsection

@extends('layouts.public')

@section('title', $annonce['titre'] ?? 'Annonce')
@section('meta_description', Illuminate\Support\Str::limit($annonce['description'] ?? '', 160))
@section('og_title', $annonce['titre'] ?? 'Annonce')
@section('og_description', Illuminate\Support\Str::limit($annonce['description'] ?? '', 160))
@section('og_type', 'product')

@php
    $materiauLabels = [
        'bois' => 'Bois', 'metal' => 'Métal', 'textile' => 'Textile',
        'plastique' => 'Plastique', 'verre' => 'Verre',
        'electronique' => 'Électronique', 'autre' => 'Autre',
    ];
    $etatLabels = [
        'neuf' => 'Neuf', 'bon' => 'Bon état',
        'use' => 'Usé', 'a_reparer' => 'À réparer',
    ];
@endphp

@section('content')
<div class="page-container">
    <a href="{{ route('marche.index') }}" style="display:inline-flex; align-items:center; gap:8px; font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--coffee); margin-bottom:24px; opacity:0.6; transition:opacity 0.15s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour au marché
    </a>

    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:48px; align-items:start;">
        {{-- Photos --}}
        <div>
            <div style="border:var(--border); box-shadow:var(--shadow); background:var(--wheat); height:400px; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:16px;">
                @if(!empty($annonce['objets']) && !empty($annonce['objets'][0]['photos']))
                <img src="/uploads/{{ $annonce['objets'][0]['photos'][0]['url'] }}" alt="{{ $annonce['titre'] }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                <span class="font-mono" style="font-size:0.85rem; opacity:0.4;">Pas de photo</span>
                @endif
            </div>
            @if(!empty($annonce['objets']) && count($annonce['objets'][0]['photos'] ?? []) > 1)
            <div style="display:flex; gap:12px;">
                @foreach($annonce['objets'][0]['photos'] as $photo)
                <div style="width:80px; height:80px; border:var(--border); background:var(--wheat); overflow:hidden;">
                    <img src="/uploads/{{ $photo['url'] }}" alt="" style="width:100%; height:100%; object-fit:cover;">
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <span class="badge {{ ($annonce['type_annonce'] ?? '') === 'don' ? 'badge-valid' : 'badge-cherry' }}">{{ ($annonce['type_annonce'] ?? '') === 'don' ? 'Don' : 'Vente' }}</span>
                @if(!empty($annonce['objets']))
                <span class="badge badge-waiting">{{ $materiauLabels[$annonce['objets'][0]['materiau'] ?? ''] ?? '' }}</span>
                <span class="badge" style="background:transparent;">{{ $etatLabels[$annonce['objets'][0]['etat'] ?? ''] ?? '' }}</span>
                @endif
            </div>

            <h1 style="font-family:'Bebas Neue',sans-serif; font-size:clamp(2rem,4vw,3rem); letter-spacing:0.04em; line-height:1; margin-bottom:20px;">{{ $annonce['titre'] }}</h1>

            @if(($annonce['type_annonce'] ?? '') === 'vente')
            <p style="font-family:'Bebas Neue',sans-serif; font-size:3rem; color:var(--cherry); margin-bottom:24px;">{{ number_format($annonce['prix'] ?? 0, 2) }}&euro;</p>
            @else
            <p style="margin-bottom:24px;"><span class="badge badge-valid" style="font-size:0.85rem; padding:6px 16px;">Gratuit</span></p>
            @endif

            <p style="font-size:1rem; line-height:1.7; margin-bottom:32px; white-space:pre-line;">{{ $annonce['description'] }}</p>

            {{-- Vendeur (anonymisé RGPD) --}}
            <div style="border:var(--border); padding:20px; margin-bottom:24px; background:white;">
                <p class="font-mono" style="font-size:0.75rem; color:var(--teal); margin-bottom:10px;">Vendeur</p>
                <p style="font-size:1.05rem; font-weight:600;">
                    {{ $annonce['vendeur']['prenom'] ?? '' }} {{ $annonce['vendeur']['nom_initiale'] ?? '' }}
                    @if($annonce['vendeur']['certifie'] ?? false)
                    <span style="color:var(--forest); font-weight:700;" title="Compte certifié"> &#10003; Certifié</span>
                    @endif
                </p>
                <p style="font-size:0.9rem; opacity:0.6;">{{ $annonce['ville'] ?? '' }}</p>
                @if(($annonce['vendeur']['score_upcycling'] ?? 0) > 0)
                <p style="font-size:0.85rem; margin-top:8px;">
                    <span class="font-mono" style="font-size:0.7rem; color:var(--forest);">Score Upcycling : {{ $annonce['vendeur']['score_upcycling'] }}</span>
                </p>
                @endif
            </div>

            {{-- Actions (mur d'inscription) --}}
            <div style="display:flex; flex-direction:column; gap:12px;">
                <a href="{{ route('particulier.login') }}?intent=commande&annonce={{ $annonce['id_annonce'] }}"
                   class="btn btn-primary btn-lg btn-block"
                   data-requires-auth
                   data-auth-title="Connectez-vous pour commander">
                    @if(($annonce['type_annonce'] ?? '') === 'don')
                    Récupérer cet objet
                    @else
                    Commander &middot; {{ number_format($annonce['prix'] ?? 0, 2) }}&euro;
                    @endif
                </a>
                <a href="{{ route('particulier.login') }}?intent=message&annonce={{ $annonce['id_annonce'] }}"
                   class="btn btn-secondary btn-block"
                   data-requires-auth
                   data-auth-title="Connectez-vous pour contacter le vendeur">
                   Contacter le vendeur
                </a>
            </div>

            {{-- Infos objet --}}
            @if(!empty($annonce['objets']))
            <div style="margin-top:32px; border:var(--border); padding:20px; background:white;">
                <p class="font-mono" style="font-size:0.75rem; color:var(--teal); margin-bottom:12px;">Caractéristiques</p>
                @foreach($annonce['objets'] as $objet)
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.9rem;">
                    <div><span style="opacity:0.6;">Matériau :</span> <strong>{{ $materiauLabels[$objet['materiau']] ?? '' }}</strong></div>
                    <div><span style="opacity:0.6;">État :</span> <strong>{{ $etatLabels[$objet['etat']] ?? '' }}</strong></div>
                    @if(!empty($objet['categorie']))
                    <div><span style="opacity:0.6;">Catégorie :</span> <strong>{{ $objet['categorie'] }}</strong></div>
                    @endif
                    @if(!empty($objet['poids_kg']))
                    <div><span style="opacity:0.6;">Poids :</span> <strong>{{ $objet['poids_kg'] }} kg</strong></div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            <p class="font-mono" style="font-size:0.7rem; margin-top:16px; opacity:0.4;">
                Remise : {{ ($annonce['mode_remise'] ?? '') === 'conteneur' ? 'Via conteneur' : 'En main propre' }}
            </p>
        </div>
    </div>
</div>
@endsection

@section('styles')
@media (max-width: 768px) {
    .page-container > div:last-of-type { grid-template-columns: 1fr !important; }
}
@endsection

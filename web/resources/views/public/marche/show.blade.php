@extends('layouts.public')

@section('pub_slot')@include('partials.pub-slot')@endsection

@section('title', $annonce['titre'] ?? 'Annonce')
@section('meta_description', Illuminate\Support\Str::limit($annonce['description'] ?? '', 160))
@section('og_title', $annonce['titre'] ?? 'Annonce')
@section('og_description', Illuminate\Support\Str::limit($annonce['description'] ?? '', 160))
@section('og_type', 'product')

@php
    $materiauLabels = collect($materiaux ?? [])->pluck('libelle', 'code')->all();
    $etatLabels = [
        'neuf' => 'Neuf', 'bon' => 'Bon état',
        'use' => 'Usé', 'a_reparer' => 'À réparer',
    ];
@endphp

@section('content')
<div class="page-container">
    <a href="{{ route('annonces.index') }}" style="display:inline-flex; align-items:center; gap:8px; font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--coffee); margin-bottom:24px; opacity:0.6; transition:opacity 0.15s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour au marché
    </a>

    <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:48px; align-items:start;">
        
        <div>
            <div style="border:var(--border); box-shadow:var(--shadow); background:var(--wheat); height:400px; display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:16px;">
                @if(!empty($annonce['objets']) && !empty($annonce['objets'][0]['photos']))
                <img id="mainPhoto" src="{{ media_url($annonce['objets'][0]['photos'][0]['url']) }}" alt="{{ $annonce['titre'] }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                <span class="font-mono" style="font-size:0.85rem; opacity:0.4;">Pas de photo</span>
                @endif
            </div>
            @if(!empty($annonce['objets']) && count($annonce['objets'][0]['photos'] ?? []) > 1)
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                @foreach($annonce['objets'][0]['photos'] as $i => $photo)
                <button type="button" class="thumb-btn{{ $i === 0 ? ' active' : '' }}" data-src="{{ media_url($photo['url']) }}"
                        style="width:80px; height:80px; border:var(--border); background:var(--wheat); overflow:hidden; padding:0; cursor:pointer;">
                    <img src="{{ media_url($photo['url']) }}" alt="Vue {{ $i + 1 }}" style="width:100%; height:100%; object-fit:cover; display:block;">
                </button>
                @endforeach
            </div>
            @endif
        </div>

        <div>
            <div style="display:flex; gap:8px; margin-bottom:16px;">
                <span class="badge {{ ($annonce['type_annonce'] ?? '') === 'don' ? 'badge-valid' : 'badge-cherry' }}">{{ ($annonce['type_annonce'] ?? '') === 'don' ? 'Don' : 'Vente' }}</span>
                @if(!empty($annonce['objets']))
                <span class="badge badge-waiting">{{ $materiauLabels[$annonce['objets'][0]['materiau'] ?? ''] ?? ucfirst($annonce['objets'][0]['materiau'] ?? '') }}</span>
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

            {{-- Achat réservé aux professionnels / artisans (cf. cahier des charges) --}}
            <div id="buyBlock" style="display:none; flex-direction:column; gap:12px;">
                <button type="button"
                        id="btnAddPanier"
                        class="btn btn-primary btn-lg btn-block"
                        data-id="{{ $annonce['id_annonce'] }}"
                        data-titre="{{ $annonce['titre'] }}"
                        data-prix="{{ $annonce['prix'] ?? 0 }}"
                        data-type="{{ $annonce['type_annonce'] ?? 'vente' }}"
                        data-mode="{{ $annonce['mode_remise'] ?? '' }}"
                        data-vendeur="{{ ($annonce['vendeur']['prenom'] ?? '') . ' ' . ($annonce['vendeur']['nom_initiale'] ?? '') }}">
                    @if(($annonce['type_annonce'] ?? '') === 'don')
                    Ajouter au panier · Gratuit
                    @else
                    Ajouter au panier · {{ number_format($annonce['prix'] ?? 0, 2) }}€
                    @endif
                </button>
                <a href="{{ route('panier.index') }}" class="btn btn-secondary btn-block">Voir mon panier</a>
            </div>

            {{-- Message affiché aux particuliers / visiteurs --}}
            <div id="proOnlyNote" style="display:none; padding:14px 16px; background:var(--wheat,#D8C99B); border:2px solid var(--coffee,#120309); font-size:0.9rem; line-height:1.5;">
                <strong>Récupération réservée aux professionnels et artisans.</strong><br>
                Les objets déposés par les particuliers sont récupérés par les pros via les conteneurs UpcycleConnect.
                <span id="proOnlyCta"></span>
            </div>
            <p id="panierFlash" style="display:none; margin-top:12px; padding:10px 14px; background:#dff5e1; border-left:3px solid var(--forest,#3a7d44); font-size:0.9rem;"></p>

            @if(!empty($annonce['objets']))
            <div style="margin-top:32px; border:var(--border); padding:20px; background:white;">
                <p class="font-mono" style="font-size:0.75rem; color:var(--teal); margin-bottom:12px;">Caractéristiques</p>
                @foreach($annonce['objets'] as $objet)
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.9rem;">
                    <div><span style="opacity:0.6;">Matériau :</span> <strong>{{ $materiauLabels[$objet['materiau']] ?? ucfirst($objet['materiau']) }}</strong></div>
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

            @php
                $mode = $annonce['mode_remise'] ?? '';
                $cont = $annonce['conteneur'] ?? null;
                $adrRemise = $annonce['adresse_remise'] ?? null;
                $mapsUrl = null;
                if ($mode === 'conteneur' && $cont) {
                    $dest = (isset($cont['latitude'], $cont['longitude']) && $cont['latitude'] !== null && $cont['longitude'] !== null)
                        ? $cont['latitude'] . ',' . $cont['longitude']
                        : trim(($cont['adresse'] ?? '') . ', ' . ($cont['code_postal'] ?? '') . ' ' . ($cont['ville'] ?? ''));
                    $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($dest);
                } elseif ($mode === 'main_propre' && $adrRemise) {
                    $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($adrRemise);
                }
            @endphp

            <div style="margin-top:20px; border:2px solid var(--coffee); background:white; box-shadow:var(--shadow-sm); padding:16px 18px;">
                <div class="font-mono" style="font-size:0.7rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--teal); margin-bottom:8px;">
                    {{ $mode === 'conteneur' ? 'Point de collecte' : 'Remise en main propre' }}
                </div>

                @if($mode === 'conteneur')
                    @if($cont)
                        <div style="font-weight:600; font-size:1rem; margin-bottom:2px;">
                            {{ $cont['conteneur_ref'] ?? 'Conteneur' }}
                        </div>
                        <div style="font-size:0.95rem; line-height:1.4;">
                            {{ $cont['adresse'] ?? '' }}@if(!empty($cont['code_postal']) || !empty($cont['ville'])),
                            {{ $cont['code_postal'] ?? '' }} {{ $cont['ville'] ?? '' }}@endif
                        </div>
                    @else
                        <div style="font-size:0.95rem;">Via conteneur — point de collecte communiqué après validation.</div>
                    @endif
                @else
                    @if($adrRemise)
                        <div style="font-size:0.95rem; line-height:1.4;">{{ $adrRemise }}</div>
                    @else
                        <div style="font-size:0.95rem;">Remise en main propre — adresse communiquée par le vendeur.</div>
                    @endif
                @endif

                @if($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="margin-top:12px; display:inline-block;">Itinéraire Google Maps →</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
@media (max-width: 768px) {
    .page-container > div:last-of-type { grid-template-columns: 1fr !important; }
}
.thumb-btn { transition: border-color 0.12s, transform 0.12s; }
.thumb-btn:hover { transform: translateY(-2px); }
.thumb-btn.active { border-color: var(--cherry); box-shadow: 0 0 0 2px var(--cherry); }
@endsection

@section('scripts')
<script>
// Achat des objets réservé aux professionnels / artisans (cf. cahier des charges).
(function() {
    var buyBlock = document.getElementById('buyBlock');
    var note = document.getElementById('proOnlyNote');
    var cta = document.getElementById('proOnlyCta');
    var role = null;
    var token = localStorage.getItem('auth_token');
    if (token) {
        try {
            var payload = JSON.parse(atob(token.split('.')[1].replace(/-/g, '+').replace(/_/g, '/')));
            role = payload.role || null;
        } catch (e) { role = null; }
    }
    if (role === 'professionnel') {
        if (buyBlock) buyBlock.style.display = 'flex';
        if (note) note.style.display = 'none';
    } else {
        if (buyBlock) buyBlock.style.display = 'none';
        if (note) note.style.display = 'block';
        if (cta) {
            cta.innerHTML = token
                ? ''
                : '<br><a href="/register-pro" style="color:var(--cherry,#A4243B);font-weight:600;">Devenir professionnel →</a>';
        }
    }
})();

(function() {
    var btn = document.getElementById('btnAddPanier');
    if (!btn) return;
    function setEtat() {
        var id = btn.getAttribute('data-id');
        if (window.UCPanier && window.UCPanier.has(id)) {
            btn.textContent = '✓ Déjà dans le panier';
            btn.disabled = true;
            btn.style.opacity = '0.7';
        }
    }
    setEtat();
    btn.addEventListener('click', function() {
        var added = window.UCPanier.add({
            id_annonce: btn.getAttribute('data-id'),
            titre: btn.getAttribute('data-titre'),
            prix: btn.getAttribute('data-prix'),
            type_annonce: btn.getAttribute('data-type'),
            mode_remise: btn.getAttribute('data-mode'),
            vendeur: btn.getAttribute('data-vendeur')
        });
        var flash = document.getElementById('panierFlash');
        flash.textContent = added ? 'Ajouté au panier !' : 'Déjà dans votre panier.';
        flash.style.display = 'block';
        setEtat();
        setTimeout(function() { flash.style.display = 'none'; }, 2500);
    });
})();

(function() {
    var main = document.getElementById('mainPhoto');
    if (!main) return;
    document.querySelectorAll('.thumb-btn').forEach(function(t) {
        t.addEventListener('click', function() {
            main.src = t.getAttribute('data-src');
            document.querySelectorAll('.thumb-btn').forEach(function(b) { b.classList.remove('active'); });
            t.classList.add('active');
        });
    });
})();
</script>
@endsection

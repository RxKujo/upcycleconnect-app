@extends('layouts.public')

@section('title', $item['titre'] ?? 'Formation')

@section('content')
@php
    $categorieLabels = [
        'formation' => 'Formation', 'atelier' => 'Atelier',
        'conference' => 'Conférence',
    ];
    $formatLabels = ['presentiel' => 'Présentiel', 'distanciel' => 'Distanciel'];
    $debut = \Carbon\Carbon::parse($item['date_debut']);
    $fin = \Carbon\Carbon::parse($item['date_fin']);
    $prix = $item['prix'] ?? 0;
    $places = $item['nb_places_dispo'] ?? 0;
@endphp
<div class="page-container" style="max-width:820px;">
    <a href="{{ route('formations.index') }}" class="font-mono" style="font-size:0.75rem; text-transform:uppercase; opacity:0.6; text-decoration:none;">&larr; Retour au catalogue</a>

    <div style="display:flex; gap:8px; flex-wrap:wrap; margin:20px 0 12px;">
        <span class="badge badge-cherry">{{ $categorieLabels[$item['categorie']] ?? ucfirst($item['categorie']) }}</span>
        <span class="badge badge-waiting">{{ $formatLabels[$item['format']] ?? ucfirst($item['format']) }}</span>
    </div>

    <h1 class="page-title" style="margin-bottom:24px;">{{ $item['titre'] }}</h1>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:28px;">
        <div style="border:var(--border); padding:16px 20px; background:white;">
            <p class="font-mono" style="font-size:0.7rem; opacity:0.55; text-transform:uppercase; margin-bottom:6px;">Date</p>
            <p style="font-size:0.95rem;">{{ $debut->locale('fr')->isoFormat('dddd D MMMM Y') }}</p>
            <p style="font-size:0.85rem; opacity:0.7;">{{ $debut->format('H\hi') }} — {{ $fin->format('H\hi') }}</p>
        </div>
        <div style="border:var(--border); padding:16px 20px; background:white;">
            <p class="font-mono" style="font-size:0.7rem; opacity:0.55; text-transform:uppercase; margin-bottom:6px;">Lieu</p>
            <p style="font-size:0.95rem;">{{ $item['lieu'] ?? 'En ligne' }}</p>
        </div>
    </div>

    <div style="border:var(--border); padding:24px; background:white; margin-bottom:28px;">
        <p class="font-mono" style="font-size:0.7rem; opacity:0.55; text-transform:uppercase; margin-bottom:10px;">Description</p>
        <p style="font-size:1rem; line-height:1.6; white-space:pre-line;">{{ $item['description'] }}</p>
    </div>

    <div style="border:var(--border); padding:24px; background:white; box-shadow:var(--shadow-sm);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:12px;">
            <div>
                @if($prix > 0)
                <span style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--cherry);">{{ number_format($prix, 2) }}&euro;</span>
                @else
                <span class="badge badge-valid" style="font-size:0.85rem;">Gratuit</span>
                @endif
            </div>
            <span class="font-mono" style="font-size:0.8rem; opacity:0.6;">
                {{ $places }} place{{ $places > 1 ? 's' : '' }} disponible{{ $places > 1 ? 's' : '' }}
            </span>
        </div>

        <button type="button" id="btnReserver" class="btn btn-primary btn-lg"
                data-id="{{ $item['id_catalogue_item'] }}"
                data-prix="{{ $prix }}"
                @if($places <= 0) disabled @endif>
            @if($places <= 0) Complet @else Réserver @endif
        </button>
        <p id="reserveResult" style="display:none; margin-top:16px; padding:14px; font-size:0.9rem;"></p>
    </div>
</div>

<div id="authModal" style="display:none; position:fixed; inset:0; z-index:500; align-items:center; justify-content:center; background:rgba(18,3,9,0.55); padding:20px;">
    <div style="background:var(--cream); border:3px solid var(--coffee); box-shadow:6px 6px 0 var(--coffee); max-width:420px; width:100%; padding:32px;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px;">
            <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:0.04em; line-height:1;">Connexion requise</h3>
            <button type="button" id="authModalClose" aria-label="Fermer" style="background:none; border:none; font-size:1.5rem; line-height:1; cursor:pointer; color:var(--coffee);">&times;</button>
        </div>
        <p style="font-size:0.95rem; opacity:0.75; margin-bottom:24px;">Pour réserver cette formation, connectez-vous ou créez un compte gratuit.</p>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <a id="loginReturnLink" href="/login?return={{ urlencode(request()->getPathInfo()) }}" class="btn btn-primary btn-block">Se connecter</a>
            <a href="{{ route('particulier.register') }}" class="btn btn-secondary btn-block">Créer un compte</a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const authModal = document.getElementById('authModal');
function openAuthModal() { authModal.style.display = 'flex'; }
function closeAuthModal() { authModal.style.display = 'none'; }
document.getElementById('authModalClose')?.addEventListener('click', closeAuthModal);
authModal?.addEventListener('click', function (e) { if (e.target === authModal) closeAuthModal(); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeAuthModal(); });

document.getElementById('btnReserver')?.addEventListener('click', async function () {
    const btn = this;
    const id = btn.dataset.id;
    const prix = parseFloat(btn.dataset.prix) || 0;
    const result = document.getElementById('reserveResult');
    const token = localStorage.getItem('auth_token');

    result.style.display = 'none';

    if (!token) {
        openAuthModal();
        return;
    }

    if (prix > 0) {
        result.style.display = 'block';
        result.style.background = '#fff4d6';
        result.style.borderLeft = '3px solid #b88a00';
        result.textContent = 'Le paiement en ligne pour les formations payantes sera bientôt disponible. Les réservations gratuites sont déjà ouvertes.';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Réservation…';
    try {
        const res = await fetch(API_BASE + '/api/catalogue/' + id + '/reserver', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({})
        });
        const body = await res.json();
        result.style.display = 'block';
        if (res.ok || res.status === 201) {
            result.style.background = '#dff5e1';
            result.style.borderLeft = '3px solid #3a7d44';
            let profileHref = '/particulier/profile';
            try { const r = JSON.parse(atob(token.split('.')[1])); if (r.role === 'professionnel') profileHref = '/professionnel/profile'; } catch(e) {}
            result.innerHTML = '<strong>Réservation confirmée !</strong> Retrouvez-la dans <a href="' + profileHref + '" style="text-decoration:underline;">votre espace</a>.';
            btn.textContent = 'Réservé';
        } else {
            result.style.background = '#fde2e2';
            result.style.borderLeft = '3px solid #b00';
            result.textContent = body.erreur || 'Réservation impossible.';
            btn.disabled = false;
            btn.textContent = 'Réserver';
        }
    } catch (e) {
        result.style.display = 'block';
        result.style.background = '#fde2e2';
        result.style.borderLeft = '3px solid #b00';
        result.textContent = 'Erreur de connexion : ' + e.message;
        btn.disabled = false;
        btn.textContent = 'Réserver';
    }
});
</script>
@endsection

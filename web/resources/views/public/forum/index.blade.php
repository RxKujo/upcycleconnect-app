@extends('layouts.public')

@section('title', 'Forum')
@section('meta_description', 'Forum communautaire UpcycleConnect. Posez vos questions et partagez vos expériences.')

@section('content')
<div class="page-container">
    <p class="section-label">Communauté</p>
    <h1 class="page-title">Forum</h1>
    <p class="page-subtitle">Échangez avec la communauté, posez vos questions et partagez vos retours d'expérience</p>

    <div style="margin-bottom:32px;" id="newSujetSection">
        <a href="{{ route('particulier.login') }}?intent=forum" class="btn btn-primary" data-requires-auth data-auth-title="Connectez-vous pour poster" id="loginToPost" style="display:none;">
            + Nouveau sujet
        </a>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('newSujetForm').style.display='block'; this.style.display='none';" id="openNewSujetForm" style="display:none;">
            + Nouveau sujet
        </button>
    </div>

    <form id="newSujetForm" autocomplete="off" style="display:none; border:var(--border); padding:24px; background:white; box-shadow:var(--shadow-sm); margin-bottom:32px;" onsubmit="return submitNewSujet(event);">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; margin-bottom:16px;">Lancer un nouveau sujet</h3>
        <div style="margin-bottom:12px;">
            <label class="font-mono" style="display:block; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Titre</label>
            <input type="text" name="titre" required minlength="5" maxlength="300" style="width:100%; padding:10px 12px; border:var(--border); background:var(--cream); font-family:inherit;" />
        </div>
        <div style="margin-bottom:12px;">
            <label class="font-mono" style="display:block; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Catégorie (optionnel)</label>
            <input type="text" name="categorie" maxlength="100" placeholder="ex: reparation, formation, conseil..." style="width:100%; padding:10px 12px; border:var(--border); background:var(--cream); font-family:inherit;" />
        </div>
        <div style="margin-bottom:16px;">
            <label class="font-mono" style="display:block; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; margin-bottom:6px;">Message</label>
            <textarea name="contenu" required minlength="5" rows="5" style="width:100%; padding:10px 12px; border:var(--border); background:var(--cream); font-family:inherit; resize:vertical;"></textarea>
        </div>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-primary">Publier</button>
            <button type="button" onclick="document.getElementById('newSujetForm').style.display='none'; document.getElementById('openNewSujetForm').style.display='inline-block';" style="padding:10px 20px; border:var(--border); background:var(--cream); cursor:pointer; font-family:inherit;">Annuler</button>
        </div>
        <p id="newSujetError" style="margin-top:12px; color:#b00; display:none;"></p>
    </form>

    <script>
    (function() {
        var token = localStorage.getItem('auth_token');
        if (token) {
            document.getElementById('openNewSujetForm').style.display = 'inline-block';
        } else {
            document.getElementById('loginToPost').style.display = 'inline-block';
        }
    })();
    async function submitNewSujet(e) {
        e.preventDefault();
        var form = e.target;
        var err = document.getElementById('newSujetError');
        err.style.display = 'none';
        var token = localStorage.getItem('auth_token');
        if (!token) { window.location.href = '/login?intent=forum'; return false; }
        var data = {
            titre: form.titre.value.trim(),
            categorie: form.categorie.value.trim(),
            contenu: form.contenu.value.trim()
        };
        try {
            var res = await fetch('http://localhost:8888/api/v1/forum/sujets', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify(data)
            });
            var body = await res.json();
            if (!res.ok) { err.textContent = body.erreur || 'Erreur'; err.style.display = 'block'; return false; }
            form.reset();
            window.location.href = '/forum/' + body.id_sujet;
        } catch (ex) {
            err.textContent = 'Impossible de contacter le serveur.';
            err.style.display = 'block';
        }
        return false;
    }
    </script>

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

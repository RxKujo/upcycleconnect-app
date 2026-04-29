@extends('layouts.public')

@section('title', $sujet['titre'] ?? 'Sujet')
@section('og_title', $sujet['titre'] ?? 'Forum')

@section('content')
<div class="page-container" style="max-width:900px;">
    <a href="{{ route('forum.index') }}" style="display:inline-flex; align-items:center; gap:8px; font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.08em; color:var(--coffee); margin-bottom:32px; opacity:0.6; transition:opacity 0.15s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.6'">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/></svg>
        Retour au forum
    </a>

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

    @php
        $messages = $sujet['messages'] ?? [];
        $msgById = [];
        foreach ($messages as $m) { $msgById[$m['id_message']] = $m; }
    @endphp
    <div style="display:flex; flex-direction:column; gap:16px; margin-bottom:48px;">
        @forelse($messages as $index => $message)
        @php
            $parentId = $message['id_parent_message'] ?? null;
            $parent = $parentId && isset($msgById[$parentId]) ? $msgById[$parentId] : null;
            $indent = $parent ? 'margin-left:32px; border-left:3px solid var(--teal);' : '';
        @endphp
        <div id="msg-{{ $message['id_message'] }}" style="border:var(--border); padding:24px; background:{{ $index === 0 ? 'white' : 'var(--cream)' }}; {{ $index === 0 ? 'box-shadow:var(--shadow-sm);' : '' }} {{ $indent }}">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-weight:600; font-size:0.95rem;">
                    {{ $message['auteur_prenom'] ?? '' }} {{ $message['auteur_nom_initiale'] ?? '' }}
                </span>
                <span class="font-mono" style="font-size:0.7rem; opacity:0.4;">
                    {{ \Carbon\Carbon::parse($message['date_publication'])->locale('fr')->diffForHumans() }}
                </span>
            </div>
            @if($parent)
            <a href="#msg-{{ $parent['id_message'] }}" style="display:block; font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.55; margin-bottom:10px; padding:8px 12px; background:rgba(0,0,0,0.04); border-left:2px solid var(--teal);">
                ↳ En réponse à <strong>{{ $parent['auteur_prenom'] }} {{ $parent['auteur_nom_initiale'] }}</strong>
                : {{ \Illuminate\Support\Str::limit($parent['contenu'], 80) }}
            </a>
            @endif
            <p style="font-size:0.95rem; line-height:1.7; white-space:pre-line;">{{ $message['contenu'] }}</p>
            <div style="margin-top:12px; display:flex; gap:8px;">
                <button type="button" class="reply-btn"
                        data-msg-id="{{ $message['id_message'] }}"
                        data-msg-author="{{ $message['auteur_prenom'] }} {{ $message['auteur_nom_initiale'] }}"
                        style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; padding:6px 12px; border:var(--border); background:transparent; cursor:pointer; opacity:0.7;">
                    ↳ Répondre
                </button>
                <button type="button" class="report-btn"
                        data-msg-id="{{ $message['id_message'] }}"
                        style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.08em; padding:6px 12px; border:1px solid #b00; background:transparent; color:#b00; cursor:pointer; opacity:0.6;">
                    ⚑ Signaler
                </button>
            </div>
        </div>
        @empty
        <p style="text-align:center; opacity:0.6; padding:40px;">Aucun message dans ce sujet.</p>
        @endforelse
    </div>

    <div id="replyLoginBox" style="display:none; border:var(--border); padding:32px; background:white; box-shadow:var(--shadow-sm); text-align:center;">
        <p style="font-size:1rem; margin-bottom:16px; opacity:0.7;">Vous souhaitez participer à cette discussion ?</p>
        <a href="{{ route('particulier.login') }}?intent=forum_reponse&sujet={{ $sujet['id_sujet'] }}" class="btn btn-primary" data-requires-auth data-auth-title="Connectez-vous pour répondre">
            Se connecter pour répondre
        </a>
    </div>

    <form id="replyForm" autocomplete="off" style="display:none; border:var(--border); padding:24px; background:white; box-shadow:var(--shadow-sm);" onsubmit="return submitReply(event, {{ $sujet['id_sujet'] }});">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; margin-bottom:8px;">Votre réponse</h3>
        <div id="replyContext" style="display:none; font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.6; margin-bottom:12px; padding:8px 12px; background:rgba(0,0,0,0.04); border-left:2px solid var(--teal);">
            <span id="replyContextText"></span>
            <button type="button" id="cancelReplyContext" style="float:right; background:none; border:none; cursor:pointer; color:#b00; font-family:inherit; font-size:0.72rem;">✕ retirer</button>
        </div>
        <input type="hidden" name="id_parent_message" id="replyParentId" value="" />
        <textarea name="contenu" id="replyTextarea" autocomplete="off" required minlength="2" rows="5" placeholder="Écrivez votre message..." style="width:100%; padding:10px 12px; border:var(--border); background:var(--cream); font-family:inherit; resize:vertical; margin-bottom:16px;"></textarea>
        <div style="display:flex; gap:12px;">
            <button type="submit" class="btn btn-primary">Publier ma réponse</button>
        </div>
        <p id="replyError" style="margin-top:12px; color:#b00; display:none;"></p>
    </form>
</div>

<script>
(function() {
    var token = localStorage.getItem('auth_token');
    document.getElementById(token ? 'replyForm' : 'replyLoginBox').style.display = 'block';
    if (!token) return;

    document.querySelectorAll('.reply-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var msgId = btn.getAttribute('data-msg-id');
            var author = btn.getAttribute('data-msg-author');
            document.getElementById('replyParentId').value = msgId;
            document.getElementById('replyContextText').textContent = '↳ Réponse à ' + author;
            document.getElementById('replyContext').style.display = 'block';
            var ta = document.getElementById('replyTextarea');
            document.getElementById('replyForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(function() { ta.focus(); }, 300);
        });
    });

    document.getElementById('cancelReplyContext').addEventListener('click', function() {
        document.getElementById('replyParentId').value = '';
        document.getElementById('replyContext').style.display = 'none';
    });

    document.querySelectorAll('.report-btn').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            var msgId = btn.getAttribute('data-msg-id');
            var motif = prompt('Pourquoi signalez-vous ce message ?', '');
            if (motif === null) return;
            try {
                var res = await fetch('http://localhost:8888/api/v1/forum/signaler', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify({ id_message: parseInt(msgId, 10), motif: motif })
                });
                var body = await res.json();
                alert(res.ok ? 'Merci, votre signalement a été transmis aux modérateurs.' : (body.erreur || 'Erreur'));
                if (res.ok) {
                    btn.disabled = true;
                    btn.textContent = '✓ Signalé';
                    btn.style.opacity = '0.4';
                }
            } catch (e) {
                alert('Impossible de contacter le serveur.');
            }
        });
    });
})();
async function submitReply(e, sujetId) {
    e.preventDefault();
    var form = e.target;
    var err = document.getElementById('replyError');
    err.style.display = 'none';
    var token = localStorage.getItem('auth_token');
    if (!token) { window.location.href = '/login?intent=forum_reponse&sujet=' + sujetId; return false; }
    var payload = { contenu: form.contenu.value.trim() };
    var parent = form.id_parent_message.value;
    if (parent) payload.id_parent_message = parseInt(parent, 10);
    try {
        var res = await fetch('http://localhost:8888/api/v1/forum/sujets/' + sujetId + '/messages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        var body = await res.json();
        if (!res.ok) { err.textContent = body.erreur || 'Erreur'; err.style.display = 'block'; return false; }
        form.reset();
        document.getElementById('replyTextarea').value = '';
        document.getElementById('replyParentId').value = '';
        document.getElementById('replyContext').style.display = 'none';
        var url = new URL(window.location.href);
        url.searchParams.set('_', Date.now());
        window.location.replace(url.toString());
    } catch (ex) {
        err.textContent = 'Impossible de contacter le serveur.';
        err.style.display = 'block';
    }
    return false;
}
</script>
@endsection

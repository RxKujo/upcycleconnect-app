@extends('layouts.salarie')

@section('title', $article ? 'Modifier article' : 'Nouvel article')

@section('styles')
<style>
.editor-toolbar { display: flex; flex-wrap: wrap; gap: 4px; border: 3px solid var(--coffee); border-bottom: none; background: var(--wheat); padding: 8px; }
.editor-btn { min-width: 36px; height: 34px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; background: var(--cream); color: var(--coffee); border: 2px solid var(--coffee); font-family: 'DM Mono', monospace; font-size: 0.85rem; font-weight: 700; box-shadow: 1px 1px 0 rgba(18,3,9,0.25); transition: all 0.1s; }
.editor-btn:hover { background: white; transform: translate(-1px,-1px); box-shadow: 2px 2px 0 rgba(18,3,9,0.3); }
.editor-btn:active { transform: translate(1px,1px); box-shadow: none; }
.editor-sep { width: 2px; background: rgba(18,3,9,0.2); margin: 2px 4px; }
.editor-area { border: 3px solid var(--coffee); background: white; min-height: 320px; padding: 18px 20px; outline: none; font-family: 'Outfit', sans-serif; font-size: 1.05rem; line-height: 1.7; color: #2a2118; box-shadow: 3px 3px 0 rgba(18,3,9,0.1); overflow-y: auto; }
.editor-area:focus { border-color: var(--forest); }
.editor-area:empty::before { content: attr(data-placeholder); opacity: 0.4; }
.editor-area h2 { font-family: 'Bebas Neue', sans-serif; font-size: 1.9rem; margin: 22px 0 10px; line-height: 1.1; }
.editor-area h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.45rem; margin: 18px 0 8px; }
.editor-area p { margin: 0 0 14px; }
.editor-area ul, .editor-area ol { padding-left: 26px; margin: 0 0 14px; }
.editor-area ul { list-style: disc outside; }
.editor-area ol { list-style: decimal outside; }
.editor-area li { margin-bottom: 6px; }
.editor-area blockquote { border-left: 5px solid var(--forest); background: rgba(36,79,38,0.07); margin: 0 0 16px; padding: 10px 16px; font-style: italic; }
.editor-area a { color: var(--teal); text-decoration: underline; }
.editor-hint { font-family: 'DM Mono', monospace; font-size: 0.72rem; opacity: 0.55; margin: 10px 0 0; }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $article ? 'Modifier' : 'Nouvel' }} article</h1>
    <a href="{{ route('salarie.articles.index') }}" class="btn-secondary">← Retour</a>
</div>

@php
    $isEdit = $article !== null;
    $action = $isEdit ? route('salarie.articles.update', $article['id_article']) : route('salarie.articles.store');
    $contenu = $article['contenu'] ?? '';
    $contenuIsHtml = $contenu !== strip_tags($contenu);
@endphp

<form action="{{ $action }}" method="POST" class="card" autocomplete="off" id="article-form">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-group">
        <label class="form-label" for="titre">Titre</label>
        <input type="text" name="titre" id="titre" class="form-input" required maxlength="300"
               value="{{ old('titre', $article['titre'] ?? '') }}">
    </div>

    <div class="form-group">
        <label class="form-label" for="categorie">Rubrique de publication</label>
        @php $catActuelle = old('categorie', $article['categorie'] ?? ''); @endphp
        <select name="categorie" id="categorie" class="form-select">
            <option value="">— Aucune (non classé) —</option>
            @foreach(config('articles.categories') as $key => $label)
                <option value="{{ $key }}" {{ $catActuelle === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; opacity:0.55; margin:10px 0 0;">
            Détermine la rubrique sous laquelle l'article apparaîtra côté public (page Ressources).
        </p>
    </div>

    <div class="form-group">
        <label class="form-label">Contenu</label>
        <div class="editor-toolbar" id="editor-toolbar">
            <button type="button" class="editor-btn" data-block="h2" title="Grand titre">Titre</button>
            <button type="button" class="editor-btn" data-block="h3" title="Sous-titre">Sous-titre</button>
            <button type="button" class="editor-btn" data-block="p" title="Revenir au texte normal">Texte</button>
            <span class="editor-sep"></span>
            <button type="button" class="editor-btn" data-cmd="bold" title="Gras" style="font-weight:900;">G</button>
            <button type="button" class="editor-btn" data-cmd="italic" title="Italique" style="font-style:italic;">I</button>
            <button type="button" class="editor-btn" data-cmd="underline" title="Souligné" style="text-decoration:underline;">S</button>
            <span class="editor-sep"></span>
            <button type="button" class="editor-btn" data-cmd="insertUnorderedList" title="Liste à puces">Liste</button>
            <button type="button" class="editor-btn" data-cmd="insertOrderedList" title="Liste numérotée">Liste num.</button>
            <button type="button" class="editor-btn" data-block="blockquote" title="Citation (re-cliquer pour annuler)">Citation</button>
            <span class="editor-sep"></span>
            <button type="button" class="editor-btn" data-cmd="createLink" title="Insérer un lien">Lien</button>
            <button type="button" class="editor-btn" data-cmd="removeFormat" title="Effacer la mise en forme">Effacer</button>
        </div>
        <div class="editor-area" id="editor" contenteditable="true" data-placeholder="Rédigez votre ressource pédagogique : titres, paragraphes, listes, citations…">@if($contenuIsHtml){!! $contenu !!}@else{{ $contenu }}@endif</div>
        <textarea name="contenu" id="contenu-hidden" style="display:none;"></textarea>
        <p class="editor-hint">Mise en forme : titres, gras/italique/souligné, listes, citations et liens. Le rendu sera identique côté lecteur.</p>
    </div>

    <div class="form-group">
        <label class="form-label" for="statut">Statut</label>
        <select name="statut" id="statut" class="form-select" required>
            <option value="brouillon" {{ old('statut', $article['statut'] ?? 'brouillon') === 'brouillon' ? 'selected' : '' }}>Brouillon (non visible)</option>
            <option value="publie" {{ old('statut', $article['statut'] ?? '') === 'publie' ? 'selected' : '' }}>Publié</option>
            <option value="archive" {{ old('statut', $article['statut'] ?? '') === 'archive' ? 'selected' : '' }}>Archivé</option>
        </select>
    </div>

    <div style="display:flex; gap:16px;">
        <button type="submit" class="btn-primary">{{ $isEdit ? 'Mettre à jour' : 'Créer' }}</button>
        <a href="{{ route('salarie.articles.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection

@section('scripts')
<script>
(function () {
    const editor = document.getElementById('editor');
    const hidden = document.getElementById('contenu-hidden');
    const toolbar = document.getElementById('editor-toolbar');
    const form = document.getElementById('article-form');
    if (!editor || !hidden || !form) return;

    // Paragraphe par défaut quand on tape (au lieu de <div>)
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}

    function placeCaretEnd(node) {
        const r = document.createRange();
        r.selectNodeContents(node);
        r.collapse(false);
        const s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);
    }
    function ancestorTag(tag) {
        const sel = window.getSelection();
        let node = sel.rangeCount ? sel.anchorNode : null;
        while (node && node !== editor) {
            if (node.nodeType === 1 && node.tagName.toLowerCase() === tag) return node;
            node = node.parentNode;
        }
        return null;
    }
    function unwrap(el) {
        // Remplace le bloc par un paragraphe contenant son contenu.
        const p = document.createElement('p');
        p.innerHTML = el.innerHTML || '<br>';
        el.replaceWith(p);
        placeCaretEnd(p);
    }

    function applyBlock(tag) {
        const bq = ancestorTag('blockquote');
        if (tag === 'blockquote') {
            if (bq) unwrap(bq);                              // déjà en citation → on sort
            else document.execCommand('formatBlock', false, 'blockquote');
            return;
        }
        if (bq) { unwrap(bq); if (tag === 'p') return; }     // sortir de la citation d'abord
        const current = (document.queryCommandValue('formatBlock') || '').toLowerCase();
        document.execCommand('formatBlock', false, (current === tag && tag !== 'p') ? 'p' : tag);
    }

    toolbar.addEventListener('click', function (e) {
        const btn = e.target.closest('.editor-btn');
        if (!btn) return;
        e.preventDefault();
        editor.focus();
        if (btn.dataset.block) {
            applyBlock(btn.dataset.block);
        } else if (btn.dataset.cmd === 'createLink') {
            const url = prompt('Adresse du lien (https://…)');
            if (url) document.execCommand('createLink', false, url);
        } else {
            document.execCommand(btn.dataset.cmd, false, null);
        }
        sync();
    });

    // Filet de sécurité clavier : Ctrl+Entrée sort de la citation.
    editor.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            const bq = ancestorTag('blockquote');
            if (bq) { e.preventDefault(); unwrap(bq); sync(); }
        }
    });

    function sync() { hidden.value = editor.innerHTML.trim(); }
    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);

    form.addEventListener('submit', function (e) {
        sync();
        // Contenu vide = on bloque (le serveur l'exige aussi).
        if (!editor.textContent.trim()) {
            e.preventDefault();
            alert('Le contenu de l\'article ne peut pas être vide.');
            editor.focus();
        }
    });

    sync();
})();
</script>
@endsection

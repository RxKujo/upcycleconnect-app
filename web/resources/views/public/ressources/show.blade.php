@extends('layouts.public')

@section('title', ($article['titre'] ?? 'Ressource') . ' — UpcycleConnect')

@section('styles')
.res-wrap { max-width: 820px; margin: 0 auto; padding: 48px 24px 80px; }
.res-back { display: inline-flex; align-items: center; gap: 8px; font-family: 'DM Mono', monospace; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.06em; color: var(--coffee); opacity: 0.6; text-decoration: none; margin-bottom: 32px; transition: opacity 0.15s; }
.res-back:hover { opacity: 1; }

.res-head { border-bottom: 4px solid var(--coffee); padding-bottom: 28px; margin-bottom: 36px; }
.res-cat { display: inline-block; font-family: 'DM Mono', monospace; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.06em; padding: 5px 12px; border: 2px solid var(--coffee); box-shadow: 2px 2px 0 rgba(18,3,9,0.25); margin-bottom: 18px; }
.res-title { font-family: 'Bebas Neue', sans-serif; font-size: 3rem; line-height: 1.02; letter-spacing: 0.02em; color: var(--coffee); margin: 0 0 16px; }
.res-meta { font-family: 'DM Mono', monospace; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.5; display: flex; gap: 16px; flex-wrap: wrap; }

.res-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 36px; }

/* Typographie du contenu pédagogique */
.res-content { font-size: 1.08rem; line-height: 1.75; color: #2a2118; }
.res-content > *:first-child { margin-top: 0; }
.res-content h2 { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 0.02em; color: var(--coffee); margin: 40px 0 14px; line-height: 1.1; }
.res-content h3 { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; letter-spacing: 0.02em; color: var(--coffee); margin: 30px 0 10px; }
.res-content p { margin: 0 0 18px; }
.res-content a { color: var(--teal); text-decoration: underline; }
.res-content ul, .res-content ol { margin: 0 0 18px; padding-left: 28px; }
.res-content ul { list-style: disc outside; }
.res-content ol { list-style: decimal outside; }
.res-content li { margin-bottom: 8px; }
.res-content blockquote { margin: 0 0 22px; padding: 14px 20px; border-left: 5px solid var(--forest); background: rgba(36,79,38,0.07); font-style: italic; }
.res-content u { text-decoration: underline; }
.res-content strong { font-weight: 700; }
.res-content img { max-width: 100%; height: auto; border: 3px solid var(--coffee); box-shadow: var(--shadow-sm); margin: 12px 0; }

/* Impression / export PDF : on n'imprime que l'article. */
@media print {
    body * { visibility: hidden !important; }
    .printable, .printable * { visibility: visible !important; }
    .printable { position: absolute; left: 0; top: 0; width: 100%; padding: 0; }
    .no-print { display: none !important; }
    .res-title { font-size: 26pt; }
    .res-content { font-size: 12pt; }
    .res-cat { box-shadow: none; }
    a[href]::after { content: ""; }
}
@endsection

@section('content')
@php
    $cats = config('articles.categories');
    $catKey = $article['categorie'] ?? null;
    $catLabel = $catKey ? ($cats[$catKey] ?? $catKey) : null;
    $c = $article['contenu'] ?? '';
    $isHtml = $c !== strip_tags($c);
    $auteur = trim(($article['auteur_prenom'] ?? '') . ' ' . ($article['auteur_nom_initiale'] ?? ''));
@endphp

<div class="res-wrap">
    <a href="{{ route('ressources.index') }}" class="res-back no-print">← Retour aux ressources</a>

    <div class="printable">
        <header class="res-head">
            @if($catLabel)<span class="res-cat">{{ $catLabel }}</span>@endif
            <h1 class="res-title">{{ $article['titre'] ?? 'Sans titre' }}</h1>
            <div class="res-meta">
                @if(!empty($article['date_publication']))<span>{{ \Carbon\Carbon::parse($article['date_publication'])->locale('fr')->isoFormat('D MMMM YYYY') }}</span>@endif
                @if($auteur)<span>par {{ $auteur }}</span>@endif
            </div>
        </header>

        <div class="res-content">
            @if($isHtml)
                {!! $c !!}
            @else
                {!! nl2br(e($c)) !!}
            @endif
        </div>
    </div>

    <div class="res-actions no-print" style="margin-top:48px;">
        <button type="button" class="btn btn-primary" id="btn-pdf" data-filename="{{ \Illuminate\Support\Str::slug($article['titre'] ?? 'ressource') }}" data-i18n="resources.pdf">Télécharger en PDF</button>
        <a href="{{ route('ressources.index') }}" class="btn btn-secondary" data-i18n="resources.all">Toutes les ressources</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
(function () {
    const btn = document.getElementById('btn-pdf');
    if (!btn) return;
    btn.addEventListener('click', function () {
        const el = document.querySelector('.printable');
        const name = (btn.dataset.filename || 'ressource') + '.pdf';
        if (window.html2pdf) {
            btn.disabled = true;
            const ancien = btn.textContent;
            btn.textContent = 'Génération…';
            window.html2pdf().set({
                margin: [12, 14, 16, 14],
                filename: name,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['avoid-all', 'css'] }
            }).from(el).save().then(function () {
                btn.disabled = false;
                btn.textContent = ancien;
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = ancien;
                window.print();
            });
        } else {
            // Repli si la lib n'a pas chargé (hors-ligne).
            window.print();
        }
    });
})();
</script>
@endsection

{{-- Emplacement publicitaire public : charge les pubs actives (rotation pondérée
     côté API) et suit les clics. S'auto-masque s'il n'y a aucune pub active. --}}
<section id="pub-slot" style="display:none; max-width:1180px; margin:0 auto; padding:56px 24px;">
    <p style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.12em; opacity:0.45; margin:0 0 18px;">Nos annonceurs</p>
    <div id="pub-slot-grid" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:20px;"></div>
</section>

<script>
(function () {
    var API = '{{ config("services.api.public_url") }}';
    var slot = document.getElementById('pub-slot');
    var grid = document.getElementById('pub-slot-grid');
    if (!slot || !grid) return;

    function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

    fetch(API + '/api/v1/public/publicites')
        .then(function (r) { return r.ok ? r.json() : []; })
        .then(function (pubs) {
            if (!Array.isArray(pubs) || pubs.length === 0) return;
            pubs.forEach(function (p) {
                var a = document.createElement('a');
                a.href = p.url_cible || '#';
                a.target = '_blank';
                a.rel = 'noopener sponsored';
                a.style.cssText = 'display:block; border:3px solid var(--coffee); background:var(--cream); box-shadow:4px 4px 0 var(--coffee); text-decoration:none; color:var(--coffee); overflow:hidden; transition:transform .15s, box-shadow .15s;';
                a.onmouseenter = function () { a.style.transform = 'translate(-3px,-3px)'; a.style.boxShadow = '7px 7px 0 var(--coffee)'; };
                a.onmouseleave = function () { a.style.transform = ''; a.style.boxShadow = '4px 4px 0 var(--coffee)'; };

                var visuel = '';
                if (p.visuel_url) {
                    visuel = '<div style="height:150px; background:#eee center/cover no-repeat; border-bottom:3px solid var(--coffee); background-image:url(\'' + encodeURI(p.visuel_url) + '\');"></div>';
                }
                a.innerHTML = visuel +
                    '<div style="padding:14px 16px;">' +
                    '<span style="font-family:\'DM Mono\',monospace; font-size:0.64rem; text-transform:uppercase; letter-spacing:0.08em; opacity:0.55;">' + escapeHtml(p.nom_entreprise || 'Annonceur') + '</span>' +
                    '<p style="font-family:\'Bebas Neue\',sans-serif; font-size:1.35rem; margin:4px 0 0; line-height:1.1; letter-spacing:0.02em;">' + escapeHtml(p.titre) + '</p>' +
                    '</div>';

                a.addEventListener('click', function () {
                    try { navigator.sendBeacon(API + '/api/v1/public/publicites/' + p.id_publicite + '/clic'); } catch (e) {}
                });
                grid.appendChild(a);
            });
            slot.style.display = 'block';
        })
        .catch(function () { /* silencieux : pas de pub, pas de bloc */ });
})();
</script>

@extends('layouts.public')

@section('title', 'Mon panier')

@section('content')
<div class="page-container" style="max-width:900px;">
    <p class="section-label">Achats</p>
    <h1 class="page-title">Mon panier</h1>

    <div id="panierEmpty" style="display:none; text-align:center; padding:80px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:2rem; margin-bottom:12px;">Panier vide</h3>
        <p style="opacity:0.6; margin-bottom:24px;">Découvrez les annonces du marché et ajoutez des objets à votre panier.</p>
        <a href="{{ route('annonces.index') }}" class="btn btn-primary">Voir le marché</a>
    </div>

    <div id="panierContent" style="display:none;">
        <div id="panierItems" style="display:flex; flex-direction:column; gap:12px; margin-bottom:32px;"></div>

        <div style="border:var(--border); padding:24px; background:white; box-shadow:var(--shadow-sm);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <span class="font-mono" style="font-size:0.85rem; opacity:0.6;">Total</span>
                <span id="panierTotal" style="font-family:'Bebas Neue',sans-serif; font-size:2.5rem; color:var(--cherry,#a72f43);">0,00 €</span>
            </div>
            <p id="panierAuthWarn" style="display:none; font-size:0.85rem; color:#a72f43; margin-bottom:16px;">
                Vous devez être <a href="/login?intent=checkout" style="text-decoration:underline;">connecté</a> pour valider votre commande.
            </p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <button type="button" id="btnCheckout" class="btn btn-primary btn-lg">Valider la commande</button>
                <button type="button" id="btnClear" class="btn btn-secondary">Vider le panier</button>
                <a href="{{ route('annonces.index') }}" class="btn btn-secondary">Continuer mes achats</a>
            </div>
            <p id="checkoutResult" style="display:none; margin-top:16px; padding:14px; font-size:0.9rem;"></p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function formatPrix(p) {
    return (parseFloat(p) || 0).toFixed(2).replace('.', ',') + ' €';
}

function render() {
    var items = window.UCPanier.items();
    var empty = document.getElementById('panierEmpty');
    var content = document.getElementById('panierContent');
    if (items.length === 0) {
        empty.style.display = 'block';
        content.style.display = 'none';
        return;
    }
    empty.style.display = 'none';
    content.style.display = 'block';

    var html = items.map(function(i) {
        var prixLabel = i.type_annonce === 'don' ? 'Gratuit' : formatPrix(i.prix);
        return '<div style="display:grid; grid-template-columns:1fr auto auto; gap:16px; align-items:center; border:var(--border); padding:16px 20px; background:white;">' +
            '<div>' +
                '<a href="/annonces/' + i.id_annonce + '" style="font-weight:600; font-size:1rem; text-decoration:none;">' + escapeHtml(i.titre) + '</a>' +
                '<p class="font-mono" style="font-size:0.72rem; opacity:0.55; margin-top:4px;">' +
                    'Vendeur ' + escapeHtml(i.vendeur || '—') +
                    ' · Remise ' + escapeHtml(i.mode_remise === 'conteneur' ? 'via conteneur' : 'main propre') +
                '</p>' +
            '</div>' +
            '<span style="font-family:\'Bebas Neue\',sans-serif; font-size:1.5rem; color:var(--cherry,#a72f43);">' + prixLabel + '</span>' +
            '<button type="button" data-remove="' + i.id_annonce + '" style="background:none; border:none; cursor:pointer; color:#b00; font-family:\'DM Mono\',monospace; font-size:0.7rem; text-transform:uppercase;">✕ Retirer</button>' +
        '</div>';
    }).join('');
    document.getElementById('panierItems').innerHTML = html;

    document.getElementById('panierTotal').textContent = formatPrix(window.UCPanier.total());

    document.querySelectorAll('[data-remove]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            window.UCPanier.remove(btn.getAttribute('data-remove'));
            render();
        });
    });

    document.getElementById('panierAuthWarn').style.display = localStorage.getItem('auth_token') ? 'none' : 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    render();

    document.getElementById('btnClear').addEventListener('click', function() {
        if (confirm('Vider le panier ?')) {
            window.UCPanier.clear();
            render();
        }
    });

    document.getElementById('btnCheckout').addEventListener('click', async function() {
        var btn = this;
        var result = document.getElementById('checkoutResult');
        result.style.display = 'none';
        btn.disabled = true;
        btn.textContent = 'Traitement…';
        try {
            var out = await window.UCPanier.checkout();
            if (!out) return;
            var failed = (out.body.failed || []).length;
            var created = (out.body.created || []).length;
            if (created > 0 && failed === 0) {
                result.style.background = '#dff5e1';
                result.style.borderLeft = '3px solid #3a7d44';
                result.innerHTML = '<strong>' + created + ' commande(s) créée(s) !</strong> Total payé : ' + formatPrix(out.body.total) +
                    '. <a href="/mes-commandes" style="text-decoration:underline;">Voir mes commandes</a>';
            } else if (created > 0) {
                result.style.background = '#fff4d6';
                result.style.borderLeft = '3px solid #b88a00';
                result.innerHTML = created + ' commande(s) OK, ' + failed + ' échec(s). Détails : ' +
                    (out.body.failed.map(function(f) { return f.erreur; }).join(', '));
            } else {
                result.style.background = '#fde2e2';
                result.style.borderLeft = '3px solid #b00';
                result.textContent = 'Aucune commande créée. ' + (out.body.erreur || (out.body.failed || []).map(function(f) { return f.erreur; }).join(', '));
            }
            result.style.display = 'block';
            render();
        } catch (e) {
            result.style.display = 'block';
            result.style.background = '#fde2e2';
            result.textContent = 'Erreur : ' + e.message;
        } finally {
            btn.disabled = false;
            btn.textContent = 'Valider la commande';
        }
    });
});
</script>
@endsection

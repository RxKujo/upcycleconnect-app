@extends('layouts.public')

@section('title', 'Mes commandes')

@section('content')
<div class="page-container" style="max-width:900px;">
    <p class="section-label">Espace acheteur</p>
    <h1 class="page-title">Mes commandes</h1>

    <div id="cmdLoginRequired" style="display:none; text-align:center; padding:60px 20px; border:var(--border); background:white;">
        <p style="margin-bottom:20px;">Connectez-vous pour consulter vos commandes.</p>
        <a href="/login?intent=commandes" class="btn btn-primary">Se connecter</a>
    </div>

    <div id="cmdLoading" style="text-align:center; padding:60px; opacity:0.5;">Chargement…</div>

    <div id="cmdEmpty" style="display:none; text-align:center; padding:60px 20px; border:var(--border); background:white;">
        <h3 style="font-family:'Bebas Neue',sans-serif; font-size:1.5rem; margin-bottom:8px;">Aucune commande</h3>
        <p style="opacity:0.6; margin-bottom:16px;">Vous n'avez pas encore commandé d'article.</p>
        <a href="{{ route('annonces.index') }}" class="btn btn-primary">Voir le marché</a>
    </div>

    <div id="cmdList" style="display:flex; flex-direction:column; gap:16px;"></div>
</div>
@endsection

@section('scripts')
<script>
const API_BASE = 'http://localhost:8888';
const STATUT_LABELS = {
    'commandee':    { txt: 'Commandée', bg: '#fff4d6' },
    'deposee':      { txt: 'Déposée',   bg: '#e3eefd' },
    'en_conteneur': { txt: 'En conteneur', bg: '#e3eefd' },
    'recuperee':    { txt: 'Récupérée', bg: '#dff5e1' },
    'annulee':      { txt: 'Annulée',   bg: '#fde2e2' }
};

function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function(c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}

function formatDate(s) {
    if (!s) return '';
    var d = new Date(s);
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatPrix(p) {
    return (parseFloat(p) || 0).toFixed(2).replace('.', ',') + ' €';
}

document.addEventListener('DOMContentLoaded', async function() {
    var token = localStorage.getItem('auth_token');
    var loading = document.getElementById('cmdLoading');
    var empty = document.getElementById('cmdEmpty');
    var loginReq = document.getElementById('cmdLoginRequired');
    var list = document.getElementById('cmdList');

    if (!token) {
        loading.style.display = 'none';
        loginReq.style.display = 'block';
        return;
    }

    try {
        var res = await fetch(API_BASE + '/api/v1/commandes/me', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        if (!res.ok) throw new Error('Erreur ' + res.status);
        var commandes = await res.json();
        loading.style.display = 'none';
        if (!commandes || commandes.length === 0) {
            empty.style.display = 'block';
            return;
        }
        list.innerHTML = commandes.map(function(c) {
            var st = STATUT_LABELS[c.statut] || { txt: c.statut, bg: '#eee' };
            var prixLabel = c.type_annonce === 'don' ? 'Gratuit' : formatPrix(c.prix);
            var dateLim = c.date_limite_recuperation ? '<p class="font-mono" style="font-size:0.7rem; opacity:0.55; margin-top:6px;">À récupérer avant le ' + formatDate(c.date_limite_recuperation) + '</p>' : '';
            return '<div style="border:var(--border); padding:20px 24px; background:white; box-shadow:var(--shadow-sm);">' +
                '<div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:8px;">' +
                    '<div>' +
                        '<a href="/annonces/' + c.id_annonce + '" style="font-weight:600; font-size:1.05rem; text-decoration:none;">' + escapeHtml(c.titre) + '</a>' +
                        '<p class="font-mono" style="font-size:0.7rem; opacity:0.55; margin-top:4px;">' +
                            'Commande #' + c.id_commande +
                            ' · ' + formatDate(c.date_commande) +
                            ' · Vendeur ' + escapeHtml(c.vendeur_prenom + ' ' + (c.vendeur_nom_initiale || '')) +
                        '</p>' +
                    '</div>' +
                    '<span class="badge" style="background:' + st.bg + ';">' + st.txt + '</span>' +
                '</div>' +
                '<div style="display:flex; justify-content:space-between; align-items:center;">' +
                    '<span class="font-mono" style="font-size:0.72rem; opacity:0.6;">Remise ' + (c.mode_remise === 'conteneur' ? 'via conteneur' : 'en main propre') + '</span>' +
                    '<span style="font-family:\'Bebas Neue\',sans-serif; font-size:1.5rem; color:var(--cherry,#a72f43);">' + prixLabel + '</span>' +
                '</div>' +
                dateLim +
            '</div>';
        }).join('');
    } catch (e) {
        loading.textContent = 'Erreur de chargement : ' + e.message;
    }
});
</script>
@endsection

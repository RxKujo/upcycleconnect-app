// Panier UpcycleConnect — stockage localStorage
(function () {
    const KEY = 'uc_panier';
    const API_BASE = 'http://localhost:8888';

    function read() {
        try { return JSON.parse(localStorage.getItem(KEY) || '[]'); }
        catch { return []; }
    }
    function write(items) {
        localStorage.setItem(KEY, JSON.stringify(items));
        document.dispatchEvent(new CustomEvent('panier:change', { detail: { count: items.length } }));
    }

    const Panier = {
        items() { return read(); },
        count() { return read().length; },
        total() { return read().reduce((s, i) => s + (parseFloat(i.prix) || 0), 0); },
        has(id) { return read().some(i => i.id_annonce === Number(id)); },
        add(item) {
            const items = read();
            if (items.some(i => i.id_annonce === Number(item.id_annonce))) return false;
            items.push({
                id_annonce: Number(item.id_annonce),
                titre: item.titre || '',
                prix: parseFloat(item.prix) || 0,
                type_annonce: item.type_annonce || 'vente',
                mode_remise: item.mode_remise || '',
                vendeur: item.vendeur || ''
            });
            write(items);
            return true;
        },
        remove(id) { write(read().filter(i => i.id_annonce !== Number(id))); },
        clear() { write([]); },
        async checkout() {
            const token = localStorage.getItem('auth_token');
            if (!token) {
                window.location.href = '/login?intent=checkout';
                return null;
            }
            const items = read().map(i => ({ id_annonce: i.id_annonce }));
            const res = await fetch(API_BASE + '/api/v1/commandes/checkout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify({ items })
            });
            const body = await res.json();
            if (res.ok || res.status === 201) {
                if (body.failed && body.failed.length === 0) {
                    write([]);
                } else if (body.created && body.created.length > 0) {
                    const successIds = body.created.map(c => c.id_annonce);
                    write(read().filter(i => !successIds.includes(i.id_annonce)));
                }
            }
            return { res, body };
        }
    };

    window.UCPanier = Panier;

    // Met à jour le badge dans la navbar à chaque changement
    function refreshBadge() {
        const badge = document.getElementById('nav-cart-count');
        if (!badge) return;
        const c = Panier.count();
        badge.textContent = c;
        badge.style.display = c > 0 ? 'inline-flex' : 'none';
    }
    document.addEventListener('panier:change', refreshBadge);
    document.addEventListener('DOMContentLoaded', refreshBadge);
    window.addEventListener('storage', function (e) {
        if (e.key === KEY) refreshBadge();
    });
})();

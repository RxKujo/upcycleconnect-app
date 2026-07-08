// Carte réutilisable des conteneurs (Leaflet + OpenStreetMap), bundlée par Vite.
//
// Utilisation (Blade) :
//   <div data-conteneurs-map
//        data-api="{{ config('services.api.public_url') }}"
//        data-selectable="1"></div>
//   @vite('resources/js/conteneurs-map.js')
//
// La carte s'auto-initialise sur chaque élément [data-conteneurs-map] :
//   - charge GET {api}/api/v1/public/conteneurs (lat/lng déjà en base) ;
//   - pose un marqueur par conteneur, popup avec adresse + lien Itinéraire
//     (Google/Apple Maps) et, si data-selectable, un bouton « Sélectionner » ;
//   - bouton « Autour de moi » : géolocalise, recentre, trie par distance
//     (Haversine, côté client) et ouvre le conteneur le plus proche.
//
// Événements émis sur l'élément carte :
//   - 'conteneur:selected'  detail = { conteneur }        (clic sur « Sélectionner »)
//   - 'conteneurs:nearest'  detail = { position, liste }  (après géolocalisation)

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/conteneurs-map.css';

// Correctif classique : avec un bundler, Leaflet ne retrouve pas ses icônes.
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Indispensable : sinon Icon.Default préfixe son « imagePath » détecté (en dev,
// l'URL absolue du serveur Vite) devant nos URLs déjà absolues → URL doublée, 404.
// On supprime ce comportement pour que Leaflet utilise nos URLs telles quelles.
delete L.Icon.Default.prototype._getIconUrl;

L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// --- Constantes et fonctions utilitaires ---

const CENTRE_FRANCE = [46.603354, 1.888334];

// Distance à vol d'oiseau entre deux points GPS (km).
function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const toRad = (d) => (d * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

function itineraireUrl(lat, lng) {
    // URL universelle : ouvre Google Maps (et Apple Maps sur iOS) en itinéraire.
    return `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
}

function popupHtml(c, selectable, distanceKm) {
    const lignes = [
        `<strong>${escapeHtml(c.conteneur_ref)}</strong>`,
        `${escapeHtml(c.adresse)}, ${escapeHtml(c.ville)}`,
        `Capacité : ${escapeHtml(c.capacite)} objets`,
    ];
    if (typeof distanceKm === 'number') {
        lignes.push(`<em>À ${distanceKm.toFixed(distanceKm < 10 ? 1 : 0)} km</em>`);
    }
    const actions = [
        `<a href="${itineraireUrl(c.latitude, c.longitude)}" target="_blank" rel="noopener" class="cmap-link">Itinéraire</a>`,
    ];
    if (selectable) {
        actions.push(`<button type="button" class="cmap-btn" data-select-conteneur="${c.id_conteneur}">Sélectionner</button>`);
    }
    return `<div class="cmap-popup">${lignes.join('<br>')}<div class="cmap-actions">${actions.join('')}</div></div>`;
}

// Contrôle Leaflet « Autour de moi ».
function boutonAutourDeMoi(onClick) {
    const Control = L.Control.extend({
        options: { position: 'topright' },
        onAdd() {
            const btn = L.DomUtil.create('button', 'cmap-locate');
            btn.type = 'button';
            btn.title = 'Trouver les conteneurs autour de moi';
            btn.textContent = 'Autour de moi';
            L.DomEvent.disableClickPropagation(btn);
            L.DomEvent.on(btn, 'click', onClick);
            return btn;
        },
    });
    return new Control();
}

// --- Initialisation d'une carte sur un element [data-conteneurs-map] ---

function initCarte(el) {
    const api = (el.dataset.api || '').replace(/\/$/, '');
    const selectable = el.dataset.selectable === '1' || el.dataset.selectable === 'true';
    const zoom = parseInt(el.dataset.zoom || '', 10);

    const map = L.map(el).setView(CENTRE_FRANCE, Number.isNaN(zoom) ? 6 : zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    let conteneurs = [];
    const markers = new Map(); // id_conteneur -> L.Marker
    let userMarker = null;

    // Délégation : clic sur le bouton « Sélectionner » d'une popup.
    if (selectable) {
        map.on('popupopen', (e) => {
            const node = e.popup.getElement();
            const btn = node && node.querySelector('[data-select-conteneur]');
            if (!btn) return;
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.selectConteneur, 10);
                const c = conteneurs.find((x) => x.id_conteneur === id);
                if (c) {
                    el.dispatchEvent(new CustomEvent('conteneur:selected', { detail: { conteneur: c }, bubbles: true }));
                    map.closePopup();
                }
            }, { once: true });
        });
    }

    function autourDeMoi() {
        if (!navigator.geolocation) {
            alert("La géolocalisation n'est pas disponible sur votre navigateur.");
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude: lat, longitude: lng } = pos.coords;
                if (userMarker) map.removeLayer(userMarker);
                userMarker = L.circleMarker([lat, lng], {
                    radius: 8, color: '#1b5e20', fillColor: '#43a047', fillOpacity: 0.9, weight: 2,
                }).addTo(map).bindPopup('Vous êtes ici');

                const avecGeo = conteneurs.filter((c) => c.latitude && c.longitude);
                const tries = avecGeo
                    .map((c) => ({ c, d: haversineKm(lat, lng, c.latitude, c.longitude) }))
                    .sort((a, b) => a.d - b.d);

                // Met à jour les popups avec la distance.
                tries.forEach(({ c, d }) => {
                    const m = markers.get(c.id_conteneur);
                    if (m) m.setPopupContent(popupHtml(c, selectable, d));
                });

                el.dispatchEvent(new CustomEvent('conteneurs:nearest', {
                    detail: { position: { lat, lng }, liste: tries.map((t) => ({ ...t.c, distance_km: t.d })) },
                    bubbles: true,
                }));

                if (tries.length) {
                    const proche = tries[0];
                    map.setView([proche.c.latitude, proche.c.longitude], 12);
                    markers.get(proche.c.id_conteneur)?.openPopup();
                } else {
                    map.setView([lat, lng], 12);
                }
            },
            () => alert("Impossible de vous géolocaliser (permission refusée ?)."),
            { enableHighAccuracy: true, timeout: 8000 },
        );
    }

    boutonAutourDeMoi(autourDeMoi).addTo(map);

    async function charger() {
        try {
            const r = await fetch(`${api}/api/v1/public/conteneurs`);
            conteneurs = r.ok ? await r.json() : [];
        } catch (_e) {
            conteneurs = [];
        }
        const avecGeo = conteneurs.filter((c) => c.latitude && c.longitude);
        avecGeo.forEach((c) => {
            const m = L.marker([c.latitude, c.longitude]).addTo(map);
            m.bindPopup(popupHtml(c, selectable));
            markers.set(c.id_conteneur, m);
        });
        if (avecGeo.length) {
            map.fitBounds(L.latLngBounds(avecGeo.map((c) => [c.latitude, c.longitude])).pad(0.2));
        }
    }

    charger();

    // Recalcule la taille si l'élément était masqué à l'init puis affiché
    // (ex. panneau « conteneur » d'un formulaire multi-étapes) — sinon Leaflet
    // s'initialise à 0px et la carte reste grise.
    if (typeof ResizeObserver !== 'undefined') {
        let lastW = el.clientWidth;
        new ResizeObserver(() => {
            const w = el.clientWidth;
            if (w > 0 && w !== lastW) { lastW = w; map.invalidateSize(); }
        }).observe(el);
    }

    return map;
}

// --- Auto-initialisation de toutes les cartes presentes sur la page ---

function initAll() {
    document.querySelectorAll('[data-conteneurs-map]').forEach((el) => {
        if (el.dataset.cmapInit) return;
        el.dataset.cmapInit = '1';
        try {
            initCarte(el);
        } catch (err) {
            console.error('[conteneurs-map] échec de l\'initialisation :', err);
            el.innerHTML = '<div style="padding:16px;font:13px/1.5 monospace;color:#b00020;background:#fff3f3;">'
                + 'Carte indisponible : ' + escapeHtml(err && err.message ? err.message : String(err)) + '</div>';
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

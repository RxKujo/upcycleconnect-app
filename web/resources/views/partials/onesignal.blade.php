{{-- Notifications push OneSignal (Web Push).
     Rendu uniquement si l'App ID est configuré (ONESIGNAL_APP_ID).
     Associe l'utilisateur connecté via External ID = id utilisateur (extrait du
     JWT uc_token), pour un ciblage côté API Go sans stocker de player_id. --}}
@if(config('services.onesignal.app_id'))
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
<script>
window.OneSignalDeferred = window.OneSignalDeferred || [];
window.OneSignalDeferred.push(async function(OneSignal) {
    await OneSignal.init({
        appId: @js(config('services.onesignal.app_id')),
        allowLocalhostAsSecureOrigin: true, // autorise le dev en http://localhost
    });

    // External ID = id utilisateur, extrait du JWT (base64url) sans appel réseau.
    try {
        const token = localStorage.getItem('uc_token') || localStorage.getItem('auth_token');
        if (token) {
            const part = token.split('.')[1] || '';
            const json = atob(part.replace(/-/g, '+').replace(/_/g, '/').padEnd(part.length + (4 - part.length % 4) % 4, '='));
            const uid = JSON.parse(json).id;
            if (uid) {
                await OneSignal.login(String(uid));
            }
        }
    } catch (e) {
        console.warn('[OneSignal] External ID non défini :', e);
    }

    // Invite l'utilisateur à activer les notifications (non bloquant).
    try { OneSignal.Slidedown.promptPush(); } catch (e) {}
});
</script>
@endif

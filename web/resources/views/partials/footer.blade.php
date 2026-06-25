<footer style="background:var(--coffee); color:var(--cream); border-top:var(--border);">
    <div style="max-width:1200px; margin:0 auto; padding:80px 32px 64px; display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:48px;">
        <div>
            <a href="{{ route('home') }}" style="font-family:'Bebas Neue',sans-serif; font-size:1.8rem; letter-spacing:0.1em; color:var(--wheat); display:block; margin-bottom:14px;">UpcycleConnect</a>
            <p style="font-size:0.9rem; color:rgba(245,240,225,0.6); line-height:1.6; max-width:260px;" data-i18n="footer.tagline">
                Réduire les déchets, valoriser les matériaux, connecter les communautés.
            </p>
        </div>
        <nav aria-label="Plateforme">
            <p style="font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--wheat); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid rgba(245,240,225,0.15);" data-i18n="footer.platform">Plateforme</p>
            <ul style="display:flex; flex-direction:column; gap:10px;">
                <li><a href="{{ route('annonces.index') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.market">Marché</a></li>
                <li><a href="{{ route('evenements.index') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.events">Événements</a></li>
                <li><a href="{{ route('ressources.index') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.resources">Ressources</a></li>
                <li><a href="{{ route('forum.index') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.forum">Forum</a></li>
            </ul>
        </nav>
        <nav aria-label="Entreprise">
            <p style="font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--wheat); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid rgba(245,240,225,0.15);" data-i18n="footer.company">Entreprise</p>
            <ul style="display:flex; flex-direction:column; gap:10px;">
                <li><a href="{{ route('a-propos') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.about">À propos</a></li>
                <li><a href="{{ route('services-pro') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.servicespro">Services Pro</a></li>
            </ul>
        </nav>
        <nav aria-label="Légal">
            <p style="font-family:'DM Mono',monospace; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--wheat); margin-bottom:20px; padding-bottom:10px; border-bottom:2px solid rgba(245,240,225,0.15);" data-i18n="footer.legal">Légal</p>
            <ul style="display:flex; flex-direction:column; gap:10px;">
                <li><a href="{{ route('cgu') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.cgu">CGU</a></li>
                <li><a href="{{ route('rgpd') }}" style="font-size:0.9rem; color:rgba(245,240,225,0.6); transition:color 0.15s;" data-i18n="nav.rgpd">RGPD</a></li>
            </ul>
        </nav>
    </div>
    <div style="border-top:2px solid rgba(245,240,225,0.1); padding:20px 32px; max-width:1200px; margin:0 auto;">
        <p style="font-family:'DM Mono',monospace; font-size:0.72rem; text-transform:uppercase; letter-spacing:0.05em; color:rgba(245,240,225,0.35);">
            &copy; 2026 UpcycleConnect — Digital Worm Mission 1
        </p>
    </div>
</footer>

<style>
footer a:hover { color: var(--cream) !important; }

@media (max-width: 1024px) {
    footer > div:first-child { grid-template-columns: 1fr 1fr !important; }
}
@media (max-width: 768px) {
    footer > div:first-child { grid-template-columns: 1fr !important; gap: 32px !important; }
}
</style>

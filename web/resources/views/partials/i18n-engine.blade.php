{{-- Moteur i18n partagé. Applique la langue enregistrée (localStorage uc_lang)
     sur les éléments [data-i18n] / [data-i18n-html] / [data-i18n-ph], en
     chargeant le dictionnaire via /api/v1/public/i18n/{code} (repli sur le texte
     FR d'origine). À inclure dans les layouts internes (particulier, pro,
     salarié, admin) qui n'embarquent pas déjà le moteur du layout public. --}}
<script>
if (typeof window.__ucI18n === 'undefined') {
    window.__ucI18n = true;
    const _i18nCache = {};

    async function applyTranslations(lang) {
        document.querySelectorAll('[data-i18n]').forEach(el => {
            if (el.dataset.i18nOrig === undefined) el.dataset.i18nOrig = el.textContent;
        });
        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            if (el.dataset.i18nOrigHtml === undefined) el.dataset.i18nOrigHtml = el.innerHTML;
        });
        document.querySelectorAll('[data-i18n-ph]').forEach(el => {
            if (el.dataset.i18nOrigPh === undefined) el.dataset.i18nOrigPh = el.getAttribute('placeholder') || '';
        });

        if (lang === 'fr') {
            document.querySelectorAll('[data-i18n]').forEach(el => { el.textContent = el.dataset.i18nOrig; });
            document.querySelectorAll('[data-i18n-html]').forEach(el => { el.innerHTML = el.dataset.i18nOrigHtml; });
            document.querySelectorAll('[data-i18n-ph]').forEach(el => { el.setAttribute('placeholder', el.dataset.i18nOrigPh); });
            return;
        }

        let dict = _i18nCache[lang];
        if (!dict) {
            try {
                const res = await fetch('{{ config("services.api.public_url") }}/api/v1/public/i18n/' + encodeURIComponent(lang));
                dict = res.ok ? await res.json() : {};
            } catch (e) { dict = {}; }
            _i18nCache[lang] = dict;
        }

        document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            el.textContent = (dict && dict[key]) ? dict[key] : el.dataset.i18nOrig;
        });
        document.querySelectorAll('[data-i18n-html]').forEach(el => {
            const key = el.getAttribute('data-i18n-html');
            el.innerHTML = (dict && dict[key]) ? dict[key] : el.dataset.i18nOrigHtml;
        });
        document.querySelectorAll('[data-i18n-ph]').forEach(el => {
            const key = el.getAttribute('data-i18n-ph');
            el.setAttribute('placeholder', (dict && dict[key]) ? dict[key] : el.dataset.i18nOrigPh);
        });
    }

    function setLang(lang) {
        localStorage.setItem('uc_lang', lang);
        document.documentElement.lang = lang;
        document.querySelectorAll('.nav-lang-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.lang === lang);
        });
        applyTranslations(lang);
    }

    window.applyTranslations = applyTranslations;
    window.setLang = setLang;

    document.addEventListener('DOMContentLoaded', function () {
        setLang(localStorage.getItem('uc_lang') || 'fr');
    });
}
</script>

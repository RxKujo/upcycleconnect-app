import i18next from 'i18next';
import LanguageDetector from 'i18next-browser-languagedetector';
import fr from './fr.json';
import en from './en.json';

i18next
    .use(LanguageDetector)
    .init({
        fallbackLng: 'fr',
        supportedLngs: ['fr', 'en'],
        resources: {
            fr: { translation: fr },
            en: { translation: en },
        },
        detection: {
            order: ['localStorage', 'navigator'],
            caches: ['localStorage'],
            lookupLocalStorage: 'uc_lang',
        },
        interpolation: { escapeValue: false },
    });

export default i18next;

export function t(key, options = {}) {
    return i18next.t(key, options);
}

export function setLang(lang) {
    i18next.changeLanguage(lang);
    localStorage.setItem('uc_lang', lang);
    document.documentElement.lang = lang;
}

export function getCurrentLang() {
    return i18next.language || 'fr';
}

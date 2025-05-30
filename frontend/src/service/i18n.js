import cs from '@/language/cs.json';
import en from '@/language/en.json';
import sk from '@/language/sk.json';
import { createI18n } from 'vue-i18n';

const messages = {
    en,
    cs,
    sk
};

const systemLanguage = navigator.language.split('-')[0];

const i18n = createI18n({
    legacy: false,
    locale: systemLanguage, // Default language
    fallbackLocale: 'cs', // second language
    messages
});

export const setLanguage = (language) => {
    i18n.global.locale.value = language; // Dynamická změna jazyka
};

export const getLanguage = () => {
    return systemLanguage;
};

export default i18n;

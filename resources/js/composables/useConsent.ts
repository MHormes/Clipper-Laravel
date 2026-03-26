import { computed, ref } from 'vue';

const CONSENT_KEY = 'cookie_consent';

type ConsentStatus = 'accepted' | 'declined' | null;

// Module-level ref so state is shared across all composable calls
const consent = ref<ConsentStatus>(
    typeof window !== 'undefined' ? (localStorage.getItem(CONSENT_KEY) as ConsentStatus) : null,
);

// If user already accepted in a previous session, grant analytics immediately
if (consent.value === 'accepted' && typeof (window as Window & { gtag?: Function }).gtag === 'function') {
    (window as Window & { gtag?: Function }).gtag!('consent', 'update', {
        analytics_storage: 'granted',
    });
}

export function useConsent() {
    const hasDecided = computed(() => consent.value !== null);

    function accept() {
        consent.value = 'accepted';
        localStorage.setItem(CONSENT_KEY, 'accepted');

        if (typeof (window as Window & { gtag?: Function }).gtag === 'function') {
            (window as Window & { gtag?: Function }).gtag!('consent', 'update', {
                analytics_storage: 'granted',
            });
        }
    }

    function decline() {
        consent.value = 'declined';
        localStorage.setItem(CONSENT_KEY, 'declined');
    }

    return { consent, hasDecided, accept, decline };
}

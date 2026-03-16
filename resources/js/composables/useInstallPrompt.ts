import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
}

export function useInstallPrompt() {
    const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
    const isInstalled = ref(false);
    const isMobileDevice = ref(false);

    const updateMobileDeviceState = () => {
        if (typeof window === 'undefined') {
            return;
        }

        const navigatorWithUserAgentData = window.navigator as Navigator & {
            userAgentData?: {
                mobile?: boolean;
            };
        };

        if (typeof navigatorWithUserAgentData.userAgentData?.mobile === 'boolean') {
            isMobileDevice.value = navigatorWithUserAgentData.userAgentData.mobile;
            return;
        }

        isMobileDevice.value = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(window.navigator.userAgent);
    };

    const updateInstalledState = () => {
        if (typeof window === 'undefined') {
            return;
        }

        isInstalled.value =
            window.matchMedia('(display-mode: standalone)').matches ||
            Boolean((window.navigator as Navigator & { standalone?: boolean }).standalone);
    };

    const handleBeforeInstallPrompt = (event: Event) => {
        event.preventDefault();
        deferredPrompt.value = event as BeforeInstallPromptEvent;
        updateInstalledState();
    };

    const handleAppInstalled = () => {
        deferredPrompt.value = null;
        isInstalled.value = true;
    };

    const promptInstall = async () => {
        if (!deferredPrompt.value) {
            return false;
        }

        await deferredPrompt.value.prompt();

        const { outcome } = await deferredPrompt.value.userChoice;

        deferredPrompt.value = null;

        if (outcome === 'accepted') {
            isInstalled.value = true;
        }

        return outcome === 'accepted';
    };

    onMounted(() => {
        updateMobileDeviceState();
        updateInstalledState();

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.removeEventListener('appinstalled', handleAppInstalled);
    });

    return {
        canInstall: computed(() => isMobileDevice.value && !isInstalled.value && deferredPrompt.value !== null),
        isInstalled: computed(() => isInstalled.value),
        promptInstall,
    };
}

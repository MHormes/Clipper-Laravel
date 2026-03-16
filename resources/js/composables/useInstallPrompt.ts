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
        updateInstalledState();

        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.removeEventListener('appinstalled', handleAppInstalled);
    });

    return {
        canInstall: computed(() => !isInstalled.value && deferredPrompt.value !== null),
        isInstalled: computed(() => isInstalled.value),
        promptInstall,
    };
}

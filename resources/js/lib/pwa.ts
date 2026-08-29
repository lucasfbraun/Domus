import { computed, readonly, ref } from 'vue';

type PromptOutcome = 'accepted' | 'dismissed';

export interface BeforeInstallPromptEvent extends Event {
    readonly platforms: string[];
    readonly userChoice: Promise<{ outcome: PromptOutcome; platform: string }>;
    prompt: () => Promise<void>;
}

export type InstallResult = PromptOutcome | 'unavailable';

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const installed = ref(false);

const isBrowser = typeof window !== 'undefined';

function isAppleStandalone(): boolean {
    return (
        isBrowser &&
        Boolean((window.navigator as Navigator & { standalone?: boolean }).standalone)
    );
}

function detectInstalled(): boolean {
    if (!isBrowser) {
        return false;
    }

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: minimal-ui)').matches ||
        isAppleStandalone()
    );
}

function detectIos(): boolean {
    if (!isBrowser) {
        return false;
    }

    const agent = window.navigator.userAgent;

    // iPadOS reports itself as a Mac, so touch points are the only reliable hint.
    return (
        /iphone|ipad|ipod/i.test(agent) ||
        (/macintosh/i.test(agent) && window.navigator.maxTouchPoints > 1)
    );
}

/**
 * Chrome fires `beforeinstallprompt` once, shortly after load, and never replays it.
 * Listening from a component's `onMounted` races that event, so the capture has to
 * happen while the entry bundle evaluates.
 */
function startListening(): void {
    if (!isBrowser) {
        return;
    }

    installed.value = detectInstalled();

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt.value = event as BeforeInstallPromptEvent;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
        installed.value = true;
    });

    window
        .matchMedia('(display-mode: standalone)')
        .addEventListener('change', (event) => {
            if (event.matches) {
                installed.value = true;
            }
        });
}

startListening();

export function usePwaInstall() {
    const isInstalled = readonly(installed);
    const isIos = detectIos();
    const canPromptInstall = computed(
        () => deferredPrompt.value !== null && !installed.value,
    );

    async function promptInstall(): Promise<InstallResult> {
        const promptEvent = deferredPrompt.value;

        if (!promptEvent) {
            return 'unavailable';
        }

        // The captured event is single-use, whatever the user decides.
        deferredPrompt.value = null;

        await promptEvent.prompt();

        const { outcome } = await promptEvent.userChoice;

        if (outcome === 'accepted') {
            installed.value = true;
        }

        return outcome;
    }

    return { isInstalled, isIos, canPromptInstall, promptInstall };
}

import { ref, reactive } from "vue";

export const pwaState = reactive({
    canInstall: false,
    isInstalled: false,
    isIos: false,
    isStandalone: false,
    hasUpdate: false,
    registration: null,
    deferredPrompt: null,
});

export function initPwa() {
    if (typeof window === "undefined") return;

    // Detect standalone mode (already installed & running as PWA)
    const isStandalone =
        window.matchMedia("(display-mode: standalone)").matches ||
        window.navigator.standalone === true ||
        document.referrer.includes("android-app://");

    pwaState.isStandalone = isStandalone;
    pwaState.isInstalled = isStandalone;

    // Detect iOS
    const userAgent = window.navigator.userAgent.toLowerCase();
    const isIos = /iphone|ipad|ipod/.test(userAgent);
    pwaState.isIos = isIos;

    // Listen for beforeinstallprompt event (Chromium, Edge, Android Chrome, Samsung Internet)
    window.addEventListener("beforeinstallprompt", (e) => {
        // Prevent default browser mini-infobar
        e.preventDefault();
        pwaState.deferredPrompt = e;
        pwaState.canInstall = true;
    });

    // Listen for app installed event
    window.addEventListener("appinstalled", () => {
        pwaState.canInstall = false;
        pwaState.isInstalled = true;
        pwaState.deferredPrompt = null;
        console.log("[PWA] BPVP Pangkep - Super APP berhasil terpasang!");
    });

    // Register Service Worker
    if ("serviceWorker" in navigator) {
        window.addEventListener("load", async () => {
            try {
                const reg = await navigator.serviceWorker.register("/sw.js", {
                    scope: "/",
                });
                pwaState.registration = reg;

                // Check for updates
                reg.addEventListener("updatefound", () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener("statechange", () => {
                            if (
                                newWorker.state === "installed" &&
                                navigator.serviceWorker.controller
                            ) {
                                pwaState.hasUpdate = true;
                            }
                        });
                    }
                });
            } catch (err) {
                console.warn("[PWA] Service worker registration error:", err);
            }
        });

        // Auto reload on controller change
        let refreshing = false;
        navigator.serviceWorker.addEventListener("controllerchange", () => {
            if (!refreshing) {
                refreshing = true;
                window.location.reload();
            }
        });
    }
}

export async function promptInstall() {
    if (pwaState.deferredPrompt) {
        try {
            pwaState.deferredPrompt.prompt();
            const { outcome } = await pwaState.deferredPrompt.userChoice;
            if (outcome === "accepted") {
                pwaState.canInstall = false;
            }
            pwaState.deferredPrompt = null;
            return outcome === "accepted";
        } catch (err) {
            console.warn("[PWA] Prompt error:", err);
            return false;
        }
    }
    return false;
}

export function updateApp() {
    if (pwaState.registration && pwaState.registration.waiting) {
        pwaState.registration.waiting.postMessage({ type: "SKIP_WAITING" });
    } else {
        window.location.reload();
    }
}

export function triggerInstallPrompt() {
    if (typeof window !== "undefined") {
        window.dispatchEvent(new CustomEvent("bpvp:open-pwa-install"));
    }
}

const CACHE_NAME = "bpvp-superapp-v1.0.0";
const OFFLINE_URL = "/offline.html";

const PRECACHE_ASSETS = [
    "/",
    OFFLINE_URL,
    "/manifest.json",
    "/favicon.ico",
    "/icons/icon-72x72.png",
    "/icons/icon-96x96.png",
    "/icons/icon-128x128.png",
    "/icons/icon-144x144.png",
    "/icons/icon-152x152.png",
    "/icons/icon-192x192.png",
    "/icons/icon-384x384.png",
    "/icons/icon-512x512.png",
    "/icons/apple-touch-icon.png",
    "/icons/maskable-icon-512x512.png",
];

// Install: Pre-cache offline shell
self.addEventListener("install", (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn("[PWA SW] Precache warning:", err);
            });
        }),
    );
    self.skipWaiting();
});

// Activate: Clean up old caches
self.addEventListener("activate", (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((name) => {
                        if (name !== CACHE_NAME) {
                            return caches.delete(name);
                        }
                    }),
                );
            })
            .then(() => self.clients.claim()),
    );
});

// Fetch logic
self.addEventListener("fetch", (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET, Chrome extension schemes, and cross-origin requests
    if (request.method !== "GET" || !url.protocol.startsWith("http")) {
        return;
    }

    // 1. Navigation requests (HTML pages) -> Network first, fallback to cache, then offline.html
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Cache successful page navigations
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    const offlineFallback = await caches.match(OFFLINE_URL);
                    return (
                        offlineFallback ||
                        new Response("Offline", {
                            status: 503,
                            statusText: "Offline",
                        })
                    );
                }),
        );
        return;
    }

    // 2. Static assets (CSS, JS, Fonts, Images) -> Stale-while-revalidate
    const isStaticAsset =
        url.pathname.startsWith("/build/") ||
        url.pathname.startsWith("/icons/") ||
        url.pathname.startsWith("/storage/") ||
        url.pathname.endsWith(".css") ||
        url.pathname.endsWith(".js") ||
        url.pathname.endsWith(".woff2") ||
        url.pathname.endsWith(".png") ||
        url.pathname.endsWith(".avif") ||
        url.pathname.endsWith(".webp") ||
        url.pathname.endsWith(".svg");

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                const fetchPromise = fetch(request)
                    .then((networkResponse) => {
                        if (networkResponse && networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return networkResponse;
                    })
                    .catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            }),
        );
        return;
    }

    // 3. Other requests -> Network with cache fallback
    event.respondWith(fetch(request).catch(() => caches.match(request)));
});

// Support instant update via postMessage
self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
});

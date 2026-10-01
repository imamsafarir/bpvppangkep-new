const CACHE_NAME = "bpvp-superapp-v2.0.0";
const OFFLINE_URL = "/offline.html";

const PRECACHE_ASSETS = [
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

// Install: Pre-cache offline shell only
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

// Activate: Clean up all old caches immediately
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

// Fetch logic: Never serve stale cache for dynamic data, uploads, or admin pages
self.addEventListener("fetch", (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // 1. Only handle GET and same-origin HTTP/HTTPS requests
    if (request.method !== "GET" || !url.protocol.startsWith("http")) {
        return;
    }

    // 2. NEVER intercept or cache dynamic data, admin pages, APIs, or user media uploads (/storage)
    const isDynamicOrBypassed =
        url.pathname.startsWith("/admin") ||
        url.pathname.startsWith("/dashboard") ||
        url.pathname.startsWith("/lms/admin") ||
        url.pathname.startsWith("/lms/student") ||
        url.pathname.startsWith("/login") ||
        url.pathname.startsWith("/logout") ||
        url.pathname.startsWith("/api") ||
        url.pathname.startsWith("/storage") || // User media must ALWAYS be fresh from server!
        url.pathname.startsWith("/s/") || // Shortlink redirects
        url.searchParams.has("token") ||
        request.headers.get("X-Inertia") === "true" ||
        request.headers.get("X-Requested-With") === "XMLHttpRequest";

    if (isDynamicOrBypassed) {
        // Let browser handle request directly from network without SW interference
        return;
    }

    // 3. Navigation requests (HTML pages for public visitors) -> Network strictly, fallback to offline.html
    if (request.mode === "navigate") {
        event.respondWith(
            fetch(request).catch(async () => {
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

    // 4. Truly immutable build assets (/build/) and static PWA icons (/icons/) -> Cache first
    const isImmutableAsset =
        url.pathname.startsWith("/build/") ||
        url.pathname.startsWith("/icons/") ||
        url.pathname === "/favicon.ico" ||
        url.pathname === "/manifest.json";

    if (isImmutableAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                });
            }),
        );
        return;
    }

    // 5. All other requests -> Direct fetch without caching
    event.respondWith(fetch(request));
});

// Support instant update and cache clearing via postMessage
self.addEventListener("message", (event) => {
    if (event.data && event.data.type === "SKIP_WAITING") {
        self.skipWaiting();
    }
    if (event.data && event.data.type === "CLEAR_ALL_CACHES") {
        caches.keys().then((names) => {
            for (const name of names) {
                caches.delete(name);
            }
        });
    }
});

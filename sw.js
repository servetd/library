const CACHE_VERSION = 'pdfkutuphanem-v2';
const STATIC_CACHE = CACHE_VERSION + '-static';
const DYNAMIC_CACHE = CACHE_VERSION + '-dynamic';

const STATIC_ASSETS = [
    '/',
    '/index.php',
    '/notebook.php',
    '/manage.php',
    '/reader.php',
    '/offline.html',
    '/assets/css/style.css',
    '/assets/css/reader.css',
    '/assets/js/app.js',
    '/assets/js/reader.js',
    '/assets/js/dictionary.js',
    '/assets/js/notebook.js',
    '/assets/js/manage.js',
    '/assets/js/notes.js',
    '/notes.php',
    '/manifest.json',
    '/icon.php?size=192',
];

// Install - precache static assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate - clean old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => caches.delete(key))
            )
        ).then(() => self.clients.claim())
    );
});

// Fetch strategy
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') return;

    // Skip PDF serving requests (too large to cache)
    if (url.pathname === '/api/books.php' && url.searchParams.has('serve')) return;

    // Skip external requests (CDN for pdf.js, etc.)
    if (url.origin !== location.origin) return;

    // API calls: network-first with cache fallback
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(DYNAMIC_CACHE).then(cache => {
                            cache.put(event.request, clone);
                        });
                    }
                    return response;
                })
                .catch(() => caches.match(event.request))
        );
        return;
    }

    // Static assets and pages: cache-first with network fallback
    event.respondWith(
        caches.match(event.request)
            .then(cached => {
                if (cached) return cached;
                return fetch(event.request)
                    .then(response => {
                        if (response.ok && (
                            url.pathname.endsWith('.css') ||
                            url.pathname.endsWith('.js') ||
                            url.pathname.endsWith('.php') ||
                            url.pathname === '/'
                        )) {
                            const clone = response.clone();
                            caches.open(DYNAMIC_CACHE).then(cache => {
                                cache.put(event.request, clone);
                            });
                        }
                        return response;
                    })
                    .catch(() => {
                        // Navigation requests get offline page
                        if (event.request.mode === 'navigate') {
                            return caches.match('/offline.html');
                        }
                    });
            })
    );
});

/*
 | Service worker Portal Wali Murid (WALMUR).
 |
 | Strategi:
 |   - Halaman berisi data anak TIDAK pernah di-cache (privasi). Navigasi
 |     memakai network-first dan hanya jatuh ke halaman offline.html saat
 |     jaringan mati.
 |   - Aset statis (CSS/JS hasil build, ikon, gambar, font) memakai
 |     stale-while-revalidate agar tampilan terasa instan seperti aplikasi.
 |
 | Versi cache dinaikkan setiap kali daftar aset berubah supaya cache lama
 | dibersihkan pada event activate.
 */

const CACHE_VERSION = 'walmur-v3';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/apple-touch-icon.png',
    '/images/logo-walmur-emblem.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE_ASSETS)).then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => !key.startsWith(CACHE_VERSION)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

function isCacheableAsset(url) {
    return (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/uploads/') ||
        url.pathname === '/manifest.webmanifest' ||
        /\.(?:css|js|mjs|woff2?|ttf|otf|png|jpe?g|gif|svg|webp|ico)$/i.test(url.pathname)
    );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Hanya tangani GET. Livewire mengirim POST untuk aksi; jangan diintervensi.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Biarkan request lintas-origin (font Bunny, dsb.) ditangani default browser.
    if (url.origin !== self.location.origin) {
        return;
    }

    // Komponen Livewire (polling/update) tidak boleh di-cache.
    if (url.pathname.startsWith('/livewire/') || request.headers.get('X-Livewire')) {
        return;
    }

    // Navigasi: network-first, fallback ke halaman offline.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() =>
                caches.match(OFFLINE_URL).then((cached) => cached ?? new Response('Offline', { status: 503 })),
            ),
        );

        return;
    }

    // Aset statis: stale-while-revalidate.
    if (isCacheableAsset(url)) {
        event.respondWith(
            caches.open(STATIC_CACHE).then(async (cache) => {
                const cached = await cache.match(request);

                const network = fetch(request)
                    .then((response) => {
                        if (response && response.status === 200 && response.type === 'basic') {
                            cache.put(request, response.clone());
                        }

                        return response;
                    })
                    .catch(() => cached);

                return cached ?? network;
            }),
        );
    }
});

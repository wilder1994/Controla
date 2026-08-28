self.addEventListener('install', (event) => {
    event.waitUntil(caches.open('controla-sup-v20').then((cache) => cache.addAll(['./', './index.html', './app.js', './manifest.json'])));
});
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== 'controla-sup-v20').map((k) => caches.delete(k)))),
    );
});
self.addEventListener('fetch', (event) => {
    event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request)));
});

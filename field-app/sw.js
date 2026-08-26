self.addEventListener('install', (event) => {
    event.waitUntil(caches.open('controla-sup-v1').then((cache) => cache.addAll(['./', './index.html', './app.js', './manifest.json'])));
});
self.addEventListener('fetch', (event) => {
    event.respondWith(caches.match(event.request).then((cached) => cached || fetch(event.request)));
});

const CACHE_NAME = 'archive-system-v1';
const urlsToCache = [
  '/',
  '/dashboard-student.php',
  '/dashboard-instructor.php',
  '/dashboard-admin.php',
  '/browse.php',
  '/threads.php',
  '/assets/bootstrap/bootstrap.min.css',
  '/assets/bootstrap/bootstrap.bundle.min.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => {
        // Return cached version or fetch from network
        return response || fetch(event.request);
      })
  );
});
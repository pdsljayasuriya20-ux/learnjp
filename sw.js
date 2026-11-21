/* sw.js - minimal service worker (makes the site installable on Android; shows a friendly page when offline) */
var OFFLINE = '<!doctype html><html lang="si"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Offline</title>' +
  '<style>body{font-family:system-ui,sans-serif;background:#e6e9f2;color:#2d3250;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;text-align:center;padding:24px}' +
  'button{background:#4f46e5;color:#fff;border:0;border-radius:999px;padding:12px 26px;font-weight:700;font-size:1rem;margin-top:14px}</style></head>' +
  '<body><div><div style="font-size:3rem">📡</div><h2>අන්තර්ජාල සම්බන්ධතාවය නැහැ</h2><p>Internet එක ආපසු ආවම නැවත උත්සාහ කරන්න.</p>' +
  '<button onclick="location.reload()">නැවත උත්සාහ කරන්න</button></div></body></html>';

self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });

self.addEventListener('fetch', function (e) {
  var r = e.request;
  if (r.method !== 'GET' || r.mode !== 'navigate') return;      // only page loads; everything else goes straight to the network
  e.respondWith(fetch(r).catch(function () {
    return new Response(OFFLINE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
  }));
});

/**
 * tracking.js - sends page views, time, scroll, clicks to /track.php
 * Put in public_html/ (next to track.php). Change ENDPOINT if track.php is elsewhere.
 */
(function () {
  'use strict';
  if (window.__laTrack) return;
  window.__laTrack = true;

  var ENDPOINT = '/track.php';
  var pageUrl = location.pathname + location.search;
  var noop = function () {};

  function send(data, beacon) {
    try {
      var fd = new FormData();
      for (var k in data) if (Object.prototype.hasOwnProperty.call(data, k)) fd.append(k, data[k]);
      if (beacon && navigator.sendBeacon) { navigator.sendBeacon(ENDPOINT, fd); return; }
      fetch(ENDPOINT, { method: 'POST', body: fd, credentials: 'same-origin', keepalive: true }).catch(noop);
    } catch (e) {}
  }

  /* ---------- page view ---------- */
  send({
    track_visit: 1,
    page_url: pageUrl,
    page_title: document.title || '',
    screen_resolution: screen.width + 'x' + screen.height,
    referrer: document.referrer || ''
  });

  /* ---------- time on site (cumulative per browser tab session, only while tab is visible) ---------- */
  var shownSince = document.visibilityState === 'visible' ? Date.now() : null;
  function store(v) {
    try { if (v === undefined) return parseInt(sessionStorage.getItem('la_time') || '0', 10) || 0; sessionStorage.setItem('la_time', String(v)); } catch (e) { return 0; }
  }
  function flushTime() {
    if (shownSince !== null) {
      store(store() + Math.round((Date.now() - shownSince) / 1000));
      shownSince = null;
    }
    var t = store();
    if (t > 0) send({ update_time: 1, time_spent: t }, true);
  }
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') flushTime();
    else shownSince = Date.now();
  });
  window.addEventListener('pagehide', flushTime);

  /* ---------- scroll depth (25/50/75/100, once each) ---------- */
  var marks = [25, 50, 75, 100], sentMarks = {}, ticking = false;
  function onScroll() {
    if (ticking) return;
    ticking = true;
    setTimeout(function () {
      ticking = false;
      var h = document.documentElement, max = h.scrollHeight - h.clientHeight;
      if (max <= 0) return;
      var pct = Math.round((window.pageYOffset || h.scrollTop) / max * 100);
      marks.forEach(function (m) {
        if (pct >= m && !sentMarks[m]) { sentMarks[m] = 1; send({ track_scroll: 1, scroll_depth: m, page_url: pageUrl }); }
      });
    }, 250);
  }
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------- clicks ---------- */
  document.addEventListener('click', function (ev) {
    var el = ev.target && ev.target.closest ? ev.target.closest('a,button,[data-track]') : null;
    if (!el) return;
    var href = el.getAttribute('href') || '';
    var isLink = el.tagName === 'A' && href !== '' && href.charAt(0) !== '#' && href.indexOf('javascript:') !== 0;

    if (isLink && /^https?:/i.test(el.href) && el.hostname && el.hostname !== location.hostname) {
      send({ track_outbound: 1, destination: el.href, page_url: pageUrl }, true);      // external (incl. wa.me)
    } else if (isLink && /^(tel|mailto):/i.test(href)) {
      send({ track_click: 1, element_type: 'contact', element_text: (el.textContent || '').trim(), element_href: href }, true);
    } else if (el.hasAttribute('data-track') || el.tagName === 'BUTTON') {
      send({
        track_click: 1,
        element_type: el.getAttribute('data-track') || el.tagName.toLowerCase(),
        element_text: (el.textContent || el.value || '').trim().slice(0, 100),
        element_href: href || pageUrl
      }, true);
    }
  }, true);

  /* ---------- JS errors (max 3 per page) ---------- */
  var errN = 0;
  window.addEventListener('error', function (e) {
    if (errN++ >= 3) return;
    send({ track_error: 1, error_message: e.message || '', error_file: e.filename || '', error_line: e.lineno || '' }, true);
  });

  /* ---------- custom events: laTrack.event('signup_click', 'N4 plan') ---------- */
  window.laTrack = { event: function (name, data) { send({ track_custom: 1, event_name: name, event_data: data || '' }); } };
})();

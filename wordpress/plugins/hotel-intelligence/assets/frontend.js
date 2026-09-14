(function () {
  'use strict';

  var LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
  var LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
  var LEAFLET_CSS_SRI = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
  var LEAFLET_JS_SRI = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';

  function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
  function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  document.documentElement.classList.add('sthi-js');

  document.addEventListener('DOMContentLoaded', function () {
    initJourneyNav();
    measureStickyTop();
    initCinema();
    initLightbox();
    initReveal();
    initMapAutoLoad();
    window.addEventListener('resize', debounce(measureStickyTop, 140));
    window.addEventListener('load', measureStickyTop, { once: true });
    window.addEventListener('scroll', throttle(function () {
      measureStickyTop();
      updatePageProgress();
      updateActiveSection();
    }, 70), { passive: true });
    updatePageProgress();
    updateActiveSection();
  });

  function measureStickyTop() {
    /*
     * Server Turizm Header/Footer v1.1.10 is the active public shell. Its
     * Porto header is hidden and the visible topbar/header/logo use these real
     * selectors. Hero document-flow clearance is handled by the shell's own
     * st-shell-internal #main rule; JS only supplies sticky sub-navigation and
     * hanging-logo safe geometry.
     */
    var siteHeader = qs('.st-site-header');
    var topbar = qs('.st-topbar');
    var admin = qs('#wpadminbar');
    var headerBottom = 0;

    if (siteHeader) {
      var hr = siteHeader.getBoundingClientRect();
      if (hr.height > 0 && hr.bottom > 0) headerBottom = Math.max(0, hr.bottom);
    } else {
      /* Small compatibility fallback if the accepted shell is temporarily absent. */
      var adminBottom = 0;
      if (admin) {
        var ar = admin.getBoundingClientRect();
        if (ar.height > 0 && ar.bottom > 0) adminBottom = Math.max(0, ar.bottom);
      }
      var topbarBottom = adminBottom;
      if (topbar) {
        var tr = topbar.getBoundingClientRect();
        if (tr.height > 0 && tr.bottom > 0) topbarBottom = Math.max(topbarBottom, tr.bottom);
      }
      headerBottom = topbarBottom;
    }

    var stickyTop = Math.max(0, Math.round(headerBottom));
    document.documentElement.style.setProperty('--sthi-sticky-top', stickyTop + 'px');

    /* No runtime Hero offset. The Header/Footer shell owns #main clearance. */
    document.documentElement.style.setProperty('--sthi-hero-safe-top', '0px');

    var logoBottom = headerBottom;
    var logoRight = 0;
    var logo = qs('.st-brand-logo');
    if (logo) {
      var lr = logo.getBoundingClientRect();
      if (lr.width > 0 && lr.height > 0 && lr.bottom > 0) {
        logoBottom = Math.max(logoBottom, lr.bottom);
        logoRight = Math.max(0, lr.right);
      }
    }

    var nav = journeyNav || qs('[data-sthi-journey-nav]');
    var navHeight = nav ? nav.offsetHeight : 56;
    var navSafeLeft = 0;
    if (nav && window.innerWidth > 820 && logoRight > 0) {
      var nr = nav.getBoundingClientRect();
      var navIsSticky = nr.top <= stickyTop + 14;
      if (navIsSticky && logoRight > nr.left) {
        navSafeLeft = Math.max(0, Math.min(240, Math.round(logoRight - nr.left + 14)));
      }
    }
    document.documentElement.style.setProperty('--sthi-nav-safe-left', navSafeLeft + 'px');

    var storyTop = Math.max(stickyTop + 10 + navHeight + 18, Math.round(logoBottom) + 18);
    document.documentElement.style.setProperty('--sthi-story-top', storyTop + 'px');
  }

  var journeyNav;
  var storySections = [];

  function initJourneyNav() {
    journeyNav = qs('[data-sthi-journey-nav]');
    storySections = qsa('[data-sthi-section]');
    if (!journeyNav) return;

    qsa('a[href^="#"]', journeyNav).forEach(function (link) {
      link.addEventListener('click', function (e) {
        var id = link.getAttribute('href').slice(1);
        var target = document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
        history.replaceState(null, '', '#' + id);
      });
    });

    qsa('[data-sthi-scroll-to]').forEach(function (link) {
      link.addEventListener('click', function (e) {
        var target = document.getElementById(link.getAttribute('data-sthi-scroll-to'));
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
      });
    });
  }

  function updateActiveSection() {
    if (!journeyNav || !storySections.length) return;
    var sticky = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--sthi-sticky-top')) || 0;
    var marker = sticky + journeyNav.offsetHeight + 90;
    var active = storySections[0].getAttribute('data-sthi-section');
    storySections.forEach(function (section) {
      if (section.getBoundingClientRect().top <= marker) active = section.getAttribute('data-sthi-section');
    });
    qsa('[data-sthi-nav-link]', journeyNav).forEach(function (link) {
      var on = link.getAttribute('data-sthi-nav-link') === active;
      link.classList.toggle('is-active', on);
      if (on) ensureVisibleInNav(link);
    });
  }

  function ensureVisibleInNav(link) {
    var scroller = link.closest('.sthi-journey-scroll');
    if (!scroller || window.innerWidth > 820) return;
    var lr = link.getBoundingClientRect(), sr = scroller.getBoundingClientRect();
    if (lr.left < sr.left || lr.right > sr.right) {
      var targetLeft = link.offsetLeft - (scroller.clientWidth - link.offsetWidth) / 2;
      scroller.scrollTo({ left: Math.max(0, targetLeft), behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
    }
  }

  function updatePageProgress() {
    var app = qs('.sthi-hotel-app');
    if (!app) return;
    var r = app.getBoundingClientRect();
    var total = Math.max(1, r.height - window.innerHeight);
    var progress = Math.min(1, Math.max(0, -r.top / total));
    app.style.setProperty('--sthi-page-progress', progress.toFixed(4));
  }

  function initCinema() {
    var grid = qs('[data-sthi-cinema-grid]');
    if (!grid) return;
    var progress = qs('.sthi-cinema-mobile-progress span', grid);
    function update() {
      if (!progress || window.innerWidth > 820) return;
      var max = Math.max(1, grid.scrollWidth - grid.clientWidth);
      var p = Math.min(1, Math.max(0, grid.scrollLeft / max));
      progress.style.transform = 'translateX(' + (p * 400) + '%)';
    }
    grid.addEventListener('scroll', throttle(update, 30), { passive: true });
    update();

    if (window.matchMedia && window.matchMedia('(pointer:fine)').matches && !prefersReducedMotion()) {
      var main = qs('.sthi-cinema-tile-1', grid);
      var img = main && qs('img', main);
      if (main && img) {
        main.addEventListener('mousemove', function (e) {
          var r = main.getBoundingClientRect();
          var x = (e.clientX - r.left) / r.width - .5;
          var y = (e.clientY - r.top) / r.height - .5;
          img.style.transform = 'scale(1.035) translate(' + (x * -7).toFixed(1) + 'px,' + (y * -7).toFixed(1) + 'px)';
        });
        main.addEventListener('mouseleave', function () { img.style.transform = ''; });
      }
    }
  }

  function initReveal() {
    var items = qsa('[data-sthi-reveal]');
    if (!items.length || prefersReducedMotion() || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: .08 });
    items.forEach(function (el) { observer.observe(el); });
  }

  function initLightbox() {
    var dataEl = qs('#sthi-gallery-data');
    var dialog = qs('#sthi-lightbox');
    if (!dataEl || !dialog) return;
    var items = parseGallery(dataEl);
    if (!items.length) return;
    var img = qs('figure img', dialog);
    var count = qs('.sthi-lightbox-count', dialog);
    var current = 0;

    function render(index) {
      current = (index + items.length) % items.length;
      img.src = items[current].url;
      img.alt = items[current].alt || '';
      count.textContent = (current + 1) + ' / ' + items.length;
    }
    function open(index) {
      render(index || 0);
      if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', 'open');
      document.documentElement.classList.add('sthi-lightbox-open');
    }
    function close() {
      if (typeof dialog.close === 'function') dialog.close(); else dialog.removeAttribute('open');
      document.documentElement.classList.remove('sthi-lightbox-open');
    }

    qsa('[data-sthi-open-gallery]').forEach(function (button) {
      button.addEventListener('click', function () {
        var idx = parseInt(button.getAttribute('data-sthi-index') || '', 10);
        if (isNaN(idx)) idx = 0;
        open(idx);
      });
    });
    var closeBtn = qs('.sthi-lightbox-close', dialog), prev = qs('.sthi-lightbox-prev', dialog), next = qs('.sthi-lightbox-next', dialog);
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (prev) prev.addEventListener('click', function () { render(current - 1); });
    if (next) next.addEventListener('click', function () { render(current + 1); });
    dialog.addEventListener('click', function (event) { if (event.target === dialog) close(); });
    dialog.addEventListener('close', function () { document.documentElement.classList.remove('sthi-lightbox-open'); });
    document.addEventListener('keydown', function (event) {
      if (!dialog.hasAttribute('open')) return;
      if (event.key === 'ArrowLeft') render(current - 1);
      if (event.key === 'ArrowRight') render(current + 1);
      if (event.key === 'Escape') close();
    });
  }

  function initMapAutoLoad() {
    qsa('.sthi-live-map').forEach(function (mapEl) {
      var start = qs('[data-sthi-map-start]', mapEl);
      if (!start) return;
      var launched = false;
      function launch() {
        if (launched || mapEl.dataset.ready === '1') return;
        launched = true;
        start.disabled = true;
        var strong = qs('strong', start), small = qs('small', start);
        if (strong) strong.textContent = 'Harita yükleniyor…';
        loadLeaflet().then(function () { buildMap(mapEl); }).catch(function () {
          launched = false;
          start.disabled = false;
          if (strong) strong.textContent = 'Harita yüklenemedi';
          if (small) small.textContent = 'Google Maps bağlantısını kullanabilirsiniz.';
        });
      }
      start.addEventListener('click', launch);
      if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
          if (entries[0].isIntersecting) { launch(); observer.disconnect(); }
        }, { rootMargin: '360px 0px', threshold: .01 });
        observer.observe(mapEl);
      }
    });
  }

  function loadLeaflet() {
    if (window.L && window.L.map) return Promise.resolve(window.L);
    if (window._sthiLeafletPromise) return window._sthiLeafletPromise;
    window._sthiLeafletPromise = new Promise(function (resolve, reject) {
      if (!qs('link[data-sthi-leaflet]')) {
        var link = document.createElement('link');
        link.rel = 'stylesheet'; link.href = LEAFLET_CSS; link.integrity = LEAFLET_CSS_SRI; link.crossOrigin = '';
        link.setAttribute('data-sthi-leaflet', '1'); document.head.appendChild(link);
      }
      var existing = qs('script[data-sthi-leaflet]');
      if (existing) {
        existing.addEventListener('load', function () { window.L && window.L.map ? resolve(window.L) : reject(new Error('Leaflet unavailable')); }, { once: true });
        existing.addEventListener('error', reject, { once: true });
        return;
      }
      var script = document.createElement('script');
      script.src = LEAFLET_JS; script.integrity = LEAFLET_JS_SRI; script.crossOrigin = ''; script.async = true;
      script.setAttribute('data-sthi-leaflet', '1');
      script.onload = function () { window.L && window.L.map ? resolve(window.L) : reject(new Error('Leaflet unavailable')); };
      script.onerror = reject; document.head.appendChild(script);
    });
    return window._sthiLeafletPromise;
  }

  function buildMap(el) {
    if (el.dataset.ready === '1') return;
    var hotelLat = parseFloat(el.dataset.hotelLat), hotelLng = parseFloat(el.dataset.hotelLng);
    if (!isFinite(hotelLat) || !isFinite(hotelLng)) return;
    var targetLat = parseFloat(el.dataset.targetLat), targetLng = parseFloat(el.dataset.targetLng);
    var hasTarget = isFinite(targetLat) && isFinite(targetLng);
    var hotelName = el.dataset.hotelName || 'Hotel';
    var targetName = el.dataset.targetName || 'Harem';
    var distance = el.dataset.distance || '';

    el.innerHTML = '';
    var map = L.map(el, { zoomControl: true, attributionControl: true, scrollWheelZoom: false, tap: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

    function markerHtml(kind, letter) { return '<div class="sthi-map-marker ' + kind + '">' + letter + '</div>'; }
    var hotelIcon = L.divIcon({ className: 'sthi-map-divicon', html: markerHtml('is-hotel', 'H'), iconSize: [34,34], iconAnchor: [17,17] });
    L.marker([hotelLat, hotelLng], { icon: hotelIcon }).addTo(map).bindPopup('<strong>' + escapeHtml(hotelName) + '</strong>');

    if (hasTarget) {
      var targetIcon = L.divIcon({ className: 'sthi-map-divicon', html: markerHtml('is-haram', '★'), iconSize: [34,34], iconAnchor: [17,17] });
      L.marker([targetLat, targetLng], { icon: targetIcon }).addTo(map).bindPopup('<strong>' + escapeHtml(targetName) + '</strong>');
      var line = L.polyline([[hotelLat, hotelLng],[targetLat, targetLng]], { color: '#c9a227', weight: 4, opacity: .9, dashArray: '10 8' }).addTo(map);
      if (distance) {
        var mid = [(hotelLat + targetLat) / 2, (hotelLng + targetLng) / 2];
        L.marker(mid, { interactive: false, icon: L.divIcon({ className: 'sthi-distance-divicon', html: '<div class="sthi-map-distance-label">' + escapeHtml(distance) + '</div>', iconSize: [86,30], iconAnchor: [43,15] }) }).addTo(map);
      }
      map.fitBounds(line.getBounds(), { padding: [48,48], maxZoom: 16 });
    } else {
      map.setView([hotelLat, hotelLng], 15);
    }
    el.dataset.ready = '1';
    el._leafletMap = map;
    setTimeout(function () { map.invalidateSize(); }, 100);
  }

  function parseGallery(el) { try { return JSON.parse(el.textContent || '[]'); } catch (e) { return []; } }
  function escapeHtml(value) { return String(value).replace(/[&<>'"]/g, function (c) { return ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[c]; }); }
  function prefersReducedMotion() { return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; }
  function debounce(fn, wait) { var t; return function () { var args = arguments, ctx = this; clearTimeout(t); t = setTimeout(function () { fn.apply(ctx,args); }, wait); }; }
  function throttle(fn, wait) { var last = 0, timer; return function () { var now = Date.now(), args = arguments, ctx = this; if (now-last >= wait) { last=now; fn.apply(ctx,args); } else { clearTimeout(timer); timer=setTimeout(function(){ last=Date.now(); fn.apply(ctx,args); }, wait-(now-last)); } }; }
})();

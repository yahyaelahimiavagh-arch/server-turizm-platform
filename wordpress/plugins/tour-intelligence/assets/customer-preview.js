(function () {
  'use strict';

  function visible(el) {
    if (!el) return false;
    var r = el.getBoundingClientRect();
    var cs = window.getComputedStyle(el);
    return r.width > 0 && r.height > 0 && cs.display !== 'none' && cs.visibility !== 'hidden';
  }

  function findHeader() {
    var selectors = ['#stSiteHeader','header#stSiteHeader','.st-site-header','#header','header#header','.header-wrapper','header.site-header','.site-header'];
    for (var i = 0; i < selectors.length; i++) {
      var el = document.querySelector(selectors[i]);
      if (visible(el)) return el;
    }
    return null;
  }

  function markLiveNavSurface() {
    var selectors = [
      '#header .main-menu-wrap',
      '#header .header-bottom',
      '.header-wrapper .main-menu-wrap',
      '.header-wrapper .header-bottom',
      '#header .header-main .header-row',
      '.header-wrapper .header-main .header-row'
    ];
    for (var i = 0; i < selectors.length; i++) {
      var el = document.querySelector(selectors[i]);
      if (visible(el) && el.querySelector('nav,ul.menu,.main-menu,a')) {
        el.classList.add('stti-live-nav-surface');
        return el;
      }
    }
    return null;
  }

  function documentTop(el) {
    var r = el.getBoundingClientRect();
    return r.top + window.pageYOffset;
  }

  function collapseEmptyHeaderBands() {
    var selectors = [
      '#header .header-main',
      '.header-wrapper .header-main',
      '#header .header-spacer',
      '.header-wrapper .header-spacer',
      '#header .header-gap',
      '.header-wrapper .header-gap',
      '#header .sticky-header-spacer',
      '.header-wrapper .sticky-header-spacer',
      '#header .header-placeholder',
      '.header-wrapper .header-placeholder'
    ];
    var seen = [];
    selectors.forEach(function (selector) {
      document.querySelectorAll(selector).forEach(function (band) {
        if (seen.indexOf(band) !== -1) return;
        seen.push(band);
        if (!visible(band)) return;
        var r = band.getBoundingClientRect();
        if (r.height > 54) return;

        var meaningful = false;
        var candidates = band.querySelectorAll('a,img,button,nav,ul,ol,.menu,.logo,.header-logo,.social-icons');
        for (var i = 0; i < candidates.length; i++) {
          if (visible(candidates[i]) && candidates[i].getBoundingClientRect().height > 6) {
            meaningful = true;
            break;
          }
        }
        if (!meaningful && band.textContent.trim() === '') {
          band.classList.add('stti-empty-header-band');
        }
      });
    });
  }

  function syncHeaderOffset() {
    var root = document.querySelector('.stti-cx');
    var hero = document.querySelector('.stti-cx-hero');
    var header = findHeader();
    if (!root || !hero || !header) return;

    /* v0.5.7: the live Server Turizm header is #stSiteHeader. The previous
       selector preferred Porto's hidden #header, so the seam calculation never
       saw the visible fixed navigation. We now measure the actual live header
       and extend ONLY the hero artwork/overlay upward to its top edge. */
    root.style.setProperty('--stti-header-overlap', '0px');
    root.style.setProperty('--stti-hero-bleed-up', '0px');

    window.requestAnimationFrame(function () {
      var hr = header.getBoundingClientRect();
      var er = hero.getBoundingClientRect();
      var cs = window.getComputedStyle(header);
      if (!visible(header) || !visible(hero)) return;

      var headerTop = hr.top;
      if (cs.position !== 'fixed' && cs.position !== 'sticky') {
        headerTop = Math.max(0, hr.top);
      }

      /* Extend the artwork from the hero's normal top to the visible header's
         top. In the measured runtime that is 66px -> 30px = 36px. This covers
         the white #main / .main-content spacer without moving navigation or
         editorial content. */
      var bleed = Math.max(0, Math.ceil(er.top - headerTop));
      if (bleed > 180) bleed = 0; // fail closed on unexpected theme geometry
      root.style.setProperty('--stti-hero-bleed-up', bleed > 0 ? bleed + 'px' : '0px');
    });
  }

  function safeStorageGet(key) {
    try {
      var raw = window.localStorage.getItem(key);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      if (!parsed || !isFinite(parsed.lat) || !isFinite(parsed.lng)) return null;
      return parsed;
    } catch (e) {
      return null;
    }
  }

  function safeStorageSet(key, value) {
    try {
      window.localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {}
  }

  function normalKey(value) {
    return String(value || '').trim().toLocaleLowerCase('tr-TR').replace(/\s+/g, ' ');
  }

  function sleep(ms) {
    return new Promise(function (resolve) { window.setTimeout(resolve, ms); });
  }

  function updateMapStatus(text, state) {
    var el = document.getElementById('stti-cx-map-status');
    if (!el) return;
    el.classList.remove('ok', 'warn');
    if (state) el.classList.add(state);
    el.textContent = text;
  }

  function geocodeStop(stop, config) {
    if (!stop || !stop.queryable || !stop.query) {
      return Promise.resolve({ stop: stop, ok: false, reason: 'country_missing' });
    }

    var cacheKey = [config.cacheNamespace || 'stti_route_geo_v1', normalKey(stop.query)].join('|');
    var cached = safeStorageGet(cacheKey);
    if (cached) {
      return Promise.resolve({
        stop: stop,
        ok: true,
        lat: Number(cached.lat),
        lng: Number(cached.lng),
        source: 'browser_cache'
      });
    }

    var url = (config.geocoder || 'https://nominatim.openstreetmap.org/search') +
      '?format=jsonv2&limit=1&addressdetails=0&accept-language=tr&q=' + encodeURIComponent(stop.query);

    return fetch(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
      credentials: 'omit',
      referrerPolicy: 'strict-origin-when-cross-origin'
    }).then(function (response) {
      if (!response.ok) throw new Error('geocoder_http_' + response.status);
      return response.json();
    }).then(function (rows) {
      if (!Array.isArray(rows) || !rows.length) return { stop: stop, ok: false, reason: 'not_found' };
      var lat = Number(rows[0].lat);
      var lng = Number(rows[0].lon);
      if (!isFinite(lat) || !isFinite(lng)) return { stop: stop, ok: false, reason: 'invalid_coordinates' };
      safeStorageSet(cacheKey, { lat: lat, lng: lng, resolvedAt: Date.now(), query: stop.query });
      return { stop: stop, ok: true, lat: lat, lng: lng, source: 'nominatim' };
    }).catch(function () {
      return { stop: stop, ok: false, reason: 'request_failed' };
    });
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[char];
    });
  }

  function makePinIcon(stop) {
    var order = String(stop.order || '').padStart(2, '0');
    var label = escapeHtml(stop.name || 'Durak');
    return window.L.divIcon({
      className: 'stti-map-div-icon',
      html: '<div class="stti-map-pin">' + order + '<span class="stti-map-pin-label">' + label + '</span></div>',
      iconSize: [38, 38],
      iconAnchor: [19, 19]
    });
  }

  function renderGlobalRouteMap() {
    var mapEl = document.getElementById('stti-cx-route-map');
    var config = window.STTI_CX_MAP || null;
    if (!mapEl || !config || !Array.isArray(config.stops) || !config.stops.length) return;

    if (!window.L) {
      updateMapStatus('Harita altyapısı yüklenemedi; rota çizgisi aşağıda korunuyor.', 'warn');
      return;
    }

    var map = window.L.map(mapEl, {
      scrollWheelZoom: false,
      zoomControl: true,
      worldCopyJump: true,
      minZoom: Number(config.minZoom || 2)
    }).setView([20, 0], 2);

    window.L.tileLayer(config.tiles, {
      attribution: config.tileAttribution || '&copy; OpenStreetMap contributors',
      maxZoom: 19,
      minZoom: Number(config.minZoom || 2)
    }).addTo(map);

    var resolved = [];
    var unresolved = [];
    var chain = Promise.resolve();

    config.stops.forEach(function (stop, idx) {
      chain = chain.then(function () {
        updateMapStatus('Rota konumları doğrulanıyor… ' + (idx + 1) + '/' + config.stops.length, '');
        return geocodeStop(stop, config).then(function (result) {
          if (result.ok) resolved.push(result);
          else unresolved.push(result);
        });
      }).then(function () {
        if (idx < config.stops.length - 1) return sleep(Number(config.geocodeDelayMs || 1100));
      });
    });

    chain.then(function () {
      resolved.sort(function (a, b) { return Number(a.stop.order) - Number(b.stop.order); });

      var timelineItems = Array.prototype.slice.call(document.querySelectorAll('.stti-cx-route-line article'));
      var markerEntries = [];
      var routeShell = mapEl.closest ? mapEl.closest('.stti-cx-map-shell') : null;
      var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      resolved.forEach(function (point) {
        var marker = window.L.marker([point.lat, point.lng], {
          icon: makePinIcon(point.stop),
          keyboard: true,
          opacity: 1
        }).addTo(map).bindTooltip(escapeHtml(point.stop.name || ''), {
          direction: 'top', offset: [0, -20], opacity: 0.95
        });
        markerEntries.push({ point: point, marker: marker });
      });

      var latlngs = resolved.map(function (p) { return [p.lat, p.lng]; });
      if (resolved.length >= 2) {
        var bounds = window.L.latLngBounds(latlngs);
        map.fitBounds(bounds, {
          padding: [72, 72],
          maxZoom: Number(config.maxZoom || 13)
        });
      } else if (resolved.length === 1) {
        map.setView([resolved[0].lat, resolved[0].lng], 8);
      } else {
        map.setView([20, 0], 2);
      }

      function setPinState(index, current) {
        var entry = markerEntries[index];
        if (!entry) return;
        var markerEl = entry.marker.getElement ? entry.marker.getElement() : null;
        if (!markerEl) return;
        var pin = markerEl.querySelector('.stti-map-pin');
        if (!pin) return;
        pin.classList.add('is-visible');
        if (current) pin.classList.add('is-current');
        else pin.classList.remove('is-current');
      }

      function setTimelineReached(stopOrder) {
        var idx = Number(stopOrder || 0) - 1;
        if (idx >= 0 && timelineItems[idx]) timelineItems[idx].classList.add('is-reached');
      }

      function drawSegment(a, b, duration) {
        var shadow = window.L.polyline([[a.lat, a.lng], [b.lat, b.lng]], {
          color: '#071b4d', weight: 9, opacity: 0.22, lineJoin: 'round', interactive: false
        }).addTo(map);
        var line = window.L.polyline([[a.lat, a.lng], [b.lat, b.lng]], {
          color: '#d4af37', weight: 4, opacity: 0.98, lineJoin: 'round', interactive: false,
          className: 'stti-route-anim-path'
        }).addTo(map);
        if (prefersReducedMotion) return Promise.resolve({ shadow: shadow, line: line });
        return new Promise(function (resolve) {
          window.requestAnimationFrame(function () {
            var path = line.getElement ? line.getElement() : null;
            if (!path || !path.getTotalLength) { resolve({ shadow: shadow, line: line }); return; }
            var length = Math.max(1, path.getTotalLength());
            path.style.transition = 'none';
            path.style.strokeDasharray = length + ' ' + length;
            path.style.strokeDashoffset = String(length);
            path.getBoundingClientRect();
            window.requestAnimationFrame(function () {
              path.style.transition = 'stroke-dashoffset ' + duration + 'ms cubic-bezier(.4,0,.2,1)';
              path.style.strokeDashoffset = '0';
              window.setTimeout(function () {
                path.style.strokeDasharray = 'none';
                path.style.strokeDashoffset = '0';
                resolve({ shadow: shadow, line: line });
              }, duration + 40);
            });
          });
        });
      }

      function waitUntilMapVisible() {
        if (prefersReducedMotion || !('IntersectionObserver' in window)) return Promise.resolve();
        return new Promise(function (resolve) {
          var r = mapEl.getBoundingClientRect();
          if (r.top < window.innerHeight * 0.92 && r.bottom > window.innerHeight * 0.08) { resolve(); return; }
          var observer = new IntersectionObserver(function (entries) {
            if (entries.some(function (entry) { return entry.isIntersecting; })) {
              observer.disconnect(); resolve();
            }
          }, { threshold: 0.18 });
          observer.observe(mapEl);
        });
      }

      function runRouteAnimation() {
        if (!resolved.length) return Promise.resolve();
        if (routeShell) routeShell.classList.add('is-animating');
        updateMapStatus('Rota çiziliyor… 1/' + resolved.length + ' · ' + (resolved[0].stop.name || 'Başlangıç'), '');
        setPinState(0, true);
        setTimelineReached(resolved[0].stop.order);
        if (prefersReducedMotion) {
          for (var k = 1; k < resolved.length; k++) {
            drawSegment(resolved[k - 1], resolved[k], 0);
            setPinState(k - 1, false);
            setPinState(k, true);
            setTimelineReached(resolved[k].stop.order);
          }
          if (routeShell) routeShell.classList.remove('is-animating');
          return Promise.resolve();
        }

        var seq = Promise.resolve();
        resolved.slice(1).forEach(function (point, idx) {
          var previous = resolved[idx];
          var currentIndex = idx + 1;
          seq = seq.then(function () {
            updateMapStatus('Rota çiziliyor… ' + (currentIndex + 1) + '/' + resolved.length + ' · ' + (point.stop.name || 'Durak'), '');
            return drawSegment(previous, point, Number(config.segmentDurationMs || 1050));
          }).then(function () {
            setPinState(currentIndex - 1, false);
            setPinState(currentIndex, true);
            setTimelineReached(point.stop.order);
            return sleep(Number(config.markerRevealMs || 220));
          });
        });
        return seq.then(function () {
          if (routeShell) routeShell.classList.remove('is-animating');
        });
      }

      function setFinalStatus() {
        if (resolved.length === config.stops.length) {
          updateMapStatus(resolved.length + '/' + config.stops.length + ' durak dünya haritasında doğrulandı · rota tamamlandı.', 'ok');
        } else if (resolved.length) {
          updateMapStatus(
            resolved.length + '/' + config.stops.length + ' durak doğrulandı · ' +
            unresolved.length + ' durak inceleme bekliyor. Koordinat uydurulmadı.',
            'warn'
          );
        } else {
          updateMapStatus('Harita konumları doğrulanamadı. Koordinat uydurulmadı; rota çizgisi aşağıda korunuyor.', 'warn');
        }
      }

      window.setTimeout(function () { map.invalidateSize(); }, 120);
      waitUntilMapVisible().then(function () {
        return sleep(Number(config.animationStartDelayMs || 260));
      }).then(runRouteAnimation).then(setFinalStatus);

    });
  }

  function init() {
    markLiveNavSurface();
    collapseEmptyHeaderBands();
    syncHeaderOffset();
    renderGlobalRouteMap();

    window.addEventListener('resize', syncHeaderOffset, { passive: true });
    window.addEventListener('orientationchange', syncHeaderOffset, { passive: true });

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(function () {
        markLiveNavSurface();
        collapseEmptyHeaderBands();
        syncHeaderOffset();
      });
    }

    window.setTimeout(function () {
      markLiveNavSurface();
      collapseEmptyHeaderBands();
      syncHeaderOffset();
    }, 250);
    window.setTimeout(function () {
      markLiveNavSurface();
      collapseEmptyHeaderBands();
      syncHeaderOffset();
    }, 900);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

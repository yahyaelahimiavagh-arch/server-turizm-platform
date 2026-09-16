#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/detail-route-v119.php").read_text(encoding="utf-8")
css = (ROOT / "assets/detail-route-v120.css").read_text(encoding="utf-8")
js = (ROOT / "assets/detail-route-v120.js").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.2 lineage": " * Version: 1.2.2" in plugin and "define('STTI_RELEASE_VERSION', '1.2.2');" in plugin,
    "premium body class": "stti-v120-premium-detail" in php and "stti-customer-preview-mode" in php,
    "v120 assets wired": "detail-route-v120.css" in php and "detail-route-v120.js" in php,
    "accepted shell reused": "assets/customer-preview.js" in php,
    "static canonical map replaced only on detail": "wp_dequeue_script('stti-canonical-map-v080')" in php,
    "canonical-only config": "STTI_V120_DETAIL_MAP" in php and "STTI_V120_DETAIL_MAP" in js,
    "no browser geocoder": "nominatim" not in js.lower() and "fetch(" not in js,
    "progressive segment animation": "strokeDashoffset" in js and "drawSegment" in js,
    "city-by-city marker reveal": "is-current" in js and "is-visible" in js and "markerRevealMs" in js,
    "timeline sync": "stti-cx-route-line article" in js and "is-reached" in js,
    "viewport trigger": "IntersectionObserver" in js,
    "reduced motion": "prefers-reduced-motion" in js and "prefers-reduced-motion" in css,
    "premium map skin": "leaflet-tile-pane" in css and "stti-v120-route-line" in css and "stti-cx-map-shell" in css,
    "premium page polish": "stti-cx-summary-card" in css and "stti-cx-day" in css and "stti-cx-cta" in css,
    "no publication unlock": "indexation'=>false" in php and "sitemap'=>false" in php and "schema'=>false" in php and "canonical'=>false" in php,
}

failed=[name for name,ok in checks.items() if not ok]
for name,ok in checks.items(): print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

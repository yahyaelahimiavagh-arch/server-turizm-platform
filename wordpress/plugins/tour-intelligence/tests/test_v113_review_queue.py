#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/review-queue-v113.php").read_text(encoding="utf-8")
js = (ROOT / "assets/review-queue-v113.js").read_text(encoding="utf-8")
css = (ROOT / "assets/review-queue-v113.css").read_text(encoding="utf-8")

checks = {
    "plugin header v1.1.4": " * Version: 1.1.4" in plugin,
    "release constant v1.1.4": "define('STTI_RELEASE_VERSION', '1.1.4');" in plugin,
    "review module wired": "includes/review-queue-v113.php" in plugin,
    "review screen scoped": "view === 'review'" in php and "page === 'stti-tour-intelligence'" in php,
    "canonical candidates read": "stti_get_candidates()" in php,
    "relation review reused": "stti_v070_relation_review" in php,
    "geo review reused": "stti_v071_geo_review" in php,
    "no review write handler": "admin_post_" not in php,
    "no direct database mutation": all(x not in php.lower() for x in ["$wpdb->insert", "$wpdb->update", "$wpdb->delete"]),
    "public locks stay false": "'publicRoute'=>false" in php and "'indexation'=>false" in php and "'sitemap'=>false" in php,
    "inline inspect action": "İncele" in js and "stti-rq-toggle" in js,
    "explicit edit action": "Tur Bilgilerini Düzenle" in js,
    "edit url normalized before html escape": "function rawUrl(value)" in js and "var editUrl = rawUrl(item.editUrl);" in js,
    "preview url normalized before html escape": "var previewUrl = rawUrl(item.previewUrl);" in js,
    "double escaped query entities normalized": ".replace(/&amp;/gi, '&')" in js and ".replace(/&#0*38;/gi, '&')" in js,
    "no implicit approval": "Onaylama Adımına Geç" in js and "Onay için eksikleri tamamla" in js,
    "approval button does not post": "fetch(" not in js and "admin-post" not in js,
    "blocker details visible": "Onay blockerları" in js,
    "source review card": "Kaynak" in php,
    "route review card": "Rota" in php,
    "geo review card": "Konum / Geo" in php,
    "hotel review card": "Oteller" in php,
    "transport review card": "Ulaşım" in php,
    "date review card": "Tarih" in php,
    "price review card": "Fiyat" in php,
    "responsive review css": "@media(max-width:782px)" in css,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

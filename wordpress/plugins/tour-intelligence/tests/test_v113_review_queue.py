#!/usr/bin/env python3
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/review-queue-v113.php").read_text(encoding="utf-8")
js = (ROOT / "assets/review-queue-v113.js").read_text(encoding="utf-8")
css = (ROOT / "assets/review-queue-v113.css").read_text(encoding="utf-8")

m = re.search(r"define\('STTI_RELEASE_VERSION', '(\d+)\.(\d+)\.(\d+)'\);", plugin)
release = tuple(map(int, m.groups())) if m else (0, 0, 0)

checks = {
    "release preserves v1.1.7 review lineage": release >= (1, 1, 7),
    "review module wired": "includes/review-queue-v113.php" in plugin,
    "approval module wired": "includes/approval-v115.php" in plugin,
    "hub visibility module wired": "includes/hub-visibility-v117.php" in plugin,
    "review screen scoped": "view === 'review'" in php and "page === 'stti-tour-intelligence'" in php,
    "canonical candidates read": "stti_get_candidates()" in php,
    "relation review reused": "stti_v070_relation_review" in php,
    "geo review reused": "stti_v071_geo_review" in php,
    "no review write handler": "admin_post_" not in php,
    "no direct database mutation": all(x not in php.lower() for x in ["$wpdb->insert", "$wpdb->update", "$wpdb->delete"]),
    "public locks stay false": "'publicRoute'=>false" in php and "'indexation'=>false" in php and "'sitemap'=>false" in php,
    "inline inspect action": "İncele" in js and "stti-rq-toggle" in js,
    "explicit edit action": "Tur Bilgilerini Düzenle" in js,
    "edit url normalized before html escape": "var editUrl = rawUrl(item.editUrl);" in js,
    "approval url normalized": "var approvalUrl = rawUrl(item.approvalUrl);" in js,
    "real approval url supplied": "'approvalUrl'=>function_exists('stti_v115_approval_url')" in php,
    "ready button uses approval url": "href=\"' + esc(approvalUrl)" in js,
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

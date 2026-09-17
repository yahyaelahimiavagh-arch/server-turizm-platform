#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/customer-content-v124.php").read_text(encoding="utf-8")
tpl = (ROOT / "includes/detail-route-v124-template.php").read_text(encoding="utf-8")
css = (ROOT / "assets/detail-route-v124.css").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.4": " * Version: 1.2.4" in plugin and "define('STTI_RELEASE_VERSION', '1.2.4');" in plugin,
    "v124 module wired": "includes/customer-content-v124.php" in plugin,
    "old route renderer replaced": "remove_action('template_redirect', 'stti_v119_maybe_render_detail_route', 2)" in php and "stti_v124_maybe_render_detail_route" in php,
    "customer model canonical identity": all(x in php for x in ["short_title","tour_code","short_description"]),
    "customer model destinations": all(x in php for x in ["primary_city","countries","cities","departure_city","return_city"]),
    "customer model complete travel": "stti_v124_raw_hotel_fallbacks" in php and "stti_v124_raw_transport_fallbacks" in php,
    "explicitly rejected hidden": "=== 'rejected'" in php,
    "pending canonical facts allowed": "review_status" in php and "pending" in php,
    "customer model pricing services visa media": all(x in php for x in ["price_items","included","excluded","requirements","gallery"]),
    "overview rendered": "00 · TUR HAKKINDA" in tpl and "GİDİŞ ŞEHRİ" in tpl and "DÖNÜŞ ŞEHRİ" in tpl,
    "route detail rendered": "01 · ROTA" in tpl and "stti-v124-route-details" in tpl,
    "itinerary rendered": "02 · TUR PROGRAMI" in tpl and "Aktiviteler" in tpl and "Öğünler" in tpl,
    "hotels transport rendered": "03 · KONAKLAMA & ULAŞIM" in tpl and "stti-v124-hotel-grid" in tpl and "stti-v124-transport-list" in tpl,
    "all prices rendered": "04 · FİYATLAR" in tpl and "stti-v124-price-grid" in tpl,
    "services visa rendered": "05 · HİZMETLER & VİZE" in tpl and "FİYATA DAHİL" in tpl and "VİZE" in tpl,
    "gallery rendered": "06 · GALERİ" in tpl and "stti-v124-gallery" in tpl,
    "workflow metadata not leaked": all(x not in tpl for x in ["source_ref", "source_completeness", "origin_fixture_id", "reviewed_by", "actor_id"]),
    "seo publication remain private": "publication" not in tpl and "provenance" not in tpl,
    "no seo unlock": all(x not in php for x in ["wp_robots", "wpseo_canonical", "wpseo_json_ld_output", "wp_register_sitemap_provider", "flush_rewrite_rules", "add_rewrite_rule"]),
    "responsive complete surface": "@media(max-width:680px)" in css and "stti-v124-price-grid" in css and "stti-v124-hotel-grid" in css,
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

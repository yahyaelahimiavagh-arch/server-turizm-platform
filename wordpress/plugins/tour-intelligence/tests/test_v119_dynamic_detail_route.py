#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/detail-route-v119.php").read_text(encoding="utf-8")
tpl = (ROOT / "includes/detail-route-v119-template.php").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.1": " * Version: 1.2.1" in plugin and "define('STTI_RELEASE_VERSION', '1.2.1');" in plugin,
    "detail module wired": "includes/detail-route-v119.php" in plugin,
    "visual finish wired": "includes/visual-settings-v121.php" in plugin,
    "accepted renderer reused": "stti_v080_renderer_model" in php and "stti_v080_enqueue_assets" in php,
    "premium customer skin reused": "stti-customer-preview-mode" in php and "v0.6.5 premium Customer Preview" in php,
    "explicit per-tour gate": "publication" in php and "detail_route" in php,
    "separate detail master": "stti_v119_detail_master" in php and "stti_v119_detail_master_enabled" in php,
    "immutable stable-id path": "'/turlar/' . $stable_id . '/'" in php and "stt-\\d{6}" in php,
    "approved readiness required": "editorial_not_approved" in php and "renderer_not_ready" in php,
    "route fail closed": "Tour detail renderer is not ready. Fail closed." in php,
    "hard noindex": "$robots['noindex'] = true" in php and "$robots['noarchive'] = true" in php,
    "canonical disabled": "remove_action('wp_head', 'rel_canonical')" in php and "wpseo_canonical" in php,
    "schema disabled": "wpseo_json_ld_output" in php and "rank_math/json_ld" in php,
    "no sitemap provider": "wp_register_sitemap_provider" not in php,
    "no rewrite rules": "add_rewrite_rule" not in php and "flush_rewrite_rules" not in php,
    "hub CTA upgrades only when route active": "stti_v119_detail_url_if_public" in php and "Turu İncele" in php,
    "route uses customer template": "stti_v119_render_detail_template" in php,
    "premium hero": "stti-cx-hero" in tpl and "SERVER TURİZM · KÜLTÜR TURLARI" in tpl,
    "premium summary cards": "stti-cx-summary-grid" in tpl and "stti-cx-summary-card price" in tpl,
    "canonical customer map": "stti-cx-route-map" in tpl and "CANONICAL ROTA HARİTASI" in tpl,
    "premium route timeline": "stti-cx-route-line" in tpl,
    "premium itinerary accordion": "<details class=\"stti-cx-day\">" in tpl and "Yolculuğun akışı." in tpl,
    "verified hotels transport": "KONAKLAMA" in tpl and "ULAŞIM" in tpl,
    "services and visa": "FİYATA DAHİL" in tpl and "FİYATA DAHİL DEĞİL" in tpl and "VİZE" in tpl,
    "customer CTA": "WhatsApp'tan Bilgi Al" in tpl and "stti-cx-cta" in tpl,
    "no private-preview toolbar": "stti-cx-privatebar" not in tpl,
    "audit events": "detail_route_enabled" in php and "detail_route_disabled" in php,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

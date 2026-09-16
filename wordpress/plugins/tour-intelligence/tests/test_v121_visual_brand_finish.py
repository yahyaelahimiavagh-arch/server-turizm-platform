#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/visual-settings-v121.php").read_text(encoding="utf-8")
compat = (ROOT / "includes/hub-render-compat-v118.php").read_text(encoding="utf-8")
detail_css = (ROOT / "assets/detail-route-v121.css").read_text(encoding="utf-8")
hub_css = (ROOT / "assets/tour-hub-v121.css").read_text(encoding="utf-8")
admin_js = (ROOT / "assets/visual-settings-v121.js").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.2": " * Version: 1.2.2" in plugin and "define('STTI_RELEASE_VERSION', '1.2.2');" in plugin,
    "visual module wired": "includes/visual-settings-v121.php" in plugin,
    "dedicated visual admin lineage": "Görsel Ayarları" in php and "stti-visual-settings-v121" in php,
    "wordpress media picker": "wp_enqueue_media()" in php and "wp.media" in admin_js,
    "tour image fields lineage": "hero_image_url" in php and "cover_image_url" in php,
    "presentation controls lineage": all(x in php for x in ["hero_overlay","hero_focal_x","hero_focal_y","hero_height"]),
    "global visual fallbacks": all(x in php for x in ["hub_hero_image","default_hero_image","default_card_image"]),
    "hub cover prefers explicit cover": "stti_v121_effective_card" in php and "cover_image_url" in php,
    "hub renderer delegates v121": "stti_v121_hub_html" in compat,
    "porto gap cleanup detail": "main-content" in detail_css and "padding-top:0!important" in detail_css,
    "porto gap cleanup hub": "stti-v121-hub-page" in hub_css and "padding-top:0!important" in hub_css,
    "brand hero photographic": "--stti-v121-hero" in detail_css and "background-size:cover" in detail_css,
    "hub hero configurable": "--stti-v121-hub-hero" in hub_css,
    "navy gold language": "#071b4d" in detail_css and "#d4af37" in detail_css and "#071b4d" in hub_css,
    "mobile polish": "@media(max-width:680px)" in detail_css and "@media(max-width:680px)" in hub_css,
    "no publication field mutation": "['publication']" not in php and "['editorial']" not in php and "['lifecycle']" not in php,
    "no seo unlock": all(x not in php for x in ["wp_robots","wpseo_canonical","wpseo_json_ld_output","wp_register_sitemap_provider","flush_rewrite_rules"]),
    "audit visual changes": "visual_presentation_updated" in php,
}

failed=[name for name,ok in checks.items() if not ok]
for name,ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

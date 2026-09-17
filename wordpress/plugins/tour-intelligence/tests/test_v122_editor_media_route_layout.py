#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/brand-finish-v122.php").read_text(encoding="utf-8")
css = (ROOT / "assets/detail-route-v122.css").read_text(encoding="utf-8")
js = (ROOT / "assets/detail-route-v122.js").read_text(encoding="utf-8")
admin_js = (ROOT / "assets/operator-media-v122.js").read_text(encoding="utf-8")
admin_css = (ROOT / "assets/operator-media-v122.css").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.3 lineage": " * Version: 1.2.3" in plugin and "define('STTI_RELEASE_VERSION', '1.2.3');" in plugin,
    "v122 module wired": "includes/brand-finish-v122.php" in plugin,
    "editor media assets": "operator-media-v122.js" in php and "operator-media-v122.css" in php and "wp_enqueue_media()" in php,
    "editor media pane": "data-pane=\"10\"" in admin_js and "Bu tura özel görseller" in admin_js,
    "wordpress media picker": "wp.media" in admin_js and "Medya Kütüphanesi" in admin_js,
    "ajax save guarded": "wp_ajax_stti_v122_save_editor_visuals" in php and "check_ajax_referer" in php and "current_user_can('manage_options')" in php,
    "media payload write only": "$payload['media']['hero_image_url']" in php and "$payload['media']['cover_image_url']" in php,
    "presentation payload write only": all(x in php for x in ["hero_overlay","hero_focal_x","hero_focal_y","hero_height"]),
    "no publication mutations": all(x not in php for x in ["$payload['publication']", "$payload['editorial']", "$payload['lifecycle']"]),
    "checksum updated": "hash('sha256', $json)" in php and "updated_at" in php and "visual_presentation_updated" in php,
    "full bleed restored": "margin-left:calc(50% - 50vw)!important" in css and "width:100vw!important" in css,
    "dynamic route count": "--stti-v122-stop-count" in css and "--stti-v122-stop-count" in js and "data-stop-count" in js,
    "route centered": "margin:32px auto 0!important" in css and "justify-content:center!important" in css,
    "actual stop max width": "count*220" in js and "count<=5" in js,
    "outbound return labels": "GİDİŞ" in js and "DÖNÜŞ" in js and "stti-v122-direction" in css,
    "canonical endpoint fields": "departure_city" in php and "return_city" in php and "departureCity" in js and "returnCity" in js,
    "destination editor helper": "Departure City" in admin_js and "Return City" in admin_js,
    "global visual defaults only runtime": "cfg.mode==='defaults'" in admin_js and "$panels.slice(1).remove()" in admin_js,
    "responsive admin": "@media(max-width:782px)" in admin_css,
    "no seo unlock": all(x not in php for x in ["wp_robots", "wpseo_canonical", "wpseo_json_ld_output", "wp_register_sitemap_provider", "flush_rewrite_rules", "add_rewrite_rule"]),
}

failed=[name for name,ok in checks.items() if not ok]
for name,ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)
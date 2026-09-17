#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/persistence-v123.php").read_text(encoding="utf-8")
hub_css = (ROOT / "assets/hub-layout-v123.css").read_text(encoding="utf-8")
editor_js = (ROOT / "assets/operator-persistence-v123.js").read_text(encoding="utf-8")
editor_css = (ROOT / "assets/operator-persistence-v123.css").read_text(encoding="utf-8")

checks = {
    "plugin v1.2.4": " * Version: 1.2.4" in plugin and "define('STTI_RELEASE_VERSION', '1.2.4');" in plugin,
    "v123 persistence wired": "includes/persistence-v123.php" in plugin,
    "legacy save handler replaced": "remove_action('admin_post_stti_save_candidate', 'stti_handle_save_candidate')" in php and "stti_v123_handle_save_candidate" in php,
    "media and publication protected from legacy rebuild": "unset($fresh['media'], $fresh['publication'])" in php,
    "recursive extension preservation": "stti_v123_merge_payload_value" in php and "$merged = $existing" in php,
    "ordered editor collections replace cleanly": "stti_v123_array_is_list_compat" in php and "return $fresh" in php,
    "main save accepts tour visuals": all(x in php for x in ["stti_v123_hero_image_url","stti_v123_cover_image_url","stti_v123_hero_overlay","stti_v123_hero_focal_x","stti_v123_hero_focal_y","stti_v123_hero_height"]),
    "media controls submitted with main form": "setName('stti-v122-hero','stti_v123_hero_image_url')" in editor_js and "setName('stti-v122-cover','stti_v123_cover_image_url')" in editor_js,
    "simple description bound canonical": "Kısa Açıklama" in editor_js and "[name=\"short_description\"]" in editor_js,
    "description styled": "stti-v123-description-field textarea" in editor_css,
    "hub viewport full bleed": "width:100vw!important" in hub_css and "margin-left:calc(50% - 50vw)!important" in hub_css and "margin-right:calc(50% - 50vw)!important" in hub_css,
    "hub inherited padding removed": "padding-left:0!important" in hub_css and "padding-right:0!important" in hub_css,
    "hub hero no boxed radius": "border-radius:0!important" in hub_css,
    "checksum and audit remain": "hash('sha256', $json)" in php and "candidate_updated_lossless" in php,
    "no SEO or route unlock": all(x not in php for x in ["wp_robots","wpseo_canonical","wpseo_json_ld_output","wp_register_sitemap_provider","flush_rewrite_rules","add_rewrite_rule"]),
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)
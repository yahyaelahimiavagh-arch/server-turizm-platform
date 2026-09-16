#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/hub-render-compat-v118.php").read_text(encoding="utf-8")

checks = {
    "plugin v1.1.8": " * Version: 1.1.8" in plugin and "define('STTI_RELEASE_VERSION', '1.1.8');" in plugin,
    "compat module wired after visibility": plugin.index("includes/hub-visibility-v117.php") < plugin.index("includes/hub-render-compat-v118.php"),
    "exact Hub request gate preserved": "stti_v110_is_hub_request()" in php and "stti_v110_hub_enabled()" in php,
    "explicit visibility records preserved": "stti_v117_hub_records()" in php,
    "legacy fragile filters retired at runtime": "remove_filter('the_content', 'stti_v110_replace_hub_content', 99)" in php and "remove_filter('the_content', 'stti_v117_replace_hub_content', 100)" in php,
    "queried page primed before builder render": "add_action('wp', 'stti_v118_prime_queried_page_content', 99)" in php and "post_content = $html" in php,
    "main query post object primed": "$wp_query->post" in php and "$wp_query->posts" in php,
    "late content fallback": "add_filter('the_content', 'stti_v118_force_queried_page_content', 9999)" in php,
    "get content fallback": "add_filter('get_the_content', 'stti_v118_force_queried_page_content', 9999)" in php,
    "secondary content protected by queried ID": "$current_id !== $queried_id" in php,
    "no route takeover": "template_redirect" not in php and "add_rewrite_rule" not in php and "flush_rewrite_rules" not in php,
    "no SEO ownership": all(x not in php for x in ["wp_robots", "rel_canonical", "wpseo_canonical", "wp_register_sitemap_provider"]),
    "no publication mutation": "$wpdb" not in php and "update_option" not in php,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

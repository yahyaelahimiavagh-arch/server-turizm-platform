#!/usr/bin/env python3
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / 'server-turizm-tour-intelligence.php').read_text(encoding='utf-8')
hub = (ROOT / 'includes/tour-hub-v110.php').read_text(encoding='utf-8')
css = (ROOT / 'assets/tour-hub-v110.css').read_text(encoding='utf-8')


def version_tuple(value):
    match = re.fullmatch(r'(\d+)\.(\d+)\.(\d+)', value or '')
    return tuple(map(int, match.groups())) if match else None


header_match = re.search(r'^ \* Version: ([0-9]+\.[0-9]+\.[0-9]+)$', plugin, re.MULTILINE)
release_match = re.search(r"define\('STTI_RELEASE_VERSION', '([0-9]+\.[0-9]+\.[0-9]+)'\);", plugin)
header_version = version_tuple(header_match.group(1) if header_match else '')
release_version = version_tuple(release_match.group(1) if release_match else '')
release_at_least_v110 = (
    header_version is not None
    and release_version is not None
    and header_version == release_version
    and release_version >= (1, 1, 0)
)

checks = {
    'release is at least 1.1.0': release_at_least_v110,
    'v1.0 baseline marker preserved': "STTI_V100_ACCEPTED_RELEASE', '1.0.0'" in plugin,
    'hub module wired': "includes/tour-hub-v110.php" in plugin,
    'exact existing hub path': "'path'=>'/kultur-turlari/'" in hub,
    'hub master default off': "get_option($cfg['master_option'],'0')==='1'" in hub,
    'content replacement not route takeover': "add_filter('the_content','stti_v110_replace_hub_content',99)" in hub and 'template_redirect' not in hub,
    'no rewrite generation': 'add_rewrite_rule' not in hub and 'flush_rewrite_rules' not in hub,
    'no seo ownership': all(x not in hub for x in ['wp_robots','rel_canonical','wpseo_canonical','rank_math/frontend/canonical','wp_register_sitemap_provider']),
    'canonical source direct read': 'stti_get_candidates()' in hub,
    'editorial approval required': "($row['editorial']??'')!=='approved'" in hub,
    'past tours excluded': "($row['temporal']??'')==='past'" in hub and "$end<$today" in hub,
    'future dated first': "if ($a['dated']!==$b['dated']) return $a['dated']?-1:1;" in hub,
    'existing single-tour public gate respected': 'stti_v100_surface_state' in hub and "if (!empty($surface['route']))" in hub,
    'contact fallback present': "home_url('/iletisim/')" in hub,
    'empty state present': 'Yeni turlar hazırlanıyor' in hub,
    'admin capability guarded': "current_user_can('manage_options')" in hub and "check_admin_referer('stti_v110_tour_hub')" in hub,
    'css responsive grid': 'grid-template-columns:repeat(3' in css and '@media(max-width:640px)' in css,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

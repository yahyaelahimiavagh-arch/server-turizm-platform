#!/usr/bin/env python3
import re
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
cfg = (ROOT / "includes/public-pilot-v100-config.php").read_text(encoding="utf-8")
ready = (ROOT / "includes/public-pilot-v100-readiness.php").read_text(encoding="utf-8")
control = (ROOT / "includes/public-pilot-v100-control.php").read_text(encoding="utf-8")
route = (ROOT / "includes/public-pilot-v100-route.php").read_text(encoding="utf-8")
seo = (ROOT / "includes/public-pilot-v100-seo.php").read_text(encoding="utf-8")
admin = (ROOT / "includes/public-pilot-v100-admin.php").read_text(encoding="utf-8")
template = (ROOT / "includes/public-pilot-v100-template.php").read_text(encoding="utf-8")
loader = (ROOT / "includes/public-pilot-v100.php").read_text(encoding="utf-8")


def version_tuple(value):
    match = re.fullmatch(r"(\d+)\.(\d+)\.(\d+)", value or "")
    return tuple(map(int, match.groups())) if match else None


header_match = re.search(r"^ \* Version: ([0-9]+\.[0-9]+\.[0-9]+)$", plugin, re.MULTILINE)
release_match = re.search(r"define\('STTI_RELEASE_VERSION', '([0-9]+\.[0-9]+\.[0-9]+)'\);", plugin)
header_version = version_tuple(header_match.group(1) if header_match else "")
release_version = version_tuple(release_match.group(1) if release_match else "")
release_at_least_v110 = (
    header_version is not None
    and release_version is not None
    and header_version == release_version
    and release_version >= (1, 1, 0)
)

checks = {
    "v1.0 accepted release marker preserved": "define('STTI_V100_ACCEPTED_RELEASE', '1.0.0');" in plugin,
    "current release is at least v1.1": release_at_least_v110,
    "public loader active": "includes/public-pilot-v100.php" in plugin,
    "exact stable-id allowlist": "'stable_id'=>'STT-000001'" in cfg,
    "exact path allowlist": "'path'=>'/turlar/buyuk-iran-kultur-turu/'" in cfg,
    "public master default off": "get_option((string)$name,'0')==='1'" in cfg,
    "six separate options": all(x in cfg for x in ["stti_v100_public_master","stti_v100_public_route","stti_v100_indexation","stti_v100_sitemap","stti_v100_schema","stti_v100_canonical"]),
    "editorial approval required": "editorial_not_approved" in ready,
    "renderer readiness required": "renderer_not_ready" in ready,
    "fast rollback collapse": "if(!$next['master'])" in control and "array('route','indexation','sitemap','schema','canonical')" in control,
    "release audit event": "v100_public_pilot_gates_updated" in control,
    "no rewrite generation": "add_rewrite_rule" not in route and "flush_rewrite_rules" not in route,
    "exact request matcher": "hash_equals(stti_v100_public_config()['path'],$candidate)" in route,
    "robots gate": "stti_v100_robots_filter" in seo and "noindex" in seo,
    "canonical gate": "stti_v100_print_canonical" in seo,
    "schema gate": "stti_v100_print_schema" in seo,
    "sitemap provider": "WP_Sitemaps_Provider" in seo and "wp_register_sitemap_provider" in seo,
    "third-party seo suppressed": all(x in seo for x in ["wpseo_canonical","wpseo_json_ld_output","rank_math/frontend/canonical","rank_math/json_ld"]),
    "admin capability": "current_user_can('manage_options')" in admin,
    "admin nonce": "check_admin_referer('stti_v100_public_pilot')" in admin,
    "all modules wired": all(x in loader for x in ["public-pilot-v100-config.php","public-pilot-v100-readiness.php","public-pilot-v100-control.php","public-pilot-v100-seo.php","public-pilot-v100-admin.php","public-pilot-v100-template.php","public-pilot-v100-route.php"]),
    "public template has no private banner": "ÖZEL ÖNİZLEME" not in template and "NO PUBLIC ROUTE" not in template,
    "empty itinerary hidden": "stti_v100_public_itinerary_has_content" in template,
}
failed=[name for name,passed in checks.items() if not passed]
for name,passed in checks.items(): print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

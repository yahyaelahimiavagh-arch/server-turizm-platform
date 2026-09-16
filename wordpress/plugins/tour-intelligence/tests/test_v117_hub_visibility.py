#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/hub-visibility-v117.php").read_text(encoding="utf-8")

checks = {
    "plugin v1.1.7": " * Version: 1.1.7" in plugin and "define('STTI_RELEASE_VERSION', '1.1.7');" in plugin,
    "module loaded": "includes/hub-visibility-v117.php" in plugin,
    "explicit hub flag": "publication" in php and "hub_visible" in php,
    "approved alone not enough": "if (!stti_v117_hub_visible($payload)) continue;" in php,
    "base editorial eligibility preserved": "stti_v110_record_is_eligible($row, $payload" in php,
    "public render uses gated records": "stti_v110_render_hub(stti_v117_hub_records())" in php,
    "late content filter overrides legacy eligible set": "add_filter('the_content', 'stti_v117_replace_hub_content', 100)" in php,
    "capability guard": "current_user_can('manage_options')" in php,
    "nonce guard": "check_admin_referer('stti_v117_hub_visibility_'" in php,
    "enable fails closed when not eligible": "Tour is not editorially eligible for Hub visibility." in php,
    "only payload visibility updated": "$payload['publication']['hub_visible'] = $target" in php,
    "no hub master write": "update_option" not in php,
    "audit evidence": "hub_visibility_enabled" in php and "hub_visibility_disabled" in php,
    "operator panel": "Hub’da Göster" in php and "Hub’dan Gizle" in php,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

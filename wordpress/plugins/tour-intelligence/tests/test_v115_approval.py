#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
php = (ROOT / "includes/approval-v115.php").read_text(encoding="utf-8")
css = (ROOT / "assets/approval-v115.css").read_text(encoding="utf-8")

checks = {
    "approval module loaded": "includes/approval-v115.php" in plugin,
    "approval version": " * Version: 1.1.5" in plugin,
    "hidden approval page": "stti-tour-approval" in php and "remove_submenu_page" in php,
    "capability guard": "current_user_can('manage_options')" in php,
    "nonce guard": "check_admin_referer('stti_v115_approve_candidate_'" in php,
    "explicit confirmation required": "confirm_editorial" in php and "required" in php,
    "server readiness recheck": "stti_v113_review_queue_summary($row)" in php and "approvalReady" in php,
    "only needs review may approve": "!== 'needs_review'" in php,
    "approved is idempotent": "=== 'approved'" in php and "approved', 'already'" in php,
    "editorial transition": "$payload['lifecycle']['editorial'] = 'approved'" in php,
    "row editorial transition": "array('editorial'=>'approved','payload'=>$json,'checksum'=>$checksum,'updated_at'=>$now)" in php,
    "publication preserved": "Publication state mutation detected. Fail closed." in php and "$publication_after !== $publication_before" in php,
    "no hub option mutation": "update_option" not in php,
    "no publish post mutation": "wp_update_post" not in php and "wp_publish_post" not in php,
    "audit event": "candidate_approved" in php,
    "public state visible": "Public route" in php and "Indexation" in php and "Sitemap" in php and "Hub Master" in php,
    "responsive approval css": "@media(max-width:782px)" in css,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

#!/usr/bin/env python3
"""Historical v0.3.1 private-candidate invariants preserved by later patches."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def read(path):
    return (ROOT / path).read_text(encoding="utf-8")


main = read("server-turizm-program-intelligence.php")
plugin = read("includes/class-stpi-plugin.php")
admin = read("includes/class-stpi-admin.php")
importer = read("includes/class-stpi-importer.php")
store = read("includes/class-stpi-store.php")
all_php = "\n".join(path.read_text(encoding="utf-8") for path in ROOT.rglob("*.php"))

required = {
        "private program CPT": "'public'              => false" in plugin and "'show_in_rest'        => false" in plugin,
    "staged import": "stpi_stage_import" in importer and "stpi_commit_import" in importer,
    "human confirmation": "confirm_review" in importer and "check_admin_referer" in importer,
    "stable allocator lock": "GET_LOCK" in store and "STP-" in store and "RELEASE_LOCK" in store,
    "idempotency": "_stpi_source_payload_hash" in store and "UNCHANGED" in store and "CONFLICT" in store,
    "archive snapshot": "_stpi_archive_snapshots" in store and "hotel_facts" in store,
    "audit log": "STPI_Audit::log" in store and "stpi-audit" in admin,
    "no public renderer": "register_rest_route" not in all_php and "add_shortcode" not in all_php and "template_include" not in all_php,
    "no delete path": "wp_delete_post" not in all_php and "wp_trash_post" not in all_php,
}

failed = [name for name, passed in required.items() if not passed]
for name, passed in required.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

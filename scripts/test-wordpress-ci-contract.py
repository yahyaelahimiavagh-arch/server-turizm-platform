#!/usr/bin/env python3
"""Static guardrails for the WordPress runtime/package GitHub workflow."""

from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
workflow = (ROOT / ".github/workflows/wordpress-runtime-and-package.yml").read_text(encoding="utf-8")
runtime = (ROOT / "wordpress/plugins/tour-intelligence/tests/wp_runtime_smoke.php").read_text(encoding="utf-8")

checks = {
    "mariadb service": "image: mariadb:11" in workflow,
    "latest disposable wordpress": "core download" in workflow and "--version=latest" in workflow,
    "plugin activation": "plugin activate" in workflow,
    "wp runtime smoke": "wp_runtime_smoke.php" in workflow,
    "replacement folder identity": "server-turizm-tour-intelligence-v0.1.0-t2-admin-preview" in workflow,
    "verified zip": "unzip -t" in workflow and "sha256sum" in workflow,
    "artifact upload": "actions/upload-artifact@v4" in workflow,
    "completion positive": "Valid completion reaches REVIEW" in runtime,
    "hash fail closed": "Tampered source hash is invalid" in runtime,
    "duration fail closed": "Wrong deterministic duration is invalid" in runtime,
    "publication fail closed": "Public/indexable request is invalid" in runtime,
    "legacy importer regression": "Accepted PARTIAL import still proposes CREATE" in runtime,
    "database no-write assertion": "All dry runs preserve database and sequence" in runtime,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")

raise SystemExit(1 if failed else 0)

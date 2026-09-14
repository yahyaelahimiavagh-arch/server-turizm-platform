#!/usr/bin/env python3
"""Static guardrails for the WordPress runtime/package GitHub workflows."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
workflow = (ROOT / ".github/workflows/wordpress-runtime-and-package.yml").read_text(encoding="utf-8")
v100_workflow = (ROOT / ".github/workflows/stti-v100-public-pilot.yml").read_text(encoding="utf-8")
runtime = (ROOT / "wordpress/plugins/tour-intelligence/tests/wp_runtime_smoke.php").read_text(encoding="utf-8")
geo = (ROOT / "wordpress/plugins/tour-intelligence/includes/geo-resolver.php").read_text(encoding="utf-8")
canonical_map = (ROOT / "wordpress/plugins/tour-intelligence/assets/canonical-geo-map.js").read_text(encoding="utf-8")
renderer = (ROOT / "wordpress/plugins/tour-intelligence/includes/customer-renderer-v080.php").read_text(encoding="utf-8")
shell = (ROOT / "wordpress/plugins/tour-intelligence/assets/customer-shell-v080.js").read_text(encoding="utf-8")
v090_runtime = (ROOT / "wordpress/plugins/tour-intelligence/tests/wp_runtime_real_full_tour_v090.php").read_text(encoding="utf-8")
v100_runtime = (ROOT / "wordpress/plugins/tour-intelligence/tests/wp_runtime_public_pilot_v100.php").read_text(encoding="utf-8")
v100_cfg = (ROOT / "wordpress/plugins/tour-intelligence/includes/public-pilot-v100-config.php").read_text(encoding="utf-8")
v100_route = (ROOT / "wordpress/plugins/tour-intelligence/includes/public-pilot-v100-route.php").read_text(encoding="utf-8")

checks = {
    "mariadb service": "image: mariadb:11" in workflow,
    "latest disposable wordpress": "core download" in workflow and "--version=latest" in workflow,
    "plugin activation": "plugin activate" in workflow,
    "wp runtime smoke": "wp_runtime_smoke.php" in workflow,
    "v080 runtime chained": "wp_runtime_customer_renderer_v080.php" in workflow,
    "v090 real tour runtime chained": "wp_runtime_real_full_tour_v090.php" in workflow,
    "replacement folder identity": "server-turizm-tour-intelligence-v0.1.0-t2-admin-preview" in workflow,
    "verified zip": "unzip -t" in workflow and "sha256sum" in workflow,
    "artifact upload": "actions/upload-artifact@v4" in workflow,
    "completion positive": "Valid completion reaches REVIEW" in runtime,
    "hash fail closed": "Tampered source hash is invalid" in runtime,
    "duration fail closed": "Wrong deterministic duration is invalid" in runtime,
    "publication fail closed": "Public/indexable request is invalid" in runtime,
    "legacy importer regression": "Accepted PARTIAL import still proposes CREATE" in runtime,
    "database no-write assertion": "All dry runs preserve database and sequence" in runtime,
    "v071 relation regression chained": "wp_runtime_v071_regressions.php" in runtime,
    "v071 geo runtime chained": "wp_runtime_geo_resolver.php" in runtime,
    "canonical geo config exposes no geocoder/cache keys": "'geocoder' =>" not in geo and "cacheNamespace" not in geo,
    "canonical map performs no browser geocoding/cache": "fetch(" not in canonical_map and "localStorage" not in canonical_map,
    "v080 renderer is private no-write": "'public'=>false" in renderer and "'indexable'=>false" in renderer and "'writes'=>0" in renderer,
    "v080 shell performs no browser geocoding/cache": "fetch(" not in shell and "localStorage" not in shell,
    "v090 runtime requires exact real stable id": "STT-000001" in v090_runtime,
    "v090 runtime proves cleanup": "cleans up tables and Stable-ID sequence" in v090_runtime,
    "v090 runtime preserves public locks": "all public and SEO exposure stays OFF" in v090_runtime,
    "v100 dedicated runtime workflow": "wp_runtime_public_pilot_v100.php" in v100_workflow and "image: mariadb:11" in v100_workflow and "--version=latest" in v100_workflow,
    "v100 exact single route": "STT-000001" in v100_cfg and "/turlar/buyuk-iran-kultur-turu/" in v100_cfg,
    "v100 master defaults off": "get_option((string)$name,'0')==='1'" in v100_cfg,
    "v100 no rewrite generation": "add_rewrite_rule" not in v100_route and "flush_rewrite_rules" not in v100_route,
    "v100 runtime proves fast rollback": "Public Master OFF collapses every child gate immediately" in v100_runtime,
    "v100 runtime restores release state": "Release option restored:" in v100_runtime,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")

raise SystemExit(1 if failed else 0)

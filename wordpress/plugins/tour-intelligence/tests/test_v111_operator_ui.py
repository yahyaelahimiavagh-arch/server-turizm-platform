#!/usr/bin/env python3
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
plugin = (ROOT / "server-turizm-tour-intelligence.php").read_text(encoding="utf-8")
loader = (ROOT / "includes/operator-editor-v111.php").read_text(encoding="utf-8")
js = (ROOT / "assets/operator-editor-v111.js").read_text(encoding="utf-8")
css = (ROOT / "assets/operator-editor-v111.css").read_text(encoding="utf-8")

header_match = re.search(r"^ \* Version: (\d+)\.(\d+)\.(\d+)$", plugin, re.MULTILINE)
release_match = re.search(r"define\('STTI_RELEASE_VERSION', '(\d+)\.(\d+)\.(\d+)'\);", plugin)
header_version = tuple(map(int, header_match.groups())) if header_match else (0, 0, 0)
release_version = tuple(map(int, release_match.groups())) if release_match else (0, 0, 0)

checks = {
    "plugin release at least v1.1.1": header_version >= (1, 1, 1),
    "release constant matches header": release_version == header_version,
    "operator module wired": "includes/operator-editor-v111.php" in plugin,
    "operator screen scoped to editor": "view === 'editor'" in loader and "page === 'stti-tour-intelligence'" in loader,
    "operator css scoped": "operator-editor-v111.css" in loader,
    "operator js scoped": "operator-editor-v111.js" in loader,
    "simple editor title field": "stti-op-title" in js and "public_title" in js,
    "simple editor destination field": "stti-op-country" in js and "primary_country" in js,
    "simple editor date fields": all(x in js for x in ["stti-op-start", "stti-op-end", "start_date", "end_date"]),
    "simple editor review state": "stti-op-editorial" in js and "editorial" in js,
    "simple editor availability": "stti-op-availability" in js and "availability" in js,
    "stable id never editable": "stti-op-stable" in js and "stable_id" in js and "setCanonicalValue(form, 'stable_id'" not in js,
    "canonical form remains save owner": "form.requestSubmit" in js and "stti_save_candidate" not in js,
    "advanced editor preserved": "Gelişmiş Alanlar" in js and "stti-editor-layout" in css,
    "partial source approval confirmation": "Kaynak kısmi" in js and "window.confirm" in js,
    "no public master control": "stti_v100_public_master" not in js and "hub_master" not in js,
    "no indexation control": "indexation" not in js.lower() and "sitemap" not in js.lower(),
    "no second storage path": "wpdb" not in loader.lower() and "insert" not in loader.lower(),
    "simple mode hides legacy complexity by default": "body.stti-operator-mode .stti-editor-layout" in css,
    "advanced mode restores legacy editor": "stti-operator-advanced .stti-editor-layout" in css,
    "simple mode hides canonical geo technical panel": ".stti-v071-geo-panel" in css,
    "simple mode hides release controls": ".stti-top-actions" in css and ".stti-locks" in css,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

#!/usr/bin/env python3
"""Static admin-semantics regression for v0.3.1."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
admin = (ROOT / "includes/class-stpi-admin.php").read_text(encoding="utf-8")
lifecycle = (ROOT / "includes/class-stpi-lifecycle.php").read_text(encoding="utf-8")

checks = {
    "pure temporal function exists": "public static function temporal_state" in lifecycle,
    "effective state remains composite": "'sold_out' === ( $workflow['availability'] ?? '' )" in lifecycle,
    "review uses pure temporal": "<h2>Temporal state</h2><strong><?php echo esc_html( STPI_Lifecycle::temporal_state( $program ) ); ?>" in admin,
    "review exposes effective context": "Effective state: <code><?php echo esc_html( STPI_Lifecycle::effective_state( $program ) ); ?>" in admin,
    "program table separates columns": "<th>Temporal</th><th>Effective state</th>" in admin,
    "table pure temporal cell": "<td><strong><?php echo esc_html( STPI_Lifecycle::temporal_state( $program ) ); ?></strong></td>" in admin,
    "table effective cell": "<td><code><?php echo esc_html( STPI_Lifecycle::effective_state( $program ) ); ?></code></td>" in admin,
    "table empty colspan updated": 'colspan="9"' in admin,
}

failed = [name for name, passed in checks.items() if not passed]
for name, passed in checks.items():
    print(f"{name}: {'PASS' if passed else 'FAIL'}")
raise SystemExit(1 if failed else 0)

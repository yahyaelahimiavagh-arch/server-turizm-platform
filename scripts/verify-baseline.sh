#!/usr/bin/env bash
set -euo pipefail
repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)";cd "$repo_root"
required=("docs/MASTER-PLAN.md" "docs/CURRENT-RUNTIME-INVENTORY.md" "wordpress/plugins/hotel-intelligence/server-turizm-hotel-intelligence.php" "wordpress/plugins/program-intelligence/server-turizm-program-intelligence.php" "wordpress/plugins/program-publishing-integration/server-turizm-program-publishing-integration.php" "wordpress/plugins/tour-intelligence/server-turizm-tour-intelligence.php" "wordpress/plugins/tour-intelligence/includes/tour-hub-v110.php" "wordpress/plugins/tour-intelligence/includes/operator-editor-v111.php" "wordpress/plugins/tour-intelligence/assets/tour-hub-v110.css" "wordpress/plugins/tour-intelligence/assets/operator-editor-v111.css" "wordpress/plugins/tour-intelligence/assets/operator-editor-v111.js" "wordpress/plugins/direct-sync-foundation/server-turizm-direct-sync.php" "integrations/google-sheets/shared/ST-Direct-Sync.gs" "integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs" "integrations/google-sheets/umrah/ST_TDE_Exporter.gs" "integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs" "integrations/google-sheets/umrah/ST-Umrah-Config.gs" "integrations/google-sheets/umrah/ST-Umrah-Exporter.gs" "integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs" "integrations/google-sheets/umrah/ST-Umrah-Menu.gs" "integrations/google-sheets/tours/ST-Tour-Generator.gs" "integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs" "integrations/google-sheets/tours/ST-Tour-Menu.gs" "wordpress/server-config/.htaccess")
for path in "${required[@]}";do [[ -f "$path" ]]||{ printf 'MISSING: %s\n' "$path" >&2;exit 1;};done
credential_pattern='AIza[0-9A-Za-z_-]{30,}|gh[pousr]_[0-9A-Za-z]{30,}|github_pat_[0-9A-Za-z_]{30,}|[0-9]{8,10}:[A-Za-z0-9_-]{30,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----|sk-[A-Za-z0-9_-]{20,}'
if grep -RIEq --exclude-dir=.git --exclude='verify-baseline.sh' "$credential_pattern" .;then printf 'Credential-shaped value detected; inspect before commit.\n' >&2;exit 1;fi
if command -v php >/dev/null 2>&1;then while IFS= read -r -d '' file;do php -l "$file" >/dev/null;done < <(find wordpress -type f -name '*.php' -print0);else printf 'NOTICE: php is unavailable; PHP syntax check skipped.\n';fi
python3 wordpress/plugins/program-intelligence/tests/test_v031_invariants.py
python3 wordpress/plugins/program-intelligence/tests/test_v031_temporal_semantics.py
python3 wordpress/plugins/program-intelligence/tests/validate_examples.py
python3 wordpress/plugins/tour-intelligence/tests/test_v065_ai_completion.py
python3 wordpress/plugins/tour-intelligence/tests/test_v080_static_contract.py
python3 wordpress/plugins/tour-intelligence/tests/test_v090_real_tour_fixture.py
python3 wordpress/plugins/tour-intelligence/tests/test_v100_public_pilot.py
python3 wordpress/plugins/tour-intelligence/tests/test_v110_tour_hub.py
python3 wordpress/plugins/tour-intelligence/tests/test_v111_operator_ui.py
python3 wordpress/plugins/direct-sync-foundation/tests/test_direct_sync_static.py
python3 wordpress/plugins/direct-sync-foundation/tests/test_transport_retry_static.py
python3 scripts/test-separated-apps-script-stacks.py
php wordpress/plugins/tour-intelligence/tests/test_v070_review_relations.php
php wordpress/plugins/tour-intelligence/tests/test_v071_geo_resolver.php
node --check wordpress/plugins/tour-intelligence/assets/review-relations.js
node --check wordpress/plugins/tour-intelligence/assets/geo-resolver.js
node --check wordpress/plugins/tour-intelligence/assets/canonical-geo-map.js
node --check wordpress/plugins/tour-intelligence/assets/customer-shell-v080.js
node --check wordpress/plugins/tour-intelligence/assets/operator-editor-v111.js
node --check < integrations/google-sheets/shared/ST-Direct-Sync.gs
node --check < integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs
node --check < integrations/google-sheets/umrah/ST-Umrah-Config.gs
node --check < integrations/google-sheets/umrah/ST-Umrah-Exporter.gs
node --check < integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs
node --check < integrations/google-sheets/umrah/ST-Umrah-Menu.gs
node --check < integrations/google-sheets/tours/ST-Tour-Generator.gs
node --check < integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs
node --check < integrations/google-sheets/tours/ST-Tour-Menu.gs
python3 scripts/test-wordpress-ci-contract.py
python3 tests/test_current_program_intelligence.py
node wordpress/plugins/program-intelligence/tests/test_exporter.js
node --check < integrations/google-sheets/umrah/ST_TDE_Exporter.gs
node --check < integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs
printf 'Baseline verification PASS\n'

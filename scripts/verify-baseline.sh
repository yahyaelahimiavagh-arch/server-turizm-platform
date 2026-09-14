#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

required=(
  "docs/MASTER-PLAN.md"
  "docs/CURRENT-RUNTIME-INVENTORY.md"
  "wordpress/plugins/hotel-intelligence/server-turizm-hotel-intelligence.php"
  "wordpress/plugins/program-intelligence/server-turizm-program-intelligence.php"
  "wordpress/plugins/program-publishing-integration/server-turizm-program-publishing-integration.php"
  "wordpress/plugins/tour-intelligence/server-turizm-tour-intelligence.php"
  "integrations/google-sheets/umrah/ST_TDE_Exporter.gs"
  "integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs"
  "wordpress/server-config/.htaccess"
)

for path in "${required[@]}"; do
  if [[ ! -f "$path" ]]; then
    printf 'MISSING: %s\n' "$path" >&2
    exit 1
  fi
done

credential_pattern='AIza[0-9A-Za-z_-]{30,}|gh[pousr]_[0-9A-Za-z]{30,}|github_pat_[0-9A-Za-z_]{30,}|[0-9]{8,10}:[A-Za-z0-9_-]{30,}|-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----|sk-[A-Za-z0-9_-]{20,}'
if grep -RIEq --exclude-dir=.git --exclude='verify-baseline.sh' "$credential_pattern" .; then
  printf 'Credential-shaped value detected; inspect before commit.\n' >&2
  exit 1
fi

if command -v php >/dev/null 2>&1; then
  while IFS= read -r -d '' file; do
    php -l "$file" >/dev/null
  done < <(find wordpress -type f -name '*.php' -print0)
else
  printf 'NOTICE: php is unavailable; PHP syntax check skipped.\n'
fi

python3 wordpress/plugins/program-intelligence/tests/test_v031_invariants.py
python3 wordpress/plugins/program-intelligence/tests/test_v031_temporal_semantics.py
python3 wordpress/plugins/program-intelligence/tests/validate_examples.py
python3 wordpress/plugins/tour-intelligence/tests/test_v065_ai_completion.py
python3 scripts/test-wordpress-ci-contract.py
python3 tests/test_current_program_intelligence.py
node wordpress/plugins/program-intelligence/tests/test_exporter.js
node --check < integrations/google-sheets/umrah/ST_TDE_Exporter.gs
node --check < integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs

printf 'Baseline verification PASS\n'

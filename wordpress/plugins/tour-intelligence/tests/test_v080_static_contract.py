#!/usr/bin/env python3
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
plugin=(ROOT/'server-turizm-tour-intelligence.php').read_text(encoding='utf-8')
renderer=(ROOT/'includes/customer-renderer-v080.php').read_text(encoding='utf-8')
shell=(ROOT/'assets/customer-shell-v080.js').read_text(encoding='utf-8')
contract=(ROOT/'contracts/STTI-v0.8.0-CUSTOMER-RENDERER-CONTRACT.md').read_text(encoding='utf-8')
checks={
 'header version': 'Version: 0.8.0' in plugin,
 'release marker': "define('STTI_RELEASE_VERSION', '0.8.0');" in plugin,
 'renderer loaded': "includes/customer-renderer-v080.php" in plugin,
 'renderer contract': "STTI-CUSTOMER-RENDERER-1.0.0" in renderer and "STTI-CUSTOMER-RENDERER-1.0.0" in contract,
 'no primary guess': "no_primary_selected" in renderer,
 'hotel live read': "hotel_intelligence_live_read" in renderer,
 'private no-write': "'public'=>false" in renderer and "'indexable'=>false" in renderer and "'writes'=>0" in renderer,
 'shell no fetch': 'fetch(' not in shell,
 'shell no localStorage': 'localStorage' not in shell,
}
for name,ok in checks.items(): print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(0 if all(checks.values()) else 1)

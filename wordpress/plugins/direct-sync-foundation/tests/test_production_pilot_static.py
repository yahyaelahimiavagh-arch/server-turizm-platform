#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[4]
pilot = (ROOT / 'integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs').read_text(encoding='utf-8')
menu = (ROOT / 'integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs').read_text(encoding='utf-8')

checks = {
    'selected umrah validate exists': 'function stDirectSyncValidateSelectedUmrah()' in pilot,
    'selected umrah apply exists': 'function stDirectSyncSelectedUmrah()' in pilot,
    'selected tour validate exists': 'function stDirectSyncValidateSelectedTour()' in pilot,
    'umrah pilot scopes selected source row': "=== selectedRow" in pilot and 'batch.programs = [matches[0]];' in pilot,
    'umrah validate uses no removals': "stDirectSyncSend_('umrah', 'validate'" in pilot and 'removals: []' in pilot,
    'umrah apply asks confirmation': "ui.alert(" in pilot and "ui.ButtonSet.YES_NO" in pilot,
    'umrah apply excludes removals': "stDirectSyncSend_('umrah', 'apply'" in pilot and 'removals: []' in pilot,
    'validate does not write sidecar': 'stDirectSyncPilotStateIndex_' in pilot and 'getSheetByName(ST_DIRECT_SYNC.STATE_SHEET)' in pilot and 'insertSheet' not in pilot,
    'tour validate mode': "stDirectSyncSend_('tour', 'validate'" in pilot,
    'pilot menu validate umrah': 'Ön Kontrol — Seçili Umrah (WP yazma yok)' in menu,
    'pilot menu selected umrah apply': 'Pilot Güncelle — Seçili Umrah' in menu,
    'full umrah sync still present': 'Siteyi Güncelle — Tüm Aktif Umrah' in menu,
    'pilot menu validate tour': 'Ön Kontrol — Seçili Tur (WP yazma yok)' in menu,
    'legacy onOpen untouched': 'function onOpen' not in menu and 'function onOpen' not in pilot,
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

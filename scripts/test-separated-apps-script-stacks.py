#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
UMRAH = ROOT / 'integrations/google-sheets/umrah'
TOURS = ROOT / 'integrations/google-sheets/tours'

umrah_files = [
    UMRAH / 'ST-Umrah-Config.gs',
    UMRAH / 'ST-Umrah-Exporter.gs',
    UMRAH / 'ST-Umrah-Direct-Sync.gs',
    UMRAH / 'ST-Umrah-Menu.gs',
]
tour_files = [
    TOURS / 'ST-Tour-Generator.gs',
    TOURS / 'ST-Tour-Direct-Sync.gs',
    TOURS / 'ST-Tour-Menu.gs',
]

for path in umrah_files + tour_files:
    if not path.is_file():
        raise SystemExit(f'MISSING separated Apps Script file: {path.relative_to(ROOT)}')

umrah = '\n'.join(p.read_text(encoding='utf-8') for p in umrah_files)
tour = '\n'.join(p.read_text(encoding='utf-8') for p in tour_files)
config = (UMRAH / 'ST-Umrah-Config.gs').read_text(encoding='utf-8')
exporter = (UMRAH / 'ST-Umrah-Exporter.gs').read_text(encoding='utf-8')
umrah_sync = (UMRAH / 'ST-Umrah-Direct-Sync.gs').read_text(encoding='utf-8')
tour_generator = (TOURS / 'ST-Tour-Generator.gs').read_text(encoding='utf-8')
tour_sync = (TOURS / 'ST-Tour-Direct-Sync.gs').read_text(encoding='utf-8')

checks = {
    'Umrah stack has no Tour runtime symbols': 'stTour' not in umrah and 'ST_TOUR' not in umrah,
    'Tour stack has no Umrah runtime symbols': 'stUmrah' not in tour and 'ST_UMRAH' not in tour,
    'Umrah removal is AD only': 'COL_REMOVE: 30' in config and "REMOVE_HEADER: 'Programı Kaldır'" in config,
    'Umrah exporter enforces canonical removal layout': 'stUmrahAssertLayout_' in exporter and 'ST_UMRAH.COL_REMOVE' in exporter,
    'Umrah canonical provenance preserved': "notes: 'Shadow export; operator verification required before approval.'" in exporter,
    'Umrah client uses only umrah adapter': "adapter:'umrah'" in umrah_sync and "adapter:'tour'" not in umrah_sync,
    'Tour client uses only tour adapter': "adapter:'tour'" in tour_sync and "adapter:'umrah'" not in tour_sync,
    'Tour technical controls are header driven': 'TECH_STABLE_ID_HEADER' in tour_generator and 'TECH_CHECKSUM_HEADER' in tour_generator,
    'Tour technical controls append dynamically': 'stable=last+1;' in tour_generator and 'checksum=last+2;' in tour_generator,
    'Tour has no fixed Z/AA column constants': 'TECH_STABLE_ID_COL' not in tour_generator and 'TECH_CHECKSUM_COL' not in tour_generator,
    'Tour producer compatibility version preserved': "PRODUCER_VERSION: '0.6.2.1'" in tour_generator and 'version:ST_TOUR.PRODUCER_VERSION' in tour_generator,
    'Tour source range compatibility preserved': "+'&range=A'+row+':Y'+row" in tour_generator,
    'Tour canonical provenance preserved': 'Google Sheets satırından otomatik PARTIAL JSON. Eksik bilgiler AI/human review aşamasında tamamlanmalıdır.' in tour_generator,
    'Both clients keep bounded retry': 'TRANSPORT_MAX_ATTEMPTS: 3' in config and 'TRANSPORT_MAX_ATTEMPTS:3' in tour_sync,
    'Both clients preserve HMAC signing': 'computeHmacSha256Signature' in umrah_sync and 'computeHmacSha256Signature' in tour_sync,
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
if failed:
    raise SystemExit('Separated Apps Script guard failed: ' + ', '.join(failed))
print('Separated Apps Script architecture guard: PASS')

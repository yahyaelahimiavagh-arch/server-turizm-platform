#!/usr/bin/env python3
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
REPO = ROOT.parents[2]
plugin = (ROOT / 'server-turizm-direct-sync.php').read_text(encoding='utf-8')
store = (ROOT / 'includes/class-stds-store.php').read_text(encoding='utf-8')
auth = (ROOT / 'includes/class-stds-auth.php').read_text(encoding='utf-8')
rest = (ROOT / 'includes/class-stds-rest.php').read_text(encoding='utf-8')
umrah = (ROOT / 'includes/class-stds-umrah.php').read_text(encoding='utf-8')
tour = (ROOT / 'includes/class-stds-tour.php').read_text(encoding='utf-8')
client = (REPO / 'integrations/google-sheets/shared/ST-Direct-Sync.gs').read_text(encoding='utf-8')
menu = (REPO / 'integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs').read_text(encoding='utf-8')
contract = (ROOT / 'contracts/ST-DIRECT-SYNC-1.0.0.md').read_text(encoding='utf-8')
workflow_path = REPO / '.github/workflows/unified-direct-sync.yml'
workflow = workflow_path.read_text(encoding='utf-8') if workflow_path.exists() else ''

checks = {
    'plugin version': 'Version: 0.1.3' in plugin and "define('STDS_VERSION', '0.1.3');" in plugin and "define('STDS_CONTRACT', 'ST-DIRECT-SYNC-1.0.0');" in plugin,
    'single shared REST route': "'/direct-sync'" in rest and "register_rest_route('server-turizm/v1'" in rest,
    'auth secret external': "defined('ST_DIRECT_SYNC_SECRET')" in auth and "ST_DIRECT_SYNC_SECRET', '" not in auth,
    'timestamp skew enforced': 'MAX_SKEW=300' in auth,
    'HMAC SHA256 enforced': "hash_hmac('sha256'" in auth and "hash('sha256',$raw)" in auth,
    'key id enforced': 'x-st-sync-key-id' in auth,
    'request id idempotency': 'UNIQUE KEY request_id' in store and 'idempotent_replay' in rest,
    'nonce replay protection': 'UNIQUE KEY nonce_hash' in store and 'nonce_hash' in auth,
    'body mutation conflict': 'stds_idempotency_conflict' in rest,
    'two adapters only': "array('umrah','tour')" in rest,
    'validate and apply modes': "array('validate','apply')" in rest,
    'tour checksum conflict': 'CHECKSUM_MISMATCH' in tour and 'expected_checksum_sha256' in tour,
    'tour archive without delete': "'editorial'=>'archived'" in tour and 'delete(' not in tour and 'wp_delete' not in tour,
    'tour publication stays private': "'public_route'=>false" in tour and "'indexable'=>false" in tour and "'sitemap'=>false" in tour,
    'umrah canonical checksum': "'_stpi_payload_hash'" in umrah and 'expected_checksum_sha256' in umrah,
    'umrah archive through lifecycle': "STPI_Store::transition($post_id,'archive')" in umrah and 'wp_delete' not in umrah,
    'umrah validate exposes resolved target': "$target_id=$program_id!==''?$program_id:(string)($plan['program_id']??'');" in umrah,
    'umrah sidecar stable id excluded from source hash': "$source_program['program_id']=null;" in umrah and 'STABLE_ID_SOURCE_MISMATCH' in umrah,
    'live Program update fails closed before mutation': 'PUBLIC_PROGRAM_UPDATE_REQUIRES_CONTROLLED_REVIEW' in umrah and "($plan['operation']??'')==='UPDATE_CANDIDATE'" in umrah and "!empty($impact['protected'])" in umrah,
    'live Program archive fails closed before mutation': 'PUBLIC_PROGRAM_ARCHIVE_REQUIRES_CONTROLLED_REVIEW' in umrah,
    'public impact reads Publishing registry only': "get_option('stppi_registry',array())" in umrah and "array('public_noindex','indexable')" in umrah,
    'public impact metadata surfaced to Sheets': 'public_impact' in umrah and 'CANLI PROGRAM KORUMASI' in client,
    'response never unlocks public': "'public_exposure_changed'=>false" in rest,
    'apps script secret property': 'PropertiesService.getScriptProperties()' in client and 'ST_DIRECT_SYNC_SECRET' in client,
    'apps script HMAC': 'computeHmacSha256Signature' in client,
    'apps script HTTPS only': '/^https:\\/\\//i.test(endpoint)' in client,
    'apps script signed headers': all(x in client for x in ['X-ST-Sync-Timestamp','X-ST-Sync-Nonce','X-ST-Sync-Key-Id','X-ST-Sync-Signature']),
    'tour reuses Z AA controls': 'STTI_TECH_STABLE_ID_COL' in client and 'STTI_TECH_CHECKSUM_COL' in client,
    'umrah sidecar state': "STATE_SHEET: 'ST Direct Sync State'" in client and 'sheet.hideSheet()' in client,
    'umrah explicit removals': 'stDirectSyncUmrahRemovals_' in client,
    'installable Siteyi Guncelle menu': 'ScriptApp.newTrigger' in menu and 'Siteyi Güncelle — Tüm Aktif Umrah' in menu and 'Siteyi Güncelle — Seçili Tur' in menu,
    'legacy onOpen not replaced': 'function onOpen' not in menu and 'function onOpen' not in client,
    'contract documents archive never delete': 'Archive never means delete.' in contract,
    'runtime workflow present': 'wp_runtime_direct_sync.php' in workflow,
    'workflow activates all dependencies': all(x in workflow for x in ['program-intelligence','tour-intelligence','direct-sync-foundation']),
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

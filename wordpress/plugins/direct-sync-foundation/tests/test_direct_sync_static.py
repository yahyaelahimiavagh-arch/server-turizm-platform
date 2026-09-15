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
    'apps script client hotfix version': "VERSION: '0.1.3.2'" in client,
    'single shared REST route': "'/direct-sync'" in rest and "register_rest_route('server-turizm/v1'" in rest,
    'auth secret external': "defined('ST_DIRECT_SYNC_SECRET')" in auth and "ST_DIRECT_SYNC_SECRET', '" not in auth,
    'timestamp skew enforced': 'MAX_SKEW=300' in auth,
    'HMAC SHA256 enforced': "hash_hmac('sha256'" in auth and "hash('sha256',$raw)" in auth,
    'key id enforced': 'x-st-sync-key-id' in auth,
    'request id idempotency': 'UNIQUE KEY request_id' in store and 'idempotent_replay' in rest,
    'nonce replay protection': 'UNIQUE KEY nonce_hash' in store and 'nonce_hash' in auth,
    'body mutation conflict': 'stds_idempotency_conflict' in rest,
    'processing state exposed for safe retry': "stds_processing" in rest,
    'two adapters only': "array('umrah','tour')" in rest,
    'validate and apply modes': "array('validate','apply')" in rest,
    'tour checksum conflict': 'CHECKSUM_MISMATCH' in tour and 'expected_checksum_sha256' in tour,
    'tour archive without delete': "'editorial'=>'archived'" in tour and 'delete(' not in tour and 'wp_delete' not in tour,
    'tour publication stays private': "'public_route'=>false" in tour and "'indexable'=>false" in tour and "'sitemap'=>false" in tour,
    'umrah canonical checksum': "'_stpi_payload_hash'" in umrah and 'expected_checksum_sha256' in umrah,
    'umrah archive through lifecycle': "STPI_Store::transition($post_id,'archive')" in umrah and 'wp_delete' not in umrah,
    'umrah validate exposes resolved target': "$target_id=$program_id!==''?$program_id:(string)($plan['program_id']??'');" in umrah,
    'umrah sidecar stable id excluded from source hash': "$source_program['program_id']=null;" in umrah and 'STABLE_ID_SOURCE_MISMATCH' in umrah,
    'lifecycle-only stale checksum is narrowly reconciled': "['workflow']['editorial']='needs_review'" in umrah and 'preapproval_hash' in umrah and 'sidecar_checksum_reconciled' in umrah,
    'unrelated checksum mismatch remains conflict': "array('CHECKSUM_MISMATCH')" in umrah,
    'live Program update preserves approval automatically': "STPI_Store::transition($post_id,'approve')" in umrah and 'LIVE_UPDATE_AUTO_REFRESHED' in umrah,
    'live Program update refreshes publishing hashes': "STPPI_Renderer::model($cfg_before,false)" in umrah and "['hash']=(string)$model['hash']" in umrah and "['hotel_hash']=(string)$model['hotel_hash']" in umrah,
    'live Program update preserves route mode': "['mode']=(string)$cfg_before['mode']" in umrah,
    'live Program update has rollback snapshot': "identity_repair_snapshot" in umrah and "identity_repair_restore_snapshot" in umrah and 'direct_sync_live_refresh_rolled_back' in umrah,
    'live Program archive remains controlled': 'PUBLIC_PROGRAM_ARCHIVE_REQUIRES_CONTROLLED_REVIEW' in umrah,
    'public impact reads Publishing registry only': "get_option('stppi_registry',array())" in umrah and "array('public_noindex','indexable')" in umrah,
    'public impact metadata surfaced to Sheets': 'public_impact' in umrah and 'otomatik approval + route/hash refresh' in client,
    'response never unlocks public': "'public_exposure_changed'=>false" in rest,
    'apps script secret property': 'PropertiesService.getScriptProperties()' in client and 'ST_DIRECT_SYNC_SECRET' in client,
    'apps script HMAC': 'computeHmacSha256Signature' in client,
    'apps script HTTPS only': '/^https:\\/\\//i.test(endpoint)' in client,
    'apps script signed headers': all(x in client for x in ['X-ST-Sync-Timestamp','X-ST-Sync-Nonce','X-ST-Sync-Key-Id','X-ST-Sync-Signature']),
    'transport retry configured': 'TRANSPORT_MAX_ATTEMPTS: 3' in client and 'TRANSPORT_RETRY_DELAY_MS: 1500' in client,
    'transport retry keeps request body stable': client.index("var requestId = 'STS-'") < client.index('for (var attempt = 1; attempt <= maxAttempts; attempt++)') and client.index('var body = JSON.stringify(envelope);') < client.index('for (var attempt = 1; attempt <= maxAttempts; attempt++)'),
    'transport retry refreshes nonce signature': 'function stDirectSyncSignedFetch_' in client and "Utilities.getUuid().replace(/-/g, '')" in client and 'computeHmacSha256Signature' in client,
    'transient retry is automatic': 'stDirectSyncIsTransientTransportError_' in client and 'dns\\s*error' in client and 'network\\s*error' in client and 'Utilities.sleep(ST_DIRECT_SYNC.TRANSPORT_RETRY_DELAY_MS * attempt)' in client,
    'processing retry is automatic': "parsed.code === 'stds_processing'" in client,
    'retry recovery is surfaced to operator': 'transport_retry_recovered' in client and 'Geçici bağlantı hatası otomatik retry ile kurtarıldı' in client,
    'tour reuses Z AA controls': 'STTI_TECH_STABLE_ID_COL' in client and 'STTI_TECH_CHECKSUM_COL' in client,
    'umrah sidecar state': "STATE_SHEET: 'ST Direct Sync State'" in client and 'sheet.hideSheet()' in client,
    'umrah explicit removals': 'stDirectSyncUmrahRemovals_' in client,
    'installable Siteyi Guncelle menu': 'ScriptApp.newTrigger' in menu and 'Siteyi Güncelle — Tüm Aktif Umrah' in menu and 'Siteyi Güncelle — Seçili Tur' in menu,
    'legacy onOpen not replaced': 'function onOpen' not in menu and 'function onOpen' not in client,
    'contract documents archive never delete': 'Archive never means delete.' in contract,
    'contract documents automatic live refresh': 'Automatic live Umrah refresh' in contract and 'Manual Approve/Prepare is not required' in contract,
    'runtime workflow present': 'wp_runtime_direct_sync.php' in workflow and 'wp_runtime_umrah_live_autorefresh.php' in workflow,
    'workflow activates all dependencies': all(x in workflow for x in ['program-intelligence','program-publishing-integration','tour-intelligence','direct-sync-foundation']),
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(f"{name}: {'PASS' if ok else 'FAIL'}")
raise SystemExit(1 if failed else 0)

#!/usr/bin/env python3
"""Static release invariants preserved by v0.3.4."""
from pathlib import Path
ROOT = Path(__file__).resolve().parents[1]
def read(path): return (ROOT/path).read_text(encoding='utf-8')
main=read('server-turizm-program-intelligence.php')
plugin=read('includes/class-stpi-plugin.php')
admin=read('includes/class-stpi-admin.php')
importer=read('includes/class-stpi-importer.php')
store=read('includes/class-stpi-store.php')
all_php='\n'.join(p.read_text(encoding='utf-8') for p in ROOT.rglob('*.php'))
checks={
 'version': "Version: 0.3.4" in main and "define( \'STPI_VERSION\', \'0.3.4\' );" in main,
 'frontend bootstrap gate': "if ( ! $stpi_private_runtime )" in main and "return;" in main,
 'admin runtime retained': "is_admin()" in main,
 'cron runtime retained': "wp_doing_cron" in main,
 'private program CPT': "'public'              => false" in plugin and "'show_in_rest'        => false" in plugin,
 'staged import': 'stpi_stage_import' in importer and 'stpi_commit_import' in importer,
 'human confirmation': 'confirm_review' in importer and 'check_admin_referer' in importer,
 'stable allocator lock': 'GET_LOCK' in store and 'STP-' in store and 'RELEASE_LOCK' in store,
 'idempotency': '_stpi_source_payload_hash' in store and 'UNCHANGED' in store and 'CONFLICT' in store,
 'source-row identity drift gate': 'SOURCE_ROW_IDENTITY_DRIFT' in store and 'identity_fingerprint' in store,
 'controlled identity repair': (ROOT/'includes/class-stpi-identity-repair.php').exists() and 'identity_repair_save' in store and 'identity_repair_restore_snapshot' in store,
 'archive snapshot': '_stpi_archive_snapshots' in store and 'hotel_facts' in store,
 'audit log': 'STPI_Audit::log' in store and 'stpi-audit' in admin,
 'temporal semantics preserved': 'STPI_Lifecycle::temporal_state( $program )' in admin and 'STPI_Lifecycle::effective_state( $program )' in admin,
 'no public renderer': 'register_rest_route' not in all_php and 'add_shortcode' not in all_php and 'template_include' not in all_php,
 'no Program delete path': 'wp_delete_post' not in all_php and 'wp_trash_post' not in all_php,
}
failed=[k for k,v in checks.items() if not v]
for k,v in checks.items(): print(f"{k}: {'PASS' if v else 'FAIL'}")
raise SystemExit(1 if failed else 0)

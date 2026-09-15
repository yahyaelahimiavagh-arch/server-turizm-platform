from pathlib import Path

root = Path(__file__).resolve().parents[4]
plugin = root / "wordpress/plugins/direct-sync-foundation"
shared = root / "integrations/google-sheets/shared"

archive_php = (plugin / "includes/class-stds-umrah-archive.php").read_text(encoding="utf-8")
rest_php = (plugin / "includes/class-stds-rest.php").read_text(encoding="utf-8")
bootstrap_php = (plugin / "server-turizm-direct-sync.php").read_text(encoding="utf-8")
archive_gs = (shared / "ST-Direct-Sync-Archive.gs").read_text(encoding="utf-8")
menu_gs = (shared / "ST-Direct-Sync-Menu.gs").read_text(encoding="utf-8")

checks = {
    "plugin version 0.1.4": "Version: 0.1.4" in bootstrap_php and "STDS_VERSION', '0.1.4'" in bootstrap_php,
    "archive helper loaded": "class-stds-umrah-archive.php" in bootstrap_php,
    "REST uses archive gateway": "STDS_Umrah_Gateway::handle($doc)" in rest_php,
    "public exposure reports controlled archive": "controlled_archived" in rest_php and "public_exposure_changed" in rest_php,
    "public/noindex only automatic archive": "CONTROLLED_PUBLIC_NOINDEX_ARCHIVE_READY" in archive_php,
    "indexable archive remains fail closed": "INDEXABLE_PROGRAM_ARCHIVE_REQUIRES_SEO_REVIEW" in archive_php,
    "explicit confirmation required": "CONTROLLED_ARCHIVE_CONFIRMATION_REQUIRED" in archive_php,
    "route demoted to prepared": "['mode']='prepared'" in archive_php,
    "global public master preserved": "stppi_public_master" in archive_php and "public_gate_drift" in archive_php,
    "hub preserved": "stppi_hub_bridge_enabled" in archive_php,
    "snapshot rollback present": "identity_repair_snapshot" in archive_php and "identity_repair_restore_snapshot" in archive_php,
    "KALDIR is header driven": "stDirectSyncFindUmrahRemovalColumn_" in archive_gs and "HEADER_NAMES" in archive_gs,
    "no fixed KALDIR column": "getRange(headerRow + 1, 30" not in archive_gs,
    "operator confirmation exists": "Umrah Controlled Archive" in archive_gs and "ButtonSet.YES_NO" in archive_gs,
    "apply carries controlled approval": "controlled_archive_approved = true" in archive_gs,
    "menu exposes archive": "KALDIR → İşaretli Umrahları Arşivle" in menu_gs,
}

failed = [name for name, ok in checks.items() if not ok]
for name, ok in checks.items():
    print(("PASS" if ok else "FAIL") + ": " + name)
if failed:
    raise SystemExit("Controlled archive static guard failed: " + ", ".join(failed))
print("Controlled archive static guard: PASS")

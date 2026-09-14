# 2026-09-14 Source Intake Evidence

The baseline was assembled from the current live-server custom-source export,
the WordPress Plugins version screenshot, the authoritative Master Plan, and
accepted Google Sheets artifacts.

## Uploaded live-source inputs

- custom Server Turizm plugin archive;
- complete `mu-plugins` archive;
- Server Turizm Porto child-theme archive;
- root `.htaccess` snapshot;
- ST-TDE Umrah Google Sheets exporter;
- Umrah and Culture Tour operator-sheet CSV snapshots;
- WordPress Plugins version screenshot;
- GitHub migration handoff.

The uploaded ZIP containers are not duplicated in Git. Their extracted custom
source is stored under `wordpress/`, while the original upload hashes are in
`SHA256SUMS-uploaded.txt`.

## Accepted artifacts recovered

- authoritative 2026-09-14 Master Plan;
- STTI `0.6.2.1` Sheet→Partial JSON Apps Script;
- STTI `0.6.2.1` installation/runbook note.

## Exclusions

WordPress core, uploads, the Porto parent theme, third-party licensed plugins,
server backups, databases, credentials, and customer/passenger/passport/lead
data were not imported.

See `docs/CURRENT-RUNTIME-INVENTORY.md` for module-by-module classification and
open reconciliation items.

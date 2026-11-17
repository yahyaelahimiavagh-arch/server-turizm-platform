# Server Turizm — source recovery snapshot (2026-11-17)

**Backup branch only. Not a deploy branch. Do not merge into `main` or overwrite live plugins without comparing production state.**

## Source archive
- File: `archives/server-turizm-github-recovery-2026-11-17.tar.xz`
- SHA-256: `d6f816495f21fbdfa362fb304542486d2a25cf7dce8adeb8d08fc44a172db3d7`
- Verified locally: 88 source/documentation files, 56 PHP files, 2 Google Apps Script files.
- Contents: `server-turizm-backup/release/` (Auto Sync **2.9**, Program Intelligence **0.3.8**, Publishing Integration **0.4.23**, Apps Script **2.9**, release instructions) and `server-turizm-backup/history/` (Auto Sync 2.5–2.8 source versions).
- These are **conversation-generated candidate/recovery source snapshots**. Their existence does not prove byte-for-byte parity with plugins installed on the live WordPress site.
- The attached branch-specific workflow extracts the archive into `recovery/2026-11-17/source/`, if GitHub Actions is enabled and has write access. Check the resulting commit before treating the extracted directory as present.

```bash
sha256sum archives/server-turizm-github-recovery-2026-11-17.tar.xz
mkdir -p recovered
tar -xJf archives/server-turizm-github-recovery-2026-11-17.tar.xz -C recovered
# Paths: recovered/server-turizm-backup/release and .../history
```

## Architecture contracts
- **Google Sheets** is the editing source for program data, prices, dates, ordering and explicit archive intents.
- **WordPress Hotel Intelligence** is the source of truth for hotel metadata, media, confirmed hotel IDs and public hotel pages. The Sheets Hotels tab is a selection/directory projection.
- **Original renderer** owns the public Umrah card layout; shortcode `[stppi_umre_programs]` must remain in place. Do not replace it with alternate HTML/cards.
- Sync works in batches; `RUNNING` means database writes are enabled, **not** that a queue is stuck.
- Archive safety: explicit intent, identity checks, two valid snapshots at least 60 seconds apart, no mass archive on missing rows. Clear public-page cache after a confirmed change.
- New programs are not necessarily public just because they are `approved`; publishing registry entry, route mode, pinned model checks, Hub registry, and cache must also agree.

## November 17, 2026 live observations
- WordPress Auto Sync snapshot: **65 programs / 16 hotels**, `Queue: []`, `errors: 0`, `complete: true`, database mode `RUNNING` (screen capture, not an independent server query).
- **R5 = STP-000054**, scheduled for **2027-03-01 through 2027-03-12**; received from Google Sheets and reported as `approved/scheduled/open` and `public_noindex` in WordPress publishing UI. The user subsequently confirmed that the missing-front-end problem **was resolved**. The exact successful action (cache purge vs another fix) was not independently established.
- R1 = STP-000050, R2 = STP-000051; Jiwar Al-Madina hotel = STH-000023. R3 = STP-000052; R4 = STP-000053.
- Historical archive: R501–R509 were manually archived; Kahire4 = STP-000014 was archived through explicit sheet intent.
- Previous incident: newly approved/registered programs could be absent from the public shortcode due to missing public registry/route eligibility or stale LiteSpeed page cache. Investigate both; do not assume one based solely on `approved`.

## Recover / troubleshoot
1. Identify exact production plugin versions and current Google Apps Script source. **Do not equate generated ZIP with installed production bytes.**
2. Pause Auto Sync database writes before any plugin replacement; arrange a WordPress database + wp-content backup.
3. Preserve Direct Sync Foundation/HMAC credential settings and Script Properties, never commit or print secret values.
4. For missing card (e.g. R5): inspect Program Intelligence state; Publishing Integration stable-ID registry `mode`, `stppi_public_master`, `stppi_hub_bridge_enabled`, canonical detail route, STPPI model eligibility and pinned hashes; then clear LiteSpeed cache on /umre-1/ and refresh the page.
5. For missing hotel links: check `stays[].hotel_id` matches existing `STH-...`, Hotel Intelligence verified/published status, public hotel URL, and stale provisional approval state.
6. For archive delays: inspect explicit sheet archive flag, `pending_program_archives`, fast-archive pending count, timestamps, queue state and WP-Cron. Never force-delete based on absence alone.
7. All live edits must be coordinated with production; this branch is for recovery/debugging, not automatic deployment.

## Timeline
2.5 restore original cards; 2.6 provisional accommodation approvals; 2.7 fast archive; 2.8 Jiwar confirmed hotel linkage; 2.9 hotel directory bridge and Sheets dropdown auto-sync.

## Security
The archive was pattern-scanned for common hardcoded secrets (API keys, tokens, private keys, passwords) before upload; **this is not a substitute for a full secret audit**. Store real credentials only in secure WordPress configuration/Google Script Properties. No live customer manifest or private bookings are included intentionally.

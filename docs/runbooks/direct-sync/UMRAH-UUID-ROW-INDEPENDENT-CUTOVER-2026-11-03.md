# Umrah Direct Sync: row-independent UUID cutover

Date: 2026-11-03. Status: **feature branch / not deployed**.

## Incident and invariant

Operator can sort, insert, move, rename and update Home rows freely.
The old Apps Script sidecars keyed records by `source_row`; WordPress source keys
also included the row. After reorder, a row-based lookup can attach the wrong
STP entity or raise `SOURCE_ROW_IDENTITY_DRIFT`.

**Invariant**: a Program's identity is `(spreadsheet_id, worksheet, Source UUID)`,
not its row number, title, dates, or editable program code.

- Home!AP: Source UUID, immutable after allocation.
- Home!AQ: WordPress-issued `STP-######` (never guess or mint in Sheets).
- Home!B: human-facing program code (may change after UUID binding).
- Current row: diagnostics/presentation only, retained in provenance.
- Existing state sidecar: identifies by stable ID; upgrades with UUID + digest.
- Sorting/reordering must never create a replacement WordPress Program.
- Manual deletion of a row is NOT an archive instruction. Use `Programı Kaldır`
  and the controlled archive flow; no hard delete.

## Code changes

1. Separate Umrah Apps Script binds controls using AP/AQ and a UUID-aware state
   index, not the row number. Validates UUIDs and refuses duplicate UUID/STP.
2. WordPress Program Intelligence source key ignores row if `source_ref=uuid:<uuid>`.
   Direct Sync resolves the exact Stable ID via a verified override; the override
   is excluded from the source payload hash.
3. Legacy STP binding is allowed once for matching program_code, then future
   edits (including renaming) are UUID-bound. A copied STP with another UUID is blocked.
4. Known IDs require an expected canonical checksum. A new UUID/code never
   silently overwrites an old record with the same program_code.
5. Result write-back re-locates the current Home row by UUID before updating AQ.
   It refuses any changed AQ binding.
6. Optional 5-minute auto-sync compares source digests, validates and applies
   only changed rows; invalid rows are reported separately, without interrupting
   other valid rows. Already-live READY updates use the existing protected
   auto-refresh gate.
7. Shared legacy Umrah row-based sync is disabled unless it can delegate to
   the independent UUID-aware Umrah client. Tour sync is not changed.

## Publication policy

- CREATE writes a private Program candidate. Initial public publication
  remains behind Program Intelligence + Program Publishing readiness and approval.
- UPDATE of an existing public Program is automatic *only* when READY and
  preflight returns `auto_refresh_supported`.
- A failed validation, checksum mismatch, foreign UUID, missing Stable ID or
  ambiguous code must stop that Program, never publish guesses.
- Auto-sync intentionally does not automatically archive missing rows or
  perform public/indexation changes.

## Production deployment sequence

1. **Back up** WordPress database, Program Publishing registry, plugins,
   the entire Google Sheet including hidden `ST Umrah Sync State`, and Apps
   Script project/config (keep secrets out of logs and repository).
2. Verify the installed WordPress plugin versions match repository files.
   Deploy Program Intelligence and Direct Sync Foundation **together**.
3. Replace the **installed Apps Script source files** in the connected Umrah
   workbook; a GitHub commit alone does not update Apps Script or WordPress.
4. Run PHP lint, Apps Script syntax checks, and isolated WordPress runtime tests.
5. With no automatic trigger installed yet, validate R605-1 after moving its row;
   verify it updates the same STP and unchanged public URL. Then test a harmless
   content edit; test duplicate UUID/STP rejection; test a new unique code.
6. Fix incomplete source data such as `6 Gece, 6 Gece` (cities missing),
   and verify dates, hotels and required inclusions before release.
7. Reconcile AP/AQ and sidecar bindings from verified records. Never copy STP
   IDs from one program to another or generate them in Apps Script.
8. After successful operator acceptance, use the Umrah menu
   `Otomatik Sync (5 dakikada bir) — Aç`. Inspect the
   `ST_UMRAH_LAST_AUTO_SYNC` Script Property for blocked/synced counts.
9. In WordPress, verify R605 programs by code, price, itinerary, Stable ID,
   public URL and canonical checksum; verify no duplicated public routes.
10. Purge WordPress/CDN caches only after confirmed successful updates.

## Rollback

Disable the 5-minute trigger first. Restore matching plugin versions, Apps
Script files and relevant WordPress snapshot; preserve audit records. Do not
revert only one side of the protocol after UUID rebinding without restoring its
matching state, or legacy source-key matching can become ambiguous.

## Acceptance tests

- A program moved between rows retains its STP identity and public route.
- New unique program gets one STP; second identical sync is UNCHANGED.
- Copy-pasted duplicate UUID/STP is blocked.
- Existing program title/price/date changes are UPDATE, not CREATE.
- Invalid program blocks itself; valid unrelated programs still sync.
- Unknown/missing checksum, UUID mismatch and code collision fail closed.
- Archive requires explicit removal intent, never row disappearance.
- Existing public updates revalidate and roll back on publishing gate failure.

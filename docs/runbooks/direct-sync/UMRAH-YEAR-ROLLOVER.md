# Umrah Google Sheet — Annual Rollover Runbook

Status: operational runbook. This document does not authorize production deployment by itself.

## Rule

Use one Google Spreadsheet per operating year. Do **not** erase the prior-year `Home` sheet and refill the same source rows with a new year's Programs.

Recommended naming:

- `Server Turizm Umre Programlari 2026 Control Panel`
- `Server Turizm Umre Programlari 2027 Control Panel`
- `Server Turizm Umre Programlari 2028 Control Panel`

The spreadsheet title may change. The primary worksheet name remains exactly:

`Home`

until the ST-TDE configuration is deliberately changed and retested.

## Why a new file is required

Umrah Direct Sync technical identity uses:

`adapter + document_ref + worksheet + source_row`

where `document_ref` is the Google Spreadsheet ID. A new yearly Spreadsheet creates a new source namespace and prevents a reused row number from inheriting an old year's `STP-*` sidecar identity/checksum.

WordPress `STP-*` identity remains permanent and independent from the annual Sheet file.

## When to create the next year

The new Sheet may be prepared before 1 January. Create it when the next year's Programs begin to be sold or entered operationally.

Do not move an already-existing cross-year Program merely because the calendar changes.

Example:

- departure: 28 December 2026
- return: 6 January 2027

That Program remains owned by the 2026 source row until it finishes. Do not create a second 2027 copy.

## New-year setup

1. Create the new annual Spreadsheet.
2. Preserve the visible operator schema and keep the worksheet name `Home`.
3. Install/copy the accepted Apps Script modules:
   - `ST_TDE_Exporter.gs`
   - `ST-Direct-Sync.gs`
   - `ST-Direct-Sync-Menu.gs`
   - `ST-Direct-Sync-Pilot.gs`
   - `ST-Direct-Sync-Archive.gs` when Direct Sync v0.1.4 is production accepted.
4. Do **not** carry forward old Umrah Direct Sync state as authoritative state.
   - If the Spreadsheet was created by duplicating the prior year's file, delete or clear the hidden `ST Direct Sync State` sheet before first sync.
   - Direct Sync will recreate the sidecar with the new Spreadsheet ID.
5. Run `Direct Sync Ayarları` once for the new bound Apps Script project.
   - endpoint: existing production Direct Sync endpoint;
   - Key ID: same authorized key unless intentionally rotated;
   - HMAC secret: enter directly into Script Properties; never store it in business cells or Git.
6. Refresh/import `ST Hotel Directory` when hotel mappings are needed.
7. Enter only genuinely new Programs for the new source year.

## First new-year acceptance check

Use one genuinely new Program row.

1. `Ön Kontrol — Seçili Umrah (WP yazma yok)`
   - expected: `CREATE` for a truly new Program.
2. `Pilot Güncelle — Seçili Umrah`
   - expected: `SYNC OK` + `CREATE`.
3. Run selected-row precheck again.
   - expected: same returned `STP-*` + `UNCHANGED`.

Only after this gate passes should the new annual Sheet be used for routine selected-row sync.

## Prior-year file after rollover

Do not delete it.

The prior-year file remains the source/audit operator surface for Programs that originated there.

- unfinished Programs continue to be updated from the old file;
- completed/cancelled Programs may be marked `KALDIR` and archived through the controlled archive flow;
- old rows are not reused for a different Program;
- old `STP-*` identities are never reassigned.

## Website behavior

The website does not require a global "change year" operation.

2026 and 2027 Programs may coexist in WordPress. Program schedule/lifecycle determines whether a Program is current, upcoming, completed or archived. The annual Sheet boundary is an operator/data-source boundary, not a public-site publication boundary.

## Fail-closed rules

Stop and investigate if any of the following occurs:

- a truly new-year row resolves to an old `STP-*` unexpectedly;
- a first sync reports `UPDATE` when the operator expected `CREATE`;
- an old and new Sheet contain independent copies of the same real Program;
- the new project has missing Direct Sync Script Properties;
- `Home` was renamed without an approved ST-TDE configuration change;
- an old `ST Direct Sync State` sidecar was intentionally copied and is being treated as current state.

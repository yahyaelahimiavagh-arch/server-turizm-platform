# Server Turizm Program Intelligence v0.3.2

Foundation candidate for the shared ST-TDE Umrah and cultural tour program engine.

## Safety state

This release is deliberately **Private Candidate Locked**:

- no public URLs or templates;
- no SEO or JSON-LD output;
- only explicit administrator-confirmed writes to a non-public Program candidate store;
- no automatic archive actions;
- no modification of the existing `/umre-1/` output;
- no replacement of the current Google Sheets HTML generator.

It adds JSON Dry Run, staged/idempotent Candidate Import, immutable Stable Program IDs, human approval, lifecycle review, archive snapshots, audit events, a read-only Hotel Intelligence adapter, Hotel Directory export and Google Sheets Shadow exporter.

Detailed design notes are in `docs/architecture.md`, `docs/current-sheet-mapping.md` and `docs/publish-gates.md`.

## Contract

`schema/program-batch-v1.0.0.schema.json` is the canonical v1.0 transport contract. Google Sheets and the future WordPress editor must emit the same shape.

Identity rules:

- Program entity: `STP-000000`
- Hotel entity: `STH-000000`
- `program_code` is a display/sales label and may not be unique.
- IDs are never recycled.

## Hotel Directory and Google Sheets

1. Open **Program Intelligence → Hotel Directory** and confirm the state is `READY`.
2. Copy the directory JSON. It contains Stable Hotel IDs and read-only display data from Hotel Intelligence.
3. Add `integrations/google-sheets/ST_TDE_Exporter.gs` as a new Apps Script file; do not replace the current generator.
4. Add `stTdeOnOpen_();` immediately before the closing brace of the existing `onOpen()`.
5. Reload the spreadsheet, open **ST-TDE → Import Hotel Directory**, validate active rows, then export JSON.
6. Validate it in **JSON Dry Run**, then stage it in **Candidate Import** only after the Dry Run is accepted.

The exporter never edits `Home`. It writes only the operator-requested Hotel Directory cache tab and JSON files in Google Drive. Rows marked `Programı Kaldır` are omitted from the active partial export. In v0.3.4 the exporter can also create a separate removal manifest; WordPress Removal Review requires exact source matching plus explicit administrator confirmation before an immutable archive snapshot is created. No Program entity is deleted.

## Dry Run

1. Install only in a test/staging copy first.
2. Keep Hotel Intelligence v0.9.11 active for hotel resolution tests.
3. Open **Program Intelligence → JSON Dry Run**.
4. Paste or upload one of the bundled examples.
5. Review operation, completeness score, Publish Gate, hotel status, errors and warnings.

The Dry Run stores only a short-lived administrator report transient. It creates no program record.

## Candidate Import

1. Open **Program Intelligence → Candidate Import** and upload a validated ST-TDE JSON batch.
2. Review the CREATE / UPDATE / UNCHANGED / CONFLICT plan.
3. Confirm dates, prices, Hotel mappings and source identity.
4. Commit creates private candidates and allocates immutable `STP-*` IDs. It cannot create any public page.
5. Re-importing the identical Google Sheets row is `UNCHANGED`; changed facts become `UPDATE_CANDIDATE`.
6. Review programs individually or approve all READY review candidates with an explicit confirmation.

Candidate content is stored as canonical JSON in the private `stpi_program` post type with revisions and audit events. User-facing Program JSON can be exported again at any time.

## Lifecycle and archive

- Temporal state is computed as upcoming, in progress, completed or undated.
- Sold-out and cancelled remain separate business states.
- Admin UI displays pure **Temporal** separately from the composite **Effective state**.
- Completed/cancelled programs become Archive Candidates only.
- Archive requires an administrator action and captures a payload + resolved Hotel snapshot.
- Restore is supported; delete is not exposed anywhere in v0.3.2.

## Acceptance gate for the next version

- standard active Umrah;
- Cairo-connected Umrah;
- completed/removed Umrah;
- sold-out Umrah;
- Uzbekistan partial tour;
- Balkan partial tour;
- repeated identical payload returns `UNCHANGED`;
- a missing row creates an Archive Candidate only, never an automatic deletion.

## Known v0.3.2 exclusions

The following are intentionally not implemented yet: public cards/pages, live price table, public archive, SEO renderer, Google Sheets Stable-ID writeback, direct WordPress fact editor, social/PDF outputs and analytics attribution. Cultural-tour brochure JSON remains manual in this gate; the current `Home` exporter is deliberately scoped to its 36-column Umrah layout.

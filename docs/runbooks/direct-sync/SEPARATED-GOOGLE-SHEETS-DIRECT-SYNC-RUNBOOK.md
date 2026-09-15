# Separated Google Sheets Direct Sync — Runbook

## Status

Production operator surfaces accepted on 2026-09-15 for selected-row no-change paths.

Contract: `ST-DIRECT-SYNC-1.0.0`

WordPress endpoint:

`/wp-json/server-turizm/v1/direct-sync`

The WordPress gateway remains shared, but Google Apps Script is **not shared** between Umrah and Tour.

## Umrah project

Install only:

- `ST-Umrah-Config.gs`
- `ST-Umrah-Exporter.gs`
- `ST-Umrah-Direct-Sync.gs`
- `ST-Umrah-Menu.gs`

Run `stUmrahInstall()` once.

Use `stUmrahConfigureSync()` only when endpoint/key/secret Script Properties are missing or intentionally changed.

Hidden state sheet:

`ST Umrah Sync State`

Removal control:

`AD / Programı Kaldır`

Historical checked rows with no Stable ID/checksum are Sheet-only closed; never CREATE them only to archive them.

## Tour project

Install only:

- `ST-Tour-Generator.gs`
- `ST-Tour-Direct-Sync.gs`
- `ST-Tour-Menu.gs`

Run `stTourInstall()` once.

Technical controls are discovered by header:

- `STTI Stable ID`
- `Expected Checksum`

If absent they are appended after the last used business column and hidden. Never assume fixed Z/AA because visible business columns may occupy those positions (for example `Vize`).

## Safe selected-row sequence

For either subsystem:

1. select the intended row;
2. run precheck (`validate`);
3. inspect exact Stable ID and operation;
4. do not apply on unexpected `CREATE`, `UPDATE`, `CONFLICT`, or `ERROR`;
5. when expected, run the selected-row update;
6. verify the returned Stable ID/operation;
7. a no-change verification should remain `UNCHANGED`.

Accepted fixtures on 2026-09-15:

```text
Umrah: STP-000005 → precheck UNCHANGED → apply UNCHANGED
Tour:  STT-000001 → precheck UNCHANGED → apply UNCHANGED
```

## Retry contract

Each independent client:

- creates request ID/body once per business request;
- reuses them across transient transport retry;
- refreshes timestamp/nonce/HMAC each attempt;
- retries timeout/DNS/network failures only;
- polls `stds_processing` safely;
- limits retries to 3 attempts.

## Publication boundary

Direct Sync does not authorize public/indexation changes.

Tour public route/indexation/sitemap/schema/canonical gates remain separate.

Controlled Umrah archive is a separate acceptance gate and is not proven live by the selected-row no-change screenshots.

## Annual Umrah rollover

Create one workbook per operating year. Preserve `Home`, install the Umrah-only stack, start with a clean yearly Umrah state namespace, then prove the first new Program through `CREATE → apply CREATE → same STP-* / UNCHANGED`.

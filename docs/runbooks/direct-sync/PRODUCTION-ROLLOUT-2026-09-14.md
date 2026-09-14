# Server Turizm Direct Sync — Controlled Production Rollout

**Status:** rollout candidate only; no live change is authorized by this file alone.  
**Base production inventory:** Program Intelligence `0.3.5`, Tour Intelligence `0.6.5`.  
**Target:** Tour Intelligence `1.0.0` + Direct Sync Foundation `0.1.1` + selected-row Google Sheets pilot helpers.

## Non-negotiable locks

- Tour Public Master remains OFF.
- Tour Route / Indexation / Canonical / Schema / Sitemap remain OFF.
- Program publication/indexation is not enabled by Direct Sync.
- No bulk Umrah apply until one selected-row pilot passes all gates.
- Archive never means delete.
- If any identity/checksum result is ambiguous, stop; do not force-write.

## Accepted Tour package

Use only the CI-built Tour Intelligence `1.0.0` replacement ZIP from accepted v1.0 runtime evidence.

Inner plugin ZIP SHA-256 accepted on 2026-09-14:

`0820d58511ee19c32d7d155bda18abe7e576e6ac019ff4f32c8e2c5448a66443`

The package intentionally uses the historical deployed directory name used by the Tour runtime/package workflow. During WordPress upload, **continue only if WordPress recognizes it as the existing `Server Turizm Tour Intelligence` plugin and offers replacement from current `0.6.5` to uploaded `1.0.0`.**

If WordPress treats it as a second plugin or does not offer a replacement comparison, cancel the upload and inspect the live plugin directory before proceeding.

## Direct Sync package

Use only the CI-built `server-turizm-direct-sync-v0.1.1-REPLACE.zip` produced from the final accepted rollout-hardening commit.

Before installation, compare its SHA-256 with the checksum emitted by the same successful GitHub Actions package job. Do not rebuild manually for production.

## Phase 0 — rollback backup

Before changing production:

1. create a database backup (`.sql.gz`);
2. download/backup the current Tour Intelligence plugin directory exactly as deployed;
3. keep a copy of current `wp-config.php` outside GitHub;
4. record active plugin versions from WordPress;
5. do not put backups, DB data, secrets, passenger/passport/customer data in GitHub.

STOP if a usable rollback backup is not available.

## Phase 1 — Tour Intelligence 1.0.0 replacement

1. WordPress Admin → Plugins → Add Plugin → Upload Plugin.
2. Upload the accepted Tour Intelligence `1.0.0` ZIP.
3. Verify WordPress shows the existing `Server Turizm Tour Intelligence` and offers replacement `0.6.5 → 1.0.0`.
4. Replace only after that exact comparison is visible.
5. Verify plugin is active and reports `1.0.0`.
6. Verify Tour Public Master and every child public/SEO gate remain OFF.
7. Public pilot route must remain unavailable while Public Master is OFF.

Do not enable any Tour public gate during Direct Sync rollout.

## Phase 2 — external Direct Sync secret

Generate one strong random shared secret outside Git and store it only in production configuration + Apps Script Properties.

Example server-side generation:

```bash
openssl rand -hex 32
```

Add to production `wp-config.php` above the WordPress stop-editing line:

```php
define('ST_DIRECT_SYNC_KEY_ID', '<unique-operator-key-id>');
define('ST_DIRECT_SYNC_SECRET', '<64-hex-random-secret>');
```

Rules:

- do not paste real values into GitHub;
- do not store the secret in Sheet cells;
- do not reuse unrelated API passwords/tokens;
- backup the original config before editing.

## Phase 3 — Direct Sync Foundation 0.1.1

1. Upload accepted `server-turizm-direct-sync-v0.1.1-REPLACE.zip`.
2. Activate `Server Turizm Direct Sync Foundation`.
3. Confirm version `0.1.1`.
4. Confirm the site still loads normally.
5. Do not run a sync yet.

Endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

## Phase 4 — Google Apps Script rollout

Keep existing producers intact:

- Umrah: `ST_TDE_Exporter.gs`
- Tours: `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

Add the accepted shared files:

- `ST-Direct-Sync.gs`
- `ST-Direct-Sync-Menu.gs`
- `ST-Direct-Sync-Pilot.gs`

Then:

1. run `stDirectSyncConfigure()`;
2. enter the HTTPS endpoint above;
3. enter the exact same key ID as WordPress;
4. enter the exact same HMAC secret;
5. run `stDirectSyncInstall()` once;
6. reload the Sheet and verify menu `🔄 Server Turizm Sync` appears.

The secret must remain in Apps Script Properties, not cells.

## Phase 5 — first selected-row Umrah PRECHECK

Choose **one known active Umrah row that already exists in Program Intelligence**.

Do not choose a removal row for the first pilot.

Run:

`Ön Kontrol — Seçili Umrah (WP yazma yok)`

Expected for an already-existing canonical Program:

- operation = `UNCHANGED` or `UPDATE`;
- `stable_id` = an existing `STP-######`;
- checksum = 64-hex current canonical checksum;
- no row errors.

### Hard stop condition

If precheck returns `CREATE` for a row that is known to exist already, **STOP**. Do not click Pilot Güncelle. Source identity/bootstrap does not match the current canonical entity and must be reconciled first.

Also stop on:

- `CONFLICT`;
- `ERROR`;
- blank Stable ID for an existing row;
- unexpected checksum state;
- any public/SEO state change.

## Phase 6 — first selected-row Umrah APPLY

Only after Phase 5 passes:

Run:

`Pilot Güncelle — Seçili Umrah`

The helper asks for explicit confirmation and sends **only the selected Home row** with `removals: []`.

Expected:

- same immutable `STP-######` as precheck;
- operation `UNCHANGED` or `UPDATE` as appropriate;
- returned checksum stored in hidden `ST Direct Sync State`;
- no new duplicate Program;
- no public/indexation change.

Record the Stable ID + checksum in rollout evidence.

## Phase 7 — idempotency proof on production

Without changing the selected row:

1. run the selected-row precheck again;
2. expected operation = `UNCHANGED`;
3. Stable ID must remain identical;
4. checksum must remain identical;
5. run selected-row Apply again only if needed for the evidence gate; expected `UNCHANGED` and no duplicate/write drift.

If Stable ID or checksum unexpectedly changes, stop and reconcile.

## Phase 8 — controlled UPDATE proof

After the unchanged gate passes, optionally make one small source-supported business-data change on the same pilot row.

1. precheck must return `UPDATE` against the same Stable ID;
2. confirm the proposed source data is correct;
3. Apply selected row;
4. response must return `UPDATE` with the same Stable ID and a new checksum;
5. verify WordPress private canonical record and audit evidence.

Do not use invented/test business facts on a real commercial row.

## Phase 9 — expand scope

Only after selected-row CREATE-protection, UNCHANGED and UPDATE gates pass:

- enable normal selected-row operation for other Umrah rows;
- later evaluate `Siteyi Güncelle — Tüm Aktif Umrah`;
- Tour Direct Sync remains selected-row and private/canonical unless separately approved;
- archive flows require their own controlled production proof before routine use.

## Immediate rollback

If plugin/runtime behavior is abnormal before any sync write:

1. deactivate Direct Sync Foundation;
2. restore previous Tour plugin directory if needed;
3. restore previous `wp-config.php` if configuration caused the issue.

If a pilot sync mutated canonical Program data incorrectly:

1. stop all Direct Sync actions;
2. preserve screenshots/logs/Stable ID/checksum evidence;
3. use the pre-rollout DB backup or accepted Program lifecycle/recovery workflow to restore the exact entity;
4. do not delete the Program to hide the incident.

## Acceptance checkpoint

Production rollout is accepted only when all are true:

- Tour Intelligence `1.0.0` installed with every public/SEO gate OFF;
- Direct Sync Foundation `0.1.1` active;
- signed endpoint configured without secrets in Git/Sheet cells;
- selected-row Umrah precheck resolves an existing STP identity;
- first Apply preserves that Stable ID;
- second no-change pass is `UNCHANGED`;
- checksum remains stable on no-change;
- no duplicate Program created;
- public/indexation state unchanged;
- rollback backup remains available.

Until then, full-active Umrah sync is not an accepted production operation.

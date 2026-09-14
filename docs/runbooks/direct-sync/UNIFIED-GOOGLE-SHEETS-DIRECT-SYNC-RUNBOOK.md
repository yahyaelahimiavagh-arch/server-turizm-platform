# Unified Google Sheets Direct Sync — Runbook

## Current status

Repository candidate only. No production installation, endpoint secret, Apps Script secret or live synchronization is authorized by this runbook.

Contract: `ST-DIRECT-SYNC-1.0.0`

WordPress endpoint:

`/wp-json/server-turizm/v1/direct-sync`

## Components

- WordPress shared gateway: `wordpress/plugins/direct-sync-foundation/`
- Google Sheets shared client: `integrations/google-sheets/shared/ST-Direct-Sync.gs`
- Installable menu: `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`
- Existing Umrah producer remains `ST_TDE_Exporter.gs`
- Existing Tour producer remains `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

## Required external configuration

### WordPress

Set outside the repository, normally in `wp-config.php`:

```php
define('ST_DIRECT_SYNC_KEY_ID', '<operator-key-id>');
define('ST_DIRECT_SYNC_SECRET', '<strong-random-shared-secret>');
```

Never commit these values.

### Google Apps Script

Run `stDirectSyncConfigure()` and store:

- HTTPS WordPress endpoint;
- matching key ID;
- matching HMAC secret.

They are stored in Script Properties, not sheet cells.

Run `stDirectSyncInstall()` once to create the installable menu trigger without replacing existing `onOpen()` handlers.

## Operator actions

Menu: `🔄 Server Turizm Sync`

- `Siteyi Güncelle — Umrah`
- `Siteyi Güncelle — Seçili Tur`
- `Seçili Turu Arşivle`
- `Direct Sync Ayarları`

## Safe synchronization sequence

1. Existing generator builds/validates source data.
2. Shared client attaches Stable ID + expected checksum state.
3. Client builds `ST-DIRECT-SYNC-1.0.0` envelope.
4. Client signs the exact raw body using HMAC-SHA256.
5. WordPress validates timestamp, nonce, key ID and signature.
6. WordPress reserves `request_id` before mutation.
7. Adapter validates identity and expected checksum.
8. `validate` returns a plan without mutation; `apply` performs only accepted private/canonical mutations.
9. WordPress returns per-record operation + current checksum.
10. Sheet stores returned identity/checksum state for the next optimistic-concurrency request.

## Expected operation meanings

- `CREATE`: new immutable Stable ID allocated server-side.
- `UPDATE`: existing candidate changed after expected-checksum match.
- `UNCHANGED`: same canonical content; no canonical rewrite needed.
- `ARCHIVE`: retained entity moves to archived/closed lifecycle; never deleted.
- `CONFLICT`: identity/checksum/source state changed; operator must reconcile before retry.
- `ERROR`: validation/runtime failure; do not force-write.

## Idempotency and retry

A request must keep the same `request_id` only when retrying the exact same body.

- same ID + same body after completion returns the cached result;
- same ID + changed body fails;
- nonce reuse fails;
- a fresh business attempt uses a fresh request ID and nonce.

## Umrah identity state

The legacy Home sheet remains visually unchanged. Direct Sync stores technical identity in hidden sheet:

`ST Direct Sync State`

Key:

`adapter + document_ref + worksheet + source_row`

Values:

`STP-* + expected canonical checksum`

Rows marked `Programı Kaldır` are sent as explicit archive intents only when a previously synchronized Stable ID/checksum exists.

## Tour identity state

Tour sheets continue using accepted hidden columns:

- Z = `STTI Stable ID`
- AA = `Expected Checksum`

Successful sync writes the server-returned current values back into Z/AA.

## Publication boundary

Direct Sync never enables public exposure.

For Tours, the accepted v1.0 Public Master/Route/Indexation/Canonical/Schema/Sitemap gates remain independent and default OFF.

For Umrah, Program candidates remain governed by Program Intelligence / Publishing Integration lifecycle. Direct Sync is not permission to publish.

## Recovery

Existing JSON export/import remains available. If Direct Sync is unavailable or conflict state is unclear, stop direct writes and use the JSON/stage/review workflow for reconciliation.

## Acceptance before production

Repository acceptance must prove:

- baseline static/security gates PASS;
- disposable WordPress + MariaDB runtime PASS;
- Tour CREATE/replay/CONFLICT/UPDATE/ARCHIVE PASS;
- Umrah CREATE/CONFLICT/UPDATE/ARCHIVE PASS;
- Stable IDs remain immutable;
- archived entities remain stored;
- public gates remain unchanged;
- cleanup restores disposable runtime state;
- replacement Direct Sync ZIP/SHA256 builds successfully.

Production installation, secret configuration and first live sync require a separate explicit owner action after repository acceptance.

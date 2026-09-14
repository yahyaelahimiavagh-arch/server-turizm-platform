# Server Turizm Direct Sync — Contract 1.0.0

## Purpose

One shared authenticated Google Sheets → WordPress synchronization foundation for Umrah Program Intelligence and Tour Intelligence.

This contract does not publish content. It writes only canonical/private operating records and lifecycle state accepted by the owning subsystem.

## Endpoint

`POST /wp-json/server-turizm/v1/direct-sync`

Maximum body size: 2 MB.

Envelope:

```json
{
  "contract": "ST-DIRECT-SYNC-1.0.0",
  "request_id": "unique-request-id",
  "adapter": "umrah | tour",
  "mode": "validate | apply",
  "payload": {}
}
```

## Authentication

Secrets are external to the repository.

WordPress reads:

- `ST_DIRECT_SYNC_SECRET`
- `ST_DIRECT_SYNC_KEY_ID` (optional explicit key identifier)

Google Apps Script reads the endpoint, key ID and secret from Script Properties.

Required request headers:

- `X-ST-Sync-Timestamp`
- `X-ST-Sync-Nonce`
- `X-ST-Sync-Key-Id`
- `X-ST-Sync-Signature`

Signature canonical string:

```text
ST-DIRECT-SYNC-1\n
<unix timestamp>\n
<nonce>\n
<SHA-256 raw request body>
```

Signature = HMAC-SHA256(canonical string, shared secret).

Timestamp tolerance is 300 seconds. Nonces and request IDs are replay-protected by the shared request ledger.

## Idempotency

`request_id` is unique.

- same request ID + same body after completion → cached response, no new write;
- same request ID + different body → conflict;
- reused nonce → conflict;
- accepted request ledger entries are retained temporarily and pruned after seven days.

## Stable identity and concurrency

### Umrah

Stable identity: `STP-######`.

Existing records must supply `expected_checksum_sha256` matching the stored canonical `_stpi_payload_hash`.

New records do not supply an expected checksum. Stable IDs are allocated only by Program Intelligence.

### Tours

Stable identity: `STT-######`.

Existing records must supply the current canonical Tour checksum. A mismatch returns `CONFLICT` before mutation.

New records do not supply an expected checksum. Stable IDs are allocated only by Tour Intelligence.

## Operations

Per-record operation values may include:

- `CREATE`
- `UPDATE`
- `UNCHANGED`
- `ARCHIVE`
- `CONFLICT`
- `ERROR`

`validate` performs preflight only. `apply` performs the accepted private/canonical mutation.

Partial failures are returned per record. One record conflict must not silently overwrite another record.

## Archive policy

Archive never means delete.

Umrah archive uses the accepted Program Intelligence source-removal intent + lifecycle transition and preserves archive snapshots.

Tour archive keeps the `STT-*` entity, marks it archived/closed and preserves audit evidence.

## Publication safety

Direct Sync does not enable or request:

- Tour Public Master;
- Tour public route;
- indexation;
- sitemap;
- schema;
- canonical exposure;
- homepage exposure;
- automatic Program publication.

Tour Direct Sync forces stored publication flags private/off. Public release remains controlled independently by the accepted v1.0 release overlay.

## Google Sheets client

The shared Apps Script module reuses existing generators instead of replacing them:

- Umrah: `ST_TDE_Exporter.gs`
- Tours: `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

Tour sync reuses hidden columns Z/AA for Stable ID and Expected Checksum.

Umrah sync uses the hidden `ST Direct Sync State` sidecar sheet because the legacy Home sheet is not modified for technical identity columns.

The installable `Server Turizm Sync` menu exposes operator actions such as `Siteyi Güncelle` without replacing existing `onOpen` handlers.

## Recovery

Existing JSON export/import workflows remain available as fallback and audit evidence. Direct Sync does not remove them.

No production installation, secret configuration or live synchronization is authorized merely by merging the repository implementation.

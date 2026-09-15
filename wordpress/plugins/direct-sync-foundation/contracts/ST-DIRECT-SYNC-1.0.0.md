# Server Turizm Direct Sync — Contract 1.0.0

## Purpose

One shared authenticated Google Sheets → WordPress synchronization foundation for Umrah Program Intelligence and Tour Intelligence.

The contract does not create new public exposure. It writes canonical/private operating data and may refresh an already-approved, already-registered Umrah public projection in place when the updated Program remains fully READY.

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
- `ST_DIRECT_SYNC_KEY_ID`

Apps Script reads endpoint/key/secret from Script Properties.

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

Existing records normally supply `expected_checksum_sha256` matching canonical `_stpi_payload_hash`.

The Sheet-side Stable ID is identity/concurrency state only and is excluded from the Google Sheets source-content hash.

A stale sidecar checksum is reconciled only in two fail-safe cases:
- the source plan is truly `UNCHANGED`; or
- the supplied checksum exactly matches the same current approved Program with only `workflow.editorial` reverted to `needs_review`, proving lifecycle-only drift from an external Approve action.

Every unrelated checksum mismatch remains `CONFLICT` before mutation.

### Tours

Stable identity: `STT-######`.

Existing Tour records require the current canonical checksum. New records do not provide one. Stable IDs are allocated only by Tour Intelligence.

## Operations

Per-record operations:
- `CREATE`
- `UPDATE`
- `UNCHANGED`
- `ARCHIVE`
- `CONFLICT`
- `ERROR`

`validate` performs preflight only. `apply` performs the accepted mutation.

## Automatic live Umrah refresh

A Program already registered by Program Publishing Integration in `public_noindex` or `indexable` mode is a protected live Program.

For a protected live `UPDATE`:
1. the current Program must already be `approved`;
2. the prospective updated Program is revalidated as an approved WordPress candidate and must remain `READY`;
3. Direct Sync snapshots canonical Program state and the Publishing registry;
4. canonical source data is updated;
5. Program Intelligence approval is automatically re-applied through its real validation gate;
6. Program Publishing Integration renderer is revalidated against the updated canonical Program and current Hotel facts;
7. the existing registry entry receives the new Program hash and Hotel hash while preserving the same Stable ID, slug and route mode;
8. Public Master and Hub bridge state must remain exactly unchanged;
9. the response returns the new canonical checksum for the Sheet sidecar.

If any post-write validation, approval or renderer refresh fails, Direct Sync restores the Program snapshot and Publishing registry and returns an error. Manual Approve/Prepare is not required for a successful supported live update.

This path refreshes only a route that already exists. It must never create or open a new public route, enable Public Master, enable Hub, or change indexation mode.

Protected live archive remains separate and fail-closed with `PUBLIC_PROGRAM_ARCHIVE_REQUIRES_CONTROLLED_REVIEW` because archive intentionally removes a live Program from current inventory.

## Archive policy

Archive never means delete.

Umrah archive uses Program Intelligence source-removal intent + lifecycle transition and preserves archive snapshots. Protected live Umrah archive still requires its dedicated controlled rollout.

Tour archive keeps the `STT-*` entity, marks it archived/closed and preserves audit evidence.

## Publication safety

Direct Sync does not enable or request:
- Tour Public Master;
- new Tour public routes;
- indexation;
- sitemap;
- schema;
- canonical exposure;
- homepage exposure;
- new Umrah public routes.

Tour Direct Sync stores publication flags private/off. Public release remains independently controlled by the Tour v1.0 overlay.

For Umrah, Direct Sync may only refresh an already-live accepted route in place under the automatic live-refresh gate above.

## Google Sheets client

The shared Apps Script module reuses existing generators:
- Umrah: `ST_TDE_Exporter.gs`
- Tours: `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

Tour sync reuses hidden Z/AA for Stable ID and Expected Checksum.

Umrah sync uses hidden `ST Direct Sync State` sidecar storage so the legacy Home sheet remains visually unchanged.

Selected-row Umrah apply always runs validate first. A protected live UPDATE proceeds only when WordPress returns `auto_refresh_supported=true`; the operator confirmation explicitly reports automatic approval + route/hash refresh.

Full active-Umrah mass sync remains more restrictive and stays blocked on protected live updates until its separate all-or-nothing mass-update gate is accepted.

## Recovery

Existing JSON export/import workflows remain fallback/recovery evidence. Direct Sync does not remove them.

Repository merge alone never authorizes production installation, secret configuration, live synchronization, or a new public/indexation gate.

# Umrah `KALDIR` → Controlled Archive Runbook

Candidate release: Direct Sync v0.1.4.

This is an archive workflow, never a delete workflow.

## Operator intent

A checked `KALDIR` cell means:

> this exact source Program should leave the active sales/public flow and transition to the accepted archived lifecycle while retaining its Stable Program ID and audit history.

The `KALDIR` column is discovered by header text. Its physical spreadsheet column letter is not part of the contract.

Accepted header labels:

- `KALDIR`
- `Programı Kaldır`
- `Program Kaldır`

## Production menu flow

After v0.1.4 is production accepted:

`Server Turizm Sync → KALDIR → İşaretli Umrahları Arşivle`

The action performs:

1. find the `KALDIR` header dynamically;
2. collect checked rows only;
3. resolve each row through the hidden `ST Direct Sync State` sidecar;
4. require an existing `STP-*` Stable ID and expected checksum;
5. send a no-write `validate` request;
6. stop on any conflict/error;
7. display the exact Stable IDs to the operator;
8. require an explicit YES confirmation;
9. send `apply` with controlled-archive approval;
10. write returned archived checksum back to the Umrah sidecar.

## Live public/noindex Program

For an existing `public_noindex` Program, controlled archive is allowed only when all gates pass:

- exact Stable ID resolves to one Program;
- expected checksum equals current canonical checksum;
- source row matches the Program provenance row;
- Publishing registry entry exists and is still `public_noindex`;
- archive snapshot runtime is available;
- operator has explicitly confirmed the archive.

Successful apply:

- Program Intelligence lifecycle → `archived`;
- `STP-*` retained;
- source removal intent retained;
- Publishing registry mode → `prepared`;
- final public/noindex route therefore closes without deleting registry identity/slug history;
- Program Public Master remains unchanged;
- `/umre-1/` Hub master/bridge remains unchanged;
- Hotel relation global gate remains unchanged;
- audit event records the controlled archive.

If a postcondition fails, canonical lifecycle and Publishing registry are rolled back from snapshots.

## Indexable Program

Automatic KALDIR archive is intentionally blocked for a registry mode of `indexable`.

Expected fail-closed error:

`INDEXABLE_PROGRAM_ARCHIVE_REQUIRES_SEO_REVIEW`

Reason: removing an indexable commercial URL requires an explicit SEO/redirect/archive policy. Direct Sync must not silently make that decision.

## Missing Direct Sync state

If a checked row has no sidecar Stable ID/checksum, archive is blocked before WordPress mutation.

First connect/sync the Program normally. Never guess an `STP-*` or checksum.

## Idempotency

After a successful archive, the returned canonical checksum is saved to the sidecar.

Running the archive check again for the same checked row should return:

`UNCHANGED`

No duplicate archive entity and no delete occurs.

## Row policy

Do not delete the Sheet row after archive solely to make the Sheet look cleaner.

Recommended operational state:

- keep the row as historical source evidence;
- keep `KALDIR` checked;
- treat the row as closed/read-only except for documented correction work.

## Safety invariants

- `KALDIR` is explicit operator intent, not inferred from a missing row;
- Sheet disappearance is never interpreted as delete/archive;
- Stable ID is immutable;
- archive is lifecycle mutation, not hard delete;
- public/noindex archive requires validate + explicit confirmation;
- indexable archive remains separate SEO review;
- no global Public Master/Hub toggle may drift during controlled archive.

# STTI v0.9.0 — First Real Full Tour Contract

Status: candidate until PR runtime acceptance and explicit owner-approved merge.

Pilot contract: `STTI-REAL-TOUR-PILOT-1.0.0`

## Authority

Real record: `STT-000001` / `IRN-2026-01` / `Büyük İran Turu`.

Business facts are bound to `examples/operator-sheet-snapshots/culture-tours-2026-09-14.csv`, row 3. The current source dates are `2027-01-26` through `2027-02-12` (17 nights / 18 days). Historical 2026 Iran fixture dates are not authoritative for this pilot.

## Truth policy

- Source-backed facts may be projected into the private canonical pilot.
- Deterministic values such as itinerary day numbers/dates may be derived from exact source dates.
- Missing Hotel, Transport and day-by-day business details remain empty/null/unknown.
- Raw operator booleans with undefined customer semantics are preserved as evidence but not converted into unsupported customer claims.
- No production approval is claimed by the disposable review fixture.

## Route / relation policy

One exact source-order Route Variant may be marked primary+confirmed for disposable runtime review. It references no Hotel or Transport relations when the operator source provides none. No missing relation is invented.

## Geo policy

Route coordinates must be explicit `external_reference` evidence with source references and confirmed private review status. Browser geocoding/localStorage never becomes canonical authority.

## Runtime evidence

Disposable WordPress must prove:
1. source-bound fixture loads;
2. relation and geo gates are ready for the asserted graph;
3. accepted v0.8 renderer consumes the record;
4. `STT-000001` can be allocated/persisted in the disposable canonical table;
5. audit evidence is emitted;
6. stored checksum equals stored canonical JSON;
7. stored candidate renders end-to-end;
8. test cleanup restores Tour count, audit count and Stable-ID sequence exactly.

## Release locks

Public routes, indexation, sitemap, schema, canonical/robots changes, homepage exposure, mass URL generation and production deployment remain OFF. Merge requires explicit owner approval.

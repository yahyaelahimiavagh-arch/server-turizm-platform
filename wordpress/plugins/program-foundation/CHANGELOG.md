# Changelog

## 0.2.0 — 2026-08-28 — H6B candidate
- H6A runtime acceptance preserved.
- Added one-way Program → Stable Hotel ID relationship graph.
- Added derived Hotel → Programs lookup without mirrored Hotel metadata.
- Added Relations admin page with reverse-derivation proof.
- Added read-only Program-side Hotel relation inspector.
- Added read-only Hotel-side Program usage inspector.
- Added `STP-*` → eligible Program public target resolver for Hotel editorial link intents.
- Public Program links remain blocked unless lifecycle is active and the assigned internal public target is published/public/index-eligible.
- No changes to Hotel Intelligence v0.8.0 code, Hotel Stable IDs, current `/umre-1/` rendering, Semantic Adapter v0.33.0, or Google Sheets 36-column HTML generator.

## 0.1.0 — 2026-08-28 — H6A runtime accepted
- Added isolated private Program Entity foundation.
- Added immutable `STP-000001` Stable Program IDs.
- Added minimal versioned Program data contract.
- Added real Stable Hotel ID validation (`STH-xxxxxx`) without name/URL guessing.
- Added CSV/TSV/JSON Data Bridge with Dry Run and CREATE/UPDATE/UNCHANGED/CONFLICT/INVALID semantics.
- Added source-key bootstrap identity for idempotent first import.
- Added audit batches and conflict-aware rollback.
- Added Google Sheets helper that preserves the existing 36-column HTML generator.

# STTI v0.6.0-A — JSON Import Contract

**Contract:** `STTI-TOUR-IMPORT-1.0.0`  
**Status:** DESIGN CANDIDATE — requires acceptance before importer implementation  
**Canonical target:** existing `STTI-TOUR-1.1.0` private candidate model  

## 1. Purpose

This contract is the external interchange format between Excel / Google Sheets / AI / manual JSON and **Server Turizm Tour Intelligence**. It deliberately does **not** expose publication controls. Importing a JSON document can never request a public route, sitemap entry, indexation, schema output, canonical change, or homepage visibility.

The workflow this contract is designed for is:

`Excel / Sheet → PARTIAL JSON → AI completion → FULL JSON → STTI Validate → Dry Run → Diff → controlled private import`

Only the first item — the JSON contract — is implemented in this package. There is **no WordPress importer, spreadsheet, Apps Script, or AI prompt in v0.6.0-A**. Those are later gates.

## 2. Two modes

### `mode = partial`
For incomplete source data. Omitted sections are valid. Minimum identity is `public_title + language`. Missing facts must be omitted, `null`, or represented by an existing explicit `unknown` enum.

Typical producer: Excel / Google Sheet.

### `mode = full`
Means **structurally complete for STTI review**, not “every real-world fact is known”. All major sections must exist, but unsupported facts can remain `null`, empty arrays, or explicit `unknown`.

Typical producer: AI completion after receiving source material plus a Partial JSON.

## 3. Non-negotiable fact policy

Every document must contain:

```json
"policy": {
  "facts": "source_only_no_invention",
  "missing_values": "omit_or_null_or_explicit_unknown",
  "publication": "force_private"
}
```

An AI is never authorized to invent hotels, flights, meals, visa rules, inclusions, prices, dates, city stops or itinerary facts. Deterministic derivations such as duration from exact start/end dates are allowed and will be recomputed by STTI.

## 4. Stable identity / optimistic concurrency

`target.stable_id = null` means a new private candidate. Future importer allocates the next STT stable ID.

For an update, `target.stable_id` must be an existing `STT-000000` style ID and `target.expected_checksum_sha256` must carry the checksum observed by the caller. A mismatch must become `CONFLICT`, never a blind overwrite.

Stable ID is immutable. Tour title, slug, dates and destination are not identity.

## 5. Source model

`sources[]` is mandatory. Each source receives a local ID such as `SRC-1`. Structured items may optionally reference one or more `source_ids`. This makes later AI completion and human review traceable without forcing tedious field-level provenance for every scalar value.

`tour.provenance.primary_source_id` selects the primary source and `source_completeness` retains the existing STTI semantics:

- `source_complete`
- `source_partial`
- `source_minimal`

## 6. Canonical mapping decisions

The importer gate that follows this contract will normalize into existing `STTI-TOUR-1.1.0`:

| Import contract | Canonical STTI | Rule |
|---|---|---|
| `tour.identity.*` | `identity.*` | direct normalize |
| `tour.destinations.*` | `destinations.*` | arrays become canonical arrays/CSV mirrors |
| `tour.date.*` | `date.*` | exact dates recompute duration + temporal |
| `tour.route.*` | `route.*` | missing stop IDs allocated sequentially |
| `tour.itinerary[]` | `itinerary[]` | exact dates can synchronize day dates |
| `tour.stays.hotels[]` | `stays.hotels[]` | relations only; Hotel Intelligence data is not duplicated |
| `tour.transport.segments[]` | `transport.segments[]` | direct normalize |
| `tour.pricing.*` | `pricing.*` | exact/from requires amount |
| `tour.services.*` | `services.*` | source-confirmed only |
| `tour.requirements.*` | `requirements.*` | `unknown` is valid |
| `tour.media.items[]` | `media.items[]` | reference-only contract; no binary embedding |
| `tour.content.*` | `content.*` | source-grounded copy |
| `tour.lifecycle.*` | canonical lifecycle | temporal remains system-derived |
| `sources + provenance` | canonical provenance | primary source becomes canonical source type/ref |

**No external `publication` object exists by design.** The future importer will always create/update a private candidate and preserve every public release lock as OFF.

## 7. Derived / authoritative fields

Future importer must treat these as system-authoritative:

- stable ID allocation
- checksum
- `updated_at` / audit event
- exact-date duration
- temporal state for exact dates
- canonical CSV mirrors
- public/indexation/sitemap/schema/canonical flags

If imported values conflict with deterministic derivation, Dry Run must report the difference and use the deterministic result.

## 8. Hotel relation rule

Two valid forms:

```json
{"mode":"hotel_intelligence","hotel_stable_id":"STH-..."}
```

or

```json
{"mode":"unresolved","unresolved_name":"Source hotel name"}
```

The import contract never duplicates Hotel Intelligence facts. Resolver/relation automation is a later gate.

## 9. Validation result expected in next gate

The future importer must return one of:

- `CREATE`
- `UPDATE`
- `UNCHANGED`
- `CONFLICT`
- `INVALID`

with separate `errors`, `warnings`, normalized preview and field-level diff. No write is allowed before explicit Import confirmation.

## 10. Acceptance boundary for v0.6.0-A

This gate is accepted when:

1. JSON Schema itself validates.
2. Partial Iran example validates.
3. Full structural Iran example validates without invented facts.
4. A document attempting to include external publication controls is rejected.
5. Update target without an expected checksum is rejected.
6. Unresolved hotel without a source name is rejected.
7. Exact/from price without amount is rejected.
8. Full mode missing a major section is rejected.

After acceptance, **v0.6.0-B = JSON Import + Validate + Dry Run only**. No Excel work begins until that runtime gate passes.

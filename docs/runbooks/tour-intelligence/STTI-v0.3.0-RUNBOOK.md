# STTI v0.3.0 — Structured Authoring Runtime Gate

## Install
1. WordPress → Plugins → Add New → Upload Plugin.
2. Upload the v0.3.0 REPLACE ZIP.
3. Choose **Replace current version**.
4. Open Tour Intelligence and verify the badge shows `STTI · STRUCTURED AUTHORING v0.3.0`.

## Invariants before testing
- Existing candidate `STT-000001` must still exist.
- Candidate count must remain 1 before any new fixture promotion.
- Public Renderer OFF.
- Public Routes OFF.
- Sitemap OFF.
- Indexation OFF.
- Homepage Adapter OFF.
- Frontend writes 0.

## Runtime Gate A — Open without save
Open `STT-000001`.
Expected:
- Route Builder is populated from the existing canonical route summary.
- Exact dates remain 16/10/2026 → 24/10/2026.
- Duration is read-only 9 days / 8 nights.
- Day Builder creates nine empty date shells without inventing itinerary content.
- Primary price remains 899 EUR, basis UNKNOWN.
- Structured price row may mirror the primary price with no added business fact.
- Hotel/Transport/Services/Requirements remain empty unless source-backed.

Do NOT save yet. Download `Taslak JSON İndir` and inspect.

## Runtime Gate B — Controlled structured save
After draft review, click `Private Kaydet`.

Expected:
- Stable ID remains `STT-000001`.
- Candidate count remains 1.
- Schema becomes `STTI-TOUR-1.1.0`.
- Route stops and itinerary shells appear in canonical payload.
- Audit event is `candidate_updated` because payload structure changed.
- No public output is produced.

Download `Tüm JSON Evidence İndir`.

## Runtime Gate C — Same-data save
Save the exact same structured payload again.

Expected:
- Stable ID unchanged.
- Candidate count unchanged.
- Checksum unchanged.
- Audit event `candidate_unchanged`.

## Runtime Gate D — Builder edit
Add one temporary source-neutral note to one structured field, save, inspect audit, then revert.
Expected:
- before/after audit is correct.
- stable ID immutable.
- checksum changes and then returns after revert.

## Fail closed
Stop if:
- candidate count unexpectedly increases,
- stable ID changes,
- any public route is created,
- sitemap/indexation/schema output changes,
- frontend writes become non-zero,
- existing v0.2.3 audit history disappears.

## Next branch after acceptance
Private Renderer Pilot for Tour Intelligence, still private/noindex/non-sitemap and with Public Master OFF.

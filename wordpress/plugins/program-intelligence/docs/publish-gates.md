# Publish Gates v1.0

## Gate A - Contract

- Schema version is exactly 1.0.0.
- Required enums and structures are valid.
- Stable IDs have valid shapes and no duplicates.
- Repeated payloads are idempotent.

## Gate B - Identity and source

- Program code and title are present.
- Source type is recorded.
- Approved or published content has `verified_at` and a human verifier.
- Existing Program IDs are never reassigned or recycled.

## Gate C - Dates and itinerary

- `scheduled_departure` requires exact start and end dates.
- End date cannot precede start date.
- Segment sequence is explicit and increasing.
- Missing dates stay missing; the importer never shifts transition/return roles.
- Month/season/unknown date programs use an interest or request-information CTA.

## Gate D - Hotels

- Every supplied STH-* resolves to exactly one Hotel Intelligence record.
- Approved/published records cannot contain unresolved or duplicate STH-* references.
- Explicit hotel-TBD labels are permitted only where the public wording remains truthful.
- Hotel images, public URL, official name, city and verified star rating are read from Hotel Intelligence.

## Gate E - Prices and capacity

- Currency is explicit.
- Price type is fixed, from or on_request.
- Amounts are numeric and non-negative.
- Occupancy and unit are explicit.
- Child pricing is structured by age/bed rule.
- Sold-out and limited labels come from availability facts, never a visual countdown trick.

## Gate F - Public and SEO readiness

- Visible facts and machine-readable facts match.
- Unknown facts are omitted, not invented.
- Canonical identity, index policy and archive state agree.
- A factual summary, internal relationships and media provenance exist.
- High-value unique program pages may index; thin or incomplete campaign records remain noindex.

## Gate G - Human approval

Dates, prices, hotel selection, archive action and public publishing require an explicit authorized approval event. No Google Sheets export can bypass this gate.

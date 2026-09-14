# STTI v0.7.1 — Canonical Geo Resolver Contract

## Purpose
Replace browser-side route geocoding/cache authority with explicit, reviewable canonical route-stop coordinates stored in the STTI payload.

## Canonical shape
`payload.geo.contract = STTI-GEO-1.0.0`

Each `payload.geo.route_stops[]` record contains:
- `stop_ref` — exact canonical `route.stops[].stop_id`
- `state` — derived only: `resolved | unresolved`
- `latitude`, `longitude` — explicit coordinates or null
- `source_type` — `unknown | source | manual_verified | external_reference | hotel_intelligence`
- `source_ref` — evidence/reference string
- `review_status` — `pending | confirmed | rejected`
- `review_note`
- server-owned `reviewed_by`, `reviewed_at`

## Non-negotiable truth rules
- No coordinate guessing.
- No browser geocoder is renderer-authoritative.
- No localStorage coordinate cache is renderer-authoritative.
- Missing coordinate remains unresolved.
- Missing `stop_id` remains a blocker; order-based fallback IDs are not canonical identity.
- Confirmed coordinates require source type + source reference.
- Reviewer identity/time is stamped server-side.
- Customer map receives only `resolved + confirmed` points.
- Public routes, indexation, sitemap, schema, canonical/robots and homepage adapter remain OFF.

## Approval rule
`editorial=approved` fails closed until all current canonical route stops have source-backed, human-confirmed geo records.

## Storage / migration
No new DB table and no schema migration. Geo lives inside the existing canonical JSON payload and therefore follows the existing checksum/audit lifecycle.

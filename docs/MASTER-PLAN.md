# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v0.7.0 ACCEPTED / v0.7.1 CANONICAL GEO ACTIVE

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> This file supersedes the prior current Master Plan. Deep historical material remains preserved in `docs/archive/MASTER-PLAN-through-2026-09-14-pre-v070.md`. No statement below authorizes production deployment unless explicitly stated.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative repository branch: `main`

Verified `main` HEAD after owner-approved PR #4 merge:

`21ca3ea3a81400f07a754d996df0ebcb802e6bf5`

Merge title:

`STTI v0.7.0 — WordPress Review Relations`

### Repository / disposable runtime state

`STTI v0.7.0 — WordPress Review Relations` is **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted gates:
- 23/23 local relation assertions PASS;
- PHP/JS checks PASS;
- Baseline Verification PASS;
- WordPress + MariaDB runtime PASS;
- v0.6.5 AI Completion NO-WRITE matrix preserved;
- Route/Hotel/Transport Human Review runtime PASS;
- Hotel resolver uses `STH-######` identity only and does not duplicate Hotel facts;
- Tour public/indexation/sitemap/schema/canonical/homepage locks remain OFF;
- replacement ZIP/SHA256 package workflow PASS.

### Production/live distinction

The repository merge is **not a live-site deployment**. `docs/CURRENT-RUNTIME-INVENTORY.md` remains the authority for what is actually installed on production. Do not relabel a repository-only accepted version as live merely because CI passed.

---

## 2. CURRENT ACTIVE STAGE — STTI v0.7.1 CANONICAL GEO RESOLVER

Goal: remove browser geocoding/cache authority from Tour route maps and replace it with explicit, reviewable canonical coordinates.

### Canonical geo contract

`payload.geo.contract = STTI-GEO-1.0.0`

Each route-stop geo record is keyed by exact canonical `route.stops[].stop_id` and stores:
- `stop_ref`;
- derived `state = resolved | unresolved`;
- explicit `latitude`, `longitude` or null;
- `source_type`;
- `source_ref`;
- `review_status = pending | confirmed | rejected`;
- `review_note`;
- server-owned `reviewed_by`, `reviewed_at`.

### Truth policy

v0.7.1 MUST NOT:
- invent a stop ID from route order;
- infer coordinates from city/country text;
- treat Nominatim/browser fetch output as canonical;
- treat browser `localStorage` as canonical;
- auto-confirm coordinates;
- silently use pending/rejected coordinates in the renderer;
- expose unresolved coordinates as if verified.

Missing coordinates are valid private data and remain `unresolved`.

### Renderer policy

The private Customer Preview may render only geo rows that are:

`resolved + source-backed + human-confirmed`

Pending/unresolved/rejected rows are excluded from map points. The historical Nominatim/localStorage pilot is neutralized as an authority. Existing renderer/theme/header behavior remains separate and continues to be improved in v0.8.0.

### Approval policy

`editorial=approved` fails closed until every current canonical route stop has a source-backed, human-confirmed geo record.

No DB migration is required. Geo lives inside the existing canonical payload and participates in the existing checksum/audit lifecycle.

---

## 3. v0.7.0 RELATION BASELINE — PRESERVE

Keep all accepted Review Relations semantics:
- explicit Route Variants;
- Hotel Options;
- Hotel Intelligence links by Stable ID only;
- explicit Transport relation refs;
- `pending | confirmed | rejected` Human Review;
- reviewer/timestamp owned by WordPress server;
- unknown remains unknown;
- zero primary variants allowed;
- zero selected Hotel option allowed;
- contradictory multiple primaries/selections blocked;
- active relations cannot reference rejected rows;
- unresolved/pending relation graph blocks editorial approval.

Hotel Intelligence continues to own Hotel facts. STTI stores only relation identity and Tour-specific context.

---

## 4. v0.6.5 NO-WRITE CONTRACT — PRESERVE

Mandatory regression matrix:

```text
Valid AI completion                     REVIEW / NO WRITE
Tampered source hash                    INVALID / NO WRITE
Wrong deterministic duration            INVALID / NO WRITE
Public/indexable request                INVALID / NO WRITE
Accepted PARTIAL import                 CREATE proposal / NO WRITE
Tour table                              unchanged during dry-run
Audit table                             unchanged during dry-run
Stable-ID sequence                      unchanged during dry-run
```

v0.7.1 does not turn AI Completion or JSON dry-run into automatic canonical write paths.

---

## 5. PUBLIC / SEO LOCKS — HARD OFF

Remain OFF:

```text
Tour Public Master                 OFF
Tour public routes                 OFF
Tour indexation                    OFF
Tour sitemap inclusion             OFF
Tour schema output                 OFF
Tour canonical/robots output       OFF / UNTOUCHED
Tour homepage adapter              OFF
Automatic publication              OFF
Mass Tour URL generation           OFF
```

Private renderer/runtime acceptance is never permission to publish.

---

## 6. v0.7.1 ACCEPTANCE GATES

Before merge:
1. Canonical Geo unit contract PASS;
2. all PHP lint PASS;
3. Geo admin JS syntax PASS;
4. Canonical map JS syntax PASS;
5. v0.7.0 relation contract remains PASS;
6. v0.6.5 static/no-write regression remains PASS;
7. disposable WordPress + MariaDB runtime loads v0.7.1;
8. runtime proves confirmed geo reaches map config;
9. runtime proves pending/unresolved geo is excluded;
10. runtime proves map config contains no geocoder/cache authority;
11. runtime proves geo analysis performs NO WRITE;
12. approval fails closed when geo is incomplete;
13. all public/SEO locks remain OFF;
14. replacement ZIP/SHA256 generated;
15. no production deployment;
16. merge requires explicit owner approval.

Current local candidate evidence:

```text
Canonical Geo assertions              22 / 22 PASS
PHP syntax                             PASS
geo-resolver.js syntax                 PASS
canonical-geo-map.js syntax            PASS
```

Local evidence is candidate evidence only until PR runtime gates pass.

---

## 7. EXECUTION ORDER TO FINISH TOUR INTELLIGENCE

```text
1. STTI v0.7.0 — Review Relations                 CLOSED / ACCEPTED
2. STTI v0.7.1 — Canonical Geo Resolver           ACTIVE NOW
3. STTI v0.8.0 — Complete Customer Renderer       NEXT
4. STTI v0.9.0 — First Real Full Tour
5. STTI v1.0 — Controlled Public Tour Pilot
6. Unified Google Sheets Direct Sync — Umrah + Tours
```

### v0.8.0 — Complete Customer Renderer
- consume canonical route variants;
- consume reviewed Hotel options/Hotel Intelligence identity;
- consume canonical geo only;
- preserve missing facts without filler;
- remain private until public gates are separately approved.

### v0.9.0 — First Real Full Tour
Use one source-complete real Tour end-to-end with source evidence, reviewed relations, reviewed geo, renderer QA and audit evidence.

### v1.0 — Controlled Public Tour Pilot
Only after explicit owner approval: one intentional route, Public Master default OFF, separate sitemap/schema/indexation/canonical gates and fast rollback.

---

## 8. UMRAH / PROGRAM INTELLIGENCE — PRESERVE

Umrah canonical system remains complete for its current scope. Do not redesign it during Tour completion.

Operational work remaining for Umrah is connection to the future shared Direct Sync foundation, not a new canonical model.

Preserve:
- immutable `STP-*` identity;
- archive-never-delete lifecycle;
- `/umre-1/` as dynamic commercial Hub;
- Hotel facts owned by Hotel Intelligence;
- protected fixtures;
- no mass Program indexation.

---

## 9. GOOGLE SHEETS — ONE SHARED FINAL CONNECTOR

Do not build separate permanent direct-sync infrastructures for Umrah and Tours.

Final target:

```text
Google Sheet
→ Siteyi Güncelle
→ authenticated request
→ identity/checksum validation
→ Umrah or Tour adapter
→ validate-before-write
→ create / update / archive / unchanged / error
→ audit evidence
→ operator result
```

Required shared properties:
- signed/authenticated requests;
- timestamp/nonce replay protection;
- idempotency;
- immutable `STP-*` / `STT-*` Stable IDs;
- expected-checksum conflict protection;
- per-record errors;
- archive-never-delete where lifecycle applies;
- JSON fallback/recovery;
- no automatic public/indexable exposure.

Current Tour Sheet `v0.6.2.1` remains a source-only PARTIAL JSON producer; it does not write WordPress.

---

## 10. SEO OPERATING STATE

2026-09-14 weekly SEO gate remains CLOSED / PASS for continuing Tour engineering.

Non-blocking backlog:
- classify Crawled-currently-not-indexed URLs;
- verify intentional robots-blocked URL;
- later improve CTR for `/umre-vizesi-nasil-alinir/`;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
- monitor Hotel query growth;
- no mass request-indexing/validation.

---

## 11. REPOSITORY OPERATING METHOD

- local design/lint/tests first;
- one coherent feature branch;
- one PR for final CI/runtime review;
- no repeated Actions polling;
- no direct live-site edits from engineering branch;
- no automatic Merge;
- explicit owner approval required for Merge.

---

## 12. PROJECT PROGRESS — PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform        ~83%
Tour Intelligence                             ~80% after accepted v0.7.0
Umrah canonical system                       100% current system scope
Umrah including direct Sheet operations       ~88%
Tour Sheet operating pipeline                 ~55%
Unified direct Sheets → WordPress foundation  ~25%
```

Do not advance the Tour percentage for v0.7.1 until runtime acceptance. After v0.7.1 acceptance, expected Tour planning progress is approximately `84%`.

Remaining path:

`Geo → Renderer → Real Tour → Controlled Pilot → Unified Sheet Connector`

---

## 13. NEXT CHECKPOINT

> Finish `STTI v0.7.1 — Canonical Geo Resolver`, run baseline + disposable WordPress/MariaDB runtime through one release PR, inspect final evidence, and stop before Merge until explicit owner approval.

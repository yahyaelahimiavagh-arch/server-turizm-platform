# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v0.7.1 ACCEPTED / v0.8.0 COMPLETE CUSTOMER RENDERER ACTIVE

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> Deep historical material remains preserved in `docs/archive/MASTER-PLAN-through-2026-09-14-pre-v070.md`. Nothing in this document authorizes production deployment unless explicitly stated.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative repository branch: `main`

Verified `main` HEAD after owner-approved PR #5 merge:

`4e543dfd7523ba81b4188489e7dd7ce6e9016c9d`

Merge title:

`Merge PR #5: STTI v0.7.1 — Canonical Geo Resolver`

### Accepted repository/runtime baseline

`STTI v0.7.1 — Canonical Geo Resolver` is **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted facts:
- v0.7 Review Relations preserved;
- v0.6.5 AI Completion NO-WRITE matrix preserved;
- canonical route-stop coordinates are explicit and reviewable;
- pending/unresolved/rejected geo never becomes canonical map authority;
- browser Nominatim/localStorage is not canonical authority;
- confirmed map points are source-backed and human-confirmed;
- rejected geo remains fail-closed until replaced;
- public/indexation/sitemap/schema/canonical/homepage locks remain OFF;
- replacement ZIP/SHA256 workflow passed;
- no production/live-site deployment occurred.

`docs/CURRENT-RUNTIME-INVENTORY.md` remains the authority for what is actually installed on production.

---

## 2. CURRENT ACTIVE STAGE — STTI v0.8.0 COMPLETE CUSTOMER RENDERER

Goal: replace the legacy private customer-preview projection with one read-only renderer that consumes the reviewed canonical Tour graph directly.

Renderer contract:

`STTI-CUSTOMER-RENDERER-1.0.0`

### Required behavior

The v0.8 renderer MUST:
- read the canonical STTI payload directly;
- consume reviewed Route Variants;
- consume reviewed Hotel Options;
- resolve Hotel Intelligence only by `STH-######` identity;
- read Hotel display facts live from Hotel Intelligence without copying them into STTI;
- consume reviewed Transport relations;
- consume only v0.7.1 canonical geo;
- exclude pending/rejected relations from customer HTML;
- preserve unknown/missing facts without filler;
- perform zero canonical writes;
- remain private/admin-only/noindex;
- keep every public/SEO gate OFF.

### Route selection policy

If exactly one confirmed `role=primary` Route Variant exists, it drives the customer route.

If reviewed variants exist but no confirmed primary exists, the renderer MUST NOT infer or choose a route.

If no Route Variant objects exist at all, the accepted legacy canonical stop sequence may be shown as a backward-compatible private fallback.

### Hotel projection policy

Hotel facts remain owned by Hotel Intelligence.

STTI stores only Tour relation identity/context. During private rendering, Hotel display name/city/star/media may be read from the resolved Hotel Intelligence entity. Those facts are not persisted into the Tour payload.

### Map policy

Only active-route stops with `resolved + human-confirmed` canonical geo become map points.

The v0.8 browser shell contains no geocoding or browser coordinate cache logic. Leaflet/OpenStreetMap remain display infrastructure only.

---

## 3. v0.7.1 CANONICAL GEO BASELINE — PRESERVE

Preserve:
- exact `route.stops[].stop_id` identity;
- explicit latitude/longitude;
- source type/reference;
- pending/confirmed/rejected Human Review;
- server-owned reviewer/timestamp;
- no city/country coordinate guessing;
- no browser geocoder authority;
- no localStorage coordinate authority;
- no DB migration;
- approval fail-closed when canonical geo is incomplete.

---

## 4. v0.7.0 REVIEW RELATIONS BASELINE — PRESERVE

Preserve:
- Route Variants;
- Hotel Options;
- Hotel Intelligence Stable-ID links only;
- Transport relation refs;
- Human Review states;
- zero primary variants allowed at canonical-data level;
- zero selected Hotel option allowed at canonical-data level;
- contradictory multiple primary/selected states blocked;
- rejected relations cannot be used by active relations;
- unresolved/pending relation graph blocks editorial approval;
- unknown remains unknown.

---

## 5. v0.6.5 NO-WRITE CONTRACT — PRESERVE

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

v0.8.0 does not turn AI Completion, JSON dry-run or renderer analysis into automatic canonical writes.

---

## 6. PUBLIC / SEO LOCKS — HARD OFF

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

Private renderer acceptance is never permission to publish.

---

## 7. v0.8.0 ACCEPTANCE GATES

Before merge:
1. all PHP syntax PASS;
2. v0.8 browser shell JS syntax PASS;
3. existing v0.6.5 NO-WRITE contract remains PASS;
4. existing v0.7 relation gates remain PASS;
5. existing v0.7.1 canonical geo gates remain PASS;
6. disposable WordPress + MariaDB activates the v0.8 package;
7. runtime proves one confirmed primary variant drives the route;
8. runtime proves no primary is ever inferred when reviewed variants exist without one;
9. runtime proves pending Hotel/Transport relations are excluded;
10. runtime proves Hotel facts are live-read from Hotel Intelligence;
11. runtime proves Tour payload is not mutated by renderer analysis;
12. runtime proves canonical map config has no geocoder/cache authority;
13. runtime proves Tour/audit/Stable-ID sequence are unchanged;
14. all public/SEO locks remain OFF;
15. replacement ZIP/SHA256 generated;
16. no production deployment;
17. merge requires explicit owner approval.

Current candidate evidence before PR:

```text
v0.8 local renderer assertions          13 / 13 PASS
PHP syntax                              PASS
customer-shell-v080.js syntax           PASS
```

Local evidence remains candidate evidence until final PR runtime gates pass.

---

## 8. EXECUTION ORDER TO FINISH TOUR INTELLIGENCE

```text
1. STTI v0.7.0 — Review Relations                 CLOSED / ACCEPTED
2. STTI v0.7.1 — Canonical Geo Resolver           CLOSED / ACCEPTED
3. STTI v0.8.0 — Complete Customer Renderer       ACTIVE NOW
4. STTI v0.9.0 — First Real Full Tour             NEXT
5. STTI v1.0 — Controlled Public Tour Pilot
6. Unified Google Sheets Direct Sync — Umrah + Tours
```

### v0.9.0 — First Real Full Tour
Use one source-complete real Tour end-to-end with source evidence, reviewed relations, reviewed geo, renderer QA and audit evidence.

### v1.0 — Controlled Public Tour Pilot
Only after explicit owner approval: one intentional route, Public Master default OFF, separate sitemap/schema/indexation/canonical gates and fast rollback.

---

## 9. UMRAH / PROGRAM INTELLIGENCE — PRESERVE

Umrah canonical system remains complete for its current scope. Do not redesign it during Tour completion.

Operational work remaining for Umrah is connection to the future shared Direct Sync foundation.

Preserve:
- immutable `STP-*` identity;
- archive-never-delete lifecycle;
- `/umre-1/` as dynamic commercial Hub;
- Hotel facts owned by Hotel Intelligence;
- protected fixtures;
- no mass Program indexation.

---

## 10. GOOGLE SHEETS — ONE SHARED FINAL CONNECTOR

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

## 11. SEO OPERATING STATE

2026-09-14 weekly SEO gate remains CLOSED / PASS for continuing Tour engineering.

Non-blocking backlog:
- classify Crawled-currently-not-indexed URLs;
- verify intentional robots-blocked URL;
- later improve CTR for `/umre-vizesi-nasil-alinir/`;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
- monitor Hotel query growth;
- no mass request-indexing/validation.

---

## 12. REPOSITORY OPERATING METHOD

- local design/lint/tests first;
- one coherent feature branch;
- one PR for final CI/runtime review;
- no repeated Actions polling;
- no direct live-site edits from engineering branch;
- no automatic Merge;
- explicit owner approval required for Merge.

---

## 13. PROJECT PROGRESS — PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform        ~84%
Tour Intelligence                             ~84% after accepted v0.7.1
Umrah canonical system                       100% current system scope
Umrah including direct Sheet operations       ~88%
Tour Sheet operating pipeline                 ~55%
Unified direct Sheets → WordPress foundation  ~25%
```

Do not advance Tour progress for v0.8.0 until runtime acceptance. After v0.8.0 acceptance, expected Tour planning progress is approximately `89%`.

Remaining path:

`Renderer → Real Tour → Controlled Pilot → Unified Sheet Connector`

---

## 14. NEXT CHECKPOINT

> Finish `STTI v0.8.0 — Complete Customer Renderer`, run baseline + disposable WordPress/MariaDB runtime through one release PR, inspect final evidence once, and stop before Merge until explicit owner approval.

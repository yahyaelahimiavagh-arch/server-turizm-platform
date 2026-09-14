# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v0.8.0 ACCEPTED / v0.9.0 FIRST REAL FULL TOUR ACTIVE

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> Deep historical material remains preserved in `docs/archive/MASTER-PLAN-through-2026-09-14-pre-v070.md`. Nothing here authorizes production deployment unless explicitly stated.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative repository branch: `main`

Verified `main` HEAD after owner-approved PR #6 merge:

`3e7a7e570efe140a8e0243f2aabce23c92fd5804`

Merge title:

`STTI v0.8.0 — Complete Customer Renderer`

### Accepted repository/runtime baseline

`STTI v0.8.0 — Complete Customer Renderer` is **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted behavior:
- exact confirmed-primary Route Variant drives the private renderer;
- renderer never guesses a primary route;
- pending/rejected Hotel and Transport relations are excluded;
- Hotel facts remain owned by Hotel Intelligence and are live-read by `STH-######` identity;
- only canonical v0.7.1 geo reaches map points;
- browser geocoding/localStorage are not coordinate authority;
- v0.7.1 Canonical Geo and v0.7 Review Relations remain preserved;
- v0.6.5 AI Completion/import NO-WRITE matrix remains preserved;
- all Tour public/indexation/sitemap/schema/canonical/homepage locks remain OFF;
- no production/live-site deployment occurred.

`docs/CURRENT-RUNTIME-INVENTORY.md` remains authoritative for what is actually installed on production.

---

## 2. CURRENT ACTIVE STAGE — STTI v0.9.0 FIRST REAL FULL TOUR

Goal: prove one real operator Tour record end-to-end through current canonical identity, source binding, review graph, canonical geo, private renderer, persistence and audit — without inventing missing business facts and without touching production.

Pilot contract:

`STTI-REAL-TOUR-PILOT-1.0.0`

### Selected real record

```text
Stable ID             STT-000001
Program No            IRN-2026-01
Tour                   Büyük İran Turu
Country                İran
Current source dates   2027-01-26 → 2027-02-12
Duration               17 Gece / 18 Gün
Route                  Tahran → Kaşan → İsfahan → Yezd → Şiraz
2-person column        899 EUR
```

Authoritative business-fact source:

`examples/operator-sheet-snapshots/culture-tours-2026-09-14.csv`, row 3.

Source binding:
- repository Git blob SHA-1: `161f1df200c5e9222b35a7b4471d03eb8e6f88ca`;
- exact row SHA-256: `800255742ca2fbe07825519e484e8374f82c6245f848c8eb8932f9889ee92f1d`;
- operator expected checksum before this pilot: `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`.

### Supersession rule

Historical Iran fixture/example dates `2026-10-16 → 2026-10-24` are **not authoritative** for v0.9. The current operator snapshot says `2027-01-26 → 2027-02-12`; static tests must fail if the old dates re-enter the real pilot.

### Missing-fact policy

The operator row does not provide Hotel names, Hotel plan, Transport, Airline, transfer detail, or day-by-day itinerary detail.

Therefore v0.9 MUST NOT invent them.

Structural completeness is allowed to contain explicit empty/null/unknown facts. In particular:
- Hotel relations remain empty until sourced;
- Transport segments remain empty until sourced;
- 18 itinerary days may carry deterministic dates while factual daily content stays null;
- price remains `899 EUR` while unsupported price-basis semantics remain `unknown`;
- raw `Vize=FALSE` is preserved as source evidence but is not interpreted into a customer visa claim without defined operator semantics.

### Route review

The exact five-stop source route is represented as one confirmed primary private Route Variant. It references no Hotel or Transport relations because none are source-backed.

This is valid: missing Hotel/Transport facts are not blockers when no such active relation is asserted.

### Geo evidence

The five route stops use explicit `external_reference` coordinates with source references. These coordinates are private renderer evidence only. Browser geocoding remains forbidden as canonical authority.

The v0.9 disposable fixture does **not** claim production human approval.

---

## 3. v0.8.0 CUSTOMER RENDERER BASELINE — PRESERVE

Preserve `STTI-CUSTOMER-RENDERER-1.0.0`:
- canonical payload direct read;
- exact confirmed-primary selection;
- no primary inference;
- reviewed relations only;
- live Hotel Intelligence reads without Tour data duplication;
- canonical geo only;
- missing facts hidden/neutral, never fabricated;
- zero renderer writes;
- private/admin-only/noindex;
- all public/SEO gates OFF.

---

## 4. v0.7.1 CANONICAL GEO BASELINE — PRESERVE

Preserve:
- exact `route.stops[].stop_id` identity;
- explicit latitude/longitude;
- source type/reference;
- pending/confirmed/rejected Human Review;
- no city/country coordinate guessing;
- no browser geocoder/localStorage authority;
- approval fail-closed when asserted canonical geo is incomplete.

---

## 5. v0.7.0 REVIEW RELATIONS BASELINE — PRESERVE

Preserve:
- Route Variants;
- Hotel Options;
- Hotel Intelligence Stable-ID links only;
- Transport relation refs;
- Human Review states;
- contradictory multiple primary/selected states blocked;
- rejected active dependencies blocked;
- unknown remains unknown.

---

## 6. v0.6.5 NO-WRITE CONTRACT — PRESERVE

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

v0.9 does not convert AI Completion or JSON dry-run into automatic canonical writes. The only v0.9 write evidence is an explicit disposable WordPress runtime fixture that cleans itself up completely.

---

## 7. PUBLIC / SEO LOCKS — HARD OFF

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

Real-tour private acceptance is never permission to publish.

---

## 8. v0.9.0 ACCEPTANCE GATES

Before merge:
1. exact operator CSV Git blob binding PASS;
2. exact populated row SHA-256 binding PASS;
3. Stable ID `STT-000001` and Program No `IRN-2026-01` match source;
4. current 2027 dates override the historical 2026 fixture;
5. 18-day / 17-night duration is deterministic;
6. source five-stop order is exact;
7. absent Hotel/Transport facts remain absent;
8. day-by-day business details remain null where unsourced;
9. `Vize=FALSE` is not over-interpreted;
10. v0.7 relation graph is ready for the asserted route only;
11. all five explicit external-reference geo rows pass v0.7.1 readiness;
12. accepted v0.8 renderer renders the real record with five canonical map points;
13. disposable WordPress persists `STT-000001` into canonical Tour storage;
14. disposable runtime emits a dedicated audit event;
15. stored checksum matches stored canonical JSON;
16. stored candidate renders end-to-end;
17. test deletes its Tour/audit rows and restores Stable-ID sequence exactly;
18. v0.6.5/v0.7/v0.7.1/v0.8 regression gates remain PASS;
19. all public/SEO locks remain OFF;
20. replacement ZIP/SHA256 generated;
21. no production deployment;
22. merge requires explicit owner approval.

---

## 9. EXECUTION ORDER TO FINISH TOUR INTELLIGENCE

```text
1. STTI v0.7.0 — Review Relations                 CLOSED / ACCEPTED
2. STTI v0.7.1 — Canonical Geo Resolver           CLOSED / ACCEPTED
3. STTI v0.8.0 — Complete Customer Renderer       CLOSED / ACCEPTED
4. STTI v0.9.0 — First Real Full Tour             ACTIVE NOW
5. STTI v1.0 — Controlled Public Tour Pilot       NEXT
6. Unified Google Sheets Direct Sync — Umrah + Tours
```

### v1.0 — Controlled Public Tour Pilot
Only after explicit owner approval: one intentional public route, Public Master default OFF, separate sitemap/schema/indexation/canonical gates, no mass URL generation and fast rollback.

---

## 10. UMRAH / PROGRAM INTELLIGENCE — PRESERVE

Umrah canonical system remains complete for its current scope. Do not redesign it during Tour completion.

Operational work remaining for Umrah is connection to the future shared Direct Sync foundation.

Preserve immutable `STP-*` identity, archive-never-delete lifecycle, `/umre-1/` as the commercial Hub, Hotel Intelligence fact ownership, protected fixtures and no mass Program indexation.

---

## 11. GOOGLE SHEETS — ONE SHARED FINAL CONNECTOR

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

Required shared properties: authenticated signed requests, replay protection, idempotency, immutable Stable IDs, expected-checksum conflict protection, per-record errors, archive-never-delete where applicable, JSON fallback/recovery and no automatic public exposure.

Current Tour Sheet `v0.6.2.1` remains a source-only PARTIAL JSON producer; it does not write WordPress.

---

## 12. SEO OPERATING STATE

2026-09-14 weekly SEO gate remains CLOSED / PASS for continuing Tour engineering.

Non-blocking backlog remains unchanged: classify crawled-not-indexed URLs, verify the intentional robots-blocked URL, later improve `/umre-vizesi-nasil-alinir/` CTR, monitor 2027 Umrah queries and Hotel query growth, and avoid mass request-indexing/validation.

---

## 13. REPOSITORY OPERATING METHOD

- local/static checks first;
- one coherent feature branch;
- one PR for final CI/runtime review;
- no repeated Actions polling;
- no direct live-site edits from engineering branch;
- no automatic Merge;
- explicit owner approval required for Merge.

---

## 14. PROJECT PROGRESS — PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform        ~86%
Tour Intelligence                             ~89% after accepted v0.8.0
Umrah canonical system                       100% current system scope
Umrah including direct Sheet operations       ~88%
Tour Sheet operating pipeline                 ~55%
Unified direct Sheets → WordPress foundation  ~25%
```

Do not advance Tour progress for v0.9 until runtime acceptance. After v0.9 acceptance, expected Tour planning progress is approximately `94%`.

Remaining path:

`Real Tour → Controlled Public Pilot → Unified Sheet Connector`

---

## 15. NEXT CHECKPOINT

> Finish `STTI v0.9.0 — First Real Full Tour`, run the source-binding baseline and disposable WordPress/MariaDB persistence→audit→renderer→cleanup gate through one release PR, inspect final evidence once, and stop before Merge until explicit owner approval.

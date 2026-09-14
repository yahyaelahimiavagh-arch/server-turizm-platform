# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v0.9.0 ACCEPTED / v1.0.0 CONTROLLED PUBLIC TOUR PILOT ACTIVE

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> Historical detail remains preserved in the repository archive. Nothing in this document authorizes production deployment unless explicitly stated.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative repository branch: `main`

Verified `main` HEAD after owner-approved PR #7 merge:

`a580a67e46e30311e07de0a36f1516be3e250550`

Merge title:

`STTI v0.9.0 — First Real Full Tour`

### Accepted repository/runtime baseline

`STTI v0.9.0 — First Real Full Tour` is **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted evidence:
- real operator record `STT-000001 / IRN-2026-01 / Büyük İran Turu` is source-bound;
- current source dates are `2027-01-26 → 2027-02-12` = `17 Gece / 18 Gün`;
- exact source route is `Tahran → Kaşan → İsfahan → Yezd → Şiraz`;
- source price evidence is `899 EUR` from the two-person column, with unsupported price-basis semantics kept unknown;
- missing Hotel/Transport/day-detail facts are not invented;
- five explicit canonical geo references pass the accepted private renderer gate;
- disposable persistence → audit → render → cleanup passed;
- v0.8 renderer, v0.7.1 geo, v0.7 relations and v0.6.5 NO-WRITE regressions remained PASS;
- all public/SEO gates stayed OFF;
- no production deployment occurred.

`docs/CURRENT-RUNTIME-INVENTORY.md` remains authoritative for what is actually installed on production. It currently records the older Tour Intelligence live runtime; repository acceptance is not live deployment.

---

## 2. CURRENT ACTIVE STAGE — STTI v1.0.0 CONTROLLED PUBLIC TOUR PILOT

Goal: create the first intentionally releasable Tour surface without mass URL generation, automatic publication or implicit SEO exposure.

Contract:

`STTI-PUBLIC-PILOT-1.0.0`

Exact pilot:

```text
Stable ID   STT-000001
Route       /turlar/buyuk-iran-kultur-turu/
Policy      exact allowlist only
```

No wildcard route exists. No rewrite rule or rewrite flush is used.

---

## 3. RELEASE OVERLAY — NO CANONICAL FACT MUTATION

v1.0 public state is a controlled release overlay over canonical Tour data.

A candidate can become release-ready only when:
- exact allowlisted Stable ID exists;
- row editorial = `approved`;
- payload editorial = `approved`;
- exact dates are valid;
- Tour is not completed;
- accepted v0.8 renderer model is ready;
- reviewed route is renderable;
- exact/from price has source amount + currency.

Optional missing facts remain hidden/neutral. They are never fabricated for the public surface.

---

## 4. PUBLIC / SEO GATE MODEL

All gates default **OFF**:

```text
Public Master     OFF
Exact Route       OFF
Indexation        OFF
Canonical         OFF
Schema            OFF
Sitemap           OFF
```

Dependencies:
- Route requires Master + content readiness.
- Indexation, Canonical and Schema require effective Route.
- Sitemap requires effective Route + Indexation.
- turning Public Master OFF collapses every child gate immediately.

Gate changes are admin-only, nonce-protected and audit-evidenced with:

`v100_public_pilot_gates_updated`

### Initial public QA mode

If Master + Route are enabled while Indexation stays OFF, the route is intentionally:

`noindex, follow, noarchive`

This allows controlled human QA before search exposure.

Canonical, schema and sitemap do not auto-follow route activation; each requires its own gate.

---

## 5. SEO OUTPUT POLICY

### Canonical
Third-party canonical output is suppressed on the exact pilot surface. STTI emits a canonical only when the Canonical gate is ON.

### Schema
Third-party JSON-LD output is suppressed on the exact pilot surface. STTI emits only minimal source-safe `WebPage` JSON-LD when the Schema gate is ON. No unsupported Product/Tour facts are invented.

### Sitemap
A custom STTI provider exposes zero URLs by default. When the Sitemap gate is effectively ON, it may expose exactly one URL: the allowlisted pilot route.

### Indexation
Indexation is a distinct gate. Route visibility does not imply indexability.

---

## 6. FAST ROLLBACK

Primary rollback action:

`Public Master → OFF`

Result:
- Route OFF effectively;
- Indexation OFF;
- Canonical OFF;
- Schema OFF;
- Sitemap OFF.

No rewrite flush, route deletion or canonical Tour mutation is required.

---

## 7. PRODUCTION BOUNDARY

Merging v1.0 into `main` will **not** mean production deployment.

Even after future installation of the accepted ZIP, all release options remain OFF by default.

Before any production Route ON action, require:
1. exact live URL collision check for `/turlar/buyuk-iran-kultur-turu/`;
2. verify no conflicting WordPress page/route/redirect owns the path;
3. verify production `STT-000001` is editorial-approved and renderer-ready;
4. enable Master + Route only, leaving Indexation/Canonical/Schema/Sitemap OFF;
5. complete live visual/mobile/content QA;
6. explicitly approve each SEO gate afterward.

No production action is part of the current repository branch.

---

## 8. v1.0.0 ACCEPTANCE GATES

Before merge:
1. PHP syntax PASS for all Tour files;
2. v1.0 static contract PASS;
3. old WordPress runtime/package workflow remains unchanged and PASS;
4. dedicated v1.0 disposable WordPress + MariaDB runtime PASS;
5. all release gates prove default OFF;
6. exact allowlisted route matcher PASS;
7. non-allowlisted path rejection PASS;
8. editorial approval/readiness fail-closed behavior PASS;
9. Master-only exposes no route;
10. Route-only stays noindex with Canonical/Schema/Sitemap OFF;
11. Indexation can be enabled without auto-enabling other SEO gates;
12. Canonical gate emits only exact pilot URL;
13. Schema gate emits minimal exact-route WebPage JSON-LD;
14. Sitemap gate exposes at most one allowlisted URL;
15. Master OFF collapses every child gate immediately;
16. release gate changes produce audit evidence;
17. disposable Tour/audit/options/Stable-ID state is restored exactly;
18. v0.6.5/v0.7/v0.7.1/v0.8/v0.9 regressions remain PASS;
19. replacement ZIP/SHA256 workflow remains PASS;
20. no production deployment;
21. Merge requires explicit owner approval.

---

## 9. ACCEPTED BASELINES — PRESERVE

### v0.9 — First Real Full Tour
Preserve source binding, truth policy, accepted real record and disposable persistence/audit/render cleanup evidence.

### v0.8 — Complete Customer Renderer
Preserve canonical direct read, exact confirmed-primary selection, no route guessing, reviewed relations only, Hotel Intelligence live reads, canonical geo only and zero renderer writes.

### v0.7.1 — Canonical Geo
Preserve exact stop IDs, explicit source-backed coordinates, Human Review and no browser geocoder/localStorage authority.

### v0.7 — Review Relations
Preserve Route Variants, Hotel Options, Transport refs, review states and fail-closed contradictory/unresolved relations.

### v0.6.5 — AI Completion / Import
Preserve REVIEW/NO-WRITE semantics, source hash/duration/publication validation, dry-run DB/audit/sequence invariants and no AI direct canonical writes.

---

## 10. EXECUTION ORDER TO FINISH TOUR INTELLIGENCE

```text
1. STTI v0.7.0 — Review Relations                 CLOSED / ACCEPTED
2. STTI v0.7.1 — Canonical Geo Resolver           CLOSED / ACCEPTED
3. STTI v0.8.0 — Complete Customer Renderer       CLOSED / ACCEPTED
4. STTI v0.9.0 — First Real Full Tour             CLOSED / ACCEPTED
5. STTI v1.0.0 — Controlled Public Tour Pilot     ACTIVE NOW
6. Unified Google Sheets Direct Sync — Umrah + Tours
```

After v1.0 repository/runtime acceptance, the remaining major Tour operating branch is the shared authenticated Google Sheets Direct Sync foundation.

---

## 11. GOOGLE SHEETS — FINAL SHARED FOUNDATION

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
- replay protection;
- idempotency;
- immutable `STP-*` / `STT-*` IDs;
- expected-checksum conflict protection;
- per-record errors;
- archive-never-delete where applicable;
- JSON fallback/recovery;
- no automatic public/indexable exposure.

Current Tour Sheet `v0.6.2.1` remains source-only PARTIAL JSON generation and performs no WordPress write.

---

## 12. SEO OPERATING STATE

Weekly SEO work remains non-blocking for the current Tour engineering branch.

Backlog remains:
- classify crawled-currently-not-indexed URLs;
- verify intentional robots-blocked URL;
- later improve `/umre-vizesi-nasil-alinir/` CTR;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
- monitor Hotel query growth;
- no mass request-indexing/validation.

---

## 13. REPOSITORY OPERATING METHOD

- one coherent feature branch;
- static/lint gates before runtime;
- disposable runtime only for release evidence;
- existing accepted workflows preserved where possible;
- no direct live-site edits from engineering branch;
- no repeated Actions polling;
- no automatic Merge;
- explicit owner approval required for Merge.

---

## 14. PROJECT PROGRESS — PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform        ~89%
Tour Intelligence                             ~94% after accepted v0.9.0
Umrah canonical system                       100% current system scope
Umrah including direct Sheet operations       ~88%
Tour Sheet operating pipeline                 ~55%
Unified direct Sheets → WordPress foundation  ~25%
```

Do not advance Tour progress for v1.0 until runtime acceptance. After v1.0 repository/runtime acceptance, expected Tour planning progress is approximately `98%` before the shared Direct Sync operating branch.

Remaining path:

`Controlled Public Pilot → Unified Sheet Connector`

---

## 15. NEXT CHECKPOINT

> Finish `STTI v1.0.0 — Controlled Public Tour Pilot`, run baseline + legacy runtime/package + dedicated v1.0 disposable runtime gates, inspect final CI once, and stop before Merge until explicit owner approval. Production deployment and actual gate activation remain separate owner-controlled actions.

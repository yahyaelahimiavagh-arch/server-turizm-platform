# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-16 — AUTHORITATIVE SITE-WIDE CHECKPOINT — STTI v1.1.6 PRODUCTION ACCEPTED

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-16**
>
> Repository merge, Production deployment, Google Sheets synchronization, editorial approval, public rendering, indexation, sitemap, schema and Search Console submission are separate gates. Never infer one from another.
>
> This checkpoint supersedes the stale Tour-Hub-installation and STTI v1.1.2 documentation checkpoints. PR #22 is merged and the Production Tour workflow has now been observed end-to-end through editorial approval while all public/SEO locks remained OFF.

---

## 1. AUTHORITATIVE REPOSITORY CHECKPOINT

Repository:

`yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch:

`main`

Current merged `main`:

`09f026f621a0057b52333699331f7aeb8ee56947`

Merged progression now includes:

```text
STTI v0.6.5  AI Completion Review Contract       MERGED / ACCEPTED
STTI v0.7.0  Review Relations                    MERGED / ACCEPTED
STTI v0.7.1  Canonical Geo Resolver              MERGED / ACCEPTED
STTI v0.8.0  Complete Customer Renderer          MERGED / ACCEPTED
STTI v0.9.0  First Real Full Tour                MERGED / ACCEPTED
STTI v1.0.0  Controlled Public Tour Pilot        MERGED / CONTROLLED
PR #17       Separated Umrah/Tour Sheet stacks   MERGED
PR #18       Dynamic Culture Tours Hub v1.1      MERGED
PR #19       Tour Direct Sync Production closeout MERGED
PR #20       Operator-first Tour editor          MERGED
PR #22       Review Queue + guarded approval     MERGED
```

Current Production Tour Intelligence release:

`1.1.6`

Classification:

`LIVE / PRODUCTION ACCEPTED for the tested operator-review-approval scope`

---

## 2. WHOLE-SITE SEO EXECUTIVE STATE

### Last accepted search-health evidence

The latest preserved weekly SEO review was captured on 2026-09-14 using Search Console data complete through 2026-09-11.

It showed:

- overall search visibility growing;
- average position slightly improving;
- CTR still needing work on high-impression informational pages;
- `/umre-1/` carrying meaningful commercial search demand;
- `2027 Umre fiyatları` intent emerging as an opportunity;
- Hotel Intelligence beginning to produce entity-level visibility.

These measurements are historical evidence, not a current 2026-09-16 Search Console reading. The next trend decision must use a fresh export.

### Preserve accepted SEO baselines

- homepage canonical/indexed baseline;
- Homepage Intelligence accepted baseline;
- Homepage Journey Evidence accepted baseline;
- `/umre-1/` commercial Hub architecture;
- accepted Umrah Hub visual baseline;
- Hotel Intelligence canonical entity model;
- first-party trust/journey content already accepted through technical SEO QA;
- Program public-route and Program indexation separation;
- Tour editorial/public/indexation separation.

---

## 3. CANONICAL OWNERSHIP MODEL

### Umrah / Program facts

Canonical owner: `Program Intelligence`

Stable identity: `STP-######`

Last known live version: `0.3.5`

Publishing route/mode owner: `Program Publishing Integration 0.4.14`

Rules:

- Stable ID is immutable;
- source row/title/slug is not identity;
- archive retains entity, snapshots and audit history;
- ordinary lifecycle removal is archive, not hard delete;
- Sheet sync success never implies indexation/publication.

### Tour facts

Canonical owner: `Tour Intelligence`

Stable identity: `STT-######`

Production accepted release: `1.1.6`

Editorial approval is now a guarded canonical transition, but **approval is not publication**.

### Hotel facts

Canonical owner: `Hotel Intelligence`

Stable identity: `STH-######`

Last known live version: `0.9.11`

Tour/Program relations must reference canonical Hotel identity when resolved. Hotel name/media/address/reference facts must not be duplicated as a second canonical source.

---

## 4. UMRAH PUBLIC / SEO BOUNDARY

Preserve:

- `/umre-1/` remains the live structured commercial Hub;
- real Program entities keep immutable `STP-*` identities;
- `STP-000036` and `STP-000037` remain protected fixtures;
- Program detail indexation remains OFF pending a separate staged approval;
- Program sitemap inclusion remains OFF;
- bulk Program Search Console submission remains OFF;
- Program IndexNow bulk submission remains OFF;
- no mass removal of Program `noindex`;
- removed/historical Programs are archived, not deleted.

The Sheet removal control remains an operator intent only; it does not justify creating a historical Program merely to archive it.

---

## 5. TOUR PUBLIC / SEO BOUNDARY — NON-NEGOTIABLE

Tour Intelligence, Review Queue, Approval and Direct Sync must never silently enable:

- Tour Public Master;
- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure;
- mass Tour URL generation.

Production evidence on `STT-000002` proved that the guarded approval transition can complete while all of these remain OFF.

---

## 6. DIRECT SYNC SERVER CONTRACT — ACCEPTED

Contract:

`ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Adapters:

- `umrah`
- `tour`

Modes:

- `validate` — preflight only;
- `apply` — mutation only after accepted validation and explicit operator intent.

Authentication is external configuration only:

- `ST_DIRECT_SYNC_ENDPOINT`
- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Never store secrets in Git, Sheet cells or exported JSON.

### Transport safety

Preserve:

- bounded retry;
- one request ID/body per business request;
- fresh timestamp/nonce/HMAC per transport attempt;
- retry only transient network/timeout/DNS failures;
- safe `stds_processing` replay polling;
- validate/status after an uncertain timeout rather than blindly repeating Apply.

Production Tour Sheet testing observed intermittent DNS/latency and one Apps Script maximum-execution-time event, but canonical identity and idempotency remained correct after recovery.

---

## 7. GOOGLE SHEETS ARCHITECTURE — HARD SEPARATION

### Umrah bound Apps Script

Active source:

- `integrations/google-sheets/umrah/ST-Umrah-Config.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Menu.gs`

Hidden identity sheet: `ST Umrah Sync State`

No Tour runtime/menu belongs in this Apps Script project.

### Tour bound Apps Script

Active source:

- `integrations/google-sheets/tours/ST-Tour-Generator.gs`
- `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs`
- `integrations/google-sheets/tours/ST-Tour-Menu.gs`

One-time installer:

`stTourInstall()`

Technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

They belong together, are appended after business columns if absent, and remain hidden for normal operators. Fixed Z/AA targeting is retired. Business fields such as `Vize` must never be overwritten.

Generator implementation remains `1.0.2`; payload producer contract remains `0.6.2.1` intentionally to avoid accepted checksum drift.

---

## 8. TOUR SHEET → SITE CONNECTION — CLOSED / PRODUCTION ACCEPTED

Controlled Production fixture:

```text
Local ID: ID-642D23A3
Original title: Iran Test Turu
Updated title: Iran Test Turu Update Test
Canonical Stable ID: STT-000002
```

### CREATE proof

```text
Local validation       Target NEW
Remote validate        STT-000002 — CREATE
Controlled Apply       STT-000002 — CREATE
Second validate        STT-000002 — UNCHANGED
```

### UPDATE proof

```text
Local validation       Target STT-000002
Remote validate        STT-000002 — UPDATE
Controlled Apply       STT-000002 — UPDATE
Second validate        STT-000002 — UNCHANGED
```

Acceptance:

```text
Selected-row CREATE                    PASS
Selected-row UPDATE                    PASS
Stable ID continuity                   PASS
Checksum continuity                    PASS
Post-write idempotency                 PASS / UNCHANGED
Duplicate loop                         NOT OBSERVED
Bulk Tour mutation                     NOT AUTHORIZED / NOT TESTED
Unexpected public/indexation change    NONE OBSERVED
```

---

## 9. STTI OPERATOR UI — PRODUCTION ACCEPTED

Production release progression during QA:

```text
1.1.2  simplified daily Tour editor
1.1.3  operator-first Review Queue
1.1.4  Review Queue editor-link fix
1.1.5  guarded approval screen introduced
1.1.6  wp-admin approval-route registration fix
```

Current Production accepted release:

`1.1.6`

### Simple editor

Default operator view exposes common fields only:

- Tour title;
- country/destination;
- departure/return dates;
- duration;
- primary price/currency;
- reservation status;
- visa status;
- editorial status;
- route summary and detail shortcuts.

Advanced canonical fields remain available behind `Gelişmiş Alanlar`.

No-change save on `STT-000002` returned:

`Private candidate unchanged. Public output remains OFF.`

### Review Queue

Review Queue is operator-first and read-oriented. It summarizes canonical readiness without inventing data.

Observed checks include:

- source completeness;
- route;
- canonical Geo;
- Hotel relations;
- transport segments;
- dates;
- price;
- day-by-day itinerary.

Unknown facts remain unknown. Placeholder Hotels/transport were explicitly reviewed/rejected rather than converted into false canonical claims.

### Canonical Geo review

For the controlled Iran fixture, Tehran / Isfahan / Shiraz city-center coordinates were source-backed and human-confirmed before readiness was accepted.

Result:

`Geo 3/3 confirmed`

### Guarded editorial approval

Approval screen requirements:

- `manage_options` capability;
- valid nonce;
- explicit confirmation checkbox;
- server-side readiness recheck at submit time;
- allowed transition only `needs_review → approved`;
- repeated approval is idempotent;
- audit event `candidate_approved`;
- publication envelope preserved.

Production result for `STT-000002`:

```text
Editorial      APPROVED
Hard blockers  0
Public route    OFF
Hub visibility  OFF
Indexation      OFF
Sitemap         OFF
Homepage        OFF
Hub Master      OFF
```

This is accepted evidence that editorial approval is independent from publication/SEO activation.

---

## 10. CULTURE TOURS HUB v1.1 — PRODUCTION INSTALLED / MASTER OFF

Contract:

`STTI-TOUR-HUB-1.1.0`

Existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Current Production state:

`OFF`

Architecture:

- no new rewrite route;
- existing WordPress page retains URL/permalink/theme shell/page SEO/canonical/indexation ownership;
- Hub replaces only the existing page content when explicitly enabled;
- OFF preserves/restores the existing page content path;
- Hub reads canonical `STT-*` records directly; no duplicate Hub database.

### Production eligibility proof

Before approval:

`eligible = 0`

After `STT-000002` editorial approval:

`eligible = 1`

While `eligible = 1` and Hub Master remained OFF, `/kultur-turlari/` was re-opened and the existing legacy/current WordPress page was still shown unchanged.

Acceptance:

```text
Hub installed                  PASS
Eligible record detection      PASS / 1
Hub Master                     OFF
Legacy page preserved while OFF PASS
Public/SEO side effect         NONE OBSERVED
```

---

## 11. SEO BACKLOG — PRESERVED / RE-PRIORITIZED

### P0 — fresh Search Console gate

Next weekly SEO review must:

- export fresh Performance data;
- compare last 7 days vs previous 7 days and 28-day baseline;
- review Indexing/Coverage and representative URL samples;
- classify meaningful `Crawled — currently not indexed` URLs by page type;
- verify robots-blocked URLs are intentional;
- investigate 5xx/404 before redirecting/requesting indexing;
- avoid mass indexing requests and mass exclusion validation.

### P1 — commercial / SERP optimization

- improve CTR/intent alignment for the Umrah visa guide after crawl hygiene;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları` demand;
- monitor Hotel entity-query growth;
- strengthen real first-party trust/journey evidence;
- when the Culture Tours Hub is eventually activated, measure impressions/CTR separately from individual Tour-detail indexing.

### P2 — global technical SEO

1. OG cleanup without duplicate theme/plugin output;
2. image ALT/accessibility root-cause fixes;
3. intrinsic width/height and LCP-image policy;
4. structured data only from visible verified facts;
5. internal-link/crawl-depth improvements across Hub → Program/Tour → Hotel entities;
6. no unsupported schema and no random SEO link injection.

### P3 — generator semantic SEO

Preserve the `SEO-GEN-1A` zero-visual-change rule when Program generator work resumes:

- no redesign merely for SEO;
- semantic/accessibility changes must be visually neutral;
- test one representative Program first;
- regress standard / four-date / combined-route cases;
- batch only after those gates pass.

---

## 12. SAFETY / OWNERSHIP RULES — NON-NEGOTIABLE

- Program facts → Program Intelligence;
- Tour facts → Tour Intelligence;
- Hotel facts/media → Hotel Intelligence;
- publication route/mode → controlled publishing gates;
- Sheet sync → immutable Stable ID + checksum concurrency;
- removal → archive, not ordinary hard delete;
- unknown facts stay unknown;
- AI/operator tooling must not invent Hotels, transport, dates, coordinates or route facts;
- editorial approval is not publication;
- repository acceptance is not Production deployment;
- Production deployment is not indexation approval;
- public/indexation/schema/sitemap changes require explicit staged gates.

---

## 13. CURRENT ACCEPTANCE REGISTER

```text
Program Intelligence 0.3.5                              LAST KNOWN LIVE / PRESERVE
Publishing Integration 0.4.14                           LAST KNOWN LIVE / PRESERVE
Hotel Intelligence 0.9.11                               LAST KNOWN LIVE / PRESERVE
Tour Intelligence 1.1.6                                 PRODUCTION ACTIVE / ACCEPTED SCOPE
Direct Sync foundation                                  MERGED / ACCEPTED CONTRACT
Direct Sync v0.1.4 repository                           MERGED
Separated Umrah Apps Script path                        PRODUCTION OBSERVED / ACCEPTED
Tour Sheet selected-row CREATE                          PRODUCTION ACCEPTED
Tour Sheet selected-row UPDATE                          PRODUCTION ACCEPTED
Tour post-write idempotency                             PRODUCTION ACCEPTED / UNCHANGED
Operator-first Tour editor                              PRODUCTION ACCEPTED
Review Queue                                            PRODUCTION ACCEPTED
Guarded editorial approval                             PRODUCTION ACCEPTED
STT-000002 editorial state                              APPROVED
STT-000002 hard blockers                                0
Tour Hub v1.1 installed                                 PRODUCTION VERIFIED
Tour Hub eligible Tours                                 1
Tour Hub Master                                         OFF
/kultur-turlari/ while Hub OFF                          LEGACY/CURRENT PAGE PRESERVED
Program detail indexation                               OFF
Program sitemap                                         OFF
Tour public/indexation/schema/sitemap gates             OFF / SEPARATE
```

---

## 14. CURRENT EXECUTION ORDER

```text
1. Close/merge this documentation checkpoint                           NOW
2. Keep Hub Master OFF while reviewing the first eligible card model    REQUIRED
3. Verify rollback path and current /kultur-turlari/ baseline           REQUIRED
4. Controlled Hub Master activation only with explicit owner approval   NEXT RUNTIME GATE
5. Immediately verify page content, theme shell, links and mobile        REQUIRED
6. If regression: Hub Master OFF → rollback to legacy content            IMMEDIATE
7. Keep Tour detail public/indexation/schema/sitemap gates separate      REQUIRED
8. Run fresh Search Console weekly gate                                 WEEKLY
9. Continue CTR/content/image/schema/internal-link backlog               AFTER STABILITY
```

---

## 15. HARD STOP CONDITIONS

Stop before any Tour Apply/approval/public activation if any of these occurs unexpectedly:

- `CREATE` for an entity believed to exist;
- wrong `STT-*` identity;
- `CONFLICT`, `INVALID` or `ERROR`;
- unexpected checksum replacement;
- duplicate Tour entity;
- readiness blocker returns before approval;
- public route/indexation/schema/sitemap changes during editorial-only work;
- Hub Master changes without explicit owner intent;
- response outcome uncertain after timeout — validate/status first;
- source/reference provenance is uncertain.

No bulk action is authorized merely because the selected-row tests passed.

---

## 16. REPOSITORY OPERATING METHOD

- one coherent branch per checkpoint;
- documentation reconciliation before the next Production mutation;
- static/lint/runtime gates where applicable;
- no secrets in Git;
- no automatic Production mutation from repository work;
- Production plugin install/activation is a separate operator action;
- no runtime/SEO acceptance claim without evidence;
- merge only after the checkpoint is reviewable and current.

---

## 17. NEXT CHECKPOINT

The Tour pipeline is now accepted through:

```text
Google Sheet
→ validate
→ CREATE/UPDATE
→ canonical STT-* identity
→ UNCHANGED idempotency
→ operator review
→ source-backed blocker resolution
→ guarded editorial approval
→ automatic Hub eligibility
```

Current controlled state:

```text
STT-000002          APPROVED
Hub eligible        1
Hub Master          OFF
/kultur-turlari/    legacy/current content preserved
Tour public/SEO     OFF / separate
```

The next runtime objective is a **controlled, reversible Culture Tours Hub activation checkpoint**. Hub activation must not be treated as approval for individual Tour-detail indexing, sitemap inclusion, schema or Search Console submission.
# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-16 — AUTHORITATIVE SITE-WIDE CHECKPOINT — STTI v1.1.2 PRODUCTION ACCEPTED

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-16**
>
> Repository merge, Production deployment, Google Sheets sync, editorial approval, public rendering, indexation, sitemap, schema and Search Console submission are separate gates. Never infer one from another.

---

## 1. AUTHORITATIVE REPOSITORY CHECKPOINT

Repository:

`yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch:

`main`

Current merged `main`:

`c059e852b3a7ffd246b870a2541a135cfd00a664`

This main includes:

- PR #17 — separated Umrah + Tour Google Sheets Apps Script runtimes;
- PR #18 — dynamic Tour Hub v1.1 for the existing `/kultur-turlari/` page;
- PR #19 — 2026-09-16 SEO/runtime reconciliation and Tour Sheet connection evidence;
- PR #20 — STTI v1.1.2 operator-first Tour editor.

### Current truth

```text
STTI v0.6.5 AI Completion Contract                MERGED / CLOSED
STTI v0.7.0 WordPress Review Relations            MERGED / CLOSED
STTI v0.7.1 Canonical Geo Resolver                MERGED / CLOSED
STTI v0.8.0 Complete Customer Renderer            MERGED / CLOSED
STTI v0.9.0 First Real Full Tour                  MERGED / CLOSED
STTI v1.0.0 Controlled Public Tour Pilot          MERGED / CONTROLLED GATES
Direct Sync foundation                            MERGED / SERVER CONTRACT ACCEPTED
Separated Umrah/Tour Sheet stacks                 MERGED / PRODUCTION ACCEPTED
Tour Sheet selected-row CREATE                    PRODUCTION ACCEPTED
Tour Sheet selected-row UPDATE                    PRODUCTION ACCEPTED
Tour Hub v1.1                                     MERGED / PRODUCTION INSTALLED / MASTER OFF
STTI v1.1.2 Operator UI                           MERGED / PRODUCTION ACCEPTED
Tour Hub live activation                          OFF / SEPARATE OWNER GATE
```

The old `v0.7.0` continuation is no longer the active checkpoint. The active branch is now editorial review/Hub eligibility on top of the accepted Sheet → canonical pipeline.

---

## 2. WHOLE-SITE SEO EXECUTIVE STATE

### Last accepted search-health evidence

The latest preserved weekly SEO review was captured on 2026-09-14 using Search Console data complete through 2026-09-11.

That review showed:

- search visibility growing overall;
- average position slightly improving;
- CTR needing optimization on high-impression informational pages;
- `/umre-1/` showing useful commercial search demand;
- 2027 Umrah pricing intent emerging as a growth opportunity;
- Hotel Intelligence beginning to create entity-level search visibility.

Do **not** treat 2026-09-11 Search Console numbers as current 2026-09-16 measurements. The next weekly gate requires a fresh export before drawing a new trend conclusion.

### Preserved live SEO baselines

Preserve unless new regression evidence appears:

- homepage canonical/indexed baseline;
- Homepage Intelligence accepted baseline;
- Homepage Journey Evidence accepted baseline;
- `/umre-1/` commercial Hub architecture;
- accepted Umrah Hub visual baseline;
- Hotel Intelligence canonical entity model;
- first-party trust/journey content already accepted through technical SEO QA;
- Program detail public routes remain separated from Program indexation;
- Tour public/indexation gates remain independently controlled.

---

## 3. CURRENT CANONICAL OWNERSHIP MODEL

### Umrah / Program facts

Canonical owner:

`Program Intelligence`

Stable identity:

`STP-######`

Last known live Program Intelligence version:

`0.3.5`

Publishing route/mode owner:

`Program Publishing Integration 0.4.14`

Rules:

- Stable ID is immutable;
- source title/slug/row number is not identity;
- archive retains entity, snapshots and audit history;
- hard delete is not the normal lifecycle action;
- Program publication state must not be inferred from Sheet sync success.

### Tour facts

Canonical owner:

`Tour Intelligence`

Stable identity:

`STT-######`

Repository release:

`1.1.2`

Production release:

`1.1.2 — runtime observed/accepted on STT-000002`

Operator UI is a presentation layer over the existing canonical editor. It does not create a second Tour store or a second save path.

### Hotel facts

Canonical owner:

`Hotel Intelligence`

Stable identity:

`STH-######`

Last known live version:

`0.9.11`

Hotel name/media/address/reference-point facts must not be duplicated into Tour or Program canonical stores. Relations use the Hotel Stable ID where resolved.

---

## 4. UMRAH PUBLIC / SEO BOUNDARY

Preserved accepted state:

- `/umre-1/` is the live structured commercial Hub;
- real Program entities keep immutable `STP-*` identities;
- protected fixtures `STP-000036` and `STP-000037` remain private/protected;
- Program detail indexation remains OFF until a separate staged approval;
- Program sitemap inclusion remains OFF;
- bulk Program Search Console submission remains OFF;
- Program IndexNow bulk submission remains OFF;
- no mass removal of Program `noindex`;
- archived/removed Programs are retained, not deleted.

The Sheet removal control remains:

`AD = Programı Kaldır`

Checked historical rows without an accepted server identity are Sheet-only closed; they are never CREATEd merely so they can be archived.

---

## 5. TOUR PUBLIC / SEO BOUNDARY

Tour Intelligence, Direct Sync, Operator UI and Tour Hub must never silently enable:

- Tour Public Master;
- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure;
- mass Tour URL generation.

The existence of a canonical `STT-*` record does not mean the Tour is public or indexable.

Editorial `approved` is also **not** equivalent to public/indexable. Publication and SEO remain separate gates.

---

## 6. DIRECT SYNC SERVER CONTRACT — PRESERVE

Contract:

`ST-DIRECT-SYNC-1.0.0`

WordPress endpoint:

`/wp-json/server-turizm/v1/direct-sync`

Production URL:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Adapters:

- `umrah`
- `tour`

Modes:

- `validate` — preflight; no canonical write;
- `apply` — mutation only after accepted validation and operator intent.

### Authentication

Server-side configuration:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Tour Apps Script Properties:

- `ST_DIRECT_SYNC_ENDPOINT`
- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Secrets are external configuration only:

- never commit them to Git;
- never place them in Sheet cells;
- never place them in exported JSON;
- use Apps Script Script Properties on the client;
- use secure WordPress server configuration on the server.

### Transport safety

Both separated clients preserve:

- maximum 3 attempts;
- one request ID per business request;
- byte-identical JSON body across retries;
- fresh timestamp/nonce/HMAC for every transport attempt;
- transient timeout/DNS/network retry only;
- safe `409 stds_processing` polling;
- ordinary business/validation failures are not transport-retried into a write.

Production Tour Sheet testing on 2026-09-16 showed intermittent DNS/latency behavior. Successful calls recovered on bounded retry; one validate attempt exceeded Apps Script maximum execution time. Canonical identity and idempotency remained correct after recovery. Do not blindly repeat Apply after an uncertain response; validate/status first.

---

## 7. GOOGLE SHEETS ARCHITECTURE — HARD SEPARATION

### Umrah bound Apps Script — only Umrah code

Active source files:

- `integrations/google-sheets/umrah/ST-Umrah-Config.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Menu.gs`

Hidden state:

`ST Umrah Sync State`

No Tour runtime/menu belongs in this Apps Script project.

### Tour bound Apps Script — only Tour code

Active source files:

- `integrations/google-sheets/tours/ST-Tour-Generator.gs`
- `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs`
- `integrations/google-sheets/tours/ST-Tour-Menu.gs`

One-time install:

`stTourInstall()`

Technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

Rules:

- both belong together;
- if absent they are appended after the business columns;
- they remain hidden from normal operator use;
- business columns such as `Vize` must never be overwritten;
- fixed Z/AA targeting is retired.

Generator implementation remains `1.0.2`; canonical payload `producer.version` remains `0.6.2.1` intentionally to avoid accepted checksum drift.

---

## 8. TOUR SHEET → SITE CONNECTION — CLOSED / PRODUCTION ACCEPTED

Controlled Production fixture:

```text
Local ID: ID-642D23A3
Original title: Iran Test Turu
Canonical Stable ID: STT-000002
```

### CREATE path

```text
Local validation       Target NEW
Remote validate        STT-000002 — CREATE
Controlled Apply       STT-000002 — CREATE
Second validate        STT-000002 — UNCHANGED
```

### Intentional UPDATE path

Safe test change:

```text
Iran Test Turu
→ Iran Test Turu Update Test
```

Accepted sequence:

```text
Local validation       Target STT-000002
Remote validate        STT-000002 — UPDATE
Controlled Apply       STT-000002 — UPDATE
Second validate        STT-000002 — UNCHANGED
```

### Acceptance

```text
Menu installed                         PASS
Endpoint/auth configured               PASS
Selected-row CREATE                    PASS
Selected-row UPDATE                    PASS
Stable ID continuity                   PASS
Hidden checksum continuity             PASS
Post-write idempotency                 PASS / UNCHANGED
Duplicate CREATE/UPDATE loop           NOT OBSERVED
Bulk Tour mutation                     NOT AUTHORIZED / NOT TESTED
Unexpected public/indexation change    NONE OBSERVED
```

This closes the selected-row Tour Direct Sync connection checkpoint.

---

## 9. STTI v1.1.2 OPERATOR-FIRST TOUR EDITOR — PRODUCTION ACCEPTED

Production fixture:

`STT-000002 / Iran Test Turu Update Test`

Purpose:

- make daily Tour editing behave more like the accepted Google Sheet;
- keep technical/canonical controls available only when needed;
- preserve the original canonical data model and save owner.

### Simple-mode daily fields

- Tour title;
- country/destination;
- departure date;
- return date;
- calculated duration display;
- primary price;
- currency;
- reservation/availability;
- visa status;
- editorial review status.

### Simple-mode operator actions

- `Müşteri Önizleme`;
- `Gelişmiş Alanlar`;
- `Değişiklikleri Kaydet`;
- route detail;
- day-by-day itinerary;
- hotels;
- transport;
- price details;
- included/excluded;
- visa detail;
- technical preview.

### Technical complexity hidden by default

The following remain available in Advanced mode but are hidden from the ordinary operator screen:

- Canonical Geo / Route Stop Coordinates;
- release-lock cards;
- duplicate technical header/status layers;
- top-level technical release controls;
- legacy 16-tab canonical editor.

### Production runtime evidence

On 2026-09-16 the `STT-000002` simple editor was loaded in Production with Hub Master still OFF.

A no-change save returned:

`Private candidate unchanged. Public output remains OFF.`

Accepted result:

```text
Stable ID                     STT-000002 / preserved
Canonical payload             unchanged
Duplicate Tour                none
Public output                 OFF
Hub Master                    OFF
Indexation/sitemap/schema     unchanged / locked
No-change save                PASS
Operator UI Production QA     PASS
```

Therefore STTI v1.1.2 Operator UI is Production runtime accepted.

---

## 10. TOUR HUB v1.1 — PRODUCTION INSTALLED / MASTER OFF

Contract:

`STTI-TOUR-HUB-1.1.0`

Existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Current Production state:

`OFF`

Observed while OFF:

- existing `/kultur-turlari/` page remains the legacy/current WordPress page;
- dynamic Hub does not replace its content;
- current eligible Tour count observed before editorial approval: `0`;
- public/indexation/schema/sitemap gates remain separate.

### Architecture

The Hub does not create a new route. When explicitly enabled, it replaces only the content area of the existing WordPress page.

The existing page remains owner of:

- URL/permalink;
- theme shell;
- page-level SEO metadata;
- canonical/indexation behavior;
- rollback content.

Hub eligibility reads directly from canonical Tour Intelligence records. No second Hub Tour database is allowed.

A Tour is eligible only when its canonical lifecycle/editorial state permits it. Future dated Tours sort nearest-first; approved undated Tours may follow as `Tarih yakında`; sold-out Tours remain explicitly visible rather than silently disappearing.

Pipeline:

```text
Google Sheet Tour row
→ Direct Sync validate/apply
→ canonical STT-* record
→ human review / editorial approval
→ Tour Hub eligibility
```

Hub Master remains OFF until an explicit later owner gate.

---

## 11. SEO BACKLOG — PRESERVED / RE-PRIORITIZED

### P0 — fresh search-health evidence

At the next weekly SEO gate:

- export fresh Search Console Performance data;
- compare last 7 days vs previous 7 days and 28-day baseline;
- recheck index coverage and URL-level samples;
- classify meaningful `Crawled — currently not indexed` URLs by page type;
- verify any robots-blocked URL is intentional;
- verify any 5xx/404 issue before redirecting or requesting indexing;
- do not mass-request indexing or mass-validate exclusions.

### P1 — SERP / commercial intent

Carry forward:

- improve CTR/intent alignment for the Umrah visa guide after crawl hygiene;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları` demand;
- monitor Hotel entity query growth;
- expand useful first-party journey/trust evidence where real source material exists.

### P2 — remaining global technical SEO

1. OG layer cleanup without duplicate theme/plugin output;
2. image ALT/accessibility root-cause work;
3. intrinsic width/height and LCP image policy where appropriate;
4. structured-data expansion only from visible verified facts;
5. internal-link / crawl-depth improvements across Hub → Program/Tour → Hotel/entity relationships;
6. no random SEO link injection and no unsupported/fake schema.

### P3 — Generator semantic SEO work

The older `SEO-GEN-1A` zero-visual-change policy remains valid when Program generator work resumes:

- do not redesign accepted Program cards;
- do not add visual CSS merely for SEO;
- semantic/accessibility changes must be visual-neutral;
- test a single representative Program first;
- then regress standard / four-date / combined-route cases;
- only then batch-check the full Program set.

Image SEO and structured data remain later layers after semantic-generator stability.

---

## 12. SAFETY / OWNERSHIP RULES — NON-NEGOTIABLE

Preserve:

- Program facts → Program Intelligence;
- Tour facts → Tour Intelligence;
- Hotel facts/media → Hotel Intelligence;
- publication route/mode → Publishing registry / controlled Tour gates;
- Sheet → canonical sync uses immutable Stable IDs and checksum concurrency;
- removal → archive, never ordinary hard delete;
- unknown Tour facts stay unknown; AI/operator tooling must not invent Hotels, transport, dates, route facts or geo facts;
- source-partial Tours may keep unknowns rather than guessed data;
- public/indexation changes require explicit staged gates;
- repository acceptance never means Production deployment;
- Production deployment never means indexation approval;
- editorial approval never means public/indexable.

---

## 13. CURRENT ACCEPTANCE REGISTER

```text
Program Intelligence 0.3.5                              LAST KNOWN LIVE / PRESERVE
Publishing Integration 0.4.14                           LAST KNOWN LIVE / PRESERVE
Hotel Intelligence 0.9.11                               LAST KNOWN LIVE / PRESERVE
STTI v0.6.5 AI Completion                               MERGED / ACCEPTED
STTI v0.7.0 Review Relations                            MERGED / ACCEPTED
STTI v0.7.1 Canonical Geo                               MERGED / ACCEPTED
STTI v0.8 Customer Renderer                             MERGED / ACCEPTED
STTI v0.9 First Real Full Tour                          MERGED / ACCEPTED
STTI v1.0 controlled public architecture                MERGED / CONTROLLED
Direct Sync server foundation                           MERGED / ACCEPTED CONTRACT
Direct Sync v0.1.4 repository                           MERGED
Separated Umrah Apps Script selected-row path           PRODUCTION OBSERVED / ACCEPTED
Tour Sheet selected-row CREATE                          PRODUCTION ACCEPTED
Tour Sheet selected-row UPDATE                          PRODUCTION ACCEPTED
Tour Sheet post-write idempotency                       PRODUCTION ACCEPTED / UNCHANGED
Tour Hub v1.1 repository/static/runtime                 MERGED / ACCEPTED
Tour Intelligence v1.1.2 Production                     ACTIVE / ACCEPTED
Operator-first simple editor                            PRODUCTION ACCEPTED
Operator no-change save                                 PRODUCTION ACCEPTED / UNCHANGED
Tour Hub Master                                         OFF
Legacy /kultur-turlari/ while Hub OFF                   PRESERVED / ACCEPTED
Current eligible Hub Tours                              0 before editorial approval
Program detail indexation                               OFF
Program sitemap                                         OFF
Tour public/indexation/schema/sitemap mass gates        OFF / SEPARATE
```

---

## 14. CURRENT EXECUTION ORDER

```text
1. Close/document STTI v1.1.2 Production acceptance             NOW
2. Review STT-000002 canonical facts and blockers                NEXT
3. Resolve only source-backed review blockers                    REQUIRED
4. Set editorial approved only when review policy allows         LATER GATE
5. Verify Hub eligibility count changes while Hub Master OFF     REQUIRED
6. Review resulting Hub card data while Hub remains OFF          REQUIRED
7. Enable Hub Master only with explicit owner approval           LATER
8. Keep Tour public/indexation/schema/sitemap gates separate     ALWAYS
9. Run fresh weekly SEO/Search Console gate                      WEEKLY OPERATIONS
10. Continue CTR/content/image/schema/internal-link backlog      AFTER RUNTIME STABILITY
```

---

## 15. HARD STOP CONDITIONS

Stop before any future Apply/approval/public action if any of these occurs unexpectedly:

- `CREATE` for a Tour believed to already exist;
- wrong `STT-*` Stable ID;
- checksum conflict or unexplained checksum replacement;
- duplicate Tour entity;
- unsupported or invented factual completion;
- approval attempted while required review blockers remain;
- public route/indexation/schema/sitemap state changes without the explicit corresponding gate;
- uncertain response after timeout — validate/status first, do not blindly repeat mutation;
- Hub Master unexpectedly ON;
- existing `/kultur-turlari/` rollback content unavailable.

No bulk action is authorized merely because selected-row CREATE/UPDATE tests pass.

---

## 16. REPOSITORY OPERATING METHOD

- one coherent branch per checkpoint;
- documentation reconciliation before the next Production mutation;
- static/lint/runtime gates where applicable;
- no secret in Git;
- no automatic public/indexation activation;
- Production changes remain explicit owner actions;
- never claim runtime or SEO acceptance without evidence.

---

## 17. NEXT CHECKPOINT

The Sheet → canonical connection and Operator UI are now closed/accepted.

The next operational objective is **controlled editorial review of `STT-000002` while Hub Master remains OFF**.

Success for that checkpoint is:

```text
canonical source facts inspected
+ unresolved facts remain unknown rather than guessed
+ required review blockers identified/resolved from real sources only
+ editorial approval decision recorded correctly
+ Hub eligibility observed while Master remains OFF
+ no public/indexation/schema/sitemap side effect
```

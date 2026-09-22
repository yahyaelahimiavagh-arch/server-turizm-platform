# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-16 — AUTHORITATIVE SITE-WIDE CHECKPOINT — STTI v1.1.7 PRODUCTION ACCEPTED

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-16**
>
> Repository merge, Production deployment, canonical data acceptance, editorial approval, Hub visibility, Hub Master activation, individual public routes, indexation, sitemap, schema and Search Console submission are **separate gates**. Never infer one from another.

---

## 1. AUTHORITATIVE REPOSITORY CHECKPOINT

Repository:

`yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch:

`main`

Current merged checkpoint before this documentation closeout:

`beec4f95245c5a1407c7a7cfcc244cb5c6f3ba66`

This checkpoint includes:

- separated Umrah/Tour Google Sheets runtimes;
- Tour Sheet selected-row CREATE/UPDATE Direct Sync;
- STTI v1.1 Dynamic Culture Tours Hub;
- Operator-first Tour editor;
- Operator-first Review Queue;
- guarded editorial Approval step;
- STTI v1.1.7 explicit per-Tour Hub visibility gate.

### Current Tour Intelligence truth

```text
STTI v0.6.5  AI Completion Contract                 MERGED / CLOSED
STTI v0.7.0  Review Relations                       MERGED / CLOSED
STTI v0.7.1  Canonical Geo                          MERGED / CLOSED
STTI v0.8.0  Customer Renderer                      MERGED / CLOSED
STTI v0.9.0  First Real Full Tour                   MERGED / CLOSED
STTI v1.0.0  Controlled Public Tour Pilot           MERGED / CONTROLLED
STTI v1.1.x  Dynamic Culture Tours Hub              MERGED
STTI v1.1.2  Operator-first editor                  PRODUCTION ACCEPTED
STTI v1.1.4  Review Queue navigation fix            PRODUCTION ACCEPTED
STTI v1.1.6  Guarded editorial approval             PRODUCTION ACCEPTED
STTI v1.1.7  Explicit per-Tour Hub visibility       PRODUCTION ACCEPTED
```

---

## 2. WHOLE-SITE SEO EXECUTIVE STATE

### Last accepted Search Console evidence

The latest preserved weekly SEO review used Search Console data complete through **2026-09-11**. It indicated:

- overall search visibility was growing;
- average position was slightly improving;
- CTR still needed work on higher-impression informational pages;
- `/umre-1/` continued to show useful commercial demand;
- 2027 Umrah pricing queries were emerging as an opportunity;
- Hotel Intelligence had begun to create entity-level visibility.

Do **not** present those numbers as current 2026-09-16 measurements. The next search-health review requires a fresh export.

### Preserved live SEO baselines

Preserve unless new regression evidence proves otherwise:

- homepage canonical/indexed baseline;
- Homepage Intelligence accepted baseline;
- Homepage Journey Evidence accepted baseline;
- `/umre-1/` commercial Hub architecture;
- accepted Umrah Hub visual baseline;
- Hotel Intelligence canonical entity model;
- first-party journey/trust content already accepted through technical SEO QA;
- Program public routes and Program indexation remain separate;
- Tour Hub exposure, Tour detail public routes and Tour indexation remain separate.

---

## 3. CANONICAL OWNERSHIP MODEL

### Umrah / Program facts

Canonical owner: `Program Intelligence`

Stable identity: `STP-######`

Last known live Program Intelligence: `0.3.5`

Publishing route/mode owner: `Program Publishing Integration 0.4.14`

Rules:

- Stable ID is immutable;
- title, slug and Sheet row are not identity;
- archive preserves entity, snapshots and audit history;
- removal means archive, not normal hard delete;
- Sheet sync success does not imply publication/indexation.

### Tour facts

Canonical owner: `Tour Intelligence`

Stable identity: `STT-######`

Current Production-accepted release for tested workflow: `1.1.7`

Rules:

- Stable ID is immutable;
- unsupported facts remain unknown;
- editorial approval is not publication;
- Hub visibility is a separate explicit per-Tour gate;
- Hub Master is a separate page-level activation gate;
- detail route/indexation/schema/sitemap remain separate again.

### Hotel facts

Canonical owner: `Hotel Intelligence`

Stable identity: `STH-######`

Last known live version: `0.9.11`

Rules:

- Hotel names/media/address/reference points remain Hotel-owned;
- Tour/Program stores relations, not duplicated Hotel entities;
- unresolved Hotel references must never be guessed.

---

## 4. UMRAH PUBLIC / SEO BOUNDARY

Preserve:

- `/umre-1/` as the live structured Umrah Hub;
- immutable real `STP-*` Program identities;
- private protected fixtures `STP-000036` and `STP-000037`;
- Program detail indexation OFF until a separate staged approval;
- Program sitemap OFF;
- Program bulk Search Console submission OFF;
- Program bulk IndexNow submission OFF;
- no mass removal of Program `noindex`;
- archive instead of destructive deletion.

Sheet removal control remains:

`AD = Programı Kaldır`

Historical rows without accepted server identity are never CREATEd merely to archive them.

---

## 5. TOUR SHEET → CANONICAL SITE CONNECTION

### Active Tour bound Apps Script

- `integrations/google-sheets/tours/ST-Tour-Generator.gs`
- `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs`
- `integrations/google-sheets/tours/ST-Tour-Menu.gs`

One-time installer:

`stTourInstall()`

Technical identity fields are header-driven and hidden from daily operator use:

- `STTI Stable ID`
- `Expected Checksum`

Fixed Z/AA targeting is retired. Business columns such as `Vize` must never be overwritten.

### Direct Sync contract

Contract: `ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Modes:

- `validate` — no canonical write;
- `apply` — canonical mutation only after accepted validation and explicit operator intent.

Secrets remain outside Git and Sheet cells.

### Production accepted selected-row fixture

```text
Local ID: ID-642D23A3
Canonical Stable ID: STT-000002
Current title: Iran Test Turu Update Test
```

Accepted CREATE:

```text
Local validation  → Target NEW
Remote validate   → STT-000002 — CREATE
Controlled Apply  → STT-000002 — CREATE
Second validate   → STT-000002 — UNCHANGED
```

Accepted UPDATE:

```text
Local validation  → Target STT-000002
Remote validate   → STT-000002 — UPDATE
Controlled Apply  → STT-000002 — UPDATE
Second validate   → STT-000002 — UNCHANGED
```

Acceptance:

```text
Selected-row CREATE               PASS
Selected-row UPDATE               PASS
Stable ID continuity              PASS
Checksum continuity               PASS
Post-write idempotency            PASS / UNCHANGED
Duplicate loop                    NOT OBSERVED
Bulk Tour mutation                NOT AUTHORIZED / NOT TESTED
Unexpected public/SEO mutation    NONE OBSERVED
```

Transport instability remains a monitoring item. After an uncertain timeout, validate/status first; never blindly repeat Apply.

---

## 6. OPERATOR-FIRST TOUR WORKFLOW — PRODUCTION ACCEPTED

Daily operator flow:

```text
Google Sheet row
→ Local validation
→ Direct Sync validate/apply
→ canonical STT-* candidate
→ Review Queue
→ resolve only source-backed blockers
→ Editorial Approval
→ optional explicit Hub visibility
→ optional Hub Master exposure
```

### Simple editor

Default operator view keeps common fields visible and hides technical complexity. Advanced mode preserves the canonical technical editor.

Accepted no-change save proof on `STT-000002`:

`Private candidate unchanged. Public output remains OFF.`

### Review Queue

The Review Queue summarizes canonical readiness for:

- source;
- route;
- geo;
- hotels;
- transport;
- dates;
- price;
- day-by-day program.

Unsupported facts are not guessed.

### Guarded editorial approval

Production-tested transition:

```text
STT-000002
NEEDS_REVIEW → APPROVED
Hard blockers: 0
```

During approval the following remained OFF:

- Public route;
- Hub visibility;
- Indexation;
- Sitemap;
- Homepage;
- Hub Master.

Editorial approval is therefore explicitly **not publication**.

---

## 7. STTI v1.1.7 — EXPLICIT HUB VISIBILITY GATE

Production QA found a critical semantic gap: under the older Hub rule, an editorially `APPROVED` Tour became Hub-eligible immediately even while the Approval screen showed `Hub görünürlüğü = KAPALI`.

This was unsafe because `STT-000002` is only a test fixture and **there is currently no real Iran Tour for sale**.

STTI v1.1.7 closes that gap.

### Required Hub eligibility

A Tour may be rendered in the Culture Tours Hub only when all of these are true:

```text
editorial = approved
AND not cancelled/archived
AND not past
AND publication.hub_visible = true
```

Missing `hub_visible` fails closed to `false`.

### Production evidence — STT-000002

```text
Editorial                    APPROVED
Hub visibility               KAPALI / false
Hub-visible Tour count       0
Dynamic Tour Hub Master      OFF
/kultur-turlari/             existing WordPress page preserved
```

`STT-000002` must remain a **test/private fixture**. Do not press `Hub’da Göster` for it.

### Separation of gates

```text
APPROVED
  ≠ Hub visible

Hub visible
  ≠ Hub Master enabled

Hub Master enabled
  ≠ individual Tour detail route public
  ≠ indexable
  ≠ sitemap included
  ≠ schema enabled
```

This separation is now a non-negotiable publication invariant.

---

## 8. CULTURE TOURS HUB — CURRENT PRODUCTION STATE

Existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Current state:

`OFF`

When OFF:

- the existing WordPress page remains unchanged;
- no dynamic STTI cards replace its content;
- rollback is immediate by design.

Current Hub-visible Tour count:

`0`

Do **not** enable Hub Master merely to test a fake/test Tour.

### First real Tour rollout sequence

When a real Culture Tour exists:

```text
1. Enter/update the real Tour in the Tour Sheet.
2. Direct Sync validate.
3. Controlled Apply.
4. Confirm post-write UNCHANGED/idempotency.
5. Resolve Review Queue blockers using real sources only.
6. Editorial Approval.
7. Explicitly set Hub visibility = AÇIK for that real Tour.
8. Verify Hub-visible count and private/admin preview.
9. Only with explicit owner approval enable Hub Master.
10. QA /kultur-turlari/ desktop + mobile immediately.
11. Roll back Hub Master OFF if any regression appears.
```

Individual Tour detail public/indexation/schema/sitemap gates remain separate and are not authorized by Hub activation.

---

## 9. TOUR PUBLIC / SEO BOUNDARY

Tour Intelligence, Sheet sync, editorial approval, Hub visibility and Hub Master must never silently enable:

- Tour Public Master;
- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure;
- mass Tour URL generation;
- mass Search Console submission.

No production Tour URL should be created/indexed merely because a Hub card is allowed.

---

## 10. WHOLE-SITE SEO BACKLOG

### P0 — fresh search-health evidence

At the next weekly SEO gate:

- export fresh Google Search Console Performance data;
- compare last 7 days vs previous 7 days and 28-day baseline;
- recheck index coverage and representative URLs;
- classify meaningful `Crawled — currently not indexed` URLs by content type;
- verify 404/5xx/robots findings before redirects or validation;
- do not mass-request indexing.

### P1 — commercial intent / CTR

Carry forward:

- improve CTR and intent alignment for high-impression Umrah informational pages;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları` demand;
- monitor Hotel entity query growth;
- strengthen first-party journey/trust evidence where real source material exists.

### P2 — global technical SEO

Continue after runtime stability:

1. OG layer cleanup without duplicate theme/plugin output;
2. image ALT/accessibility root-cause work;
3. intrinsic width/height and LCP image policy where appropriate;
4. structured data only from visible verified facts;
5. internal-link and crawl-depth improvements across Hub → Program/Tour → Hotel entities;
6. no fake/unsupported schema and no random SEO link injection.

### P3 — semantic generator work

Preserve zero-visual-change policy:

- do not redesign accepted Program cards for SEO;
- semantic/accessibility improvements must be visually neutral;
- test one representative Program first;
- regress standard / four-date / combined-route variants;
- only then batch-check the Program set.

---

## 11. CURRENT ACCEPTANCE REGISTER

```text
Program Intelligence 0.3.5                         LAST KNOWN LIVE / PRESERVE
Publishing Integration 0.4.14                      LAST KNOWN LIVE / PRESERVE
Hotel Intelligence 0.9.11                          LAST KNOWN LIVE / PRESERVE
Direct Sync server contract                        MERGED / ACCEPTED
Tour Sheet selected-row CREATE                     PRODUCTION ACCEPTED
Tour Sheet selected-row UPDATE                     PRODUCTION ACCEPTED
Tour Sheet post-write UNCHANGED                    PRODUCTION ACCEPTED
STTI Operator-first editor                         PRODUCTION ACCEPTED
STTI Review Queue                                  PRODUCTION ACCEPTED
STTI guarded Editorial Approval                    PRODUCTION ACCEPTED
STTI v1.1.7 explicit Hub visibility                PRODUCTION ACCEPTED
STT-000002 editorial state                         APPROVED / TEST FIXTURE
STT-000002 Hub visibility                          OFF / DO NOT ENABLE
Current Hub-visible Tours                          0
Dynamic Tour Hub Master                            OFF
Existing /kultur-turlari/ while Master OFF         PRESERVED
Program detail indexation                          OFF
Program sitemap                                    OFF
Tour public/indexation/schema/sitemap gates        OFF / SEPARATE
```

---

## 12. HARD STOP CONDITIONS

Stop before any mutation if there is:

- `CREATE` for a Tour believed to already exist;
- wrong `STT-*` Stable ID;
- `CONFLICT`, `INVALID` or `ERROR`;
- unexpected checksum replacement;
- duplicate Tour entity;
- unsupported Hotel/transport/route/geo facts being guessed;
- unexpected Hub visibility change;
- unexpected Hub Master change;
- unexpected public route/indexation/schema/sitemap/homepage change;
- uncertain response after timeout — validate/status before retry.

No bulk action is authorized merely because selected-row tests pass.

---

## 13. CURRENT EXECUTION ORDER

```text
1. Keep STT-000002 private/test; Hub visibility OFF                  ACTIVE RULE
2. Keep Dynamic Tour Hub Master OFF                                 ACTIVE RULE
3. Wait for / enter first real Culture Tour                         NEXT BUSINESS INPUT
4. Run Sheet → Direct Sync → Review → Approval                      NEXT RUNTIME FLOW
5. Explicitly enable Hub visibility only for the real Tour          SEPARATE OPERATOR GATE
6. QA Hub-visible count/admin preview                               REQUIRED
7. Enable Hub Master only with explicit owner approval              FUTURE OWNER GATE
8. QA /kultur-turlari/ desktop/mobile + rollback                    REQUIRED
9. Keep individual Tour public/indexation/schema/sitemap separate   NON-NEGOTIABLE
10. Run fresh Search Console weekly SEO gate                        WEEKLY OPERATIONS
11. Continue CTR/content/image/schema/internal-link backlog          AFTER STABILITY
```

---

## 14. REPOSITORY OPERATING METHOD

- one coherent branch per checkpoint;
- documentation reconciliation after accepted runtime changes;
- static/lint/runtime gates where applicable;
- no secret in Git;
- no direct uncontrolled Production mutation from repository work;
- no automatic publication;
- Production deployment and public activation remain distinct owner actions;
- never claim runtime or SEO acceptance without evidence.

---

## 15. NEXT CHECKPOINT

The Tour infrastructure is now ready for a **real Culture Tour**, but no real Tour should be invented merely to exercise the Hub.

Success for the next business-valid checkpoint is:

```text
real Tour entered in Sheet
+ canonical STT-* created/updated safely
+ post-write UNCHANGED verified
+ source-backed Review completed
+ Editorial APPROVED
+ explicit Hub visibility AÇIK for that real Tour only
+ test fixture STT-000002 remains Hub-hidden
+ Hub Master remains OFF until separate owner approval
+ no individual Tour SEO/public side effect
```


## STCA — Server Turizm Conversational Assistant

Status: **v1.0.0 repository candidate; production master OFF**.

STCA adds Instagram DM automation as a read-only channel over the existing canonical intelligence layers. Program Intelligence remains the owner of Umrah facts (STP-*), Hotel Intelligence remains the owner of hotel facts (STH-*), and Tour Intelligence remains the owner of tour facts (STT-*).

Hard locks:

- no writes to Program / Hotel / Tour Intelligence;
- no changes to public routes, indexation, sitemap, schema, canonical tags, homepage visibility or existing SEO gates;
- Instagram outbound replies default OFF and require explicit wp-config activation;
- Meta secrets remain outside Git;
- missing prices, dates, hotels, availability, visa facts or tour publication eligibility are never invented;
- Tour responses require explicit publication.hub_visible=true plus customer-safe lifecycle;
- cancelled, archived, completed or closed Umrah departures are excluded from current offers;
- CI must pass PHP/unit, disposable WordPress+MariaDB runtime, and verified ZIP packaging before installation.

Install/runbook: docs/STCA-INSTAGRAM-ASSISTANT.md and wordpress/plugins/conversational-assistant/docs/INSTALL.md.

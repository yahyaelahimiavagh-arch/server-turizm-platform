# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-16 — AUTHORITATIVE SITE-WIDE CHECKPOINT — TOUR SHEET DIRECT SYNC NEXT

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-16**
>
> Repository merge, Production plugin deployment, Google Sheets operator acceptance, public rendering, indexation, sitemap, schema and Search Console submission are separate gates. Never infer one from another.
>
> This checkpoint supersedes the stale 2026-09-15 `Tour Hub v1.1 — MERGE PENDING` wording. PR #17 and PR #18 are merged. Production deployment/activation remains separately controlled.

---

## 1. AUTHORITATIVE REPOSITORY CHECKPOINT

Repository:

`yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch:

`main`

Current merged `main`:

`c34ece83a3e05c3e8a8c5edc3c22e06d9eaf65e2`

This main includes:

- PR #17 — separated Umrah + Tour Google Sheets Apps Script runtimes;
- PR #18 — Tour Hub v1.1 dynamic `/kultur-turlari/` pipeline.

### Current truth

```text
STTI v0.6.5 AI Completion Contract                MERGED / CLOSED
STTI v0.7.0 WordPress Review Relations            MERGED / CLOSED
STTI v0.7.1 Canonical Geo Resolver                MERGED / CLOSED
STTI v0.8.0 Complete Customer Renderer            MERGED / CLOSED
STTI v0.9.0 First Real Full Tour                  MERGED / CLOSED
STTI v1.0.0 Controlled Public Tour Pilot          MERGED / CONTROLLED GATES
Direct Sync foundation                            MERGED / SERVER CONTRACT ACCEPTED
Separated Umrah/Tour Sheet stacks                 MERGED / PRODUCTION-OBSERVED
Tour Hub v1.1                                     MERGED / REPO+RUNTIME ACCEPTED
Tour Hub v1.1 Production installation             UNVERIFIED / SEPARATE OWNER GATE
Tour Hub live activation                          OFF / SEPARATE OWNER GATE
```

The old `v0.7.0` continuation is no longer the active engineering checkpoint. Its relation model, canonical geo successor, renderer, real Tour acceptance, Direct Sync and Hub work have all advanced beyond it.

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

Do **not** treat the 2026-09-11 Search Console numbers as current 2026-09-16 measurements. The next weekly search-health gate must use a fresh export before drawing a new trend conclusion.

### Preserved live SEO baselines

Preserve unless new regression evidence appears:

- homepage canonical/indexed baseline;
- Homepage Intelligence accepted baseline;
- Homepage Journey Evidence accepted baseline;
- `/umre-1/` commercial Hub architecture;
- accepted Umrah Hub visual baseline — no redesign during unrelated engineering;
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

`1.1.0`

Production Tour Intelligence v1.1 installation is not inferred from repository Merge. Last documented live release remains separately verifiable.

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

Tour Intelligence and Direct Sync must never silently enable:

- Tour Public Master;
- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure;
- mass Tour URL generation.

The v1.0 controlled pilot architecture remains an independent allowlisted gate. The existence of a canonical `STT-*` record does not mean the Tour is public or indexable.

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

Run once after the three files are present:

`stTourInstall()`

Expected operator menu:

```text
Ön Kontrol — Seçili Tur
Siteyi Güncelle — Seçili Tur
Seçili Turu Arşivle
Seçili Satırı STTI Kaydına Bağla
Seçili Satır Bağını Temizle
Teknik Stable ID/Checksum Hazırla / Gizle
Local Satır Kontrolü
Partial JSON Göster
Partial JSON İndir
Direct Sync Ayarları
```

Technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

Rules:

- both belong together;
- if absent they are appended after the current business columns;
- they remain hidden from normal operator use;
- business columns such as `Vize` must never be overwritten;
- fixed Z/AA targeting is retired.

Generator implementation remains `1.0.2`; canonical payload `producer.version` remains `0.6.2.1` intentionally to avoid accepted checksum drift.

---

## 8. TOUR SHEET → SITE CONNECTION — ACTIVE NEXT RUNTIME GATE

The user has copied the current separated Tour scripts into the Tour Sheet. The next checkpoint is **connection verification**, not bulk publication.

### Required sequence

1. Confirm the Apps Script project contains only the accepted three Tour files.
2. Run `stTourInstall()` once and authorize the script.
3. Reload the spreadsheet and confirm the `🧭 Server Turizm Tours` menu appears.
4. Verify the Production Direct Sync Foundation plugin is active and the REST gateway exists.
5. Verify the server Key ID + HMAC Secret are configured securely.
6. In the Tour Sheet choose `Direct Sync Ayarları` and set the HTTPS endpoint, Key ID and Secret into Script Properties.
7. Prepare/hide the technical Stable ID + checksum columns if needed.
8. Select one known existing Tour row — preferably the accepted `STT-000001 / Büyük İran Turu` fixture when its source row is unchanged.
9. Run `Ön Kontrol — Seçili Tur` first.
10. Expected first proof for an unchanged linked fixture is:

```text
STT-000001 — UNCHANGED
```

11. If validation returns unexpected `CREATE`, `UPDATE`, `CONFLICT`, `INVALID` or `ERROR`, **STOP** and do not Apply.
12. Only after the expected validation result, run `Siteyi Güncelle — Seçili Tur`.
13. Re-run validate to prove idempotency / unchanged state.
14. Only after this no-change connection proof should a separately chosen intentional Tour edit be tested as a controlled `UPDATE`.
15. Do not start mass Tour operations from the Sheet during the first connection checkpoint.

### Connection acceptance target

```text
Menu installed                         PASS
Endpoint/auth configured               PASS
Known row validate                     UNCHANGED
Known row apply                        UNCHANGED
Second validate                        UNCHANGED
Duplicate STT entity                   NONE
Unexpected public/indexation change    NONE
```

---

## 9. TOUR HUB v1.1 — MERGED REPOSITORY STATE

Contract:

`STTI-TOUR-HUB-1.1.0`

Existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Default:

`OFF`

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

Future pipeline:

```text
Google Sheet Tour row
→ Direct Sync validate/apply
→ canonical STT-* record
→ human review / editorial approval
→ Tour Hub eligibility
```

### Current boundary

PR #18 is merged into `main`, but this does **not** prove Production has Tour Intelligence v1.1 installed or Hub Master enabled.

Production install and live Hub activation remain separate explicit owner gates.

---

## 10. SEO BACKLOG — PRESERVED / RE-PRIORITIZED

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

Preserved whole-site work:

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

Image SEO and structured data remain later layers after semantic-generator stability, not reasons to destabilize the current accepted UI.

---

## 11. SAFETY / OWNERSHIP RULES — NON-NEGOTIABLE

Preserve:

- Program facts → Program Intelligence;
- Tour facts → Tour Intelligence;
- Hotel facts/media → Hotel Intelligence;
- publication route/mode → Publishing registry / controlled Tour gates;
- Sheet → canonical sync uses immutable Stable IDs and checksum concurrency;
- removal → archive, never ordinary hard delete;
- unknown Tour facts stay unknown; AI/operator tooling must not invent Hotels, transport, dates or route facts;
- public/indexation changes require explicit staged gates;
- repository acceptance never means Production deployment;
- Production deployment never means indexation approval.

---

## 12. CURRENT ACCEPTANCE REGISTER

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
Direct Sync Production exact version                    REVERIFY BEFORE NEW ROLLOUT
Separated Umrah Apps Script selected-row path           PRODUCTION OBSERVED / ACCEPTED
Separated Tour Apps Script selected-row path            PRODUCTION OBSERVED / ACCEPTED
Tour Hub v1.1 repository/static/runtime                 MERGED / ACCEPTED
Tour Hub v1.1 Production plugin installation            PENDING / UNVERIFIED
Tour Hub Master live activation                         OFF / PENDING OWNER GATE
Tour Sheet current connection                           NEXT RUNTIME CHECKPOINT
Program detail indexation                               OFF
Program sitemap                                         OFF
Tour public/indexation/schema/sitemap mass gates        OFF / SEPARATE
```

---

## 13. CURRENT EXECUTION ORDER

```text
1. Reconcile authoritative documentation with merged PR #17/#18      NOW
2. Verify Production Direct Sync plugin/gateway configuration        NEXT
3. Install/authorize Tour-only Apps Script menu                       NEXT
4. Configure Tour Direct Sync Script Properties                       NEXT
5. Prove known existing Tour validate = UNCHANGED                    REQUIRED
6. Prove apply/revalidate idempotency = UNCHANGED                    REQUIRED
7. Test one intentional controlled Tour UPDATE                        LATER GATE
8. Verify canonical STT + human review flow                           REQUIRED
9. Production-install Tour Intelligence v1.1 with Hub Master OFF      SEPARATE OWNER GATE
10. Verify old `/kultur-turlari/` page remains intact while OFF       REQUIRED
11. Inspect real eligible Hub cards                                   LATER
12. Enable Hub Master only with explicit owner approval               LATER
13. Run fresh weekly SEO/Search Console gate                          WEEKLY OPERATIONS
14. Continue CTR/content/image/schema/internal-link backlog            AFTER RUNTIME STABILITY
```

---

## 14. HARD STOP CONDITIONS FOR TOUR SHEET ROLLOUT

Stop before Apply if any of these occurs unexpectedly:

- `CREATE` for a Tour believed to already exist;
- wrong `STT-*` Stable ID;
- `CONFLICT`;
- `INVALID`;
- `ERROR`;
- unexpected checksum replacement;
- duplicate Tour entity;
- public route/indexation/schema/sitemap state changes;
- WordPress gateway/plugin version cannot be verified;
- authentication secret/key provenance is uncertain.

No bulk action is authorized merely because a single-row test passes.

---

## 15. REPOSITORY OPERATING METHOD

- one coherent branch per checkpoint;
- documentation reconciliation before the next Production mutation;
- static/lint/runtime gates where applicable;
- no direct Production mutation from repository work;
- no secret in Git;
- no automatic merge without explicit owner approval;
- Production plugin installation/activation remains a separate owner action;
- never claim runtime or SEO acceptance without evidence.

---

## 16. NEXT CHECKPOINT

The next operational objective is **Tour Sheet → Production Direct Sync connection proof** using a known existing row and `validate` first.

Success is not “the button worked.” Success is:

```text
known Stable ID
+ correct checksum
+ UNCHANGED on no-change validation
+ UNCHANGED on no-change apply/revalidate
+ no duplicate canonical entity
+ no unexpected public/indexation effect
```

After that proof, the project can move to one controlled Tour update and then to the separately approved Tour Hub v1.1 Production installation path.

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

---

## 16. P14 — INSTAGRAM SOCIAL DISTRIBUTION SYSTEM — 2026-10-03 PLANNING CHECKPOINT

Status:

```text
P14A  Instagram / Metricool connection + baseline audit     COMPLETE
P14B  Content system / pillars / highlights / design rules   ACTIVE
P14C  Umrah campaign map + publishing calendar               NEXT
P14D  Controlled publishing pilot                            LOCKED
P14E  Weekly analytics / optimization loop                   FUTURE
```

This checkpoint is **planning only**.

No Instagram post, Reel, Story or scheduled publication is authorized by this documentation update.
No paid media spend is authorized.

### 16.1 Connected channel and analytics source

Connected Instagram account:

`@servertur`

Metricool brand:

`Server Turizm`

Metricool brand ID:

`7222927`

Operational timezone:

`Europe/Istanbul`

Metricool is the current social analytics / scheduling layer. It does **not** become the canonical owner of Program, Hotel or Tour facts.

Canonical ownership remains:

- Umrah / Program facts → Program Intelligence;
- Hotel facts → Hotel Intelligence;
- Tour facts → Tour Intelligence.

For content planning, the current Google Sheet operational source is:

`Server Turizm Umre Programlari 2026 Control Panel`

Spreadsheet ID:

`1PLHeqb9ZD4z8c7kWQ1-6zDdQ38KEeaNYTf9R8DA1DVg`

Before any publication, prices, dates, hotels, route, duration and availability claims must be reconciled against the current canonical/operational source. Social copy must never invent or infer missing commercial facts.

### 16.2 First Instagram baseline — snapshot at 2026-10-03

Current follower count observed through Metricool:

`1,472`

Current following count:

`13`

Audience geography snapshot:

- Türkiye: ~94.03%;
- Istanbul: ~32.72% of the audience;
- next notable cities include Ankara, Bursa and Izmir.

Audience age signal:

- strongest age concentration is 35–54;
- female audience is the largest known gender segment;
- unknown-gender rows remain present, so gender percentages must not be overstated.

Interpretation for content design:

- trust, clarity, family relevance, spiritual context and practical travel guidance should dominate;
- the brand should not imitate youth-only trend accounts;
- real journey footage, human proof and useful Umrah guidance should be prioritized over poster-only communication.

### 16.3 Historical content findings — observed, not yet a final publishing rule

Observed 2026-07 to 2026-10 content shows a material difference between repetitive Friday-message Feed posts and commercial/travel content.

Approximate observed averages from the audited sample:

```text
Friday / prayer-style Feed posts
  Avg Reach          ~161
  Avg Interactions   ~6.8

Umrah / travel / commercial Feed posts
  Avg Reach          ~596
  Avg Interactions   ~19.5
```

This suggests repetitive Friday-message artwork should not consume a fixed weekly Feed slot by default. Friday greetings can be Story-first, with occasional exceptional Feed use when there is a strong creative or campaign reason.

Best observed Feed example in the audited period:

`Öğretmenler ile Kasım Umresi — 2026-08-11`

Observed metrics:

```text
Reach          1,127
Interactions      38
Likes             27
Saves              5
Shares             4
Follows            3
```

Working lesson:

A clearly defined audience + specific Umrah proposition + dates + hotels + price + human/religious context performs better than generic offer artwork.

Notable Reel signals:

`Özbekistan — 2026-09-09`

```text
Views                  429
Reach                  310
Interactions            21
Average watch time   10.965 s
3-second view rate    44.9%
```

The strongest distribution Reel in the audited sample was the 2026-08-14 religious Reel:

```text
Views          2,279
Reach          1,964
Interactions     114
```

Working lesson:

- spiritual/religious themes can distribute strongly;
- real travel footage can hold attention well when shown;
- future Reels should combine stronger hooks/packaging with real journey footage rather than rely on poster animation.

### 16.4 Story evidence limitation

Metricool returned no historical Story rows for the audited lookback period.

Therefore:

- do not make retrospective claims about past Story performance;
- begin prospective Story measurement from this checkpoint onward;
- Story strategy must be treated as a controlled learning loop.

### 16.5 Current posting-time signal

Metricool currently shows the strongest audience-presence window approximately:

`19:00–22:00 Europe/Istanbul`

Current peak signal:

`21:00`

This is a **working window**, not a permanent rule.

Current day-of-week output is not sufficiently differentiated to claim a best weekday. Day-level optimization must wait for controlled publishing evidence.

### 16.6 Content architecture — v0 planning model

Initial target mix:

```text
Feed publications / week: 4
  Reels:                      2
  Educational / Trust:        1
  Umrah / Tour Offer:         1

Stories:
  near-daily presence
  exact cadence to be validated prospectively
```

The Feed must not become a price-board or static catalogue.

Every content item must primarily serve at least one of:

1. Discovery / reach;
2. Trust / proof;
3. Education / save / share;
4. Lead generation / commercial conversion.

Core content pillars to design before publishing:

- Umrah education;
- program comparison;
- hotel / location proof;
- passenger experience / testimonials;
- real journey footage;
- staff / guide / company trust;
- passport / registration / preparation guidance;
- Makkah / Madinah practical guidance;
- seasonal religious moments;
- active Umrah offers;
- culture-tour storytelling where a real saleable Tour exists.

### 16.7 Planned Highlight architecture

Target information architecture:

```text
UMRE
TURLAR
OTELLER
YORUMLAR
NASIL KAYIT?
PASAPORT
SSS
HAKKIMIZDA
İLETİŞİM
```

Highlight naming, ordering, cover system and source content must be finalized in P14B before implementation.

### 16.8 Umrah campaign inventory — future departures only

Rule for campaign eligibility:

- ignore Programs whose **departure date has already passed**;
- a Program may still be active operationally after departure, but it is excluded from new sales-content planning unless explicitly requested;
- never infer remaining capacity from dates alone;
- never publish `Son X Kontenjan`, `Dolmak üzere`, `Son Yerler` or equivalent scarcity claims without current source-backed availability data.

As of 2026-10-03, the Sheet contains **38 future-departure Programs**, from Program row No. 21 through No. 58.

Current campaign waves:

#### Wave 0 — immediate / departure 2026-10-08

```text
225  Lüks       9 Gece 10 Gün
226  Ekonomik   9 Gece 10 Gün
227  Ekonomik  13 Gece 14 Gün
```

Because the departure is very close, this wave is not a normal long-form campaign. It should only be used if sales are still open and current availability is verified.

#### Wave 1 — 2026-10-29 cluster

```text
228  Lüks       9 Gece 10 Gün
229  Ekonomik   9 Gece 10 Gün
230  Ekonomik  13 Gece 14 Gün
```

This is the first full controlled campaign candidate.

Planning concept:

`29 Ekim Umre Programları — Size Uygun Seçeneği Bulun`

The three variants should be treated as a comparison family rather than three unrelated poster posts.

#### Wave 2 — November 2026

Key Programs:

- 12 Nov — Mısır Bağlantılı Umre;
- 13 Nov — Zekerya Kurt Hoca Umre Programı;
- 14 Nov — Öğretmenler ile Kasım Umresi;
- 19 Nov — standard Lüks / Ekonomik cluster;
- 21 Nov — Üçler Elektrik Lüks Umre Programı.

`Üçler Elektrik` is treated as a potentially private/group-specific Program until commercial-public eligibility is explicitly confirmed.

#### Wave 3 — December 2026 / January 2027 seasonal moments

Campaign families include:

- Regaip Kandili;
- Mekke'nin Fethi;
- Miraç Kandili;
- Beraat Kandili;
- January standard Lüks / Ekonomik Programs.

These are seasonal storytelling opportunities and must not be reduced to price-only posts.

#### Wave 4 — Ramadan 2027

Programs:

`R501–R507`

Campaign themes include:

- Tam Ramazan;
- Ramazanı Karşılayan;
- Tam Ramazan Mekke / Bayram Medine;
- Kadir Gecesi Mekke / Bayram Medine;
- Kadir Gecesi Medine / Bayram Mekke;
- shorter Ramadan Umrah variants.

Ramadan is a dedicated campaign system and requires its own content runway rather than a single launch post.

#### Wave 5 — Şevval 2027

Programs:

`R508–R509`

These remain future campaign inventory and are not near-term publishing priorities.

### 16.9 Campaign content ladder

A normal priority Umrah Program should be capable of generating a controlled sequence such as:

```text
Program introduction
→ comparison / who it is for
→ hotel proof
→ practical FAQ
→ Reel / human story
→ Story Q&A / poll
→ verified urgency / countdown
→ final CTA
```

Not every Program needs every step.

Campaign depth is chosen from:

- departure proximity;
- commercial importance;
- distinctiveness;
- available real media;
- verified capacity;
- historical performance.

### 16.10 Design-system direction

Existing Server Turizm brand direction remains:

- Navy / dark blue;
- Gold;
- Ivory;
- Orange only as a controlled accent / CTA;
- Charcoal where needed.

Target social formats:

- Feed portrait: 1080×1350;
- Story / Reel: 1080×1920;
- Square only when the content genuinely requires it.

Rules:

- Offer posts use structured hierarchy and readable pricing;
- Carousels separate information instead of compressing every fact into one poster;
- Reels should prioritize real footage and human moments;
- branding on Reels should focus on cover/title/typography, not heavy motion graphics;
- no long logo intro before the hook.

### 16.11 P14B deliverables — ACTIVE

Before any normal publishing cadence begins, complete:

1. Content pillars and decision rules;
2. Highlight architecture and cover system;
3. Umrah offer template family;
4. Comparison-carousel template;
5. Reel cover and typography system;
6. Story template family;
7. CTA library;
8. passenger-proof / testimonial format;
9. hotel-content format;
10. FAQ / educational format;
11. 30 qualified Reel concepts;
12. October publishing calendar;
13. competitor shortlist and benchmark method;
14. measurement scorecard.

### 16.12 P14C — calendar construction

After P14B acceptance:

- map each future Umrah cluster into campaign windows;
- prioritize 29 Oct and November waves;
- keep non-commercial education/trust content between offers;
- assign a content objective and KPI to every publication;
- use the 19:00–22:00 window as the first test range;
- do not schedule everything at once;
- preserve room for real-time passenger/trip content.

### 16.13 P14D — controlled publishing pilot — LOCKED

Unlock only after owner acceptance of P14B/P14C.

Pilot rules:

- begin with a small number of scheduled posts;
- confirm text, media, Program facts and CTA before scheduling;
- no fake scarcity;
- no unverified hotel / price / capacity claim;
- no automatic bulk posting;
- no paid boost by default;
- no deletion of existing posts merely because they underperformed historically.

Metricool may be used for scheduling only after this gate is explicitly opened.

### 16.14 P14E — weekly optimization loop

Once the pilot is active, review weekly:

#### Reels
- views;
- reach;
- average watch time;
- retention;
- 3-second view rate;
- shares;
- saves;
- follows.

#### Feed / Carousel
- reach;
- interactions;
- saves;
- shares;
- follows;
- profile / lead intent where available.

#### Stories
- reach;
- replies;
- exits;
- taps forward;
- taps back;
- link / CTA actions where available.

Optimization rule:

Do not chase raw views alone.

A content type may be valuable because it creates saves, shares, leads or trust even when it is not the top-viewed item.

### 16.15 Social hard-stop conditions

Stop before scheduling or publishing if:

- Program departure has passed;
- price/date/hotel/route data is stale or conflicting;
- availability is unknown but scarcity language is present;
- a group/private Program is being treated as public without confirmation;
- media rights/source are unclear;
- AI-altered content requires disclosure but is not marked appropriately;
- requested caption or creative introduces unsupported claims;
- publishing would bypass the approved campaign calendar without a genuine real-time reason.

### 16.16 P14 immediate execution order

```text
1. Keep publishing OFF                                              ACTIVE RULE
2. Finalize content pillars / Highlights / template system          P14B ACTIVE
3. Build October campaign calendar                                  P14C NEXT
4. Map 29 Oct cluster as first full campaign                        PRIORITY
5. Map November special Programs                                    PRIORITY
6. Prepare 30 Reel concepts                                         P14B
7. Define CTA + lead-attribution convention                         P14B
8. Approve first small publishing pilot                             OWNER GATE
9. Schedule only approved pilot content through Metricool           P14D
10. Review metrics weekly and revise rules                          P14E
```

Success condition for the planning checkpoint:

```text
Instagram connected
+ analytics baseline captured
+ future Umrah inventory mapped
+ past departures excluded
+ content system documented
+ no publication side effect
+ next action is plan design, not posting
```

### 16.17 P14B confirmed operating inputs — 2026-10-03

The following inputs are owner-confirmed and should be treated as the current working truth for P14B planning.

#### Media sources

Existing first-party media is hosted on Server Turizm infrastructure and is accessible through:

- Video Gallery: `https://www.serverturizm.com.tr/video-galeri/`
- Photo Gallery: `https://www.serverturizm.com.tr/foto-galeri/`
- Umrah Hub / Program media: `https://www.serverturizm.com.tr/umre-1/`

For final social production, prefer original hosted media files over screenshots or re-downloaded social-media copies whenever available.

#### Public/private Program rule

`Üçler Elektrik Lüks Umre Programı` is confirmed **PRIVATE**.

Rule:

- do not include it in public Instagram sales campaigns;
- do not create public offer posts, Reels or Stories for it unless owner explicitly changes its status;
- it may remain operationally present in the Program system.

#### Language

Social content language:

`Turkish only`

Do not introduce Persian, Arabic or English social-copy variants by default.

#### Lead CTA

Primary commercial contact channels:

- WhatsApp;
- telephone.

Working CTA hierarchy for P14B:

- Stories / mobile-first content → WhatsApp should normally be the primary action;
- Feed / Carousel / trust content → WhatsApp + telephone can both be visible;
- avoid cluttering every creative with multiple competing CTAs.

#### On-camera availability

Current presenter availability is not yet confirmed.

- Yahya: possible, requires camera test;
- Manager: permission / availability must be asked;
- Religious guide: difficult access;
- Tour guide: difficult access.

Therefore P14B must not depend on a single recurring on-camera personality.

The Reel system should support:

1. real trip footage + text hook;
2. real trip footage + voice-over;
3. staff/on-camera presenter when available;
4. passenger-generated content when permission exists.

#### Passenger testimonial gap

No usable passenger video-testimonial library currently exists.

Action required:

- create a simple recording request;
- send it to group leaders / tour leaders;
- collect short vertical testimonials from real passengers with publication permission;
- create a standard filming guide so footage is usable for Reels and Stories.

Until that pipeline exists, do not fabricate testimonial-style content or imply quotes/reviews that were not actually received.

#### Design production tool

Current operator preference / capability:

`Adobe Illustrator`

P14B should treat Illustrator as the primary static-design production tool.

Canva is optional for:

- fast resize/adaptation;
- team-editable derivatives;
- repetitive template operations;
- lightweight collaboration.

Canva is not required to replace Illustrator for brand-critical master artwork.

### 16.18 P14B-1 — Content Pillars v1 — ACCEPTED PLANNING BASELINE

The Server Turizm Instagram system will use **seven content pillars**. These are planning categories, not rigid quotas; weekly mix can change based on active departures, available real media and measured performance.

#### Pillar A — Umrah Education / Practical Guidance

Purpose:

- create saves and shares;
- answer common pre-purchase and pre-departure questions;
- build expertise and reduce repetitive support questions.

Primary formats:

- Carousel;
- short Reel with text/voice-over;
- Story FAQ.

Example topics:

- İlk kez Umreye gideceklerin bilmesi gerekenler;
- Umre valizinde ne olmalı?;
- Pasaport fotoğrafı nasıl çekilmeli?;
- 9 günlük ve 14 günlük program arasındaki fark;
- Mekke ve Medine oteli seçerken nelere dikkat edilmeli?;
- Çocukla Umreye giderken hazırlık;
- İhram öncesi hazırlık;
- bagaj / kayıt / evrak guidance using verified current company rules.

Primary KPI:

- Saves;
- Shares;
- Replies;
- qualified WhatsApp questions.

#### Pillar B — Program Discovery / Comparison / Offer

Purpose:

- convert active Program inventory into understandable choices;
- generate qualified WhatsApp / telephone leads;
- avoid a poster-only catalogue.

Primary formats:

- comparison Carousel;
- Reel explainer;
- Story sequence;
- single-image post only when there is a strong reason.

Rules:

- cluster similar departures instead of posting near-identical offers separately;
- use current Program Intelligence / operational Sheet facts;
- show who the option is for, not only price;
- never publish unsupported scarcity.

Examples:

- Lüks mü Ekonomik mi?;
- 9 Gece 10 Gün vs 13 Gece 14 Gün;
- 29 Ekim Umre seçenekleri;
- Ramazan programları karşılaştırması.

Primary KPI:

- WhatsApp starts;
- telephone enquiries;
- profile actions;
- qualified leads;
- eventual confirmed booking attribution.

#### Pillar C — Hotel / Route / Experience Proof

Purpose:

- make the package tangible;
- answer the customer's implicit question: "Nerede kalacağım ve yolculuk nasıl olacak?";
- strengthen trust with source-backed visuals.

Primary sources:

- Server Turizm Umrah Hub;
- first-party hotel galleries;
- first-party trip photography/video;
- Hotel Intelligence.

Primary formats:

- hotel Carousel;
- short room/location Reel;
- Story hotel walkthrough;
- route explainer.

Examples:

- Mias Al Madina nasıl bir otel?;
- Dar Al Ghufran'ın konum avantajı;
- Ekonomik ve Lüks oteller arasındaki pratik fark;
- Mekke → Medine transfer günü nasıl ilerler?.

Primary KPI:

- Saves;
- Shares;
- dwell / watch signals;
- hotel-specific lead questions.

#### Pillar D — Real Journey / Human Story

Purpose:

- create discovery and emotional connection;
- prove that Server Turizm actually operates real journeys;
- replace generic stock visuals with authentic travel moments.

Primary sources:

- `/video-galeri/`;
- `/foto-galeri/`;
- group leaders;
- future passenger submissions.

Primary formats:

- Reel;
- Story;
- occasional photo Carousel.

Examples:

- airport departure;
- group arrival;
- first moments in Makkah / Madinah;
- group ziyarets;
- bus / transfer moments;
- guide moments;
- return / farewell montage.

Rules:

- do not overbrand;
- hook immediately;
- no long logo intro;
- preserve dignity and privacy;
- secure publication permission where required.

Primary KPI:

- Non-follower reach;
- Views;
- Average watch time;
- Retention;
- Shares;
- Follows.

#### Pillar E — Trust / Company Proof

Purpose:

- explain why the customer should trust Server Turizm;
- make the business feel human and established.

Verified site-level trust signals available for use include:

- operating since 1998;
- public office/contact information;
- existing travel-program infrastructure;
- current verified memberships/airline references only where source-backed and current.

Primary formats:

- Carousel;
- office / behind-the-scenes Reel;
- Story;
- staff introduction.

Examples:

- 1998'den bugüne Server Turizm;
- rezervasyon süreci nasıl işliyor?;
- ofise gelmeden işlemler nasıl ilerliyor?;
- yolculuk öncesi ekip ne hazırlıyor?;
- Tur danışmanına nasıl ulaşırsınız?.

Primary KPI:

- Profile visits/actions;
- WhatsApp contacts;
- replies;
- conversion support rather than raw reach.

#### Pillar F — Passenger Voice / Social Proof

Purpose:

- create credible, human proof from real passengers;
- build a reusable testimonial library.

Current state:

`SOURCE GAP — no structured video-testimonial library yet`

Until real material is collected, this pillar remains partially blocked.

Planned capture standard:

- vertical 9:16;
- 10–30 seconds;
- quiet environment;
- natural speech;
- one clear idea per clip;
- publication permission;
- no scripted fake praise.

Suggested prompts for group leaders:

1. `Server Turizm ile yolculuğunuz nasıldı?`
2. `En memnun kaldığınız şey neydi?`
3. `Umreye gidecek birine ne söylemek istersiniz?`

Use one or two prompts per passenger; do not force all three into every clip.

Primary KPI:

- Shares;
- Replies;
- WhatsApp leads;
- assisted conversion.

#### Pillar G — Spiritual / Seasonal Connection

Purpose:

- serve the audience's spiritual context without allowing generic religious content to dominate the business Feed;
- connect seasonal moments to real travel relevance where appropriate.

Primary formats:

- Story;
- Reel with authentic sacred-place footage;
- occasional exceptional Feed post.

Examples:

- Cuma;
- Kandil periods;
- Ramazan;
- Kadir Gecesi;
- Mekke'nin Fethi campaign context;
- reflective moments from real journeys.

Rule:

Generic weekly greeting artwork must not automatically occupy a Feed slot.

Whenever possible, combine spiritual relevance with authentic first-party journey imagery or useful context.

Primary KPI:

- Reach;
- Shares;
- Replies;
- community connection.

### 16.19 Working weekly content balance

Initial controlled target — **not a permanent quota**:

```text
Discovery / Human Journey / Reel       25–30%
Education / FAQ / Save-worthy          20–25%
Program / Offer / Comparison           20–25%
Hotel / Route / Experience             10–15%
Trust / Company                         10–15%
Passenger Voice                         grows as source library is created
Seasonal / Spiritual                    event-driven, Story-first
```

Do not force a category into a week merely to satisfy percentages.

The active Program calendar and real first-party media always take precedence over artificial quota completion.

### 16.20 Format-to-purpose rule

Working format assignment for 2026:

```text
Reel       → discovery, human proof, journey, fast education
Carousel   → comparison, FAQ, checklists, save/share value
Story      → daily connection, Q&A, countdown, WhatsApp CTA, live journey
Single     → exceptional announcements / brand moments only
```

This is intentionally aligned with current platform evidence showing Reels as a strong discovery/interaction format and Carousels as a strong save-oriented format. The Server Turizm account's own baseline must override broad industry averages when future account evidence conflicts.

### 16.21 P14B-2 — Highlight Information Architecture v1

Target Highlight order:

```text
1. UMRE
2. TURLAR
3. OTELLER
4. YORUMLAR
5. NASIL KAYIT?
6. PASAPORT
7. SSS
8. HAKKIMIZDA
9. İLETİŞİM
```

Detailed roles:

#### UMRE
- currently saleable public Umrah families;
- avoid permanent stale price cards;
- use link / WhatsApp CTA to current details.

#### TURLAR
- only real public culture tours;
- private group programs excluded.

#### OTELLER
- Makkah / Madinah hotel proof;
- current program-relevant hotel media;
- location/benefit explanations.

#### YORUMLAR
- real passenger testimonials;
- starts sparse and grows;
- no invented quotes.

#### NASIL KAYIT?
- contact;
- consultation;
- required info;
- payment / operational steps only if current and verified;
- office-visit requirement or remote process as actually supported.

#### PASAPORT
- how to photograph first page;
- MRZ, passport number, name/surname and details must be fully readable;
- common mistakes;
- privacy-sensitive handling guidance.

#### SSS
- practical Umrah questions;
- baggage;
- children;
- meals;
- transportation;
- registration;
- verified program conditions.

#### HAKKIMIZDA
- since 1998;
- office/team;
- trust proof;
- service approach.

#### İLETİŞİM
- WhatsApp;
- telephone;
- office;
- working hours;
- website.

### 16.22 Highlight visual direction

Covers should be a unified family, not nine unrelated illustrations.

Working cover rule:

- dark Navy base;
- Ivory / Gold iconography;
- maximum one simple symbol per Highlight;
- short uppercase Turkish label;
- no photographic thumbnail as the master cover;
- legible at small circular size.

Final artwork master should be created in Adobe Illustrator.

### 16.23 P14B-3 — Reel production modes

Because stable presenter availability is not confirmed, the Reel system must work in four modes:

```text
R1  First-party footage + on-screen text
R2  First-party footage + Turkish voice-over
R3  Presenter-to-camera + supporting B-roll
R4  Passenger / group-leader UGC + brand outro
```

Priority at launch:

`R1 + R2`

R3 is added after camera tests.

R4 expands once the testimonial capture pipeline starts.

Each Reel should normally contain:

```text
0–2 s       Hook / payoff
2–8 s       proof / context
8–20 s      useful content or experience
final       one clear CTA or conclusion
```

This is a working creative structure, not a hard duration limit.

### 16.24 P14B next open items

Still required before P14B can close:

1. static design template family;
2. typography / safe-area rules;
3. CTA library;
4. testimonial request message for group leaders;
5. media capture guide;
6. 30 Reel concept bank;
7. KPI scorecard;
8. lead-attribution naming convention;
9. competitor benchmark shortlist / audit;
10. first October calendar draft.

Publishing remains LOCKED.

### 16.25 P14B-4 — CTA System v1

The default rule is **one primary action per content item**.

Commercial hierarchy:

```text
Primary mobile lead action     WhatsApp
Secondary lead action          Telephone
Support / discovery action     Website
Community action               Reply / Comment / Save / Share
```

Do not place WhatsApp, phone, website, DM, save, share and comment CTAs together in every creative.

#### CTA classes

`CTA-SALE-WA`
- use when the viewer is expected to ask about a current Program;
- wording should be direct and low-friction.

Examples:
- `Program detayları için WhatsApp'tan bize yazın.`
- `Size uygun Umre programını birlikte seçelim — WhatsApp'tan ulaşın.`
- `Tarih ve oda seçenekleri için tur danışmanımıza WhatsApp'tan yazın.`

`CTA-SALE-PHONE`
- use for high-intent or older-audience creatives where telephone support is useful.

Examples:
- `Detaylı bilgi için: 0212 621 05 00`
- `Tur danışmanımızla görüşmek için bizi arayın.`

`CTA-SAVE`
- use on checklists, preparation and educational Carousels.

Examples:
- `Umre hazırlığında tekrar bakmak için kaydedin.`
- `Bu listeyi yolculuk öncesi kullanmak için kaydedin.`

`CTA-SHARE`
- use when the content naturally helps a travel companion / family member.

Examples:
- `Umreye birlikte gideceğiniz kişiye gönderin.`
- `Bu bilgiyi ihtiyacı olan bir yakınınızla paylaşın.`

`CTA-REPLY`
- use in Stories for qualification and conversation.

Examples:
- `İlk Umreniz mi? Cevabınızı bize yazın.`
- `Lüks mü ekonomik mi düşünüyorsunuz?`

`CTA-COMMENT`
- use selectively where a genuine answer is useful;
- do not manufacture meaningless engagement bait.

Example:
- `Sizin için en önemli konu hangisi: otel konumu, süre, yoksa fiyat?`

### 16.26 CTA placement rules

Feed / Carousel:

- primary CTA usually in final slide + caption ending;
- WhatsApp and phone can both exist, but visual hierarchy must make one primary.

Reels:

- avoid covering the first seconds with contact information;
- hook first, CTA at the end or in caption;
- WhatsApp CTA is preferred for current Programs.

Stories:

- one action per frame whenever possible;
- use WhatsApp / link / reply action as appropriate;
- avoid tiny phone numbers over busy footage.

### 16.27 P14B-5 — Passenger testimonial capture system

Current objective:

Create a repeatable first-party social-proof pipeline from active Umrah groups.

#### Group-leader request message — Turkish master copy

```text
Selamünaleyküm hocam,

Server Turizm'in sosyal medya içeriklerinde gerçek yolcu deneyimlerine daha fazla yer vermek istiyoruz.

Müsait olduğunuz bir anda, gönüllü olan yolcularımızdan 10–30 saniyelik kısa dikey videolar çekebilir misiniz?

Videoda aşağıdaki sorulardan sadece birine veya ikisine doğal şekilde cevap vermeleri yeterlidir:

• Server Turizm ile yolculuğunuz nasıl geçti?
• En memnun kaldığınız hizmet ne oldu?
• Umreye gidecek birine ne tavsiye edersiniz?

Mümkünse:
• Telefon dikey tutulsun
• Ortam sessiz olsun
• Yüz net ve aydınlık görünsün
• Kamera çok uzak olmasın
• Video doğal olsun; ezberlenmiş bir metin gerekmiyor

Videonun Server Turizm sosyal medya hesaplarında paylaşılmasına yolcumuzun onay verdiğinden de emin olalım.

Çok teşekkür ederiz.
```

#### Capture quality standard

Required:

- vertical 9:16;
- 1080p minimum when the phone supports it;
- clean lens;
- stable framing;
- face approximately chest-up;
- quiet location;
- avoid strong backlight;
- avoid loud copyrighted music in the background;
- 10–30 seconds preferred;
- natural answer, not a forced sales script.

Optional B-roll to collect on every group:

- airport;
- group meeting point;
- bus;
- hotel lobby;
- room / breakfast;
- Makkah / Madinah walking moments where appropriate;
- ziyarets;
- luggage / arrival;
- farewell / return.

### 16.28 Consent / dignity rule for passenger media

Before publishing identifiable passenger testimonial footage, confirm that the passenger knowingly agreed to social-media publication.

Do not publish:

- private documents;
- passport details;
- vulnerable/embarrassing moments;
- people who object to recording;
- close-ups of children without appropriate guardian permission.

### 16.29 P14B-6 — KPI Scorecard v1

The system will evaluate content by its job, not by one universal score.

#### Discovery Reel

Primary:
- Reach;
- Non-follower distribution where available;
- Views;
- Average watch time;
- Retention / view rate;
- Shares;
- Follows.

Secondary:
- Likes;
- Comments.

#### Educational Carousel

Primary:
- Saves;
- Shares;
- Reach;
- Follows / profile actions where available.

Secondary:
- Likes;
- Comments.

#### Offer / Program content

Primary:
- WhatsApp leads;
- telephone leads;
- qualified questions;
- booking attribution.

Secondary:
- Reach;
- Saves;
- Shares.

A lower-reach Program post may still be commercially successful if it creates qualified leads or bookings.

#### Story

Primary:
- Replies;
- link / WhatsApp actions;
- reach;
- exits;
- taps forward / back.

#### Trust / testimonial

Primary:
- Replies;
- shares;
- WhatsApp lead assists;
- conversion support.

### 16.30 Weekly reporting questions

Every weekly review should answer:

1. Which content generated the most **qualified attention**, not only views?
2. Which Reel had the strongest watch behavior?
3. Which Carousel generated the most saves/shares?
4. Which Program content created WhatsApp/telephone enquiries?
5. Which Story frames caused exits?
6. What topic should be repeated with a new angle?
7. What should be stopped or reduced?
8. Did any content use stale or unsupported commercial facts?

### 16.31 P14B-7 — Lead attribution naming convention

Until the CRM is fully integrated, use a lightweight source convention when possible.

Recommended source values:

```text
IG_REEL_<topic-or-program>
IG_CAROUSEL_<topic-or-program>
IG_STORY_<topic-or-program>
IG_PROFILE
IG_HIGHLIGHT_UMRE
IG_HIGHLIGHT_OTELLER
```

Examples:

```text
IG_REEL_29EKIM
IG_CAROUSEL_29EKIM_COMPARE
IG_STORY_RAMAZAN
IG_HIGHLIGHT_UMRE
```

Where a direct technical UTM / CRM source is not yet available, the operator may ask the lead a lightweight source question rather than pretending attribution is exact.

Future target:

`content → WhatsApp / call → CRM lead source → WON booking`

### 16.32 P14B status after v1 systems definition

```text
Content pillars                     DONE
Highlight architecture              DONE
Reel production modes               DONE
CTA system                          DONE
Testimonial collection system       DONE
Media capture guide                 DONE
KPI scorecard                       DONE
Lead-attribution convention         DONE
Static Illustrator template family NEXT
Typography / safe-area rules        NEXT
30 Reel concept bank                NEXT
Competitor benchmark audit          NEXT
October calendar                    AFTER TEMPLATE / CONCEPT REVIEW
```

Publishing remains LOCKED.

### 16.33 P14B-8 — Adobe Illustrator Social Design System v1

Primary production tool:

`Adobe Illustrator`

Canva remains optional for adaptation/collaboration and is **not** the master-artwork source.

#### Master artboards

Create and preserve these master sizes:

```text
IG-FEED-PORTRAIT     1080 × 1350 px
IG-STORY             1080 × 1920 px
IG-REEL-COVER        1080 × 1920 px
IG-SQUARE            1080 × 1080 px   // only when needed
```

Document color mode:

`RGB`

Export color space:

`sRGB`

Static export:

- PNG for text-heavy / graphic-heavy artwork;
- high-quality JPG for photo-heavy artwork where file size matters.

Do not treat print DPI as a primary Instagram quality control; pixel dimensions, export sharpness and readable typography are the controlling factors.

### 16.34 Brand color roles

Canonical working palette:

```text
Navy      #071B4D / #0B1B2E
Gold      #C9A227 / #D4AF37
Ivory     #F8F4EA
Orange    #E87512
Charcoal  #2B2B2B
White     #FFFFFF
```

Usage roles:

- Navy = primary brand/background/authority;
- Ivory/White = readability and clean information surfaces;
- Gold = premium/spiritual accent, dividers, badges;
- Orange = controlled conversion accent only;
- Charcoal = long text / secondary information.

Do not use Gold and Orange as competing primary CTA colors in the same creative.

### 16.35 Layout grid — Feed 1080×1350

Working safe frame:

```text
Left / right content margin:   72 px minimum
Top safe margin:               72 px minimum
Bottom safe margin:            90 px minimum
Primary internal grid:         12 columns
Base spacing unit:             12 px
Common spacing multiples:      12 / 24 / 36 / 48 / 72
```

Hero content should generally stay inside:

`x = 72…1008`

Avoid placing small text, logos or critical price details against the edges.

Recommended vertical zoning:

```text
0–18%     Brand / campaign / hook
18–62%    Hero image / core message
62–86%    Program facts / comparison / proof
86–100%   CTA / contact / footer
```

This is a modular framework, not a mandatory composition for every post.

### 16.36 Story / Reel safe-area rule — 1080×1920

Because Instagram UI overlays can cover the top and bottom of vertical content, critical text must remain inside a conservative central safe zone.

Working social-safe frame:

```text
Horizontal:  90 px minimum from each edge
Top:         250 px reserved from critical text
Bottom:      320 px reserved from critical text
```

Critical content = hook, price, date, hotel name, CTA, phone number.

Background photography may extend full bleed.

Reel cover must also remain legible when Instagram crops it into a Feed/Profile preview. Therefore the **core cover title and visual subject** should stay near the central 4:5 area.

### 16.37 Typography hierarchy — tool-independent

Exact brand font family remains a separate asset audit item. Until the current brand font is confirmed, do **not** lock a new permanent typeface merely for Instagram.

Use this hierarchy regardless of final font family:

#### Feed 1080×1350

```text
H1 / Hero title        72–96 px
H2 / Program title     54–72 px
Price / primary number 72–110 px
Key fact               34–44 px
Body                    28–36 px
Caption-like microcopy  24–28 px
Footer/contact           24–30 px
```

#### Story / Reel 1080×1920

```text
Hook                     76–110 px
Main information          52–72 px
Supporting text           34–46 px
CTA                       38–52 px
Footer / contact          28–34 px
```

Rules:

- maximum 2 font families in a single creative;
- maximum 3 meaningful weights;
- never use ALL CAPS for long paragraphs;
- prefer short Turkish headlines;
- price must never visually overpower the Program identity to the point that the post looks like a discount supermarket ad;
- line spacing should remain generous for the 35–54 core audience.

### 16.38 Illustrator layer convention

Every master template should use predictable layer names:

```text
00_GUIDES
01_BG
02_PHOTO
03_OVERLAY
04_BRAND
05_HEADLINE
06_PROGRAM_FACTS
07_PRICE
08_BADGES
09_CTA
10_CONTACT
11_LEGAL_NOTES
```

Lock `00_GUIDES` before production.

Use Symbols / Global Swatches / Paragraph Styles / Character Styles where practical so repeated Programs can be adapted without manual restyling.

### 16.39 Template family — minimum viable master set

#### T01 — Program Hero

Purpose:

Single priority Program / campaign launch.

Components:

- campaign kicker;
- Program title;
- departure / duration;
- one strong hotel/travel image;
- starting price or room prices when commercially useful;
- one primary CTA;
- Server Turizm identity.

Avoid:

- listing every child price, hotel feature and legal note on slide 1.

#### T02 — Program Comparison Carousel

Purpose:

Compare related Program variants.

Recommended slide logic:

```text
S1  Campaign hook / family title
S2  Who should choose option A?
S3  Who should choose option B?
S4  Duration / hotel / price comparison
S5  What's included / verified differentiators
S6  Decision helper
S7  CTA — WhatsApp / telephone
```

For three variants, comparison can extend to 8 slides if readability requires it.

#### T03 — Educational Carousel

Purpose:

Save/share content.

Recommended slide logic:

```text
S1  strong practical question / problem
S2–6  one idea per slide
S7  concise recap
S8  Save / Share CTA
```

Do not decorate every slide differently. Use one visual family and consistent navigation/progress markers.

#### T04 — Hotel Proof

Purpose:

Make accommodation tangible.

Components:

- hotel name;
- city;
- verified category / location fact if current;
- real hotel image;
- 2–4 verified practical benefits;
- related Program CTA only when relevant.

Do not use unsupported walking-distance claims.

#### T05 — Trust / Company Proof

Purpose:

Human/company confidence.

Components:

- 1998 heritage when relevant;
- office / staff / real operations imagery;
- one specific trust proposition;
- WhatsApp / phone CTA.

Avoid vague superlatives such as “Türkiye'nin en iyi turizm firması” without evidence.

#### T06 — Passenger Quote / Testimonial

Purpose:

Real social proof.

Components:

- passenger first name / role only if permission exists;
- short authentic quote;
- trip/program reference;
- real passenger image/video frame where permission exists;
- no invented star rating.

#### T07 — Seasonal / Spiritual

Purpose:

Cuma / Kandil / Ramazan / meaningful moments.

Direction:

- authentic sacred-place / journey imagery preferred;
- quiet premium composition;
- restrained branding;
- no repetitive template churn merely to fill Feed.

Story remains the default surface for routine weekly greetings.

#### T08 — Story Offer

Purpose:

Fast mobile conversion.

Frame sequence:

```text
1 Hook
2 Program / dates
3 hotel / duration / price
4 verified inclusions / differentiator
5 WhatsApp CTA
```

Do not place all information on one frame.

#### T09 — Story FAQ / Poll

Purpose:

Conversation / qualification.

Examples:

- `İlk Umreniz mi?`
- `9 gün mü 14 gün mü?`
- `Otel konumu mu, fiyat mı sizin için daha önemli?`

Use Instagram interaction-native elements when practical rather than baking fake polls into the artwork.

#### T10 — Reel Cover

Purpose:

Profile/Feed identification, not video intro.

Components:

- 3–7 word Turkish hook;
- one subject image;
- small category label;
- subtle brand marker.

The actual video must begin immediately with content; do not animate the cover as a 2–3 second logo intro.

### 16.40 Price presentation rules

Price is commercial information and must be easy to read without turning every design into price-only advertising.

Rules:

- use `…$'dan başlayan` only if a real qualifying price exists;
- room-based prices must clearly identify `2 Kişilik / 3 Kişilik / 4 Kişilik`;
- do not omit material conditions that would make the price misleading;
- no fake crossed-out price;
- no artificial “% indirim” unless a real reference price and discount are documented;
- current Sheet / canonical data must be rechecked before export/publish.

### 16.41 Contact-footer system

Create a reusable footer component instead of manually rebuilding contact details.

Working hierarchy:

```text
WhatsApp / Tur Danışmanı      primary
+90 212 621 05 00             secondary
serverturizm.com.tr           tertiary
```

Do not place all contact information at large size.

For Stories, prioritize WhatsApp visually and keep telephone secondary.

### 16.42 Brand-mark rule

Server Turizm branding should be present but not dominant.

Feed:

- logo / wordmark should normally occupy less visual weight than the content title.

Reels:

- subtle corner watermark or end-card is acceptable;
- no mandatory long logo intro.

Carousels:

- full logo can appear on first/final slide;
- middle slides may use a smaller brand marker.

### 16.43 Photo treatment

Priority order:

1. first-party Server Turizm trip imagery;
2. first-party hotel imagery;
3. verified licensed assets;
4. generated imagery only when clearly appropriate and not misleading.

Recommended treatment:

- preserve realistic colors;
- avoid excessive HDR;
- use dark Navy gradient overlays when text sits on photography;
- do not obscure Kaaba / Masjid al-Nabawi imagery with oversized price badges;
- keep sacred-place visuals dignified.

### 16.44 Static design QA checklist

Before a graphic is approved:

```text
[ ] Program title matches source
[ ] Departure / return dates verified
[ ] Duration verified
[ ] Hotel names verified
[ ] Price verified
[ ] No unsupported availability claim
[ ] CTA is singular and clear
[ ] Turkish spelling checked
[ ] Text legible on 6-inch phone
[ ] Safe area passed
[ ] Logo not oversized
[ ] First-party / licensed media source known
[ ] Private Program not exposed publicly
[ ] Export is RGB / sRGB
```

### 16.45 P14B design-system status

```text
Illustrator master sizes               DONE
Color role system                      DONE
Feed grid / spacing                    DONE
Story / Reel safe area                 DONE
Typography hierarchy                   DONE — font family pending brand-font audit
Layer convention                       DONE
10-template family                     DONE
Price rules                            DONE
Contact-footer system                  DONE
Brand-mark rule                        DONE
Photo treatment                        DONE
Static QA checklist                    DONE

Permanent typeface / font-family lock  OPEN
30 Reel concept bank                   NEXT
Competitor benchmark audit             NEXT
October calendar                       AFTER concept / competitor review
```

Publishing remains LOCKED.

### 16.46 P14B-9 — 30 Reel Concept Bank v1

The concept bank is designed around the current Server Turizm audience, first-party media availability, future Umrah inventory and the R1/R2 launch modes.

Legend:

```text
R1 = first-party footage + on-screen Turkish text
R2 = first-party footage + Turkish voice-over
R3 = presenter-to-camera + B-roll
R4 = passenger / group-leader UGC
```

#### A. High-priority launch concepts

1. **İlk kez Umreye gideceksen bunu bil**
   - Pillar: Education
   - Mode: R1/R2
   - Hook: `İlk Umrenizse, bu 3 detayı son güne bırakmayın.`
   - CTA: Save

2. **9 gün mü 14 gün mü?**
   - Pillar: Comparison
   - Mode: R2
   - Hook: `Umre programı seçerken sadece fiyata bakmayın.`
   - CTA: WhatsApp

3. **Lüks mü Ekonomik mi?**
   - Pillar: Comparison
   - Mode: R2
   - Hook: `Aradaki fark sadece otel fiyatı değil.`
   - CTA: WhatsApp

4. **Mekke'de otel seçerken yapılan en büyük hata**
   - Pillar: Hotel / Education
   - Mode: R1/R2
   - Hook: `Otelin yıldız sayısından önce buna bakın.`
   - CTA: Save / Share

5. **Umreye giderken pasaport fotoğrafı böyle çekilmez**
   - Pillar: Education
   - Mode: R1/R3
   - Hook: `Bu fotoğraf yüzünden işleminiz gecikebilir.`
   - CTA: Save

6. **Server Turizm ile bir Umre günü nasıl başlıyor?**
   - Pillar: Real Journey
   - Mode: R1/R2
   - Hook: real morning / group footage
   - CTA: Follow / WhatsApp

7. **Mekke'den Medine'ye geçiş günü**
   - Pillar: Route / Experience
   - Mode: R1/R2
   - Hook: `Programdaki “geçiş günü” gerçekte nasıl ilerliyor?`
   - CTA: Save

8. **Bir Umre paketinin içinde gerçekten neler var?**
   - Pillar: Offer / Education
   - Mode: R2
   - Hook: `Fiyata dahil olanları tek tek görelim.`
   - CTA: WhatsApp

9. **Umre valizinde mutlaka olması gerekenler**
   - Pillar: Education
   - Mode: R1/R2
   - Hook: `Valizinizi kapatmadan önce bu listeyi kontrol edin.`
   - CTA: Save / Share

10. **29 Ekim Umre seçenekleri — hangisi size uygun?**
    - Pillar: Program / Comparison
    - Mode: R2
    - Hook: `Aynı tarihte 3 farklı seçenek var.`
    - CTA: WhatsApp
    - Publish only after current Program facts are reverified.

#### B. Hotel / route / package proof

11. **Dar Al Ghufran neden Lüks programlarda öne çıkıyor?**
    - Pillar: Hotel
    - Mode: R1/R2
    - Use only verified location/amenity facts.

12. **Mias Al Madina'ya yakından bakalım**
    - Pillar: Hotel
    - Mode: R1/R2

13. **Makarem Umm Al Qura kimler için mantıklı?**
    - Pillar: Hotel / Comparison
    - Mode: R2

14. **Nusk AlHijra ile ekonomik program deneyimi**
    - Pillar: Hotel / Offer
    - Mode: R1/R2

15. **Otele varıştan odaya kadar 20 saniye**
    - Pillar: Real Journey / Hotel
    - Mode: R1

16. **Mekke ve Medine'de neden aynı otel tipi seçilmiyor?**
    - Pillar: Education / Hotel
    - Mode: R2

#### C. Preparation / FAQ

17. **Umre kayıt işlemi nasıl başlıyor?**
    - Pillar: Trust / Education
    - Mode: R2/R3
    - Explain only current verified workflow.

18. **Ofise gelmeden kayıt mümkün mü?**
    - Pillar: Trust
    - Mode: R2/R3
    - CTA: WhatsApp

19. **Çocukla Umreye giderken 3 hazırlık**
    - Pillar: Education
    - Mode: R2
    - Avoid medical advice; focus on travel/process preparation.

20. **Umrede bagaj hakkı konusunda en çok sorulan soru**
    - Pillar: FAQ
    - Mode: R2
    - Airline-specific facts must be current.

21. **Umreye ne kadar önce kayıt olmak gerekir?**
    - Pillar: Education / Sales
    - Mode: R2
    - Avoid false universal deadlines; frame around operational planning.

22. **Umre programında “3 gece Medine, 6 gece Mekke” ne demek?**
    - Pillar: Education
    - Mode: R1/R2

23. **Fiyata dahil olmayan bir şeyi nasıl anlarsınız?**
    - Pillar: Education / Trust
    - Mode: R2
    - Focus on reading program details and asking clear questions.

#### D. Human / trust / brand

24. **1998'den bugüne Server Turizm**
    - Pillar: Trust
    - Mode: R1/R2
    - Use archival/office/trip media where available.

25. **Bir Umre grubu yola çıkmadan önce ofiste ne hazırlanıyor?**
    - Pillar: Behind the scenes / Trust
    - Mode: R1/R3

26. **Tur danışmanına en çok sorulan 5 soru**
    - Pillar: Trust / FAQ
    - Mode: R3 preferred, R2 fallback.

27. **Havalimanında grup buluşması nasıl oluyor?**
    - Pillar: Real Journey / Trust
    - Mode: R1/R2

#### E. Passenger / emotional / spiritual

28. **Kabe'yi ilk kez gördüğünüz an**
    - Pillar: Human / Spiritual
    - Mode: R1/R4
    - Use respectful first-party footage and avoid staged reactions.

29. **Bir yolcunun 15 saniyelik Umre yorumu**
    - Pillar: Passenger Voice
    - Mode: R4
    - Blocked until real consented testimonial exists.

30. **Dönüş yolunda tek soru: “Bu yolculuk size ne kattı?”**
    - Pillar: Passenger Voice / Human
    - Mode: R4
    - Ideal for future group-leader capture.

### 16.47 Reel selection rule

Do not produce the concepts sequentially merely because they are numbered.

Weekly selection should consider:

- active saleable Program;
- available first-party footage;
- current audience questions;
- previous Reel watch behavior;
- seasonal relevance;
- production effort.

### 16.48 First 10-Reel controlled test set

Recommended initial test pool:

```text
R01  İlk kez Umreye gideceksen bunu bil
R02  9 gün mü 14 gün mü?
R03  Lüks mü Ekonomik mi?
R04  Mekke'de otel seçerken yapılan en büyük hata
R05  Pasaport fotoğrafı böyle çekilmez
R06  Bir Umre günü nasıl başlıyor?
R07  Mekke'den Medine'ye geçiş günü
R08  Bir Umre paketinin içinde neler var?
R09  Umre valizi checklist
R10  Active Program comparison — current verified campaign
```

The purpose of the first 10 is to test **topic + hook + format**, not to maximize posting volume.

### 16.49 Reel production QA

Before scheduling a Reel:

```text
[ ] Hook visible/audible within first 2 seconds
[ ] No long logo intro
[ ] Turkish only
[ ] First-party/licensed footage source known
[ ] Program facts reverified if commercial
[ ] No fake urgency
[ ] Subtitles readable in safe zone
[ ] One primary CTA
[ ] Cover title readable in profile crop
[ ] AI-altered realistic content disclosed when applicable
```

### 16.50 P14B status after Reel bank

```text
Illustrator design system             DONE
30 Reel concept bank                  DONE
First 10-Reel test pool               DONE
Reel QA                               DONE

Permanent typeface / font audit       OPEN
Competitor benchmark audit            NEXT
October calendar                      AFTER competitor audit
```

Publishing remains LOCKED.

### 16.51 P14B-10 — Competitor / Reference Benchmark Audit v1 — 2026-10-03

Purpose:

- understand common Umrah-market communication patterns in Türkiye;
- identify useful content and conversion patterns;
- define where Server Turizm should deliberately differentiate;
- **do not copy competitor creative, wording or layouts**.

This is a qualitative public-source benchmark, not a verified competitor-performance ranking.

Metricool currently has no Instagram competitors configured for the Server Turizm brand, so competitor-level performance data is not yet available inside the connected account. Public websites / public social references are therefore used only for directional pattern analysis.

#### Benchmark set

Direct / adjacent commercial references:

1. Züleyha Turizm
2. Hazeyn Turizm
3. Fezanur Turizm
4. Risalet Turizm
5. Kasva Travel

Non-commercial authority reference:

6. Diyanet Hac ve Umre Hizmetleri

---

#### A. Züleyha Turizm — program clarity / service differentiation

Observed public pattern:

- active Programs are presented with exact departure windows;
- airline is surfaced;
- package tier is explicit;
- starting price is shown prominently;
- service differentiators include pre-Umrah education, headset/frequency support and additional spiritual/service items.

Working lesson for Server Turizm:

- Program posts should explain **why a package exists and who it is for**;
- exact date + duration + hotel tier + flight/airline + verified differentiator should be easy to scan;
- avoid forcing users to decode three nearly identical offer posters.

Server Turizm differentiation opportunity:

- stronger comparison content;
- better hotel proof;
- more first-party journey media;
- clearer WhatsApp decision assistance.

Source:
`https://www.zuleyhaturizm.com/`

---

#### B. Hazeyn Turizm — preparation / utility-led trust

Observed public pattern:

- strong practical preparation framing;
- passport-validity check;
- document checklist;
- step-by-step organization messaging;
- WhatsApp as a clear conversion path;
- trip gallery used as proof.

Working lesson for Server Turizm:

Practical utilities can build trust before the sales conversation.

Potential Server Turizm adaptations:

- Passport photo guide;
- "Umreye Hazırlık" content series;
- baggage checklist;
- registration checklist;
- hotel / route explainer;
- future interactive preparation tools on the website.

Do not copy their interface. Use the **utility-first principle**.

Source:
`https://www.hazeynturizm.com/`
Instagram reference published by the business:
`@hazeynturizm`

---

#### C. Fezanur Turizm — visible social proof / seasonal Program naming

Observed public pattern:

- WhatsApp support is prominent;
- Google review proof is surfaced;
- active Program families are named around seasonal / contextual needs;
- website explicitly promotes Instagram as a channel for Programs, hotels and announcements.

Examples publicly surfaced include:

- Ekim Umresi;
- Sömestir Umresi;
- Siyer Anlatımlı Sömestir Umresi.

Working lesson for Server Turizm:

- named campaign concepts are easier to remember than generic `EKONOMİK UMRE PROGRAMI`;
- visible real-review proof is commercially useful;
- passenger testimonial capture is a meaningful gap in the current Server Turizm content system.

Server Turizm differentiation opportunity:

- build **video-first passenger proof**, not only text reviews;
- connect testimonials to the actual Program / journey;
- keep seasonal naming but add specific value, not just a label.

Sources:
`https://fezanurturizm.com/`
`https://fezanurturizm.com/portfolio.html`
Instagram reference published by the business:
`@fezanur.turizm`

---

#### D. Risalet Turizm — educational content engine

Observed public pattern:

- substantial informational content around Umrah/Hajj questions;
- topics include Mikat, Umrah obligations, phone/internet use, packing and other practical/religious guidance;
- Instagram is consistently linked from site content.

A third-party public Instagram snapshot (Pictame, crawled 2026-09/10 period) reported approximately:

```text
Followers       ~13.1K
Recent sample   12 posts
Video share     ~33%
Photo share     ~67%
```

The same third-party source reported videos materially outperforming photos in its observed sample.

Important limitation:

These are **third-party public estimates**, not first-party Instagram Insights and not Metricool data. They are directional only and must not be treated as audited competitor truth.

Working lesson for Server Turizm:

- educational content can create a long-tail trust/SEO/social loop;
- common operational questions should be turned into reusable Reels/Carousels;
- Server Turizm should combine this education with stronger first-party travel footage and current Program data.

Sources:
`https://www.risaletturizm.com/`
`https://www.risaletturizm.com/tr/umrenin-farzlari-nelerdir`
`https://www.risaletturizm.com/umreye-giderken-yaniniza-almaniz-gerekenler/`
Instagram reference:
`@risaletturizm`

---

#### E. Kasva Travel — transparent package / FAQ / conversion structure

Observed public pattern:

- package inclusions are easy to scan;
- 2/3/4-person room prices are clearly separated;
- FAQ addresses common buying questions;
- WhatsApp / quote request flow is visible;
- trust propositions such as guide support, transfers, hotels and payment options are grouped.

Working lesson for Server Turizm:

- commercial information should be structured, not buried in captions;
- FAQ content should be built directly from real customer objections;
- social offer creatives should make the decision easier, not merely announce a departure.

Server Turizm differentiation opportunity:

- stronger premium visual system;
- authentic journey proof;
- Program comparison;
- direct connection between Instagram lead source and CRM/WON booking.

Sources:
`https://kasvatravel.com/`
`https://kasvatravel.com/umre-programlari`
Instagram reference published by the business:
`@kasvatravel`

---

#### F. Diyanet Hac ve Umre — authority / accuracy reference

Diyanet is **not a commercial competitor**.

It is the preferred public authority reference for:

- Umrah/Hajj religious education;
- official guidance;
- terminology;
- preparation content where religious accuracy matters.

Published social account:

`@hacveumredib`

Working rule:

When Server Turizm creates religious/ritual educational content, distinguish:

- company operational advice;
- general travel advice;
- religious guidance.

Religious guidance should use authoritative source backing and should not invent rulings.

Sources:
`https://hacumre.diyanet.gov.tr/`
`https://hacumreegitim.hac.gov.tr/`

---

### 16.52 Market-pattern synthesis

The benchmark reveals six recurring market patterns:

```text
1. Program / price visibility
2. WhatsApp-first conversion
3. Trust / institution signals
4. Practical Umrah education
5. Seasonal campaign naming
6. Passenger / trip proof
```

Server Turizm already has strong raw assets for several of these:

- future Program inventory;
- Hotel Intelligence;
- first-party photo/video galleries;
- long operating history;
- website / Umrah Hub;
- WhatsApp / telephone support;
- real operational workflow.

The largest current social gaps are:

```text
A. weak structured passenger testimonial library
B. insufficient educational / save-worthy content cadence
C. too much historical dependence on generic Friday/prayer Feed posts
D. insufficient first-party journey footage packaging for Reels
E. no systematic Program comparison format
F. no social → CRM booking attribution yet
```

### 16.53 Server Turizm differentiation thesis v1

Do **not** try to win by posting more generic Umrah posters than competitors.

Working positioning:

> `Server Turizm = gerçek yolculuk kanıtı + net program seçimi + güvenilir hazırlık bilgisi + kolay WhatsApp danışmanlığı`

Content expression:

```text
GENERIC MARKET:
Poster → price → phone number

SERVER TURIZM TARGET:
Question / need
→ useful explanation
→ real proof
→ clear Program choice
→ WhatsApp consultation
→ attributable lead / booking
```

### 16.54 What to borrow as patterns — not creative copies

Use:

- clear Program names;
- exact, verified Program facts;
- structured package comparisons;
- preparation checklists;
- FAQ-derived content;
- visible first-party proof;
- clear WhatsApp action;
- seasonal campaign context;
- real passenger voices.

Do not copy:

- competitor layouts;
- captions;
- slogans;
- icons;
- exact campaign names;
- branded graphic systems;
- testimonial wording.

### 16.55 Competitive watchlist for future Metricool tracking

Recommended accounts to add manually to Metricool competitors if the current plan supports it:

```text
@fezanur.turizm
@hazeynturizm
@risaletturizm
@kasvatravel
```

Add Züleyha's current official Instagram handle after it is verified from an authoritative business-owned source.

Once competitors are configured in Metricool, P14E can compare directional public metrics such as:

- follower count;
- post volume;
- Reel volume;
- average public likes/comments;
- public engagement proxy.

Never compare their public metrics directly with Server Turizm private Insights as though the measurement methods are identical.

### 16.56 P14B completion state after benchmark audit

```text
Content pillars                     DONE
Highlight architecture              DONE
Reel production modes               DONE
CTA system                          DONE
Testimonial collection system       DONE
Media capture guide                 DONE
KPI scorecard                       DONE
Lead-attribution convention         DONE
Illustrator design system           DONE
30 Reel concept bank                DONE
Competitor/reference audit          DONE

Permanent font-family lock          OPEN — non-blocking
P14B                                READY FOR CLOSEOUT
P14C October campaign calendar      NEXT
```

The font-family decision is no longer a blocker for strategy. Existing Server Turizm brand typography should be preserved in current Illustrator work until a deliberate brand-font audit is performed.

Publishing remains LOCKED.

### 16.57 P14B closeout / P14C activation — 2026-10-03

Owner direction:

`P14B accepted — continue into calendar planning.`

State transition:

```text
P14B  Content System / Pillars / Highlights / Design Rules   CLOSED / ACCEPTED
P14C  Campaign Calendar / Production Workflow                ACTIVE
P14D  Controlled Publishing Pilot                            LOCKED
```

No post is published by this transition.

### 16.58 Instagram DM as a first-class lead channel

Instagram Direct Message is now an explicit conversion path.

Supported lead entry:

```text
Instagram content
→ Instagram DM
→ qualification / answer
→ optional WhatsApp / telephone escalation
→ CRM source attribution
→ booking / WON
```

DM should not be treated as a secondary afterthought.

#### Working CTA hierarchy

For on-platform discovery content:

```text
Primary:   Instagram DM
Fallback:  WhatsApp
High intent / assisted sale: telephone
```

For Stories:

- reply / DM can be the lowest-friction action;
- WhatsApp remains appropriate for detailed Program consultation;
- use one primary action per frame.

For Offer Carousels:

- final slide may use `DM'den “UMRE” yazın` as primary;
- WhatsApp / telephone can be shown as secondary support.

#### DM keyword convention

Working campaign keywords:

```text
UMRE       general Umrah enquiry
EKIM       October Programs
KASIM      November Programs
RAMAZAN    Ramadan Programs
OTEL       hotel-specific question
PASAPORT   passport / registration guidance
```

Do not create a different keyword for every individual post unless operationally useful.

#### DM lead-source convention

Recommended CRM/source values:

```text
IG_DM_UMRE
IG_DM_EKIM
IG_DM_KASIM
IG_DM_RAMAZAN
IG_DM_OTEL
IG_DM_PASAPORT
```

If a specific campaign needs finer attribution:

`IG_DM_29EKIM`

The existing STCA Instagram assistant remains a separate system/gate. P14C does not activate automatic outbound replies or change STCA production locks.

### 16.59 P14C — October 2026 publishing plan v1

This is a **production calendar**, not publication authorization.

Working target publish time:

`20:30 Europe/Istanbul`

Rationale:

- current Metricool audience-presence signal is strongest in the 19:00–22:00 range;
- using a consistent initial time creates cleaner comparison data;
- time will be changed later if first-party evidence supports a better slot.

#### Planned Feed / Reel cadence

| Date | Format | Working title / topic | Pillar | Primary CTA | Status |
|---|---|---|---|---|---|
| 04 Oct | Reel | İlk kez Umreye gideceksen bunu bil | Education | Save / DM UMRE | PLAN |
| 06 Oct | Carousel | 8 Ekim Umre seçenekleri | Program Comparison | DM EKIM / WhatsApp | CONDITIONAL — verify sales open |
| 08 Oct | Reel/Real Journey | Bir Umre günü nasıl başlıyor? | Human Journey | Follow / DM UMRE | CONDITIONAL — requires real departure media |
| 10 Oct | Reel | Bir Umre paketinin içinde gerçekten neler var? | Education/Offer | DM UMRE | PLAN |
| 12 Oct | Carousel | 9 gün mü 14 gün mü? | Comparison | Save / DM UMRE | PLAN |
| 14 Oct | Reel | Mekke'de otel seçerken yapılan en büyük hata | Hotel/Education | Save / Share | PLAN |
| 16 Oct | Carousel | Lüks otel kanıtı — Mias + Dar Al Ghufran | Hotel Proof | DM OTEL | PLAN — verify current facts |
| 18 Oct | Reel | 29 Ekim: Lüks mü Ekonomik mi? | Program Comparison | DM EKIM | PLAN |
| 20 Oct | Carousel | 29 Ekim Programları karşılaştırması — 228/229/230 | Offer/Comparison | DM EKIM / WhatsApp | PRIORITY |
| 22 Oct | Reel | Pasaport fotoğrafı böyle çekilmez | Education | Save / DM PASAPORT | PLAN |
| 24 Oct | Carousel | Ekonomik otel kanıtı — Nusk + Makarem | Hotel Proof | DM OTEL | PLAN — verify current facts |
| 26 Oct | Reel | Ofise gelmeden Umre kaydı mümkün mü? | Trust/Process | DM UMRE / WhatsApp | PLAN |
| 28 Oct | Carousel/Reel | Umre valizi checklist + 29 Ekim hazırlığı | Education | Save / Share | PLAN |
| 30 Oct | Carousel/Reel | Kasım Umreleri — size uygun program hangisi? | November Teaser | DM KASIM | PLAN |

31 Oct:

- Story-first November handoff;
- no mandatory Feed item;
- use real-time footage / Program status if available.

### 16.60 October Story operating layer

Stories should support the Feed plan without becoming a second poster feed.

Daily Story categories may rotate:

```text
A. active Program / verified availability
B. Q&A / poll
C. hotel / destination proof
D. real journey moment
E. preparation tip
F. DM question prompt
G. WhatsApp handoff
```

Working daily operational check:

- review active Program status;
- review DMs;
- answer / route qualified enquiries;
- choose 1–4 useful Story frames;
- do not publish filler merely to satisfy a count.

### 16.61 Conditional-content rule

Some planned dates depend on real-world source availability.

Examples:

- 06 Oct 8-Ekim offer requires confirmation that sales are still open;
- 08 Oct real-journey Reel requires usable first-party departure footage;
- Hotel content requires current source-backed hotel facts;
- scarcity language requires current capacity evidence.

If a conditional item fails its source gate, replace it with a non-commercial educational Reel/Carousel from the concept bank. Do not improvise facts.

### 16.62 Production / upload workflow

For each planned Feed/Reel item:

```text
T-1 day
  → source verification
  → media selection
  → copy / hook
  → Illustrator / video production
  → internal QA

Publish day
  → final fact check
  → export
  → caption / CTA
  → upload as draft / prepare in Metricool
  → final owner check
  → publish only if P14D is explicitly opened
```

Google Calendar should carry reminders for:

- content production;
- upload / final QA;
- daily Story + DM review.

### 16.63 P14C hard lock

Calendar events and reminders are operational preparation only.

They do **not** authorize:

- Metricool auto-publish;
- Instagram publishing;
- paid promotion;
- automatic DM replies;
- unverified Program claims.

P14D remains LOCKED until owner explicitly opens the pilot.

### 16.64 P14C Production Brief registry — 2026-10-03

Detailed October production instructions now live in:

`docs/P14C-OCTOBER-2026-PRODUCTION-BRIEFS.md`

That file is the authoritative execution companion for the October P14C calendar and contains, for every planned Reel/Carousel:

- hook;
- slide/on-screen copy;
- shot list;
- caption;
- CTA;
- required first-party media;
- Program facts where applicable;
- hard-stop / source-verification rules.

Master Plan remains the project-control document; the Production Brief file is the operational content-spec document.

### 16.65 Calendar operating rule — 09:00 work-start reminder

Owner preference:

`Daily Instagram/content work must surface in Google Calendar at 09:00 Europe/Istanbul, when the operator arrives at work.`

Calendar structure:

```text
09:00   Daily Instagram Plan + DM + Story check
09:30   Specific production brief when a production task is due
19:45   Upload + final QA on planned publish dates
20:30   Target publish time only after P14D is explicitly opened
```

The 09:00 daily event is the primary morning trigger.

### 16.66 Telephone-number verification gate

Current public sources expose more than one Server Turizm telephone number while the operational Sheet separately contains a mobile contact.

Therefore:

- Instagram DM remains the primary on-platform CTA;
- WhatsApp remains the secondary sales handoff;
- do not hard-code a public telephone number into new P14 artwork until owner/operations confirms which number is canonical for social media.

This prevents inconsistent contact information across Instagram, website and Program data.

### 16.67 P14 content universe correction — NOT an Umrah-only account

Owner correction:

`Server Turizm Instagram must not become an Umrah-program-only feed.`

The social system must cover the full brand communication mix:

```text
A. Active Umrah Programs / offers / comparisons
B. Reels / discovery / real journey
C. Stories / daily contact / Q&A / DM
D. Educational / practical travel content
E. Hotels / routes / destination proof
F. Company / trust / behind-the-scenes
G. Passenger voice / testimonials
H. Religious occasions / Kandils / Ramadan / Bayram
I. Turkish national / civic occasions
J. Public culture tours when actually saleable
K. Brand-relevant special days
```

No single category should dominate the Feed merely because source data is easier to generate.

Working rule:

`Program content is the commercial spine; it is not the entire content strategy.`

### 16.68 Occasion-content publishing model

Occasion content has four presentation levels:

```text
LEVEL 1 — Story-only
Routine weekly / low-priority greetings.

LEVEL 2 — Story + Feed static
Major religious or national occasions.

LEVEL 3 — Reel + Story + Feed/Carousel
Major occasions with real first-party journey footage or a strong campaign connection.

LEVEL 4 — Campaign layer
Ramadan / Bayram / Kandil periods directly connected to active Umrah Programs.
```

Examples:

- ordinary Friday greeting → Story-first;
- Regaib / Miraç / Berat Kandili → Story + optional premium Feed/Reel;
- Kadir Gecesi → premium spiritual content + relevant Ramadan campaign context;
- Ramazan Bayramı / Kurban Bayramı → brand-level greeting content;
- Cumhuriyet Bayramı → neutral civic greeting, no sales-heavy overlay.

### 16.69 Official Türkiye religious-content calendar — authoritative Diyanet source

Canonical date source for Kandil / Ramadan / Bayram planning:

`T.C. Diyanet İşleri Başkanlığı — Dini Günler Listesi`

#### Remaining 2026 religious dates after 2026-10-03

```text
10 Dec 2026  Perşembe   Üç Ayların Başlangıcı
10 Dec 2026  Perşembe   Regaib Kandili
```

#### 2027 religious dates — first planning horizon

```text
04 Jan 2027  Pazartesi  Miraç Kandili
22 Jan 2027  Cuma       Berat Kandili
08 Feb 2027  Pazartesi  Ramazan Başlangıcı
05 Mar 2027  Cuma       Kadir Gecesi
08 Mar 2027  Pazartesi  Ramazan Bayramı Arefesi
09 Mar 2027  Salı       Ramazan Bayramı — 1. Gün
10 Mar 2027  Çarşamba   Ramazan Bayramı — 2. Gün
11 Mar 2027  Perşembe   Ramazan Bayramı — 3. Gün
15 May 2027  Cumartesi  Kurban Bayramı Arefesi
16 May 2027  Pazar      Kurban Bayramı — 1. Gün
17 May 2027  Pazartesi  Kurban Bayramı — 2. Gün
18 May 2027  Salı       Kurban Bayramı — 3. Gün
19 May 2027  Çarşamba   Kurban Bayramı — 4. Gün
06 Jun 2027  Pazar      Hicri Yılbaşı
15 Jun 2027  Salı       Aşure Günü
13 Aug 2027  Cuma       Mevlid Kandili
29 Nov 2027  Pazartesi  Üç Ayların Başlangıcı
02 Dec 2027  Perşembe   Regaib Kandili
24 Dec 2027  Cuma       Miraç Kandili
```

Date validation rule:

- Diyanet dates are rechecked before calendar automation for a new year;
- the social calendar should create production tasks **before** the occasion, not on the occasion morning only;
- greeting copy must remain respectful and concise;
- religious rulings / ritual explanations require authoritative sourcing, not brand improvisation.

### 16.70 Türkiye official / civic dates relevant to social planning

Official holiday source:

`T.C. Diyanet İşleri Başkanlığı — Resmi Tatiller Listesi / 2429 sayılı Kanun basis`

#### Remaining 2026

```text
28–29 Oct 2026   Cumhuriyet Bayramı
```

#### 2027

```text
01 Jan 2027      Yılbaşı
23 Apr 2027      Ulusal Egemenlik ve Çocuk Bayramı
01 May 2027      Emek ve Dayanışma Günü
19 May 2027      Atatürk'ü Anma, Gençlik ve Spor Bayramı
15 Jul 2027      Demokrasi ve Milli Birlik Günü
30 Aug 2027      Zafer Bayramı
28–29 Oct 2027   Cumhuriyet Bayramı
```

For potentially political/civic commemorations, Server Turizm content should remain neutral, factual and commemorative; it should not advocate for parties, candidates or political positions.

### 16.71 Brand-relevant Turkish special-day layer

These are not automatically public holidays, but may deserve planned content when relevant to Server Turizm:

```text
10 Nov           Atatürk'ü Anma Günü
24 Nov           Öğretmenler Günü
27 Sep           Dünya Turizm Günü
```

`24 Kasım Öğretmenler Günü` is especially relevant because Server Turizm has an `Öğretmenler ile Kasım Umresi` product context.

Rule:

Special-day content must have a real brand connection or community value. Do not fill the Feed with generic calendar graphics merely because a date exists.

### 16.72 Occasion production lead times

Use these default production windows:

```text
Major campaign occasion
Ramadan / Bayram / Kadir / major Kandil
→ concept T-14 to T-21 days
→ design / shoot T-7 days
→ final QA T-2 days

National / civic greeting
→ concept T-5 days
→ design T-2 days
→ final QA T-1 day

Routine Story occasion
→ prepare T-1 day
```

This prevents same-day rushed graphics.

### 16.73 October 2026 calendar correction

The October social plan must include `29 Ekim Cumhuriyet Bayramı`.

Required treatment:

- neutral celebratory / commemorative brand content;
- Story + Feed static or short respectful Reel;
- no price / sales CTA in the greeting creative;
- use Turkish flag / travel / brand visual language without turning the occasion into an offer;
- commercial November teaser remains separate.

Production should be prepared on 28 Oct.

Suggested Turkish master copy:

`29 Ekim Cumhuriyet Bayramımız kutlu olsun.`

Optional secondary line:

`Cumhuriyetimizin 103. yılı kutlu olsun.`

Do not overload the design with long text.

### 16.74 Operator work-hours constraint — authoritative

Owner working pattern:

```text
Monday–Friday   normal Server Turizm working days
Saturday        Server Turizm work only until 14:00
Saturday >14:00 personal work time — do not schedule company content tasks
Sunday          OFF — no Server Turizm production / DM / QA / upload task
```

Calendar and content operations must respect this by default.

Implementation rule:

- 09:00 daily Instagram routine runs Monday–Saturday only;
- Sunday has no manual Server Turizm social task;
- Saturday production / QA / upload preparation must end before 14:00;
- if a Sunday post is strategically useful, it must be fully prepared and scheduled before Saturday 14:00 after P14D is opened;
- if P14D is still locked, do not create a Sunday manual obligation.

### 16.75 Non-partisan civic-content rule

Server Turizm civic / national-day content must remain **strictly non-partisan**.

For `10 Kasım Atatürk'ü Anma Günü`, `29 Ekim Cumhuriyet Bayramı` and similar dates:

Allowed:
- neutral commemoration;
- national / civic symbols;
- concise respectful copy;
- Server Turizm brand mark.

Not allowed:
- political-party logos or colors used as partisan identification;
- CHP or any other party branding;
- politician endorsements;
- campaign slogans;
- electoral messaging;
- content implying Server Turizm supports or opposes a political party.

Brand rule:

`National/civic commemoration ≠ party-political communication.`

The same non-partisan rule applies to all parties, not only one party.

### 16.76 P14C recovery checkpoint — 2026-10-06

Observed operational change:

```text
Programs 225 / 226 / 227
Departure: 08.10.2026
Operational Sheet: Programı Kaldır = TRUE
```

Decision:

- cancel the planned 8 October public sales Carousel;
- do not publish stale / removed Program inventory;
- move only the missed evergreen Reel `İlk kez Umreye gideceksen bunu bil` into the current workday;
- keep 8 October journey footage content only if the already-booked group actually travels and authentic first-party media is obtained.

Recovery principle:

`Missed content does not become automatic backlog debt.`

Revalidate first; drop expired commercial items; carry forward only content that still has value.


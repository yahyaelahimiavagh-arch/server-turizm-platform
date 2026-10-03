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


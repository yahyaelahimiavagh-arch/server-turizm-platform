# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — WEEKLY SEO GATE CLOSED / URL HYGIENE PASS / TOURS RESUME AUTHORITY

# 0. AUTHORITATIVE SUPERSESSION — FINAL WEEKLY SEO CLOSEOUT

This block is the newest execution authority for the 2026-09-14 work session. It supersedes earlier same-day continuation instructions where they conflict. Historical architecture, accepted runtime gates, rollback evidence and prior checkpoints remain preserved below.

## 0.1 WEEKLY SEO GATE — FINAL STATUS

```text
SEARCH PERFORMANCE REVIEW                 PASS
PAGE INDEXING REVIEW                      PASS
LIVE 5xx INVESTIGATION                    FIXED / VERIFIED
LEGACY 404 CLASSIFICATION                 PASS
EXACT LEGACY REDIRECT PATCH               PASS
UMRAH HUB                                 PRESERVE
PROGRAM MASS INDEXATION                   OFF
TOUR INTELLIGENCE                         RESUME NOW
```

### Search Console indexing snapshot reviewed

```text
Indexed                                  85
Not indexed                              62
Not found (404)                          15
Page with redirect                       10
Alternate with canonical                  6
Excluded by noindex                       2
Server error (5xx)                        1
Blocked by robots.txt                     1
Crawled - currently not indexed          26
Discovered - currently not indexed        1
```

Important: the Page Indexing report itself was lagged to 2026-09-04, so it is not authoritative for post-2026-09-12 Program lifecycle changes.

## 0.2 LIVE 5xx — CLOSED

Affected historical URL:

`/wp-content/themes/porto`

Root behavior before fix:
- Google Live Test returned `Server error (5xx)`;
- this was a direct request to the Porto theme directory, not a commercial page.

Accepted fix:
- exact root-directory guard added outside managed WordPress/LSCache blocks in `.htaccess`;
- only `/wp-content/themes/porto` and `/wp-content/themes/porto/` are blocked;
- real Porto assets under CSS/JS/images remain accessible;
- homepage regression check passed.

Runtime evidence after fix:

```text
Browser direct request       404 Not Found
Google Search Console LIVE   Not found (404)
Homepage                     PASS / no visual regression
```

Therefore the single live `5xx` is considered **RESOLVED**.

## 0.3 404 CLASSIFICATION — 15 URLs

### Intentional / valid 404 — keep retired

```text
/oteller/batoul-ajyad-hotel/
/oteller/the-oberoi-hotel/
/oteller/millennium-al-aqeeq-madinah-hotel/
/en//
/tour-tag/luks3/
/tour-tag/eko1/
/wp-json/serverturizm-ai/v1/lead
```

Hotel legacy 404s remain consistent with the accepted Hotel Intelligence policy: retired Porto Hotel URLs must not receive fabricated redirects.

### Exact legacy redirects — runtime PASS

The following five old commercial Umrah URLs now 301 to the canonical Hub `/umre-1/`:

```text
/tour/ramazan-ayi-luks-umre-programi-4-grup-luks-umre/
/sezonluk-umre-programlari/
/tour/ekonomik-yakin-servisli-umre-programi-19-gece-20-gun/
/umre-2/
/umre-3/
```

The following legacy Hac URL now 301 redirects to `/hac/`:

```text
/tour/2023-2024-ekonomik-hac-vizeli/
```

Implementation policy:
- exact allowlist only;
- no wildcard `/tour/*`;
- unrelated Tour routes remain untouched;
- canonical `/umre-1/` and `/hac/` remain directly routable.

Owner/runtime result: **all 6 redirect tests PASS**.

### Deferred / intentional review

```text
/kongre-seminer/     → business decision required before restore/redirect
/ramazan-umresi/     → reserve slug for future real seasonal inventory; keep 404 for now
```

## 0.4 SEO BACKLOG — NON-BLOCKING FOR TOUR RESUME

Carry forward without reopening a broad audit:

1. classify the 26 `Crawled - currently not indexed` URLs by page type;
2. verify the single robots.txt-blocked URL is intentional;
3. later improve CTR for `/umre-vizesi-nasil-alinir/`;
4. monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
5. continue Hotel query growth monitoring;
6. do not mass-request indexing or mass-validate exclusions.

These are weekly SEO operations, not blockers for the Tour branch.

## 0.5 EXECUTION ORDER FROM HERE

```text
1. SEO WEEKLY GATE                     CLOSED / PASS
2. RESUME TOUR INTELLIGENCE            NOW
3. NEXT TOUR BRANCH                    STTI v0.6.5 — AI Completion Contract
4. THEN                               Variant + Hotel Option + Hotel Intelligence + Transport review
5. THEN                               Canonical Geo Resolver
6. THEN                               Customer Renderer
7. THEN                               First real Full Tour
8. THEN                               Controlled public Tour pilot
9. AFTER Tour model is stable         Unified Google Sheets Direct Sync for Umrah + Tours
```

Do not build the Umrah-only Sheet connector before Tour Intelligence is ready. The shared connector must be built once for both domains with immutable Stable IDs, signed requests, idempotency, transactional validation, archive-never-delete semantics, per-record error handling and audit evidence.

# END OF 2026-09-14 WEEKLY SEO CLOSEOUT SUPERSESSION

---

# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — WEEKLY SEO GATE / SEARCH HEALTH REVIEW / TOURS RESUME AUTHORITY

# 0. AUTHORITATIVE SUPERSESSION — READ THIS FIRST

This checkpoint supersedes only the day-to-day continuation instructions in the 2026-09-14 Weekly SEO / Tours checkpoint. Historical architecture, accepted runtime baselines, safety rules, rollback evidence and closed gates remain preserved below unless explicitly superseded here.

## 0.1 WEEKLY SEO GATE — 2026-09-14 RESULT

### Search Console performance
Source: Google Search Console Web performance exports captured 2026-09-14. The latest complete GSC date in the export is 2026-09-11.

```text
LAST 7 DAYS (2026-09-05 → 2026-09-11)
Clicks                 92
Impressions            1,442
CTR                     6.38%
Avg position            9.02

PREVIOUS 7 DAYS (2026-08-29 → 2026-09-04)
Clicks                 111
Impressions            1,284
CTR                     8.64%
Avg position            9.53

CHANGE
Clicks                 -17.1%
Impressions            +12.3%
CTR                    -2.26 percentage points
Avg position           +0.50 positions better

LAST 28 DAYS
Clicks                 360
Impressions            4,037
CTR                     8.92%
Avg position           10.04
```

Interpretation:
- search visibility is expanding;
- average ranking improved slightly week-over-week;
- clicks declined because CTR softened while impressions grew;
- the immediate growth problem is not a broad ranking collapse;
- the next SEO opportunity is SERP CTR / intent alignment plus crawl/index hygiene.

### Important live page signals — last 7 days

```text
Homepage                       46 clicks / 364 impressions / 12.64% CTR / pos 8.03
/umre-1/                       33 clicks / 433 impressions /  7.62% CTR / pos 9.12
/umre-vizesi-nasil-alinir/      5 clicks / 451 impressions /  1.11% CTR / pos 6.46
/hac/                           3 clicks /  86 impressions /  3.49% CTR / pos 2.93
Peninsula Worth Hotel           1 click  /  84 impressions /  1.19% CTR / pos 8.33
/umre-icin-gerekli-belgeler/    1 click  /  82 impressions /  1.22% CTR / pos 9.77
```

Commercial query signal:

```text
server turizm umre fiyatları   11 clicks / 20 impressions / 55% CTR / position 1.00
umre fiyatları 2027             3 clicks / 28 impressions / 10.71% CTR / position 9.36
2027 umre fiyatları             1 click  / 17 impressions /  5.88% CTR / position 9.76
```

Verdict:
- `/umre-1/` commercial consolidation is showing positive live demand; do not reopen the accepted Hub UI.
- 2027 Umrah price intent is a real growth opportunity.
- the Umrah visa guide has strong visibility but weak CTR and deserves later snippet/intent optimization after crawl cleanup.
- Hotel Intelligence is beginning to generate query-level search visibility for real Hotel entities; monitor rather than redesign.

## 0.2 PAGE INDEXING — 2026-09-14 SCREENSHOT

Search Console Page Indexing snapshot:

```text
Indexed                         85
Not indexed                     62
Known pages total              147

Not found (404)                 15
Page with redirect              10
Alternate with canonical         6
Excluded by noindex              2
Server error (5xx)               1
Blocked by robots.txt            1
Crawled - currently not indexed 26
Discovered - currently not indexed 1
```

Critical timing note:
- the screenshot says `Last update: 9/4/26`;
- therefore this Page Indexing report lags the current site by about ten days;
- it must NOT be used to judge the 2026-09-12 Umrah archive / Stable-ID repair / Zekerya publishing incident resolution.

Interpretation:
- `Page with redirect`, `Alternate with canonical`, and intentional `noindex` are not automatically defects;
- the single `5xx` is the first indexing-health item to identify and verify;
- the 15 `404` URLs require classification into intentional retirement vs broken/valuable legacy URLs;
- 26 `Crawled - currently not indexed` pages are the largest review bucket and must be classified before requesting indexing;
- one robots-blocked URL must be checked for intentionality;
- do not mass-validate or mass-request indexing until URL-level samples are reviewed.

## 0.3 WEEKLY SEO ACTION REGISTER

### P0 — immediate verification
1. Identify the single Search Console `Server error (5xx)` URL and confirm whether the error is still live.
2. Open the 15-URL `Not found (404)` report and classify each URL:
   - valid intentional retirement;
   - should 301 to a genuinely equivalent canonical destination;
   - broken internal link / accidental 404.
3. Verify the one robots-blocked URL is intentionally blocked.

### P1 — this week
1. Review the 26 `Crawled - currently not indexed` URLs by type; do not bulk-submit them.
2. Review stale/duplicate surfaces visible in Performance, especially legacy contact / miscellaneous WordPress routes, before deciding redirects.
3. Preserve `/umre-1/` and continue monitoring its commercial-query consolidation.
4. Prepare a CTR improvement candidate for `/umre-vizesi-nasil-alinir/` only after URL hygiene review.
5. Track `umre fiyatları 2027` / `2027 umre fiyatları` as the current commercial growth cluster.

### P2 — observe / backlog
1. Monitor Peninsula Worth / Medine Hotel search growth.
2. Monitor newly published Trust / Journey Evidence article discovery/indexation.
3. Continue Core Web Vitals / mobile trend checks only when regression evidence appears.

### NO ACTION / preserve
- accepted Umrah Hub visual architecture;
- Program detail mass indexation (still OFF);
- Program sitemap inclusion (still OFF);
- fixtures STP-000036 / STP-000037;
- accepted Hotel Intelligence data model;
- accepted Homepage Intelligence baseline.

## 0.4 PROJECT PRIORITY AFTER THIS SEO GATE

Owner priority is now locked as:

```text
1. Finish the short URL-level SEO hygiene checks above
2. Resume Server Turizm Tour Intelligence
3. Next Tour branch: STTI v0.6.5 — AI Completion Contract
4. Continue WordPress Review relations: Variants + Hotel Options + Hotel Intelligence + Transport
5. Canonical Geo Resolver
6. Complete Customer Renderer
7. First real Full Tour
8. Controlled public Tour pilot only after explicit gates
9. AFTER Tour ingestion/review is stable: build one shared Google Sheets Direct Sync foundation for BOTH Umrah and Tours
```

Google Sheets direct sync remains deliberately deferred. The final shared integration must provide one operator action, authenticated requests, immutable Stable IDs, idempotency, transactional validation, archive-never-delete semantics, per-record errors, audit evidence and safe domain adapters for both `STP-*` and `STT-*`.

## 0.5 CURRENT EXECUTION VERDICT

```text
WEEKLY SEO GATE                         REVIEWED / ACTIONABLE
SEARCH VISIBILITY                       GROWING
RANKING TREND                           SLIGHTLY IMPROVED
CTR TREND                               DOWN / OPTIMIZATION OPPORTUNITY
PAGE INDEXING                           85 INDEXED / 62 NOT INDEXED (REPORT LAGGED TO 2026-09-04)
UMRAH HUB                               PRESERVE / POSITIVE COMMERCIAL SIGNAL
PROGRAM INDEXATION                      OFF
PROGRAM SITEMAP                         OFF
TOUR INTELLIGENCE                       RESUME AFTER SHORT SEO HYGIENE PASS
NEXT TOUR BRANCH                        v0.6.5 AI COMPLETION CONTRACT
UMRAH+TOUR DIRECT SHEET SYNC            DEFERRED UNTIL TOUR MODEL STABLE
```

# END OF 2026-09-14 WEEKLY SEO GATE SUPERSESSION

---

# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 AUTHORITATIVE SUPERSESSION — WEEKLY SEO GATE → TOUR INTELLIGENCE → UNIFIED SHEET SYNC

# 0. AUTHORITATIVE SUPERSESSION — READ THIS FIRST

This 2026-09-14 block is the newest authoritative continuation point for the Server Turizm website project.

It supersedes earlier day-to-day continuation instructions wherever they conflict. Historical architecture, accepted runtime gates, rollback evidence and prior Master Plan checkpoints remain preserved below unless explicitly superseded here.

The immediate execution order is now deliberately simplified:

```text
1. WEEKLY SEO / SEARCH HEALTH AUDIT
2. CONTINUE STTI — TOUR INTELLIGENCE
3. AFTER TOUR INTELLIGENCE IS OPERATIONALLY READY:
   BUILD ONE UNIFIED GOOGLE SHEETS → WORDPRESS DIRECT SYNC FOR UMRAH + TOURS
```

Do **not** spend the current work block on building the Umrah direct-Sheet connector before Tour Intelligence is ready. The direct-sync layer is intentionally deferred so Umrah and Tours can share one hardened integration architecture instead of building two separate automation stacks.

---

## 0.1 CURRENT EXECUTIVE VERDICT — 2026-09-14

### Umrah / Program Intelligence

The 2026-09-12 incident is now treated as a closed recovery event, with permanent safety changes recorded.

Accepted recovery outcomes:

```text
Explicit Programı Kaldır archive flow          PASS
Programs 219 / 220 / 221                      ARCHIVED / NOT DELETED
Immutable archive snapshots                    CREATED
Stable IDs retained                            YES
Identity-shift incident                        REPAIRED
Stable-ID entities repaired                    22
Entities deleted during repair                 0
Fixtures STP-000036 / STP-000037               UNTOUCHED / PRIVATE
Registry hash refresh                          21 PRIOR PUBLIC_NOINDEX ROUTES / PASS
Post-repair active JSON dry-run                 33 PROGRAMS
Create                                          0
Update                                          0
Unchanged                                       33
Conflicts / Blocks                              0
Zekerya mapping                                 STP-000038 / CORRECT
Öğretmenler mapping                            STP-000015 / CORRECT
Program 249 mapping                             STP-000035 / CORRECT
```

Current observed component versions after the recovery branch:

```text
Program Intelligence                           v0.3.5
Program Publishing Integration                 v0.4.14
Umrah countdown UI                             DAYS-ONLY PREMIUM / ACCEPTED
/umre-1/                                       LIVE DYNAMIC HUB
Program public mode                            PUBLIC_NOINDEX
Program indexation                             OFF
Program sitemap                                OFF
Bulk GSC / IndexNow                            OFF
```

`STP-000038 / ZEKERYA KURT HOCA UMRE PROGRAMI` is now bound to the correct Stable ID and approved. The individual publish flow appears to have been completed; one final live runtime spot-check is sufficient before treating its public/noindex route as fully re-accepted. Do not reopen the completed identity-repair chain unless regression evidence appears.

### Permanent identity rule

Source row position is **not identity**.

The hardened rule is:

```text
server-owned Stable ID > durable source identity > source-row locator only as provenance
```

A Sheet insertion, sort, row move or new row must never rotate existing `STP-*` identities again. Identity drift must fail closed.

### Explicit removal rule

`Programı Kaldır = TRUE` means operator removal intent, not deletion.

Canonical lifecycle:

```text
ACTIVE
→ explicit removal intent
→ Removal Review
→ human confirmation
→ ARCHIVED
```

Program entities, Stable IDs, snapshots and audit history are retained. There is no normal Program delete path.

---

## 0.2 GOOGLE SHEETS DIRECT SYNC — DEFERRED BY OWNER DECISION

The manual JSON upload workflow is no longer the desired daily operating model. It remains only as a controlled fallback / recovery transport.

The future target is:

```text
Google Sheet
→ one operator action: "Siteyi Güncelle"
→ authenticated direct sync
→ validate
→ identify
→ create / update / archive
→ publishing registry refresh
→ Hub / route / Hotel relation refresh
→ concise result report
```

Target operator result example:

```text
SYNC PASS
Created 1 · Updated 2 · Archived 3 · Unchanged 30 · Errors 0
```

However, implementation is **DEFERRED UNTIL AFTER THE CURRENT TOUR INTELLIGENCE BRANCH**.

Reason:
- Umrah and Tours both need safe Sheet ingestion.
- Building Umrah-only direct sync now would duplicate authentication, identity, validation, audit, retry and rollback logic.
- The final connector should support both systems through one shared integration foundation with domain-specific adapters.

Future shared direct-sync requirements:
- HMAC-signed or equivalently authenticated requests;
- replay protection / timestamp / nonce;
- idempotency keys;
- immutable server-owned Stable IDs (`STP-*`, `STT-*`);
- transactional validate-before-write;
- no global fail-close caused by one bad record;
- per-record errors;
- archive-never-delete semantics;
- audit trail;
- rollback evidence;
- no automatic public/indexable exposure outside explicit publication rules;
- human-readable one-click operator result.

Do not implement this branch until the Tour data model and review workflow are sufficiently stable.

---

## 0.3 TODAY / WEEKLY SEO GATE — FIRST PRIORITY

Today is Monday, 2026-09-14. Before further feature engineering, perform the weekly SEO / search-health review.

### Weekly SEO audit scope

#### A. Search Console performance
Compare:
- last 7 days vs previous 7 days;
- last 28 days as the broader baseline.

Review:
- clicks;
- impressions;
- CTR;
- average position;
- top queries;
- top pages;
- biggest winners / losers;
- branded vs non-branded demand where identifiable;
- device split where material.

#### B. Indexing / crawl health
Check:
- indexing report changes;
- 404 / soft-404 growth;
- redirect anomalies;
- duplicate/canonical anomalies;
- crawled/discovered-not-indexed movements;
- sitemap processing;
- accidental Program indexation;
- accidental Program sitemap inclusion.

#### C. Runtime SEO sanity
Spot-check at minimum:
- homepage;
- `/umre-1/`;
- one active Program final route — must remain `noindex` until explicit unlock;
- one Hotel page;
- Makkah/Madinah Hotel category surfaces;
- Trust / Journey Evidence article #6216;
- primary Tour surface(s) currently public;
- known historical redirect / 404 problem URLs.

#### D. Technical / performance regression
Review only for regression evidence:
- Core Web Vitals / PageSpeed trend;
- mobile rendering;
- title / meta / canonical / H1;
- schema validity;
- robots directives;
- sitemap integrity;
- obvious JS/CSS/runtime regressions.

Do **not** reopen already accepted design or technical branches just to recreate old evidence.

### SEO decision output

The weekly SEO review must end with one short action register:

```text
P0 — immediate revenue/indexing risk
P1 — this week
P2 — backlog / observe
NO ACTION — healthy / accepted
```

Only after the weekly SEO gate is understood do we resume Tour Intelligence implementation.

---

## 0.4 TOUR INTELLIGENCE — ACTIVE PRODUCT BRANCH AFTER SEO AUDIT

The 2026-09-11 STTI checkpoint remains authoritative for the Tour architecture.

Preserved accepted state:

```text
System                                        STTI — Server Turizm Tour Intelligence
Canonical schema                              STTI-TOUR-1.1.0
Import contract                               STTI-TOUR-IMPORT-1.0.0
Private Store Core                            v0.2.3 ACCEPTED
Structured Authoring Core                     v0.3.0 ACCEPTED
Private Renderer                              v0.4.0 ACCEPTED
Customer Preview                              v0.5.7 ACCEPTED
JSON Validate / Normalize / Dry Run           v0.6.0 CLOSED / RUNTIME ACCEPTED
Simple Google Sheet Intake                    v0.6.1 ACTIVE OPERATOR BASELINE
Sheet → Partial JSON                          v0.6.2.1 CLOSED / RUNTIME ACCEPTED
Current real Tour candidate                   STT-000001 / Büyük İran Turu
Tour Public Master                            OFF
Tour Public Routes                            OFF
Tour Sitemap                                  OFF
Tour Indexation                               OFF
Tour Homepage Adapter                         OFF
Tour Schema / Canonical Exposure              OFF
```

The Sheet remains intentionally simple and business-facing. Do not turn it into a large multi-tab expert authoring workbook.

### Next Tour branch

Resume with:

```text
v0.6.5 — AI Completion Contract
```

Purpose:
- define safe `PARTIAL → FULL / REVIEW` transformation;
- preserve unknowns as unknowns;
- preserve provenance;
- never invent hotels, routes, variants, prices or transport facts;
- distinguish operator facts from AI-assisted normalization / completion;
- create explicit review gates before canonical write.

Then continue in this order:

```text
1. AI Completion Contract
2. WordPress Review relations
   - Route Variants
   - Hotel Options
   - Hotel Intelligence links
   - Transport relations
3. Canonical Geo Resolver
4. Complete Customer Renderer
5. First real Full Tour
6. Controlled private/public pilot
7. Only later: Tour SEO/indexation/sitemap unlock decision
```

Locked modeling rule:
- Tour = primary product.
- Variant = materially different route / itinerary / date / transport / pricing version.
- Hotel Option = accommodation alternative inside the same Variant.

Unknown data must stay unresolved; AI must not fabricate it.

---

## 0.5 SITE-WIDE PRIORITY ORDER — UPDATED

### P0 — Current
1. Weekly SEO/search-health audit.
2. Close any newly discovered indexing/crawl/revenue regression.
3. Resume STTI v0.6.5 AI Completion Contract.

### P1 — Tour Intelligence
4. Variant / Hotel Option / Hotel Intelligence / Transport Review relations.
5. Canonical Geo Resolver.
6. Complete Customer Renderer.
7. First real Full Tour candidate.
8. Controlled Tour public pilot while all SEO/indexation locks remain explicit.

### P1.5 — Unified Operations Automation
9. Design and implement shared Google Sheets Direct Sync foundation.
10. Connect Umrah adapter.
11. Connect Tour adapter.
12. Replace normal JSON download/upload with one-click operator sync.
13. Preserve JSON as fallback/recovery transport only.

### P2 — Later strategic branches
14. Hac Intelligence.
15. Trust Intelligence expansion.
16. SEO → Lead → Revenue attribution.
17. Plugin-suite consolidation into `Server Turizm Intelligence Suite` after production modules are stable enough to migrate safely.
18. Staged Program/Tour indexation decisions only after runtime and SEO evidence gates.

---

## 0.6 NON-NEGOTIABLE SAFETY / OWNERSHIP RULES

Preserve:
- Program facts → Program Intelligence.
- Tour facts → Tour Intelligence.
- Hotel facts/media/stable IDs → Hotel Intelligence.
- Program→Hotel relation → Program `stays[].hotel_id`.
- Hotel→Program relation → dynamically derived; no mirrored Hotel-side business-data list.
- Publishing routes/modes → publishing registry.
- `/umre-1/` cards → dynamic structured-data rendering, not manual duplicated cards.
- completed/removed Programs → archive workflow, not deletion.
- fixtures `STP-000036` and `STP-000037` → private/protected.
- Program indexation/sitemap → OFF until explicit staged decision.
- Tour public/indexation/sitemap/schema/canonical/homepage exposure → OFF until controlled pilot gates pass.

Do not use a global destructive fail-close routine as the normal response to one invalid incremental record.

---

## 0.7 CURRENT CHECKPOINT SUMMARY

```text
DATE                                           2026-09-14

WEEKLY SEO
STATUS                                         FIRST ACTION TODAY
NEXT                                           GSC + INDEXING + RUNTIME SEO AUDIT

UMRAH / PROGRAM INTELLIGENCE
PROGRAM INTELLIGENCE                           v0.3.5
PROGRAM PUBLISHING INTEGRATION                 v0.4.14
/umre-1/                                       LIVE / DYNAMIC
PROGRAM ROUTES                                 PUBLIC_NOINDEX
PROGRAM INDEXATION                             OFF
PROGRAM SITEMAP                                OFF
REMOVED 219 / 220 / 221                        ARCHIVED / 0 DELETED
IDENTITY REPAIR                                22 REPAIRED / 0 DELETED
REGISTRY IDENTITY REFRESH                      21 ROUTES / PASS
ACTIVE IMPORT IDENTITY DRY RUN                 33 UNCHANGED / 0 CREATE / 0 UPDATE
STP-000038 ZEKERYA                             IDENTITY CORRECT / APPROVED / PUBLIC SPOT-CHECK PENDING
COUNTDOWN                                      DAYS-ONLY PREMIUM / ACCEPTED
DIRECT GOOGLE SHEET SYNC                       DEFERRED UNTIL AFTER TOUR BRANCH

TOUR INTELLIGENCE
SCHEMA                                         STTI-TOUR-1.1.0
IMPORT CONTRACT                                STTI-TOUR-IMPORT-1.0.0
SHEET → PARTIAL JSON                           v0.6.2.1 ACCEPTED
CURRENT REAL CANDIDATE                         STT-000001 / BÜYÜK İRAN TURU
NEXT BRANCH                                    v0.6.5 AI COMPLETION CONTRACT
TOUR PUBLIC MASTER                             OFF
TOUR PUBLIC ROUTES                             OFF
TOUR INDEXATION                                OFF
TOUR SITEMAP                                  OFF

EXECUTION ORDER
1                                              WEEKLY SEO AUDIT
2                                              TOUR INTELLIGENCE CONTINUATION
3                                              UNIFIED UMRAH + TOUR SHEET DIRECT SYNC
```

---

## 0.8 NEXT-CHAT / CONTINUATION PROMPT

> Continue the Server Turizm project from the **2026-09-14 authoritative Master Plan checkpoint**. First complete the weekly SEO/search-health audit using current Search Console evidence and targeted live runtime checks. Preserve accepted Umrah production baselines unless regression evidence appears. Programs 219/220/221 are archived, not deleted. The Stable-ID identity incident was repaired transactionally: 22 entities repaired, 0 deleted; 21 prior public/noindex route hashes were refreshed; the latest active import dry-run is 33 unchanged with Zekerya correctly mapped to STP-000038, Öğretmenler to STP-000015 and Program 249 to STP-000035. Program Intelligence is v0.3.5 and Publishing Integration is v0.4.14. Program indexation/sitemap/bulk submission remain OFF. After the SEO audit, resume **STTI Tour Intelligence** from the accepted v0.6.2.1 Sheet→Partial JSON runtime checkpoint; the next implementation branch is **v0.6.5 AI Completion Contract**, followed by WordPress Review relations for Route Variants / Hotel Options / Hotel Intelligence / Transport, Canonical Geo Resolver, Complete Customer Renderer and the first real Full Tour. Keep all Tour public/indexation/sitemap/schema/canonical/homepage locks OFF. Do **not** implement the Umrah-only direct Google Sheets connector yet. After the Tour operating model is stable, build one shared, authenticated, transactional Google Sheets→WordPress direct-sync foundation and connect both Umrah and Tours to it so normal operations become one-click and manual JSON upload remains fallback only.

# END OF 2026-09-14 AUTHORITATIVE SUPERSESSION

---

# PREVIOUS AUTHORITATIVE MASTER PLAN PRESERVED BELOW

# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-11 AUTHORITATIVE SUPERSESSION — TOUR INTELLIGENCE / GOOGLE SHEETS → PARTIAL JSON RUNTIME ACCEPTED

# 0. AUTHORITATIVE SUPERSESSION — READ THIS FIRST

This 2026-09-11 block is the newest authoritative continuation point.

It supersedes earlier day-to-day continuation instructions wherever they conflict.  
All accepted Umrah / Program Intelligence / Homepage / Hotel Intelligence / technical SEO baselines from the preserved 2026-09-10 Master Plan remain valid unless explicitly superseded here.

The 2026-09-11 work did **not** reopen accepted Umrah production branches. The active engineering branch was **STTI — Server Turizm Tour Intelligence**.

---

## 0.1 EXECUTIVE VERDICT — END OF DAY 2026-09-11

The Tour Intelligence pipeline has now reached a safe, operational Sheet→JSON→WordPress Dry Run chain:

```text
Simple Google Sheet operator row
→ STTI Google Sheets Apps Script
→ STTI-TOUR-IMPORT-1.0.0 PARTIAL JSON
→ stable-id/checksum targeting
→ WordPress Validate + Normalize + Dry Run
→ CREATE / UPDATE / UNCHANGED / CONFLICT / INVALID classification
→ NO WRITE
```

Accepted runtime result:

```text
STTI v0.6.0 JSON Validate + Normalize + Dry Run     CLOSED / RUNTIME ACCEPTED
STTI v0.6.2.1 Google Sheets → Partial JSON          CLOSED / RUNTIME ACCEPTED
Existing-record targeting                           PASS
Stable ID + expected checksum                       PASS
WordPress classification for Iran pilot             UPDATE / PASS
Canonical write during Dry Run                      NONE
Audit write during Dry Run                          NONE
Sequence write during Dry Run                       NONE
Public/SEO exposure                                 NONE
```

The operator workflow is intentionally simple. Detailed tour authoring must **not** be pushed back into a large multi-sheet intake system.

---

## 0.2 STTI ACCEPTED COMPONENT REGISTER

### Canonical Tour layer

```text
System name                 STTI — Server Turizm Tour Intelligence
Canonical schema            STTI-TOUR-1.1.0
Import contract             STTI-TOUR-IMPORT-1.0.0
Stable identity format      STT-000001...
```

Stable ID is immutable identity. Title, slug, country, date and WordPress post ID are not identity.

### Accepted branch status

```text
v0.2.3  Private Store Core                         CLOSED / RUNTIME ACCEPTED
v0.3.0  Structured Authoring Core                  CLOSED / RUNTIME ACCEPTED
v0.4.0  Private Renderer Pilot                     CLOSED / RUNTIME ACCEPTED
v0.5.7  Customer Preview / DOM header seam fix     RUNTIME ACCEPTED
v0.6.0  JSON Validate + Normalize + Dry Run        CLOSED / RUNTIME ACCEPTED
v0.6.1  Simple Google Sheet operator intake        ACTIVE OPERATOR BASELINE
v0.6.2.1 Sheet → Partial JSON + record targeting   CLOSED / RUNTIME ACCEPTED
```

### Customer Preview preserved state

The accepted preview state remains:

```text
Header seam strategy        dynamic DOM-measured hero bleed
Live header selector        actual active theme header
Route visual skin           v0.5.4 restored
Map engine                  Leaflet global route
Tiles                       OpenStreetMap
Pilot geocoder              Nominatim client
Pilot coordinate cache      browser localStorage
Unresolved geo policy       never invent; admin warning
Public route                false
Indexable                   false
Sitemap                     false
Schema output by STTI       false
Canonical changes by STTI   false
```

Do not reopen the header seam or route-map visual branch without regression evidence.

---

## 0.3 v0.6.0 JSON IMPORT GATE — FULL MATRIX ACCEPTED

The WordPress importer is in private Validate/Normalize/Dry Run mode.

Accepted classifications:

```text
CREATE       PASS
UNCHANGED    PASS
UPDATE       PASS
CONFLICT     PASS
INVALID      PASS
```

Safety state after all tests:

```text
candidate_count                 1
next_stable_id_sequence         2
canonical_write                 false
audit_write                     false
sequence_write                  false
publication_request_allowed     false
public_renderer                 false
public_routes                   false
sitemap                         false
indexation                      false
homepage_adapter                false
schema_output                   false
canonical_robots_changes        false
```

Protected Iran canonical baseline remained unchanged after the complete Dry Run test matrix.

---

## 0.4 STT-000001 — CURRENT CANONICAL BASELINE VS SHEET PROPOSAL

Canonical WordPress candidate:

```text
Stable ID        STT-000001
Title            Büyük İran Turu
Schema           STTI-TOUR-1.1.0
Canonical hash   d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348
Updated at       2026-09-11 09:47:41
Latest Audit     ID 8 / candidate_updated
```

The canonical WordPress record was **not committed again** during the Sheet/JSON tests.

The current Google Sheet proposal contains different tour dates and therefore correctly classifies as `UPDATE` during Dry Run.

Observed Sheet proposal:

```text
Start date       2027-01-26
End date         2027-02-12
Duration         18 days / 17 nights
Route            Tahran → Kaşan → İsfahan → Yezd → Şiraz
Primary price    899 EUR
Price basis      unknown
```

Dry Run result:

```text
Classification   UPDATE
Target           STT-000001
Proposed ID      STT-000001
Mode             PARTIAL
Current checksum d72c07fb...
Proposed checksum b3bafeb82018561f1a899c379e7b5d160685d6df68fd489e9b359fbfce1b6182
Write            NONE
```

This is a successful targeting test, **not an approval to commit the 2027 dates**.  
Before any future Commit, business dates must be consciously reviewed and confirmed.

---

## 0.5 GOOGLE SHEETS OPERATOR MODEL — LOCKED PHILOSOPHY

The failed direction was a large technical workbook with many tabs and engineering fields.

The accepted operator philosophy is instead:

> **One simple row per tour, visually similar to the existing Umrah operating Sheet.**

The visible operator Sheet remains business-facing and fast to fill.

Typical visible fields:

```text
Program No
Tür
Tur Adı
Ülke / Hedef
Gidiş
Dönüş
Rota
Şehirler
Ek Ülkeler
Otel(ler)
Otel / Gece Planı
Ulaşım
Havayolu
Uçuş / Transfer Notu
2 Kişilik
3 Kişilik
4 Kişilik
Çocuk
Para Birimi
Kontenjan
Dolu
Vize
```

Technical control data must not clutter normal operator work.

For existing STTI records, hidden technical metadata is used:

```text
STTI Stable ID
Expected Checksum
```

Rules:

```text
both blank                  → NEW tour / CREATE candidate
Stable ID + checksum        → existing record target
only one present            → INVALID / block generation
```

The Iran row was linked to:

```text
STT-000001
d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348
```

and WordPress correctly returned `UPDATE`.

---

## 0.6 PARTIAL JSON GENERATOR — ACCEPTED CONTRACT

Accepted generator version:

```text
STTI v0.6.2.1
Producer: Google Sheets
Mode: partial
```

Required safety policy:

```json
{
  "facts": "source_only_no_invention",
  "missing_values": "omit_or_null_or_explicit_unknown",
  "publication": "force_private"
}
```

The generator must:

- read only known Sheet facts;
- preserve missing facts as missing / null / explicit unknown;
- never invent hotels, transport facts, route facts, visa facts or price basis;
- use hidden Stable ID + checksum only for safe targeting;
- produce `city` on route stops only when the city is explicitly present in the Sheet city data;
- leave unresolved hotels unresolved;
- never request a public route, sitemap, indexation, schema or canonical change.

Current first-pilot output correctly includes:

```text
target.stable_id                     STT-000001
target.expected_checksum_sha256      d72c07...
route stop label + explicit city     PASS
pricing                              899 EUR
pricing basis                        unknown
visa                                 unknown
publication policy                   force_private
```

---

## 0.7 TOUR VARIANTS + HOTEL OPTIONS — ARCHITECTURAL DECISION LOCKED

Do **not** expand the Google Sheet now just because future tours may have multiple routes or hotels.

The Sheet is only the fast intake surface.

Missing and complex facts are completed later in WordPress Review.

Canonical interpretation:

```text
Tour          = primary commercial product / stable STT entity
Variant       = materially different version of the same Tour
Hotel Option  = accommodation alternative inside a Variant
```

### When to create a Variant

Create a Variant only when there is a material difference such as:

- different route;
- materially different itinerary;
- different departure/return pattern;
- variant-specific dates;
- variant-specific transport plan;
- variant-specific pricing package.

Do **not** create another Tour merely because one hotel changes.

### Hotel Options

If route/date/program are the same but accommodation differs:

```text
same Tour
same Variant
→ multiple Hotel Options
```

Future WordPress Review UI should support, when needed:

```text
+ Add Route Variant
+ Add Hotel Option
```

A hotel relation should eventually store structured facts such as:

```text
city
hotel_ref / stable Hotel ID when resolved
unresolved_name when Hotel Intelligence relation does not yet exist
night count
date window when known
option group / variant relation
source provenance
```

### Current hotel policy

Most Tour hotels are not yet known.

Therefore:

```text
unknown hotel             → leave absent / pending / unresolved
AI                         → MUST NOT invent
operator                   → adds later when source exists
Hotel Intelligence match  → relation by stable Hotel ID
```

For the Egypt/Cairo case, known Cairo accommodation information may be added when the real source data is ready.  
No placeholder hotels should be created for the remaining unknown stays.

---

## 0.8 WORDPRESS REVIEW IS THE COMPLETION SURFACE

WordPress Admin should remain **Review / Control**, not a place where the operator manually types dozens of initial fields.

Target workflow:

```text
Google Sheet
→ Partial JSON
→ WordPress Validate + Dry Run
→ AI Completion candidate
→ Human Review
→ Route Variant review when applicable
→ Hotel Option / Hotel Intelligence relations when known
→ Transport / Itinerary / Pricing completion
→ private canonical Commit
→ Private Customer Preview
→ later controlled public gates
```

This separates:

```text
fast business intake
from
structured canonical review
from
public publishing
```

and prevents duplicated or invented data.

---

## 0.9 NEXT BRANCH — v0.6.5 AI COMPLETION CONTRACT

This is the first branch for the next working session.

Goal:

> Convert a safe PARTIAL JSON into a richer FULL/REVIEW JSON without inventing unsupported business facts.

The contract must explicitly define:

### AI MAY

- normalize spelling and structure without changing meaning;
- transform explicitly supplied prose into structured fields;
- derive mechanically certain values such as duration from exact dates;
- classify supplied facts into existing schema fields;
- identify missing information;
- produce review warnings;
- preserve source provenance;
- propose editorial text only when clearly labeled as editorial/generated content.

### AI MUST NOT

- invent hotels;
- invent route variants;
- invent daily itinerary facts not supported by a source;
- invent airline / flight / transfer facts;
- invent visa requirements;
- invent included/excluded services;
- invent prices, price basis or occupancy;
- change exact dates without source support;
- create public/indexable states;
- overwrite an existing STT entity without Stable ID + checksum safety;
- silently turn `unknown` into a fact.

Expected output state:

```text
FULL STRUCTURE
≠
ALL FACTS KNOWN
```

A structurally complete payload may still legitimately contain:

```text
null
[]
unknown
needs_review
unresolved
```

when sources do not support more.

---

## 0.10 FOLLOW-ON TOUR INTELLIGENCE ROADMAP

After v0.6.5:

```text
v0.7.0   WordPress Review relations
         - Route Variant model
         - Hotel Options
         - Hotel Intelligence links
         - Transport relations

v0.7.1   Canonical Geo Resolver
         - reviewed coordinates
         - remove browser localStorage as canonical dependency
         - worldwide routes
         - no guessed coordinates

v0.8.0   Complete Customer Renderer
         - render complete canonical Tour data
         - variant-aware presentation
         - hotel-option-aware presentation

v0.9.0   First Real Full Tour
         - one source-complete production candidate
         - full QA

v1.0     Controlled Public Pilot
         - explicit Public Master Lock
         - one intentional route
         - no mass route generation
         - SEO/schema/canonical/sitemap gates separately approved
```

Exact version scope may be refined during implementation, but the dependency order must remain.

---

## 0.11 TOUR PUBLIC RELEASE LOCKS — STILL OFF

No work completed today authorizes public Tour exposure.

Remain OFF:

```text
Tour Public Master
Tour public routes
Tour sitemap inclusion
Tour indexation
Tour homepage adapter
Tour schema output
Tour canonical/robots changes
mass Tour URL generation
```

Do not treat a successful private renderer or Dry Run as permission to publish.

---

## 0.12 UMRAH / SEO BASELINES — PRESERVE

The existing 2026-09-10 accepted production state remains preserved:

- P6 technical SEO: CLOSED / preserve.
- P7A / ST-TDE: CLOSED / RUNTIME ACCEPTED.
- Program Intelligence v0.3.2: accepted canonical Program layer.
- 35 real Umrah Programs: approved with stable `public_noindex` routes.
- `STP-000036` and `STP-000037`: protected fixtures.
- `/umre-1/`: live dynamic Hub / accepted.
- Umrah Hub v0.2.8: frozen visual baseline.
- accepted mobile Program information architecture: preserve.
- Homepage Intelligence v0.3.3: live.
- Homepage Journey Evidence: live / accepted.
- first-party Özbekistan / İmam Buhârî article: live, technical SEO QA passed, GSC Live Test passed.
- Program detail indexation: OFF.
- Program sitemap: OFF.
- bulk GSC / IndexNow: OFF.

Do not reopen these accepted branches merely because Tour Intelligence development continues.

---

## 0.13 CURRENT CHECKPOINT SUMMARY

```text
DATE                                      2026-09-11

UMRAH / SEO
P6 TECHNICAL SEO                          CLOSED / PRESERVE
P7A / ST-TDE                              CLOSED / RUNTIME ACCEPTED
PROGRAM INTELLIGENCE                      v0.3.2 ACCEPTED
REAL UMRAH PROGRAMS                       35 APPROVED
PROGRAM ROUTES                            LIVE / PUBLIC_NOINDEX
/umre-1/                                  LIVE / ACCEPTED
PROGRAM INDEXATION                        OFF
PROGRAM SITEMAP                           OFF

TOUR INTELLIGENCE
STTI CANONICAL SCHEMA                     STTI-TOUR-1.1.0
STTI IMPORT CONTRACT                      STTI-TOUR-IMPORT-1.0.0
PRIVATE STORE CORE                        v0.2.3 ACCEPTED
STRUCTURED AUTHORING CORE                 v0.3.0 ACCEPTED
PRIVATE RENDERER                          v0.4.0 ACCEPTED
CUSTOMER PREVIEW                          v0.5.7 ACCEPTED
JSON VALIDATE/NORMALIZE/DRY RUN           v0.6.0 CLOSED / RUNTIME ACCEPTED
SIMPLE GOOGLE SHEET INTAKE                v0.6.1 ACTIVE OPERATOR BASELINE
SHEET → PARTIAL JSON                      v0.6.2.1 CLOSED / RUNTIME ACCEPTED

CURRENT REAL TOUR CANDIDATES               1
CURRENT CANDIDATE                          STT-000001 / Büyük İran Turu
CANONICAL CHECKSUM                         d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348
LATEST CANONICAL AUDIT                     ID 8
SHEET TARGETING TEST                       UPDATE / PASS
CANONICAL WRITE FROM SHEET TEST            NONE

TOUR PUBLIC MASTER                         OFF
TOUR PUBLIC ROUTES                         OFF
TOUR SITEMAP                               OFF
TOUR INDEXATION                            OFF
TOUR HOMEPAGE ADAPTER                      OFF
TOUR SCHEMA/CANONICAL EXPOSURE             OFF

NEXT BRANCH                                v0.6.5 AI COMPLETION CONTRACT
THEN                                       VARIANTS + HOTEL OPTIONS IN WORDPRESS REVIEW
```

---

## 0.14 NEXT-CHAT CONTINUATION PROMPT

Use this as the authoritative restart prompt:

> Continue the Server Turizm project from the **2026-09-11 authoritative Master Plan checkpoint**. Preserve all accepted Umrah/SEO baselines and do not reopen closed branches without regression evidence. The active branch is **STTI — Server Turizm Tour Intelligence**. `STTI-TOUR-1.1.0` is the canonical Tour schema and `STTI-TOUR-IMPORT-1.0.0` is the accepted import contract. `v0.6.0 JSON Validate + Normalize + Dry Run` is **CLOSED / RUNTIME ACCEPTED** with CREATE / UNCHANGED / UPDATE / CONFLICT / INVALID all passed and no canonical/audit/sequence writes. `v0.6.2.1 Google Sheets → Partial JSON + existing-record targeting` is **CLOSED / RUNTIME ACCEPTED**: the Iran Sheet row successfully targeted `STT-000001` using its stable ID and baseline checksum and WordPress classified it as UPDATE with NO WRITE. The canonical WordPress Iran candidate still has checksum `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`; the 2027 Sheet dates are only a proposed Dry Run update until explicitly confirmed and committed. Keep the Sheet simple and business-facing; do not reintroduce a complex multi-tab intake workbook. Missing hotels, routes and other facts stay unknown/unresolved. Complex Route Variants and Hotel Options belong in the later WordPress Review layer, not in initial Sheet intake. Tour = primary product; Variant = materially different route/itinerary/date/transport/pricing version; Hotel Option = accommodation alternative inside the same Variant. AI must never invent unknown hotels or variants. The next branch is **v0.6.5 AI Completion Contract**, defining safe PARTIAL→FULL/REVIEW transformation while preserving unknowns and provenance. After that, implement WordPress Review relations for Route Variants / Hotel Options / Hotel Intelligence / Transport, then Canonical Geo Resolver, Complete Customer Renderer, first real Full Tour, and only later a controlled public pilot. All Tour public/indexation/sitemap/schema/canonical/homepage locks remain OFF.

# END OF 2026-09-11 AUTHORITATIVE SUPERSESSION

---

# PREVIOUS AUTHORITATIVE MASTER PLAN PRESERVED BELOW

# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-10 AUTHORITATIVE CHECKPOINT — UMRAH HUB LIVE / JOURNEY EVIDENCE LIVE / DIGITAL PR ARTICLE LIVE

# 0. AUTHORITATIVE SUPERSESSION — READ THIS FIRST

This block supersedes earlier day-to-day continuation instructions wherever they conflict. Historical material below remains preserved for architecture, audit and rollback context.

## 0.1 CURRENT EXECUTION VERDICT

The Server Turizm website is now operating with the following accepted live chain:

```text
Structured Program data
→ Program Intelligence
→ controlled final Program routes (public_noindex)
→ live /umre-1/ dynamic Hub
→ Hotel Intelligence relations
→ Homepage Intelligence plugin-owned baseline
→ dynamic Trust / Journey Evidence module
→ first-party news/evidence article
→ official external source citation
```

The project must now return to measurement and controlled SEO/indexation work rather than reopening accepted UI branches.

### Current high-level status
- P6 Technical SEO foundation: CLOSED / preserve unless regression evidence appears.
- P7A / ST-TDE: FULLY CLOSED / RUNTIME ACCEPTED.
- Program Intelligence canonical layer: ACCEPTED.
- 35 real Umrah Programs: migrated, approved, stable Final Routes, `public_noindex`.
- `/umre-1/`: LIVE dynamic Hub, owner accepted.
- Umrah Hub desktop visual baseline: FROZEN / ACCEPTED.
- Umrah Hub mobile information-order hotfix: RUNTIME ACCEPTED.
- Homepage plugin-owned baseline: LIVE.
- Homepage Journey Evidence / Digital PR module: LIVE / OWNER ACCEPTED.
- First-party Özbekistan / İmam Buhârî evidence article: LIVE; technical SEO QA passed.
- News article premium UI: FINAL LIGHT-LUXURY CANDIDATE PENDING ONE LAST RUNTIME VISUAL CONFIRMATION; do not treat it as accepted until owner confirms the final rendered output.

---

## 0.2 CURRENT RUNTIME COMPONENT REGISTER

Current observed runtime/plugin state from 2026-09-10 admin checks:

- `Server Turizm Homepage Intelligence v0.3.3` — active, single installed copy after duplicate cleanup.
- Homepage mode: `PUBLIC · PLUGIN BASELINE`.
- Homepage Public Master: `AÇIK`.
- Journey Evidence: PUBLIC ON after controlled `STORY` gate.
- `Server Turizm Home Hotel Discovery v0.1.5` — active.
- `Server Turizm Hotel Intelligence v0.9.11` — active.
- `Server Turizm Program Foundation v0.2.0` — active compatibility/bridge layer; do not remove without dependency audit.
- `Server Turizm Program Intelligence v0.3.2` — accepted canonical Program layer.
- `Server Turizm Program Publishing Integration v0.4.7` — current observed publishing/integration runtime.
- `Server Turizm Umre Semantic Adapter v0.33.0` — active.
- Header/Footer and existing performance snippets/plugins remain active unless a future regression-specific audit says otherwise.

Version rule remains unchanged: higher version number does not equal accepted baseline; owner runtime PASS is required.

---

## 0.3 UMRAH PROGRAM MIGRATION — LIVE ACCEPTED STATE

All real business Programs `STP-000001…STP-000035` are approved and have stable final routes under:

```text
/umre-programlari/<stable-slug>/
```

Current route state:

```text
public_noindex
```

Full bulk runtime QA passed for all 35 real routes:
- HTTP 200;
- exactly one H1;
- `noindex` robots preserved;
- exact self-canonical;
- JSON-LD present;
- images present.

The apparent additional failed route seen during the bulk console scan was only the admin placeholder:

```text
/umre-programlari/slug/
```

It is not a real Program and is not a migration failure.

Protected fixtures remain non-public and must remain untouched:
- `STP-000036` — lifecycle fixture;
- `STP-000037` — private renderer fixture.

Do not approve, publish, archive, repurpose or expose those fixtures unless a later explicit test specifically requires it.

---

## 0.4 `/umre-1/` LIVE CUTOVER — ACCEPTED

The old manually hard-coded Program-card block in WPBakery RAW3 has been retired from live use and replaced by:

```text
[stppi_umre_programs]
```

Preserved page structure:

```text
RAW1                         KEEP
RAW2                         KEEP
[stppi_umre_programs]        LIVE dynamic Program Hub
SEO RAW #1                   KEEP
SEO RAW #2                   KEEP
SEO RAW #3                   KEEP
Page Custom CSS              KEEP until dependency cleanup is explicitly audited
Footer                       KEEP
```

All 35 Programs were visually confirmed on the live Hub.

### Frozen Hub UI
`Server Turizm Umre Hub Card UI v0.2.8` remains the accepted visual baseline.

Do not redesign or polish it further unless a real regression/bug is demonstrated.

### Accepted mobile information architecture
A serious mobile ordering defect was fixed and owner accepted. The intended mobile sequence is now:

```text
Program identity
→ hero
→ journey/timeline
→ Hotels in canonical stays[] order
→ logos
→ prices
→ details / CTA
```

No JS-only reordering or duplicated DOM should be introduced later.

---

## 0.5 HOMEPAGE INTELLIGENCE — CURRENT ACCEPTED LIVE STATE

Homepage rendering is owned by the plugin baseline, not by ad-hoc WPBakery Raw HTML editing.

Accepted state:
- `Homepage Intelligence v0.3.3`;
- `PUBLIC · PLUGIN BASELINE`;
- Public Master ON;
- existing Hero / Campaign Grid / Featured Umrah / Hac / H12F Hotel Discovery / Culture Tours preserved;
- duplicate Homepage Intelligence plugin copies were cleaned up; only one active runtime copy should exist.

### Journey Evidence / Digital PR module
A dynamic Trust/Evidence module sourced from WordPress Post `#6216` was implemented and passed:
- Admin Preview;
- desktop visual QA;
- mobile visual QA;
- controlled `STORY` public gate;
- logged-out/incognito live verification.

The module reads dynamically from the source Post rather than duplicating content:
- title;
- excerpt;
- featured image;
- date;
- permalink.

Placement:

```text
Kültür Turları
→ Journey Evidence / Özbekistan story
→ theme footer / lower homepage continuation
```

Homepage H1 ownership remains unchanged. The Journey module must not add competing H1, duplicate schema, canonical, robots, sitemap or Organization ownership.

Journey Evidence state:

```text
LIVE / OWNER ACCEPTED / UI FREEZE
```

Rollback remains isolated to the Journey module via the controlled `STORY-OFF` gate if ever needed.

---

## 0.6 FIRST-PARTY TRUST / DIGITAL PR ARTICLE — LIVE

Published article:

```text
/ozbekistan-turumuz-imam-buhari-merkezi-ziyareti/
```

Editorial basis:
- Server Turizm first-party Özbekistan journey evidence;
- visit to İmam Buhârî Uluslararası Bilimsel Araştırma Merkezi;
- external official-source citation to the Centre's publication;
- no unsupported claim that Server Turizm itself was directly named by the external outlet.

Accepted technical QA after publication:
- H1 count = 1;
- clean self-canonical on final slug;
- robots contains no `noindex`;
- JSON-LD count = 1;
- OG title present;
- OG description present;
- OG image present;
- Twitter card = `summary_large_image`;
- meta description present.

The H1 semantic issue (`H2` → `H1`) was fixed server-side for Post 6216.

Comments were removed from the article workflow; low-value author/comment blocks are intended to remain hidden in the premium article UI.

### Editorial/UI state
The original/default Porto article UI was judged too plain compared with the Homepage.

A light-luxury premium editorial direction was preferred over an all-dark redesign:
- warm ivory/paper surface;
- Server Turizm navy + gold accents;
- premium serif H1;
- connected hero/article composition;
- upgraded official-source box;
- restrained decorative texture;
- responsive mobile treatment.

Important: the final light-luxury CSS candidate was supplied immediately before this checkpoint, but the owner has not yet returned a final runtime screenshot confirming it. Therefore:

```text
NEWS ARTICLE UI = FINAL CANDIDATE / ONE LAST VISUAL PASS PENDING
```

In the next chat, do not redesign from zero. If needed, perform one quick desktop/mobile confirmation and then freeze it.

---

## 0.7 DIGITAL PR / SOCIAL DISTRIBUTION STATE

A Server Turizm Instagram feed visual was prepared around the Özbekistan / İmam Buhârî story.

Because normal Instagram feed captions do not provide a practical clickable external link path for this use case, the CTA direction was changed from a misleading click-style `HABERİ OKU` to website-oriented copy:

```text
WEB SİTEMİZDE
```

Recommended distribution pattern:
- Feed post for awareness/social proof;
- Story with Link Sticker for direct article traffic;
- website Homepage Journey Evidence for owned-site trust/internal linking;
- official external-source link preserved inside the article.

Do not overclaim `Server Turizm basında` unless an external source explicitly names Server Turizm.

---

## 0.8 SEO / INDEXATION LOCKS THAT REMAIN ACTIVE

Do not confuse the live commercial Hub with Program detail indexation.

Until an explicit later gate:
- all 35 Program detail routes remain `public_noindex`;
- Program detail sitemap inclusion remains OFF;
- bulk Program Search Console submission remains OFF;
- Program IndexNow submission remains OFF;
- fixtures 36/37 remain BLOCKED;
- do not remove Program `noindex` in bulk;
- do not mass-request indexing;
- do not re-open accepted Hub UI;
- do not duplicate Program facts in Hotel records;
- do not edit Hotel facts merely to alter Program relations.

Public indexation remains a separate owner-approved staged decision.

---

## 0.9 GOOGLE SEARCH CONSOLE SANITY — TRUST ARTICLE LIVE TEST PASSED

The newly published Trust / Journey Evidence article was inspected in Google Search Console:

```text
https://www.serverturizm.com.tr/ozbekistan-turumuz-imam-buhari-merkezi-ziyareti/
```

### Search Console indexed-state result
At first inspection Google reported:

```text
URL is not on Google
Page is not indexed: URL is unknown to Google
```

This is expected for a newly published URL and is not a technical failure.

### Live Test result — PASS
The Google Live Test then returned:
- Page availability: **Page can be indexed**;
- Crawled as: **Google Inspection Tool smartphone**;
- Crawl allowed: **Yes**;
- Page fetch: **Successful**;
- Indexing allowed: **Yes**;
- User-declared canonical: exact clean final article URL;
- Google-selected canonical: **Only determined after indexing**.

Verdict:

```text
TRUST ARTICLE SEARCH CONSOLE LIVE TEST = PASS
```

One manual **Request Indexing** action is allowed for this new editorial article. Do not repeatedly request indexing afterward.

### Remaining Search Console sanity checks for next chat
1. Inspect `/umre-1/` to confirm post-cutover crawlability/canonical/rendering remains healthy.
2. Inspect one known real Program detail route only to confirm the intentional `noindex` lock.
3. Do **not** request indexing for Program detail routes.

---

## 0.10 NEXT PROJECT BRANCH AFTER SEARCH CONSOLE SANITY

Resume the core project in this order:

```text
1. Google Search Console sanity: article LIVE TEST PASSED; finish `/umre-1/` + one Program noindex route
2. Fresh Read-only Evidence export from Program Publishing Integration
3. Verify 37-record registry / fixtures / locks remain intact
4. Recheck Program sitemap exclusion
5. Hotel → Program controlled public-link implementation
6. Runtime QA Hotel → Program derived links
7. Archive workflow + archived Program URL policy
8. Explicit staged Program indexation decision
9. Only after owner approval: sitemap/indexation/Search Console/IndexNow pilot
10. Continue Umrah topical authority + Trust/Evidence scaling
11. Then Finder / Compare / Attribution / Dashboard according to dependency order
```

Do not jump directly to mass Program indexation.

---

## 0.11 NEXT-CHAT CONTINUATION PROMPT

Use this prompt in the next normal chat:

> Continue the Server Turizm SEO / Program Intelligence project from the authoritative **2026-09-10 checkpoint**. Read the attached updated Master Plan first and treat the newest supersession as authoritative. `/umre-1/` is LIVE and dynamically rendered from Program Intelligence through the accepted Hub UI; all 35 real Programs `STP-000001…STP-000035` are approved with stable final routes in `public_noindex`; fixtures `STP-000036/37` remain blocked. Full 35-route QA passed. The mobile Program information-order hotfix is runtime accepted. Homepage Intelligence v0.3.3 is live on the plugin-owned baseline and its dynamic Journey Evidence module sourced from Post #6216 is publicly live and owner accepted. The first-party Özbekistan / İmam Buhârî article is published and passed H1/canonical/indexability/meta/OG/JSON-LD QA; its final light-luxury CSS needs at most one final visual confirmation, not a redesign. Program detail indexation, Program sitemap inclusion and bulk Search Console/IndexNow submission remain OFF. The new article Search Console Live Test has passed; first finish the remaining sanity checks for `/umre-1/` and one Program noindex route. Then capture fresh Program Publishing evidence, verify registry/fixtures/locks and sitemap exclusion, continue Hotel→Program derived public links, define archive URL policy, and only afterward present an explicit staged Program indexation decision. Do not reopen accepted P7A, Private Renderer, Hub desktop UI, Hub mobile IA, Homepage Journey Evidence or earlier technical SEO gates without regression evidence.

---

## 0.12 CURRENT CHECKPOINT SUMMARY

```text
DATE                              2026-09-10
P6 TECHNICAL SEO                  CLOSED / PRESERVE
P7A / ST-TDE                      CLOSED / RUNTIME ACCEPTED
PROGRAM INTELLIGENCE              v0.3.2 ACCEPTED
REAL PROGRAMS                     35 APPROVED
PROGRAM FINAL ROUTES              LIVE / PUBLIC_NOINDEX
PROGRAM ROUTE QA                  35/35 PASS
FIXTURES 36/37                    BLOCKED / PRIVATE
/umre-1/                          LIVE DYNAMIC HUB / ACCEPTED
HUB UI                            v0.2.8 FROZEN
MOBILE PROGRAM IA                 ACCEPTED
PROGRAM PUBLISHING INTEGRATION    v0.4.7 CURRENT OBSERVED
HOTEL INTELLIGENCE                v0.9.11 CURRENT OBSERVED
HOMEPAGE INTELLIGENCE             v0.3.3 LIVE
HOMEPAGE PUBLIC MASTER            ON
JOURNEY EVIDENCE                  LIVE / ACCEPTED
TRUST ARTICLE #6216               LIVE / SEO QA PASS / GSC LIVE TEST PASS
TRUST ARTICLE PREMIUM UI          FINAL CANDIDATE / VISUAL CONFIRMATION PENDING
PROGRAM INDEXATION                OFF
PROGRAM SITEMAP                   OFF
BULK GSC / INDEXNOW               OFF
NEXT ACTION                       GSC SANITY → EVIDENCE → HOTEL↔PROGRAM → ARCHIVE → STAGED INDEXATION DECISION
```

# END OF 2026-09-10 AUTHORITATIVE SUPERSESSION

---

# PREVIOUS AUTHORITATIVE MASTER PLAN PRESERVED BELOW

# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-08 — UMRAH HUB LIVE / 35 PROGRAMS MIGRATED / MOBILE IA HOTFIX RUNTIME ACCEPTED

# 0. AUTHORITATIVE SUPERSESSION — READ THIS FIRST

This checkpoint is the authoritative continuation point for the Server Turizm SEO / Program Intelligence project.

It supersedes earlier continuation instructions wherever they conflict, especially older instructions that said:

- `/umre-1/` must remain untouched;
- only one Program final route may be public/noindex;
- mass Program route exposure is prohibited.

Those restrictions applied to the earlier canary phase and have now been deliberately superseded by the successfully validated **controlled NOINDEX migration** recorded below.

Historical architectural baselines remain valid unless explicitly superseded here:

- `P7A / ST-TDE v0.3 = FULLY CLOSED / RUNTIME ACCEPTED`
- `Server Turizm Program Intelligence v0.3.2 — Frontend Isolation + Temporal Semantics = accepted canonical Program layer`
- `Server Turizm Private Renderer Pilot v0.1.0 = RUNTIME ACCEPTED`
- `Server Turizm Public Renderer v0.2.4 visual/detail baseline = accepted`
- `Server Turizm Program Publishing Integration v0.3.2 — Mobile IA Hotfix = current publishing/integration runtime`
- `Server Turizm Umrah Hub Card UI v0.2.8 = desktop visual/UI FREEZE baseline`
- `Mobile Information Architecture Hotfix v0.3.2 = RUNTIME ACCEPTED / P0 CLOSED`

Current continuation target:

> **Do not redesign the accepted Umrah Hub UI. Preserve the live `/umre-1/` cutover and the accepted v0.3.2 mobile information flow. Next perform the post-cutover SEO/evidence gate, then continue Hotel→Program public relations, archive workflow, and only after those pass make an explicit staged indexation/sitemap decision for Program detail URLs.**

---

# 0.1 TONIGHT'S FINAL STATE — LIVE

## `/umre-1/` cutover

`/umre-1/` has now been updated and the new Program Intelligence-driven Hub is LIVE.

The old manual Program-card implementation in WPBakery RAW3 has been retired from the live page and replaced by:

```text
[stppi_umre_programs]
```

The page structure intentionally remains:

```text
RAW1                         KEEP
RAW2                         KEEP
[stppi_umre_programs]        LIVE REPLACEMENT FOR OLD PROGRAM RAW3
SEO RAW #1                   KEEP
SEO RAW #2                   KEEP
SEO RAW #3                   KEEP
Page Custom CSS              KEEP / DO NOT CLEAN YET
Footer                       KEEP
```

The owner visually checked the live page from top to bottom after Update and confirmed that all migrated Program cards appeared.

**Result: `/umre-1/` Program Hub cutover = LIVE / OWNER ACCEPTED ✅**

Do not manually add new Program cards to RAW HTML from this point forward.

---

# 0.2 CANONICAL OPERATING FLOW — NOW ACTIVE

The intended daily Program workflow is now:

```text
Google Sheet / ST-TDE JSON
        ↓
Program Intelligence Candidate Import
        ↓
Validation / Verification / Hotel Gates
        ↓
Review / Approve
        ↓
Stable STP identity
        ↓
Final Route Registry
        ↓
/umre-programlari/<stable-slug>/
        ↓
/umre-1/ dynamic Hub
        ↓
Hotel → Program derived relation
        ↓
Completed / Cancelled
        ↓
Archive Candidate
        ↓
Human Archive Approval
```

Canonical ownership remains:

- Program business facts → Program Intelligence
- Hotel facts / media / stable Hotel IDs → Hotel Intelligence
- Program→Hotel relation → Program `stays[].hotel_id`
- Hotel→Program relation → derived dynamically; never maintain a mirrored Hotel-side Program list
- Publishing routes / public modes → Program Publishing Integration registry
- `/umre-1/` cards → dynamic shortcode output, not duplicated business data

---

# 0.3 REAL PROGRAM MIGRATION — COMPLETE

Protected real Program identities:

```text
STP-000001 … STP-000035
```

All 35 real eligible Umrah Program records were migrated through the controlled publishing workflow.

Observed final lifecycle/publishing state at the EOD gate:

```text
Editorial: approved
Schedule: scheduled
Temporal: upcoming
Final Route: public_noindex
```

The migration used:

`Server Turizm Program Publishing Integration v0.3.1 — Full Eligible Migration`

Install package used in this session:

`server-turizm-program-publishing-integration-v0.3.1-full-eligible-migration.zip`

Known SHA-256 from the build handoff:

`b2c9cb9060718609cb407567889f81a0dd8fa3407ca62ca9d1022991f25ea1b1`

The full-eligible migration replaced the earlier Wave Limit 8 process after Wave 1 proved the batch workflow end to end.

Current installed publishing runtime after the mobile P0 fix:

`Server Turizm Program Publishing Integration v0.3.2 — Mobile IA Hotfix`

Package:

`server-turizm-program-publishing-integration-v0.3.2-mobile-ia-hotfix.zip`

Accepted build SHA-256:

`357a905df6da3a0c955569b55558aa8844971f3e9377226155cf2b2c44c94447`

The v0.3.2 patch is a presentation-order hotfix only; it does not change canonical Program facts, Hotel facts, Stable IDs, final routes, publishing registry semantics, NOINDEX locks, sitemap locks, or indexation policy.

## Protected fixtures remain excluded

These are NOT sales Programs and must remain protected:

```text
STP-000036 — lifecycle fixture
STP-000037 — renderer fixture
```

Rules remain non-negotiable:

- do not approve them for sales;
- do not expose them through `/umre-1/`;
- do not index them;
- do not treat them as normal archive candidates;
- do not delete them merely to simplify inventory.

**Result: 35 real Programs migrated / fixtures 36–37 blocked ✅**

---

# 0.4 FINAL PROGRAM ROUTE QA — COMPLETE UNDER NOINDEX

All 35 real Program detail URLs are currently publicly reachable for users but intentionally **NOINDEX**.

Representative namespace:

```text
/umre-programlari/luks-umre-programi-219/
/umre-programlari/ekonomik-umre-programi-220/
/umre-programlari/misir-baglantili-umre-programi-kahire3/
...
```

A full runtime QA sweep was run against the Final Routes.

For the 35 real routes, the observed gate was:

```text
HTTP status        200
H1 count           1
robots             contains noindex
canonical          exact final URL
JSON-LD count      >= 1
images             > 0
```

All 35 real routes passed.

The QA discovery script found 36 route-like strings because the admin UI contains the documentation placeholder:

```text
/umre-programlari/slug/
```

That placeholder correctly returned `404` and is **not a Program route**.

Therefore:

```text
35 REAL PROGRAM ROUTES = PASS ✅
/umre-programlari/slug/ = DOCUMENTATION PLACEHOLDER / IGNORE ✅
```

Do not create a real route for `/umre-programlari/slug/`.

---

# 0.5 VISUAL / MODEL SPOT CHECKS — ACCEPTED

The following representative Program Detail pages were visually checked after the multi-Program rollout:

## Program 219
Standard Medine → Mekke model.

Observed:

- dates correct;
- two Hotel stays correct;
- Hotel Intelligence imagery correct;
- prices correct;
- page layout correct.

**PASS ✅**

## Kahire3
Critical complex-model regression:

```text
Kahire → Mekke → Medine
```

Observed:

- multi-stage journey rendered correctly;
- Cairo/Makkah/Madinah Hotels resolved correctly;
- three Hotel cards rendered;
- pricing rendered;
- imagery rendered.

**PASS ✅**

This proves the Renderer is not hard-coded only for a simple Medine→Mekke model.

## Program 225
Later luxury Program sample.

Observed:

- dates correct;
- Hotel relations correct;
- images correct;
- prices correct.

**PASS ✅**

---

# 0.6 UMRAH HUB CARD UI — FROZEN + MOBILE IA P0 CLOSED

Accepted desktop/UI baseline:

`Server Turizm Umrah Hub Card UI v0.2.8 — FINAL DESKTOP/UI FREEZE`

Accepted mobile information-flow patch:

`Server Turizm Program Publishing Integration v0.3.2 — Mobile IA Hotfix — RUNTIME ACCEPTED`

A serious mobile information-architecture defect was found after the live Hub cutover. The old mobile CSS reordered the three desktop regions so the pricing/CTA column could appear before the journey and Hotel information, which could confuse users about what they were booking.

The accepted v0.3.2 hotfix restores the semantic mobile sequence without duplicating Program data and without JavaScript DOM shuffling:

```text
PROGRAM identity / title / duration / countdown
        ↓
HERO IMAGE
        ↓
GİDİŞ / ARA GEÇİŞ / ... / DÖNÜŞ
        ↓
Hotel stays in canonical journey order
        ↓
Hotel trust / airline logos
        ↓
2 / 3 / 4 person prices
        ↓
ÇOCUK ÜCRETLERİ
        ↓
ÖNEMLİ NOTLAR
        ↓
TUR DETAYLARI
        ↓
REZERVASYON YAP
```

Implementation principle:

- keep the original semantic DOM order `image → center → right`;
- remove/override the mobile CSS order rule that promoted the right pricing column ahead of journey/Hotels;
- no duplicated mobile markup;
- no JS DOM reordering;
- desktop rendering remains unchanged;
- multi-destination Programs such as Kahire3 continue to render Hotel stays from canonical Program `stays[]` order rather than a hard-coded Medine/Mekke assumption.

The owner checked the corrected phone output and explicitly confirmed it.

**Result: Mobile IA P0 = FIXED / OWNER ACCEPTED / RUNTIME ACCEPTED ✅**

Do not spend more project time redesigning it unless:

1. a real functional defect or regression is found; or
2. the owner explicitly reopens the design scope.

Accepted features include:

- desktop layout;
- mobile layout with accepted semantic information order;
- legacy-fidelity three-column visual structure;
- large vertical hero;
- Program title / number / duration;
- departure countdown;
- Gidiş / Ara Geçiş / Dönüş;
- Hijri presentation;
- Hotel names;
- Hotel stars;
- stay dates;
- nights;
- Hotel page links;
- KONUM;
- GALERİ;
- up to 10 Hotel thumbnails;
- in-page Hotel Gallery viewer;
- gallery previous/next;
- keyboard/Escape support;
- mobile gallery interaction;
- 2/3/4 person pricing;
- selected-room behavior;
- `KİŞİ BAŞI`;
- child pricing accordion;
- important-notes accordion;
- trust logos;
- `TUR DETAYLARI`;
- WhatsApp `REZERVASYON YAP`;
- Hotel-name hover polish;
- responsive behavior.

The frozen card must continue to consume Program Intelligence + Hotel Intelligence data; do not reintroduce hard-coded per-Program cards.

---

## 0.6.1 MOBILE HOTFIX RELEASE ACCEPTANCE

Release status:

```text
v0.3.2 plugin install/update        PASS ✅
Desktop visual baseline             PRESERVED ✅
Mobile semantic order               PASS ✅
Hotel stay order                    CANONICAL / DYNAMIC ✅
Kahire3 multi-destination model     PRESERVED ✅
Program/Hotel canonical data        UNCHANGED ✅
Final routes / public_noindex       UNCHANGED ✅
Indexation / sitemap gates          UNCHANGED / OFF ✅
Owner phone-output verification     ACCEPTED ✅
```

This P0 is closed. Resume SEO/integration work; do not keep iterating on mobile UI unless a concrete regression appears.

---

# 0.7 SEO STATE TONIGHT — SAFE TO STOP

There is **no urgent SEO reason to roll back tonight** based on the completed runtime state.

Why:

- `/umre-1/` remains the existing public production Umrah hub;
- its SEO content blocks were preserved during the Program-card replacement;
- the detail URLs are public but explicitly `noindex`;
- canonical exists on the Final Program routes;
- JSON-LD exists on the Final Program routes;
- H1 count was validated on the Program detail routes;
- the 35 real Program routes returned HTTP 200;
- fixtures were excluded;
- sitemap/indexation unlock has NOT been intentionally approved;
- Search Console / IndexNow mass submission has NOT been authorized.

Important distinction:

> **Public/noindex is not the same as indexed.**

The 35 Program detail pages may be crawled because they are publicly linked, but they must remain excluded from search indexing while `noindex` is active.

Do NOT block these noindex URLs with `robots.txt`, because crawlers need to be able to fetch the page to see `noindex`.

## Tonight's SEO lock

Until tomorrow's explicit release gate:

```text
Program detail indexation     OFF
Program sitemap inclusion     OFF
Search Console submission     OFF
IndexNow Program submission   OFF
Hotel public Program links    OFF
```

Do not change those tonight.

---

# 0.8 POST-CUTOVER SEO GATE — FIRST TASK TOMORROW

Tomorrow begin with a short evidence/SEO verification before expanding anything else.

## Gate A — `/umre-1/` production sanity

Verify the live page:

```text
https://www.serverturizm.com.tr/umre-1/
```

Check:

1. HTTP 200
2. exactly one intended page H1
3. canonical points to the intended `/umre-1/` URL
4. robots state is appropriate for the public Hub
5. no whole-page horizontal overflow
6. all dynamic Program cards render
7. no fixture 36/37 appears
8. no duplicate Program card appears
9. SEO RAW blocks remain present
10. Footer/Header remain intact
11. old manual RAW3 Program cards are no longer rendered in addition to the shortcode
12. links from Hub cards resolve to the correct Program Final Routes

## Gate B — sitemap isolation

Recheck WordPress sitemaps.

Required state before index unlock:

- `/umre-1/` may remain in its existing Page sitemap;
- `/umre-programlari/.../` detail URLs must NOT enter the sitemap yet;
- fixture routes must never enter sitemap;
- placeholder `/umre-programlari/slug/` must not enter sitemap.

## Gate C — fresh protected evidence

Download a new:

`Read-only evidence`

after the v0.3.1 full migration, live Hub cutover, and accepted v0.3.2 mobile IA hotfix.

Verify at minimum:

- protected count remains expected;
- no duplicate STP IDs;
- fixtures 36/37 unchanged/protected;
- Program post identities remain stable;
- publishing registry contains the intended 35 routes;
- routes are `public_noindex`;
- no unexpected index unlock;
- no unexpected sitemap unlock;
- Hotel source remains derived and not duplicated.

This fresh EOD/post-cutover evidence becomes the new release evidence baseline.

---

# 0.9 HOTEL → PROGRAM PUBLIC RELATION — NEXT IMPLEMENTATION BRANCH

The reverse relation architecture has already passed Admin Preview:

```text
Hotel → Program relation
source of truth = Program.stays[].hotel_id
```

Example proven relation:

```text
MIAS HOTEL
    ← Program 222

Al-Ghufran Safwah Hotel Makkah
    ← Program 222
```

This relation is derived and does not write a Program list into Hotel records.

## Remaining work

Create the controlled public Hotel-page integration so Hotel detail pages can show current eligible Umrah Programs that use that Hotel.

Guardrails:

- derive from Program Intelligence every time;
- only show Programs eligible for current public use;
- never copy Program facts into Hotel data;
- do not enable Hotel public Program links until runtime preview passes;
- archived/completed Programs must not remain in an upcoming-program section;
- links must target stable Final Program routes.

This is the first major branch after tomorrow's post-cutover SEO/evidence check.

---

# 0.10 ARCHIVE / EXPIRY CONTRACT — ACCEPTED DESIGN, LIVE WORKFLOW STILL PENDING

Archive Policy Preview passed.

Accepted lifecycle principle:

```text
upcoming
    ↓
in_progress
    ↓
completed / cancelled
    ↓
Archive Candidate
    ↓
human approval
    ↓
archived
```

Non-negotiable:

- no automatic deletion;
- missing from Sheet does not equal deletion;
- missing from Sheet does not equal archive;
- Stable STP ID survives archive/restore;
- Hotel relation is derived from historical Program facts;
- completed/archived Programs must disappear from the current `/umre-1/` upcoming Hub;
- completed/archived Programs must disappear from Hotel current/upcoming Program lists.

## Critical SEO decision still required before indexed detail URLs

We must define the indexed Program URL policy after expiry.

Candidates to decide later:

- preserve indexed expired URL with archived/historical content;
- redirect only when there is a genuinely equivalent successor;
- do not blanket redirect unrelated expired Programs;
- do not delete indexed URLs blindly.

Do not finalize archive URL behavior after indexation without this policy.

---

# 0.11 INDEXATION / SITEMAP — EXPLICIT OWNER GATE STILL PENDING

The project has proven that the 35 Program routes can safely operate as `public_noindex`.

That does **not** automatically authorize search indexation.

Before index unlock:

1. post-cutover `/umre-1/` QA must pass;
2. fresh protected evidence must pass;
3. sitemap exclusion must be reconfirmed;
4. Hotel public relation branch should be understood/controlled;
5. archive URL policy should be explicit enough not to create future SEO churn;
6. owner must make an explicit indexation decision.

Recommended first indexation strategy:

Do **not** remove `noindex` from all 35 detail URLs at once.

Use a staged representative canary set first, for example:

- one standard Program;
- one complex Cairo-connected Program;
- one luxury Program.

Suggested already-tested representatives:

```text
219
Kahire3
225
```

Then verify:

- Google discovery/indexation behavior;
- canonical selection;
- schema processing;
- crawl behavior;
- no accidental duplicate/canonical conflict;
- no unexpected sitemap contamination;
- performance / Core Web Vitals on real crawls.

Only after observation should wider Program indexation be considered.

---

# 0.12 `/umre-1/` CONTENT / SEO CONTRACT

The new dynamic Hub replaces only the manually maintained sales-card layer.

Preserve the existing supporting SEO content of `/umre-1/`, including its current explanatory / comparison sections unless a dedicated content audit intentionally revises them.

Do not remove these merely because the Program cards are now dynamic.

The Hub page remains the main public Umrah listing page.

Program detail pages are complementary landing pages, not a reason to cannibalize the Hub.

Future keyword architecture should separate intent, for example:

```text
/umre-1/                         broad current Umrah programs / prices / selection
/umre-programlari/<program>/    exact dated Program intent
Hotel pages                      Hotel-specific intent
Evergreen Umrah guides           informational intent
```

Avoid mass duplicate titles/descriptions across Program detail pages when indexation is later enabled.

---

# 0.13 PROGRAM FOUNDATION — LEGACY STATUS

`Program Foundation v0.2.0` remains a legacy technical layer.

Current canonical operations are handled by Program Intelligence + Publishing Integration.

Do not delete/deactivate Program Foundation yet.

Tomorrow/later perform a retirement dependency audit before removing it:

- Data Bridge dependencies
- Relations dependencies
- shortcodes/hooks
- old admin references
- rollback dependencies

Only retire after proving no active production dependency remains.

---

# 0.14 ROLLBACK CONTRACT

Fast rollback remains mandatory.

## Hub rollback

If the dynamic Hub creates a production defect:

1. close Hub bridge in Publishing Integration;
2. restore the backed-up old RAW3 only if needed;
3. do not alter Program/Hotel canonical data;
4. purge LiteSpeed/CDN cache if required;
5. recheck `/umre-1/`.

## Final Program route rollback

Use the Publishing Integration master/final access control to fail closed.

Do not delete Program records to hide a route.

## Indexation rollback

If an index canary later causes a problem:

- restore `noindex`;
- remove only the affected sitemap inclusion;
- do not destroy the stable route identity;
- investigate canonical/schema/content cause before changing Program facts.

---

# 0.15 PROGRESS — EOD ESTIMATE

These are planning estimates, not automated telemetry.

## A. P7A / ST-TDE core lifecycle

`100% COMPLETE ✅`

## B. Private Renderer foundation

`100% COMPLETE ✅`

## C. Program Detail Renderer / NOINDEX production capability

`100% COMPLETE FOR CURRENT NOINDEX SCOPE ✅`

Remaining SEO release work is indexation governance, not Renderer design.

## D. Umrah Program Publishing Integration / Hub migration

`~97% COMPLETE`

Completed:

- final namespace;
- stable publishing registry;
- Program 222 canary;
- public/noindex gate;
- canonical + JSON-LD;
- final-route runtime QA;
- UI freeze;
- Hub shortcode;
- 35 eligible Program migration;
- full route QA;
- live `/umre-1/` cutover;
- protected fixture exclusion;
- mobile information-architecture P0 hotfix;
- corrected mobile output owner acceptance.

Remaining:

- fresh post-cutover evidence;
- live `/umre-1/` SEO sanity evidence;
- Hotel→Program public links;
- live archive workflow;
- explicit staged indexation/sitemap gate.

## E. Umrah Program Intelligence branch

`~97% COMPLETE`

Main remaining work is operational finishing / archive / SEO release governance rather than core data modeling.

## F. Full Server Turizm SEO & Performance Master Plan

`~72% COMPLETE`
`~28% REMAINING`

Large remaining branches include:

- Hotel→Program public integration;
- archive/indexed-URL lifecycle;
- staged Program indexation and monitoring;
- Tour Intelligence parity;
- Finder / Compare / Timeline experiences;
- Trust / Evidence / Reputation scaling;
- AI Search / citation hardening;
- WhatsApp attribution / Growth Dashboard;
- Passenger Center;
- later partner / retention / omnichannel layers;
- ongoing content, authority and technical SEO work needed to compete for major Umrah queries.

---

# 0.16 DO NOT DO TOMORROW BEFORE THE SEO GATE

Do not:

- redesign the frozen Hub cards or alter the accepted v0.3.2 mobile information order without a real regression;
- recreate Program facts inside `/umre-1/`;
- manually create 35 WordPress sales cards;
- approve/publish fixtures 36/37;
- remove `noindex` from all Program routes;
- bulk-add all Program routes to sitemap;
- submit all Program routes to Search Console;
- submit all Program routes to IndexNow;
- delete completed Programs;
- edit Hotel records just to change Program relations;
- clean old Page Custom CSS before dependency verification;
- delete Program Foundation before dependency audit.

---

# 0.17 TOMORROW — EXACT RESUME ORDER

Resume in this order:

```text
1. /umre-1/ post-cutover technical SEO QA + quick desktop/mobile regression sanity
2. Fresh Read-only Evidence export
3. 37-record / fixture / registry / lock verification
4. Sitemap exclusion recheck
5. Record Hub cutover as runtime accepted
6. Hotel → Program public-link controlled implementation
7. Runtime QA Hotel → Program links
8. Archive workflow / archived URL policy
9. Explicit staged Program indexation decision
10. Only then sitemap/Search Console/IndexNow work if approved
```

If time is limited tomorrow, finish steps 1–5 first and stop at a clean checkpoint.

---

# 0.18 CONTINUATION PROMPT FOR NEXT CHAT

> Continue the Server Turizm SEO / Program Intelligence project from the authoritative **2026-09-08 — Umrah Hub Live / 35 Programs Migrated / Mobile IA Hotfix Runtime Accepted** Master Plan. Do not reopen P7A, ST-TDE, Private Renderer or accepted Hub visual design. Program Publishing Integration **v0.3.2 Mobile IA Hotfix** is the current runtime; v0.2.8 remains the accepted desktop/UI visual baseline. All 35 real Programs `STP-000001…STP-000035` are approved and have stable `/umre-programlari/.../` Final Routes in `public_noindex`; fixtures `STP-000036/37` remain BLOCKED. Full runtime QA passed for all 35 real routes: HTTP 200, one H1, noindex robots, exact canonical, JSON-LD and images. The apparent 36th failed route was only the admin placeholder `/umre-programlari/slug/`. Visual spot checks passed for 219, Kahire3 and 225. The `/umre-1/` WPBakery cutover is LIVE: RAW1/RAW2 and the three SEO RAW blocks were preserved, old manual Program RAW3 was replaced by `[stppi_umre_programs]`, and all Programs appear live. A P0 mobile information-order defect was then fixed in v0.3.2: mobile now follows Program identity → hero → journey → Hotels in canonical `stays[]` order → logos → prices → details/CTA; no DOM duplication or JS reordering, desktop unchanged. The owner confirmed the corrected phone output. Keep Program detail indexation, Program sitemap inclusion, Search Console/IndexNow submission and Hotel public Program links OFF. First run the post-cutover `/umre-1/` SEO sanity gate and capture fresh Read-only Evidence. Then continue Hotel→Program public relations, archive lifecycle/URL policy, and only afterward present an explicit staged indexation decision.

---

# END — 2026-09-08 MOBILE IA HOTFIX ACCEPTED AUTHORITATIVE CHECKPOINT

# Current Runtime Inventory

**Inventory date:** 2026-09-16

**Repository authority:** `yahyaelahimiavagh-arch/server-turizm-platform`

**Merged main checkpoint:** `09f026f621a0057b52333699331f7aeb8ee56947`

**Evidence basis:** Production Google Sheets selected-row CREATE/UPDATE observations, Production STTI v1.1.2–v1.1.6 operator QA, guarded editorial approval on `STT-000002`, Tour Hub eligibility verification with Hub Master OFF, merged repository/runtime evidence through PR #22, and previously accepted Server Turizm baselines.

## Classification rules

- `LIVE AUTHORITATIVE`: confirmed active/aligned with accepted Production evidence.
- `PRODUCTION ACCEPTED`: directly observed and accepted for the stated Production scope.
- `ACCEPTED INTEGRATION`: accepted operator integration, not necessarily a standalone WordPress plugin.
- `MERGED / REPO+RUNTIME ACCEPTED`: merged code with accepted repository/disposable runtime evidence.
- `LAST KNOWN LIVE`: last documented live version; reverify when exact version matters.
- `LEGACY / NON-ACTIVE SOURCE`: retained only for audit/rollback.

## WordPress plugins

| Repository path | Known version/status | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LAST KNOWN LIVE / PRESERVE | Immutable `STP-*`, lifecycle/archive snapshots and identity runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LAST KNOWN LIVE / PRESERVE | Program publication/publishing-registry boundary. |
| `wordpress/plugins/tour-intelligence` | `1.1.6` | LIVE AUTHORITATIVE / PRODUCTION ACCEPTED | Operator editor, Review Queue and guarded editorial approval accepted; Hub installed, Master OFF. |
| `wordpress/plugins/direct-sync-foundation` | repo `0.1.4`; Production contract proven | ACCEPTED INTEGRATION | Shared REST gateway accepted by successful selected-row Tour CREATE/UPDATE tests. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` last known live | LAST KNOWN LIVE / PRESERVE | Canonical `STH-*` ownership retained. |

## Repository progression now merged

```text
STTI v0.6.5  AI Completion Review Contract        MERGED / ACCEPTED
STTI v0.7.0  Review Relations                     MERGED / ACCEPTED
STTI v0.7.1  Canonical Geo Resolver               MERGED / ACCEPTED
STTI v0.8.0  Complete Customer Renderer           MERGED / ACCEPTED
STTI v0.9.0  First Real Full Tour                 MERGED / ACCEPTED
STTI v1.0.0  Controlled Public Tour Pilot         MERGED / CONTROLLED
PR #17       Separated Sheet stacks               MERGED
PR #18       Dynamic Culture Tours Hub v1.1       MERGED
PR #19       Tour Direct Sync Production closeout MERGED
PR #20       Operator-first Tour editor           MERGED
PR #22       Review Queue + guarded approval      MERGED
```

## Active Google Sheets integrations

### Umrah — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/umrah/ST-Umrah-Config.gs` | clean stack config | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs` | canonical-hash-compatible exporter | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs` | independent Umrah Direct Sync | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Menu.gs` | independent Umrah menu | LIVE AUTHORITATIVE |

Hidden identity sheet:

`ST Umrah Sync State`

Accepted migration:

```text
Migrated state: 36
Invalid/skipped: 0
WordPress writes: 0
```

Accepted fixture:

`STP-000005 / Program No 223`

Observed:

```text
Precheck → STP-000005 — UNCHANGED
Apply    → STP-000005 — UNCHANGED
```

### Tour — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/tours/ST-Tour-Generator.gs` | implementation `1.0.2`; producer `0.6.2.1` | LIVE AUTHORITATIVE SOURCE |
| `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs` | independent Tour Direct Sync `1.0.1` | PRODUCTION ACCEPTED |
| `integrations/google-sheets/tours/ST-Tour-Menu.gs` | independent Tour menu | PRODUCTION ACCEPTED |

Technical identity fields:

- `STTI Stable ID`
- `Expected Checksum`

They are header-driven, appended after business columns when absent, and hidden from normal operators. Fixed Z/AA targeting is retired.

## Tour Direct Sync Production acceptance

Controlled fixture:

`Iran Test Turu / ID-642D23A3`

Canonical identity:

`STT-000002`

CREATE sequence:

```text
Local validation  → Target NEW
Precheck          → STT-000002 — CREATE
Apply             → STT-000002 — CREATE
Second precheck   → STT-000002 — UNCHANGED
```

UPDATE sequence:

```text
Tur Adı           → Iran Test Turu Update Test
Local validation  → Target STT-000002
Precheck          → STT-000002 — UPDATE
Apply             → STT-000002 — UPDATE
Second precheck   → STT-000002 — UNCHANGED
```

Result:

```text
Selected-row CREATE              PRODUCTION ACCEPTED
Selected-row UPDATE              PRODUCTION ACCEPTED
Stable ID continuity             PASS
Checksum continuity              PASS
Duplicate loop                   NOT OBSERVED
Bulk Tour sync                   NOT AUTHORIZED / NOT TESTED
```

Transport note:

- intermittent DNS/latency observed;
- bounded retries recovered successful calls;
- one validate attempt exceeded Apps Script maximum execution time;
- canonical idempotency remained correct.

Do not repeat Apply merely because a response is delayed; validate/status first.

## Direct Sync contract

Contract: `ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Server/client secret configuration remains external to Git and Sheet cells.

Preserved retry invariants:

- same request ID/body for one business request;
- fresh timestamp/nonce/HMAC per transport attempt;
- safe `stds_processing` polling;
- no business-validation failure retried into an unintended write.

## Tour Intelligence Production acceptance

Current accepted Production release:

`1.1.6`

### v1.1.2 operator editor

Accepted on `STT-000002`:

- simple daily operator view;
- advanced canonical fields preserved behind `Gelişmiş Alanlar`;
- Stable ID read-only;
- Canonical Geo hidden in simple mode;
- no-change save returned `Private candidate unchanged. Public output remains OFF.`

### v1.1.3 / v1.1.4 Review Queue

Accepted behavior:

- source/route/Geo/Hotels/transport/dates/price/itinerary summarized in one operator page;
- blocker list visible;
- no automatic approval;
- editor-link routing fixed in v1.1.4;
- unsupported placeholder facts were reviewed without inventing canonical Hotel/transport claims.

### v1.1.5 / v1.1.6 guarded approval

v1.1.5 introduced the guarded approval screen. Production exposed an inaccessible wp-admin route because the registered submenu page was immediately removed.

v1.1.6 fixed the route by preserving WordPress page registration and hiding the visual menu entry only.

Accepted approval requirements:

- capability guard;
- nonce;
- explicit checkbox;
- server-side readiness recheck;
- only `needs_review → approved`;
- idempotent repeat behavior;
- `candidate_approved` audit evidence;
- publication state preserved.

Production approval result:

```text
Stable ID        STT-000002
Editorial        APPROVED
Schedule         SCHEDULED
Availability     OPEN
Hard blockers    0
Public route     OFF
Hub visibility   OFF
Indexation       OFF
Sitemap          OFF
Homepage         OFF
Hub Master       OFF
```

## Tour Hub v1.1 — Production installed / Master OFF

Contract:

`STTI-TOUR-HUB-1.1.0`

Target page:

`/kultur-turlari/`

Hub option:

`stti_v110_hub_master`

Current state:

`OFF`

Architectural boundary:

- no new rewrite route;
- existing WordPress page owns URL, theme shell and page SEO;
- Hub replaces only page content when explicitly enabled;
- OFF preserves/restores existing content;
- no duplicate Hub Tour database;
- individual Tour public/indexation/schema/sitemap gates remain separate.

Production eligibility evidence:

```text
Before STT-000002 approval   eligible = 0
After STT-000002 approval    eligible = 1
Hub Master                   OFF
/kultur-turlari/ while OFF   existing page unchanged
```

This proves canonical editorial approval feeds Hub eligibility without activating the Hub itself.

## Retired/non-active Apps Script paths

Do not copy these into active Production Sheet projects:

- `integrations/google-sheets/shared/ST-Direct-Sync.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Archive.gs`
- `integrations/google-sheets/umrah/ST_TDE_Exporter.gs`
- `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

## SEO/publication locks

Preserve:

- `/umre-1/` accepted Hub baseline;
- Program detail indexation OFF;
- Program sitemap OFF;
- protected fixtures `STP-000036` / `STP-000037`;
- Tour Hub Master OFF until explicit activation approval;
- Tour public/indexation/sitemap/schema/canonical/homepage gates independently controlled;
- editorial approval does not imply public/indexable;
- no mass Search Console submission because Direct Sync or Approval succeeded.

## Current outcomes

| Gate | Result |
| --- | --- |
| Umrah separated selected-row no-change | PRODUCTION ACCEPTED |
| Tour selected-row CREATE | PRODUCTION ACCEPTED |
| Tour selected-row UPDATE | PRODUCTION ACCEPTED |
| Tour post-write idempotency | PRODUCTION ACCEPTED / `UNCHANGED` |
| Tour Intelligence `1.1.6` | PRODUCTION ACTIVE / ACCEPTED SCOPE |
| Operator-first editor | PRODUCTION ACCEPTED |
| Review Queue | PRODUCTION ACCEPTED |
| Guarded approval route | PRODUCTION ACCEPTED |
| `STT-000002` editorial approval | PRODUCTION ACCEPTED / `APPROVED` |
| `STT-000002` hard blockers | `0` |
| Tour Hub v1.1 installed | PRODUCTION VERIFIED |
| Tour Hub eligible records | `1` |
| Tour Hub Master | `OFF` |
| `/kultur-turlari/` while Hub OFF | EXISTING PAGE PRESERVED |
| Program mass indexation/sitemap | OFF |
| Tour detail public/SEO mass gates | OFF / SEPARATE |

## Next Production checkpoint

Keep Hub Master OFF while reviewing the first eligible Hub-card output and rollback baseline.

Only after explicit owner approval may the next controlled test enable Hub Master. If enabled, immediately verify:

- `/kultur-turlari/` content replacement only;
- theme/page shell preserved;
- no unintended canonical/indexation/schema/sitemap mutation;
- card data matches canonical facts;
- mobile/desktop behavior;
- contact/detail CTA behavior;
- immediate rollback by turning Hub Master OFF.

Tour-detail public/indexation/schema/sitemap activation remains a separate future gate.
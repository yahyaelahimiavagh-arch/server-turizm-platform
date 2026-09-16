# Current Runtime Inventory

**Inventory date:** 2026-09-16

**Repository authority:** `yahyaelahimiavagh-arch/server-turizm-platform`

**Merged main checkpoint:** `c059e852b3a7ffd246b870a2541a135cfd00a664`

**Evidence basis:** Production Google Sheets selected-row observations, Production STTI v1.1.2 screenshots/no-change save evidence, merged repository/runtime evidence through PR #20, and previously accepted live Server Turizm baselines.

## Classification rules

- `LIVE AUTHORITATIVE`: confirmed active/aligned with accepted Production evidence.
- `PRODUCTION ACCEPTED`: directly observed in Production and accepted for the stated scope.
- `ACCEPTED INTEGRATION`: accepted operator integration, not itself a WordPress plugin.
- `MERGED / REPO+RUNTIME ACCEPTED`: merged code with accepted repository/disposable runtime evidence.
- `LAST KNOWN LIVE`: last documented Production version; reverify before a rollout that depends on exact version.
- `LEGACY / NON-ACTIVE SOURCE`: retained only for audit/rollback; not an active deployment source.

## WordPress plugins

| Repository path | Known version/status | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LAST KNOWN LIVE / PRESERVE | Immutable `STP-*`, lifecycle/archive snapshots and identity runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LAST KNOWN LIVE / PRESERVE | Program publication/publishing-registry boundary. |
| `wordpress/plugins/tour-intelligence` | `1.1.2` | LIVE AUTHORITATIVE / PRODUCTION ACCEPTED | Operator-first Tour editor accepted on `STT-000002`; Tour Hub remains Master OFF. |
| `wordpress/plugins/direct-sync-foundation` | repo `0.1.4`; live contract proven | ACCEPTED INTEGRATION | Shared REST gateway/contract accepted by successful Tour CREATE/UPDATE selected-row tests. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` last known live | LAST KNOWN LIVE / PRESERVE | Canonical `STH-*` Hotel ownership retained. |

## Repository progression now merged

```text
STTI v0.6.5  AI Completion Review Contract       MERGED / ACCEPTED
STTI v0.7.0  WordPress Review Relations          MERGED / ACCEPTED
STTI v0.7.1  Canonical Geo Resolver              MERGED / ACCEPTED
STTI v0.8.0  Complete Customer Renderer          MERGED / ACCEPTED
STTI v0.9.0  First Real Full Tour                MERGED / ACCEPTED
STTI v1.0.0  Controlled Public Tour Pilot        MERGED / CONTROLLED
PR #17       Separated Sheet stacks              MERGED
PR #18       Dynamic Tour Hub v1.1               MERGED
PR #19       Direct Sync/SEO runtime closeout    MERGED
PR #20       STTI v1.1.2 Operator-first UI       MERGED
```

## Active Google Sheets integrations

### Umrah — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/umrah/ST-Umrah-Config.gs` | clean stack config | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs` | canonical-hash-compatible clean exporter | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs` | independent Umrah Direct Sync | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Menu.gs` | independent Umrah menu | LIVE AUTHORITATIVE |

Hidden identity sheet:

`ST Umrah Sync State`

One-time migration result:

```text
Migrated state: 36
Invalid/skipped: 0
WordPress writes: 0
```

Accepted Production fixture:

`STP-000005 / Program No 223`

Observed sequence:

```text
Precheck → STP-000005 — UNCHANGED
Apply    → STP-000005 — UNCHANGED
```

### Tour — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/tours/ST-Tour-Generator.gs` | implementation `1.0.2`; producer contract `0.6.2.1` | LIVE AUTHORITATIVE SOURCE |
| `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs` | independent Tour Direct Sync `1.0.1` | PRODUCTION ACCEPTED |
| `integrations/google-sheets/tours/ST-Tour-Menu.gs` | independent Tour menu | PRODUCTION ACCEPTED |

Technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

If missing, they are appended after the current business columns and hidden. Fixed Z/AA targeting is retired. Business columns such as `Vize` must never be overwritten.

### Production Tour Direct Sync acceptance — 2026-09-16

Controlled fixture:

`Iran Test Turu / ID-642D23A3`

Canonical Stable ID:

`STT-000002`

Accepted CREATE sequence:

```text
Local validation  → Target NEW
Precheck          → STT-000002 — CREATE
Apply             → STT-000002 — CREATE
Second precheck   → STT-000002 — UNCHANGED
```

Accepted intentional UPDATE sequence:

```text
Tur Adı           → Iran Test Turu Update Test
Local validation  → Target STT-000002
Precheck          → STT-000002 — UPDATE
Apply             → STT-000002 — UPDATE
Second precheck   → STT-000002 — UNCHANGED
```

Result:

```text
Tour selected-row CREATE path      PRODUCTION ACCEPTED
Tour selected-row UPDATE path      PRODUCTION ACCEPTED
Stable ID continuity               PASS
Hidden checksum continuity         PASS
Duplicate CREATE/UPDATE loop       NOT OBSERVED
Bulk Tour sync                     NOT AUTHORIZED / NOT TESTED
```

Transport note:

- intermittent DNS/latency was observed;
- successful calls recovered on bounded retry, including second/third attempts;
- one validate attempt exceeded Apps Script maximum execution time;
- canonical identity/idempotency remained correct after recovery.

Do not repeat Apply merely because a response is delayed; validate/status first.

## Direct Sync contract

Contract:

`ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Server configuration:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Tour Apps Script Properties:

- `ST_DIRECT_SYNC_ENDPOINT`
- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Secrets remain external to Git and Sheet cells.

Both independent clients preserve bounded retry, same request ID/body across transport retry, fresh timestamp/nonce/HMAC each attempt, and safe `stds_processing` replay polling.

## STTI v1.1.2 Operator-first editor — Production acceptance

Production record:

`STT-000002 / Iran Test Turu Update Test`

Release:

`1.1.2`

Purpose:

- simplify daily Tour editing;
- expose only common operator fields by default;
- preserve the canonical legacy editor behind `Gelişmiş Alanlar`;
- preserve the original canonical save handler and immutable Stable ID.

Simple mode includes:

- title;
- destination/country;
- departure/return dates;
- calculated duration;
- primary price/currency;
- reservation status;
- visa status;
- editorial review status;
- route summary and direct buttons to advanced detail areas.

Hidden by default in simple mode:

- Canonical Geo / Route Stop Coordinates;
- release-lock cards;
- duplicate technical header/status controls;
- top release controls;
- legacy 16-tab editor.

All of those remain available through Advanced mode.

### No-change Production save proof

A no-change save on `STT-000002` returned:

`Private candidate unchanged. Public output remains OFF.`

Acceptance:

```text
Stable ID preserved                PASS
Canonical no-change save           PASS / unchanged
Duplicate Tour creation            NONE
Public output                       OFF
Tour Hub Master                     OFF
Indexation                          OFF / separate
Sitemap                             OFF / separate
Schema                              OFF / separate
Operator simple UI                  PRODUCTION ACCEPTED
```

## Tour Hub v1.1 — Production installed / Master OFF

Repository paths:

- `wordpress/plugins/tour-intelligence/includes/tour-hub-v110.php`
- `wordpress/plugins/tour-intelligence/assets/tour-hub-v110.css`

Contract:

`STTI-TOUR-HUB-1.1.0`

Target existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Current Production state:

`OFF`

Observed:

- Tour Intelligence 1.1.x Hub settings page is present;
- Hub Master checkbox is OFF;
- eligible count was `0` before editorial approval;
- `/kultur-turlari/` remained the existing legacy/current WordPress page while OFF.

### Architectural boundary

The Hub replaces only the existing page content when explicitly enabled. It does not create a rewrite rule or take ownership of page-level permalink/SEO/canonical/indexation.

It does not automatically enable:

- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure.

OFF restores/preserves the current page content path immediately.

### Eligibility / future-Tour behavior

Hub reads canonical `STT-*` records directly. There is no duplicate Hub Tour database.

Eligible Tours must satisfy canonical lifecycle/editorial rules. Future dated Tours sort nearest-first. Approved undated Tours may follow as `Tarih yakında`. Sold-out Tours remain explicitly visible.

Future workflow:

```text
Google Sheet
→ Tour Direct Sync
→ STT-* canonical
→ human review / editorial approval
→ automatic Hub eligibility
```

Hub Master remains OFF until a separate explicit owner gate.

## Retired active Apps Script architecture

The following shared/operator paths remain non-active:

- `integrations/google-sheets/shared/ST-Direct-Sync.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Archive.gs`
- `integrations/google-sheets/umrah/ST_TDE_Exporter.gs`
- `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

Do not copy these retired shared/operator runtimes into the active Production Sheet project.

## SEO/publication locks

Preserve:

- `/umre-1/` accepted Hub baseline;
- Program detail indexation OFF;
- Program sitemap OFF;
- protected fixtures `STP-000036` / `STP-000037`;
- Tour Hub Master OFF until explicit approval;
- Tour public/indexation/sitemap/schema/canonical/homepage gates independently controlled and OFF unless explicitly opened;
- editorial `approved` does not itself mean public/indexable;
- no mass Search Console submission merely because Direct Sync succeeds.

## Current outcomes

| Gate | Result |
| --- | --- |
| Umrah separated selected-row no-change | PRODUCTION ACCEPTED |
| Tour selected-row CREATE | PRODUCTION ACCEPTED |
| Tour selected-row UPDATE | PRODUCTION ACCEPTED |
| Tour post-write idempotency | PRODUCTION ACCEPTED / `UNCHANGED` |
| Tour Intelligence 1.1.2 | PRODUCTION ACTIVE / ACCEPTED |
| Operator-first simple editor | PRODUCTION ACCEPTED |
| Operator no-change save | PRODUCTION ACCEPTED / unchanged |
| Canonical Geo hidden in simple mode | PRODUCTION OBSERVED / ACCEPTED |
| Advanced technical editor preserved | ACCEPTED |
| Tour Hub v1.1 installed | PRODUCTION VERIFIED |
| Hub Master | OFF |
| Legacy/current `/kultur-turlari/` while OFF | PRESERVED / ACCEPTED |
| Eligible Hub Tours before editorial approval | 0 |
| Direct Sync v0.1.4 repository | MERGED |
| Program mass indexation/sitemap | OFF |
| Tour mass public/SEO gates | OFF / SEPARATE |

## Next runtime checkpoint

Controlled editorial review of:

`STT-000002 / Iran Test Turu Update Test`

Required behavior:

```text
review canonical facts
→ keep unsupported facts unknown
→ resolve only source-backed blockers
→ editorial approval only when policy permits
→ observe Hub eligibility while Hub Master remains OFF
→ no public/indexation/schema/sitemap side effect
```

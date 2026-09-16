# Current Runtime Inventory

**Inventory date:** 2026-09-16

**Repository authority:** `yahyaelahimiavagh-arch/server-turizm-platform`

**Merged main checkpoint:** `c34ece83a3e05c3e8a8c5edc3c22e06d9eaf65e2`

**Evidence basis:** Production Google Sheets selected-row observations from the separated Umrah/Tour stacks, merged repository/runtime evidence through PR #18, and previously accepted live Server Turizm baselines.

## Classification rules

- `LIVE AUTHORITATIVE`: confirmed active/aligned with accepted Production evidence.
- `ACCEPTED INTEGRATION`: accepted operator integration, not itself a WordPress plugin.
- `MERGED / REPO+RUNTIME ACCEPTED`: merged code with accepted repository/disposable runtime evidence; does not prove Production deployment.
- `REPO MERGED / PRODUCTION VERSION UNVERIFIED`: repository release is merged, but exact live Production version was not re-verified in this checkpoint.
- `LAST KNOWN LIVE`: last documented Production version; reverify before a rollout that depends on exact version.
- `LEGACY / NON-ACTIVE SOURCE`: retained only for audit/rollback; not an active deployment source.

## WordPress plugins

| Repository path | Known version/status | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LAST KNOWN LIVE / PRESERVE | Immutable `STP-*`, lifecycle/archive snapshots and identity runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LAST KNOWN LIVE / PRESERVE | Program publication/publishing-registry boundary. |
| `wordpress/plugins/tour-intelligence` | repo `1.1.0`; last documented live `1.0.0` | REPO MERGED / PRODUCTION VERSION UNVERIFIED | PR #18 merged. v1.1 adds dynamic `/kultur-turlari/` Hub with master default OFF. Do not infer live install/activation. |
| `wordpress/plugins/direct-sync-foundation` | repo `0.1.4`; live exact version not re-verified | REPO MERGED / PRODUCTION VERSION UNVERIFIED | Shared REST gateway/contract is accepted; verify exact Production plugin version before new Tour rollout. |
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
```

The previous inventory label `Tour Hub v1.1 — MERGE PENDING` is obsolete.

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

Observed separated-stack sequence:

```text
Precheck → STP-000005 — UNCHANGED
Apply    → STP-000005 — UNCHANGED
```

### Tour — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/tours/ST-Tour-Generator.gs` | implementation `1.0.2`; producer contract `0.6.2.1` | LIVE AUTHORITATIVE SOURCE |
| `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs` | independent Tour Direct Sync `1.0.1` | ACCEPTED INTEGRATION |
| `integrations/google-sheets/tours/ST-Tour-Menu.gs` | independent Tour menu | ACCEPTED INTEGRATION |

Technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

If missing, they are appended after the current business columns and hidden. Fixed Z/AA targeting is retired. Business columns such as `Vize` must never be overwritten.

### Production runtime acceptance — 2026-09-16

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

Transport health remains a monitoring item; do not repeat Apply merely because a response is delayed.

## Direct Sync contract

Contract:

`ST-DIRECT-SYNC-1.0.0`

WordPress endpoint:

`/wp-json/server-turizm/v1/direct-sync`

Production URL:

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

## Tour Hub v1.1 — merged repository state

Repository paths:

- `wordpress/plugins/tour-intelligence/includes/tour-hub-v110.php`
- `wordpress/plugins/tour-intelligence/assets/tour-hub-v110.css`

Contract:

`STTI-TOUR-HUB-1.1.0`

Target existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Default:

`OFF`

### Architectural boundary

The Hub replaces only the content returned for the existing WordPress page when explicitly enabled. It does not create a rewrite rule or take ownership of page-level permalink/SEO/canonical/indexation.

It does not automatically enable:

- individual Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- homepage exposure.

OFF restores the current/legacy page content path immediately.

### Eligibility / future-Tour behavior

Hub reads canonical `STT-*` records directly. There is no duplicate Hub Tour database.

Eligible Tours must satisfy canonical lifecycle/editorial rules. Future dated Tours sort nearest-first. Approved undated Tours may follow as `Tarih yakında`. Sold-out Tours stay visible with an explicit status.

Future workflow:

```text
Google Sheet
→ Tour Direct Sync
→ STT-* canonical
→ human review / editorial approval
→ automatic Hub eligibility
```

### Repository/runtime status

PR #18 is merged into main and its repository/disposable-runtime acceptance is preserved.

Production installation/activation is still **UNVERIFIED / PENDING separate owner gate**. Do not call the live Hub active until the Production plugin version and Hub Master state are checked.

## Retired active Apps Script architecture

The following shared/operator paths remain non-active after the separated-stack reconciliation:

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
- Tour public/indexation/sitemap/schema/canonical/homepage gates independently controlled and OFF unless explicitly opened for an allowlisted pilot;
- no mass Search Console submission merely because Direct Sync succeeds.

## Current outcomes

| Gate | Result |
| --- | --- |
| Umrah separated selected-row no-change | PRODUCTION ACCEPTED |
| Tour selected-row CREATE | PRODUCTION ACCEPTED |
| Tour selected-row UPDATE | PRODUCTION ACCEPTED |
| Tour post-write idempotency | PRODUCTION ACCEPTED / `UNCHANGED` |
| 36-row Umrah state migration | ACCEPTED / no WordPress write |
| Tour dynamic technical columns | ACCEPTED; fixed Z/AA assumption retired |
| STTI v0.7.0 relations → v1.0 controlled pilot progression | MERGED / ACCEPTED |
| Tour Hub v1.1 static/runtime | MERGED / REPO+RUNTIME ACCEPTED |
| Tour Hub live `/kultur-turlari/` activation | UNVERIFIED / PENDING separate Production owner gate |
| Future Tour Hub eligibility after approval | REPO/RUNTIME ACCEPTED; Production activation pending |
| Direct Sync v0.1.4 repository | MERGED |
| Direct Sync exact Production version | REVERIFY BEFORE HUB ROLLOUT |
| Program mass indexation/sitemap | OFF |
| Tour mass public/SEO gates | OFF / SEPARATE |

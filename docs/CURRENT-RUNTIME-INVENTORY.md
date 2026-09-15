# Current Runtime Inventory

**Inventory date:** 2026-09-15

**Evidence:** Production Google Sheets runtime screenshots from the separated Umrah/Tour stacks, accepted repository CI/runtime evidence, and current Tour Hub v1.1 repository checkpoint.

## Classification rules

- `LIVE AUTHORITATIVE`: confirmed active/aligned with current Production evidence.
- `ACCEPTED INTEGRATION`: accepted operator integration, not itself a WordPress plugin.
- `REPO/RUNTIME ACCEPTED — MERGE PENDING`: accepted candidate with green repository/runtime evidence, not merged/deployed yet.
- `REPO ACCEPTED / PRODUCTION VERSION UNVERIFIED`: repository code accepted, but current live plugin version was not re-verified in this checkpoint.
- `LEGACY / NON-ACTIVE SOURCE`: retained only if needed for audit; not an active deployment source.

## WordPress plugins

| Repository path | Known version/status | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LIVE AUTHORITATIVE | Immutable `STP-*`, lifecycle/archive snapshots and identity runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LIVE AUTHORITATIVE | Program publication/publishing-registry boundary. |
| `wordpress/plugins/tour-intelligence` | live `1.0.0`; candidate `1.1.0` | REPO/RUNTIME ACCEPTED — MERGE PENDING | v1.1 adds dynamic `/kultur-turlari/` Hub with master default OFF; live page remains unchanged until separately deployed/enabled. |
| `wordpress/plugins/direct-sync-foundation` | repo `0.1.4`; live version not re-verified here | REPO ACCEPTED / PRODUCTION VERSION UNVERIFIED | Selected-row Production evidence below is valid; do not infer v0.1.4 live deployment from it. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` last known live | LIVE AUTHORITATIVE | Hotel canonical ownership retained. |

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
| `integrations/google-sheets/tours/ST-Tour-Generator.gs` | implementation `1.0.2`; producer contract version `0.6.2.1` | LIVE AUTHORITATIVE |
| `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs` | independent Tour Direct Sync `1.0.1` | LIVE AUTHORITATIVE |
| `integrations/google-sheets/tours/ST-Tour-Menu.gs` | independent Tour menu | LIVE AUTHORITATIVE |

Technical identity fields are header-driven and dynamically appended after current business columns. They must never overwrite fields such as `Vize`.

Accepted Production fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Observed separated-stack sequence:

```text
Precheck → STT-000001 — UNCHANGED
Apply    → STT-000001 — UNCHANGED
```

## Tour Hub v1.1 candidate

Repository path:

- `wordpress/plugins/tour-intelligence/includes/tour-hub-v110.php`
- `wordpress/plugins/tour-intelligence/assets/tour-hub-v110.css`

Contract:

`STTI-TOUR-HUB-1.1.0`

Target existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Default: `OFF`.

### Architectural boundary

Tour Hub replaces only the content returned for the existing WordPress page when explicitly enabled.

It does not:

- create a rewrite rule;
- own the page permalink;
- change page canonical/indexation/SEO metadata;
- enable any individual Tour public route;
- enable sitemap/schema/canonical gates;
- duplicate Tour canonical records.

OFF restores the existing WPBakery/static page immediately.

### Eligibility / future-Tour behavior

Hub reads directly from canonical `STT-*` rows.

Visible candidates must be:

- editorial `approved`;
- not cancelled/archived;
- not temporally past;
- not ended before today.

Dated future Tours sort nearest-first. Approved undated Tours sort after dated Tours and display `Tarih yakında`. `sold_out` remains visible as `Kontenjan dolu`.

Future workflow:

```text
Google Sheet
→ Tour Direct Sync
→ STT-* canonical
→ human review / editorial approval
→ automatic Hub eligibility
```

### Repository/runtime evidence

PR #18 candidate evidence:

- static v1.1 contract: PASS;
- v1.0 public pilot regression: PASS;
- Baseline verification: PASS;
- Unified Direct Sync + Tour Hub disposable runtime: PASS;
- approved future fixture included: PASS;
- past fixture excluded: PASS;
- needs-review fixture excluded: PASS;
- approved undated fixture retained after dated fixtures: PASS;
- sold-out state visible: PASS;
- v1.0 public gate options unchanged: PASS;
- Tour Intelligence replacement ZIP build: PASS.

Production installation/activation of Tour Hub v1.1 is **PENDING** and requires a separate explicit owner gate after Merge.

## Retired active Apps Script architecture

The following shared/operator paths are retired from the active source tree by the separated-stack reconciliation:

- `integrations/google-sheets/shared/ST-Direct-Sync.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Archive.gs`
- `integrations/google-sheets/umrah/ST_TDE_Exporter.gs`
- `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

They are not to be copied into Production Sheets after this checkpoint.

## Umrah removal/archive contract

Canonical operator control:

`AD = Programı Kaldır`

Rules:

- removed rows do not participate in active export;
- historical removed rows with no accepted Direct Sync identity are Sheet-only closed and are not CREATEd;
- linked rows may use controlled archive after server validation and operator confirmation;
- hard delete remains prohibited.

Production controlled archive itself is **not yet live accepted** in this checkpoint.

## Transport retry

Both independent clients preserve:

- bounded transient retry;
- same request ID and exact body across retries;
- fresh nonce/timestamp/HMAC per attempt;
- timeout/DNS/network classification;
- `stds_processing` polling;
- operator recovery message.

## Annual Umrah rule

One spreadsheet per operating year. Keep `Home`, use a clean yearly `ST Umrah Sync State`, and prove the first genuinely new Program with `CREATE → CREATE apply → same STP-* / UNCHANGED`.

## Current outcomes

| Gate | Result |
| --- | --- |
| Umrah separated selected-row no-change | PRODUCTION ACCEPTED |
| Tour separated selected-row no-change | PRODUCTION ACCEPTED |
| 36-row Umrah state migration | ACCEPTED / no WordPress write |
| Tour dynamic technical columns | ACCEPTED; fixed Z/AA assumption retired |
| Tour Hub v1.1 static/runtime | REPO/RUNTIME ACCEPTED — PR #18 MERGE PENDING |
| Tour Hub live `/kultur-turlari/` replacement | PENDING separate Production owner gate |
| Future Tour automatic Hub eligibility after approval | REPO/RUNTIME ACCEPTED; live pending |
| Umrah `Programı Kaldır` single control | ACCEPTED Sheet contract |
| Umrah mass no-change/preflight | PRODUCTION ACCEPTED from prior evidence |
| Umrah mass legacy live UPDATE | NOT ACCEPTED |
| Direct Sync v0.1.4 server archive | REPO/RUNTIME ACCEPTED |
| Controlled archive live Production test | PENDING |
| Tour individual public/SEO gates | SEPARATE / not changed |

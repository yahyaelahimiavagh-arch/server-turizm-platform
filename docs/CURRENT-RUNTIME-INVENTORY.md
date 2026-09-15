# Current Runtime Inventory

**Inventory date:** 2026-09-15

**Evidence:** live WordPress plugin installation screenshots, production Google Sheets runtime evidence, accepted repository CI/runtime evidence, and authoritative project checkpoints.

## Classification rules

- `LIVE AUTHORITATIVE`: confirmed active in Production and aligned with current operating evidence.
- `LIVE / RECONCILIATION REQUIRED`: active live source is authoritative, but an acceptance-label or documentation discrepancy remains.
- `LIVE EXPERIMENTAL`: active reversible runtime candidate that must not silently become a permanent architectural dependency.
- `LEGACY / RETIREMENT AUDIT REQUIRED`: retained compatibility source pending dependency/rollback audit.
- `ACCEPTED INTEGRATION`: accepted operator integration not itself a WordPress runtime plugin.
- `REPO ACCEPTED / NOT LIVE`: repository + disposable runtime accepted but not yet production installed/accepted.
- `HISTORICAL / NON-DEPLOYABLE`: retained only for audit/recovery.

## WordPress plugins

| Repository path | Live version | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/ai-assistant` | `0.2.2-modular` | LIVE AUTHORITATIVE | Credentials remain external in `wp-config.php`. |
| `wordpress/plugins/header-footer` | `1.1.12` | LIVE AUTHORITATIVE | Runtime/source aligned. |
| `wordpress/plugins/home-hotel-discovery` | `0.1.5` | LIVE AUTHORITATIVE | Accepted H12F discovery layer. |
| `wordpress/plugins/homepage-intelligence` | `0.3.3` | LIVE AUTHORITATIVE | Runtime/source aligned. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` | LIVE / RECONCILIATION REQUIRED | Live version preserved; historical golden-label discrepancy with `0.9.10` remains. Do not downgrade solely to reconcile labels. |
| `wordpress/plugins/program-foundation` | `0.2.0` | LEGACY / RETIREMENT AUDIT REQUIRED | Active compatibility/bridge layer. |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LIVE AUTHORITATIVE | Immutable `STP-*`, lifecycle/archive snapshots and identity-repair runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LIVE AUTHORITATIVE | Existing Program publication/publishing-registry boundary. Indexation remains separately controlled. |
| `wordpress/plugins/seo-perf-c1-revslider-css-unload` | `0.1.1` | LIVE EXPERIMENTAL | Scoped reversible performance candidate. |
| `wordpress/plugins/seo-perf-c2-porto-css-unload` | `0.1.0` | LIVE EXPERIMENTAL | Scoped reversible performance candidate. |
| `wordpress/plugins/seo-perf-d1-revslider-js-unload` | `0.1.0` | LIVE EXPERIMENTAL | Scoped reversible performance candidate. |
| `wordpress/plugins/tour-intelligence` | `1.0.0` | LIVE AUTHORITATIVE | v1.0 plugin installed/active. Public/indexation gates remain separate and must not be inferred ON from plugin activation. |
| `wordpress/plugins/direct-sync-foundation` | `0.1.3` | LIVE AUTHORITATIVE | Production selected-row Umrah/Tour sync accepted. v0.1.4 controlled archive is repository/runtime accepted only and is not live yet. |
| `wordpress/plugins/umre-semantic-adapter` | `0.33.0` | LIVE AUTHORITATIVE | Presentation/semantic adapter; canonical Program facts remain elsewhere. |

## Repository-only candidate newer than Production

### Direct Sync v0.1.4 — Controlled Umrah `KALDIR` archive

Classification: `REPO ACCEPTED / NOT LIVE`.

PR #16 final accepted repository/runtime checkpoint before merge approval:

`6f90f9bd7e5dbec222a496e4aa90afc6a422d58f`

Accepted CI/runtime evidence:

- Baseline verification run #61: PASS;
- Unified Direct Sync runtime run #33: PASS;
- static/security contract: PASS;
- existing Unified Direct Sync runtime: PASS;
- automatic live Umrah refresh runtime: PASS;
- controlled public/noindex Umrah archive runtime: PASS;
- replacement ZIP build: PASS.

This does **not** mean Production is v0.1.4. Production remains v0.1.3 until a separate owner-approved replacement and live QA are completed.

## MU-plugins and theme/configuration

| Repository path | Classification | Note |
| --- | --- | --- |
| `wordpress/mu-plugins/st-legacy-seo-redirects-20260914.php` | LIVE AUTHORITATIVE | Exact allowlist redirects only. |
| `wordpress/mu-plugins/st-p6-head-cleanup.php` | LIVE AUTHORITATIVE | Accepted technical SEO cleanup. |
| `wordpress/mu-plugins/st-umre-url-consolidation.php` | LIVE AUTHORITATIVE | Preserves `/umre-1/` consolidation behavior. |
| `wordpress/theme-overrides/porto-child` | LIVE AUTHORITATIVE | Child theme only; parent source excluded. |
| `wordpress/server-config/.htaccess` | LIVE AUTHORITATIVE | Exact accepted server guards preserved outside managed blocks. |

## Google Sheets integrations

| Integration | Live/accepted version | Classification | Current note |
| --- | ---: | --- | --- |
| `integrations/google-sheets/umrah/ST_TDE_Exporter.gs` | `0.2.0` | LIVE AUTHORITATIVE | Existing Umrah source producer/fallback export path. |
| `integrations/google-sheets/shared/ST-Direct-Sync.gs` | `0.1.3.2` | LIVE AUTHORITATIVE | Production one-click transient timeout/DNS/network retry accepted. |
| `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs` | current production copy | LIVE AUTHORITATIVE | Shared Umrah/Tour operator menu. |
| `integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs` | compatible with current production client | LIVE AUTHORITATIVE | Selected-row Umrah/Tour validate/apply flows. |
| `integrations/google-sheets/shared/ST-Direct-Sync-Archive.gs` | `0.1.4` | REPO ACCEPTED / NOT LIVE | Header-driven `KALDIR` archive flow; install only after owner-approved v0.1.4 rollout. |
| `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs` | `0.6.2.1` | ACCEPTED INTEGRATION | Existing Tour row can be explicitly linked to `STT-*` + checksum using hidden Z/AA controls. |

## Production Direct Sync acceptance register

### Umrah selected-row

Accepted production fixture:

`STP-000005 / Program No 223`

Accepted behavior:

- precheck resolves existing Stable ID;
- real live data update applies;
- approval is preserved automatically;
- same public/noindex route stays present;
- Publishing hash/route refresh occurs without manual Approve/Prepare;
- follow-up precheck returns `UNCHANGED`;
- no duplicate Program.

### Transport retry

Production Apps Script v0.1.3.2 is accepted for transient transport recovery.

The retry preserves request ID + exact body while refreshing timestamp/nonce/HMAC. Timeout/DNS/network transient failures are retried automatically; ordinary business/validation failures are not.

### Umrah full active batch

Observed Production full-active batch returned `UNCHANGED` for all listed Programs.

Classification:

- no-change/preflight behavior: **PRODUCTION ACCEPTED**;
- legacy live mass UPDATE: **NOT ACCEPTED**;
- only `STP-000005` currently has proven automatic live-refresh support;
- other legacy live Programs correctly fail closed for automatic refresh.

### Tour selected-row

Accepted production fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Observed:

```text
Precheck → UPDATE
Apply    → UPDATE
Precheck → UNCHANGED
```

This proves exact existing-record targeting, expected-checksum update, no duplicate `STT-000002`, and post-write idempotency.

## Controlled archive production status

The user-added `KALDIR` checkbox requirement is **not yet live accepted**.

Repository candidate behavior:

- header-driven discovery (`KALDIR`, `Programı Kaldır`, `Program Kaldır`);
- no fixed physical column dependency;
- exact Stable ID/checksum from hidden Direct Sync state;
- validate-before-write;
- explicit YES confirmation;
- archive never delete;
- public/noindex route demotion to `prepared`;
- Program Public Master / Hub bridge / Hotel gate unchanged;
- indexable route fails closed for separate SEO review;
- source-removal evidence stored inside Program Intelligence archive snapshot;
- post-archive retry returns `UNCHANGED`.

Production remains v0.1.3 until separate rollout approval.

## Annual Umrah Sheet operating rule

One Google Spreadsheet per operating year.

Do not erase/reuse old annual `Home` rows for a new year's Programs.

A new annual Spreadsheet creates a new `document_ref` namespace and prevents row-number reuse from inheriting prior-year Direct Sync identity.

Rules:

- keep primary tab name `Home` until ST-TDE is deliberately reconfigured;
- do not carry old hidden `ST Direct Sync State` as authoritative state into the new yearly file;
- run Direct Sync settings once in the new bound Apps Script project;
- first genuinely new Program must prove `CREATE → CREATE apply → same STP-* / UNCHANGED`;
- old annual Sheet remains source/audit evidence;
- cross-year Programs are not duplicated merely because the calendar changes.

Authoritative runbook:

`docs/runbooks/direct-sync/UMRAH-YEAR-ROLLOVER.md`

## Operational data snapshots

Umrah and Culture Tour CSV examples remain private, non-canonical migration/operator evidence. They contain product/program operating data, not passenger records.

Any future customer/passenger/passport/lead export remains prohibited from this repository.

## Historical material

Legacy backup sources retained under `releases/historical/` remain `HISTORICAL / NON-DEPLOYABLE` unless explicitly promoted through a new acceptance gate.

## Current reconciliation outcomes

| Gate | Result |
| --- | --- |
| Program Intelligence `0.3.5` | Live/runtime authoritative |
| Publishing Integration `0.4.14` | Live/runtime authoritative |
| Tour Intelligence `1.0.0` | Live plugin installed; public/SEO gates remain separately controlled |
| Direct Sync WordPress `0.1.3` | Production selected-row Umrah/Tour accepted |
| Direct Sync Apps Script `0.1.3.2` | Production transient retry accepted |
| Direct Sync v0.1.4 controlled archive | Repository/runtime accepted; not live |
| Tour Sheet generator `0.6.2.1` | Accepted/live operator integration |
| Hotel Intelligence `0.9.11` vs historical golden `0.9.10` | Acceptance-label discrepancy remains open; live runtime preserved |
| Secrets/private keys committed | None authorized; secrets remain external |
| Production customer/passenger/passport data | Not imported into repository |

## Locked next operational checkpoint

1. obtain explicit owner merge approval for PR #16;
2. verify `main` once after merge;
3. obtain separate explicit owner Production rollout approval;
4. replace Direct Sync plugin with accepted v0.1.4 package;
5. add archive Apps Script module/menu update to Umrah Sheet;
6. run one controlled production `KALDIR` archive test on an explicitly approved finished/disposable Program;
7. verify same `STP-*`, canonical archived, registry route `prepared`, global gates unchanged and second run `UNCHANGED`;
8. final documentation-only closeout.

Do not merge or deploy automatically. Do not unlock indexable/SEO gates as part of this checkpoint.

# Current Runtime Inventory

**Inventory date:** 2026-09-14

**Migration branch:** `migration/2026-09-14-authoritative-baseline`

**Evidence:** live-server custom-source archive, WordPress Plugins screen,
authoritative Master Plan, and accepted Library artifacts.

## Classification rules

- `LIVE AUTHORITATIVE`: present in the live-source backup and active in the
  WordPress runtime screenshot.
- `LIVE / RECONCILIATION REQUIRED`: live source is authoritative for what is
  running, but acceptance documentation differs or is incomplete.
- `LIVE EXPERIMENTAL`: active runtime candidate that must not silently become a
  permanent architectural dependency.
- `LEGACY / RETIREMENT AUDIT REQUIRED`: active compatibility source retained
  until its dependencies and rollback value are proven absent.
- `ACCEPTED INTEGRATION`: accepted external source used by the operating model.
- `HISTORICAL / NON-DEPLOYABLE`: retained for audit and recovery, excluded from
  deployable plugin paths.

## WordPress plugins

| Repository path | Plugin header version | Classification | Reconciliation note |
| --- | ---: | --- | --- |
| `wordpress/plugins/ai-assistant` | `0.2.2-modular` | LIVE AUTHORITATIVE | Runtime screenshot and source agree. Credentials remain external in `wp-config.php`. |
| `wordpress/plugins/header-footer` | `1.1.12` | LIVE AUTHORITATIVE | Source directory carried an older `v1.1.4` name; header and runtime agree on `1.1.12`. |
| `wordpress/plugins/home-hotel-discovery` | `0.1.5` | LIVE AUTHORITATIVE | Accepted H12F discovery layer. |
| `wordpress/plugins/homepage-intelligence` | `0.3.3` | LIVE AUTHORITATIVE | Source directory carried an older `v0.3.1` name; header and runtime agree on `0.3.3`. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` | LIVE / RECONCILIATION REQUIRED | Live screenshot, source header, and preserved 2026-09-10 runtime register say `0.9.11`; migration handoff calls `0.9.10` the accepted golden baseline. Do not downgrade or declare `0.9.11` accepted solely by version number. |
| `wordpress/plugins/program-foundation` | `0.2.0` | LEGACY / RETIREMENT AUDIT REQUIRED | Active compatibility/bridge layer. Do not remove before dependency and rollback audit. |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LIVE AUTHORITATIVE | Identity-repair runtime. Stable IDs remain immutable. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LIVE AUTHORITATIVE | Public Program routes remain `PUBLIC_NOINDEX`; no indexation or sitemap unlock. |
| `wordpress/plugins/seo-perf-c1-revslider-css-unload` | `0.1.1` | LIVE EXPERIMENTAL | Active performance candidate scoped to `/umre-1/`. |
| `wordpress/plugins/seo-perf-c2-porto-css-unload` | `0.1.0` | LIVE EXPERIMENTAL | Active performance candidate scoped to `/umre-1/`. |
| `wordpress/plugins/seo-perf-d1-revslider-js-unload` | `0.1.0` | LIVE EXPERIMENTAL | Reversible active candidate scoped to `/umre-1/`. |
| `wordpress/plugins/tour-intelligence` | `0.6.5` | LIVE AUTHORITATIVE | AI Completion Contract runtime accepted on 2026-09-14. Source-bound FULL/REVIEW validation is active; completion and import paths remain no-write. |
| `wordpress/plugins/umre-semantic-adapter` | `0.33.0` | LIVE AUTHORITATIVE | Presentation/semantic adapter; does not own canonical Program facts. |

## MU-plugins and theme/configuration

| Repository path | Classification | Note |
| --- | --- | --- |
| `wordpress/mu-plugins/st-legacy-seo-redirects-20260914.php` | LIVE AUTHORITATIVE | Exact allowlist redirects only; no wildcard Tour redirect. |
| `wordpress/mu-plugins/st-p6-head-cleanup.php` | LIVE AUTHORITATIVE | Accepted P6 technical SEO cleanup. |
| `wordpress/mu-plugins/st-umre-url-consolidation.php` | LIVE AUTHORITATIVE | Preserves `/umre-1/` consolidation behavior. |
| `wordpress/theme-overrides/porto-child` | LIVE AUTHORITATIVE | Child theme only; Porto parent source is excluded. |
| `wordpress/server-config/.htaccess` | LIVE AUTHORITATIVE | Includes the exact Porto root-directory 404 guard outside managed WordPress/LiteSpeed blocks. |

## Google Sheets integrations

| Repository path | Version | Classification | Note |
| --- | ---: | --- | --- |
| `integrations/google-sheets/umrah/ST_TDE_Exporter.gs` | `0.2.0` | LIVE AUTHORITATIVE | Shadow/export workflow; does not write canonical WordPress data. |
| `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs` | `0.6.2.1` | ACCEPTED INTEGRATION | Accepted Sheet→Partial JSON and stable-ID/checksum targeting generator. No WordPress write or public/SEO request. |

## Operational data snapshots

The supplied Umrah and Culture Tour CSV files are retained under
`examples/operator-sheet-snapshots/` as private, non-canonical migration
evidence. They contain product/program operating data, not passenger records.
They must never be interpreted as the canonical WordPress data store or used to
rotate stable identities. Any future customer/passenger/passport/lead export is
prohibited from this repository.

## Historical material

Legacy AI Assistant backup sources from the live ZIP are preserved under
`releases/historical/ai-assistant/`. They are intentionally outside the
deployable plugin directory and are classified `HISTORICAL / NON-DEPLOYABLE`.

## Reconciliation outcomes

| Gate | Result |
| --- | --- |
| Program Intelligence `0.3.5` | Source/runtime/documentation aligned |
| Publishing Integration `0.4.14` | Source/runtime/documentation aligned |
| Tour WordPress `0.6.5` | Source/runtime aligned; positive REVIEW, tampered hash, wrong duration, public/indexable rejection and legacy PARTIAL CREATE dry-run passed with no write |
| Tour Sheet generator `0.6.2.1` | Accepted artifact recovered and imported |
| Hotel Intelligence `0.9.11` vs golden `0.9.10` | Open acceptance-label discrepancy; live source preserved |
| Secrets/private keys in imported source | No credential-shaped value found by baseline scan |
| Porto parent / WordPress core / uploads | Not imported |
| Production customer/passenger/passport data | Not imported |

### Preserved test-version note

`wordpress/plugins/program-intelligence/tests/test_v032_invariants.py` is a
release-specific historical test that asserts the literal version `0.3.4`.
The live plugin is `0.3.5`, so that preserved test reports only its version
assertion as failed while its remaining invariants pass. It was not rewritten.
The repository-level current baseline test asserts `0.3.5` and the preserved
identity, lifecycle, private-runtime, and no-delete invariants.

## Locked next engineering branch

The migration baseline and STTI v0.6.5 runtime acceptance are closed. Resume from:

`STTI v0.7.0 — WordPress Review Relations`

All Tour public/indexation/sitemap/schema/canonical/homepage locks remain OFF.

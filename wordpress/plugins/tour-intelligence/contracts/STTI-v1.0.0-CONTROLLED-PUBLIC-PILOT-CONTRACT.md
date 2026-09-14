# STTI v1.0.0 — Controlled Public Tour Pilot Contract

## Status
Candidate contract. Repository/runtime acceptance does **not** authorize production deployment or public activation.

## Exact pilot identity
- Stable ID: `STT-000001`
- Public path: `/turlar/buyuk-iran-kultur-turu/`
- Route policy: exact allowlist only; no wildcard and no mass Tour URL generation.

## Release overlay
The public pilot is a release overlay over accepted canonical Tour data. It does not rewrite canonical Tour facts.

A Tour is content-ready only when:
1. exact allowlisted Stable ID exists;
2. row and payload editorial states are `approved`;
3. exact dates exist and the Tour is not completed;
4. the accepted v0.8 renderer model is ready;
5. at least one reviewed route stop is renderable;
6. exact/from pricing has a source amount and currency.

Missing optional Hotel/Transport/day-detail facts are not invented.

## Independent gates
All options default OFF:
- Public Master
- Exact Route
- Indexation
- Canonical
- Schema
- Sitemap

Effective dependencies:
- Route requires Master + content readiness.
- Indexation/Canonical/Schema require effective Route.
- Sitemap requires effective Route + Indexation.
- Turning Public Master OFF collapses every child gate immediately.

Gate changes are admin-only, nonce-protected, and audit-evidenced as `v100_public_pilot_gates_updated`.

## SEO behavior
With Route ON but Indexation OFF, the pilot is `noindex, follow, noarchive`.

Canonical, JSON-LD schema and sitemap entry are independently OFF until their own gates are explicitly enabled. Third-party canonical/schema output is suppressed on the pilot surface so STTI remains the release authority for this exact route.

Schema is deliberately minimal `WebPage` JSON-LD; no unsupported Tour/Product facts are invented.

## Sitemap
The custom provider exposes zero URLs unless its effective Sitemap gate is ON. When enabled it may expose exactly one URL: the allowlisted public pilot URL.

## Routing and rollback
No rewrite rule is created and no rewrite flush is required. The handler matches the exact normalized path only.

Fast rollback: turn Public Master OFF. Route, indexation, canonical, schema and sitemap become ineffective immediately without canonical Tour mutation.

## Production boundary
`docs/CURRENT-RUNTIME-INVENTORY.md` remains authoritative for production. Production currently runs the older Tour Intelligence runtime; repository acceptance must not be described as live deployment.

Before any future production Route ON action:
1. install/activate the accepted package through the separate deployment procedure;
2. verify the exact pilot path does not collide with an existing live WordPress route/page/redirect;
3. verify `STT-000001` production canonical data is editorial-approved and renderer-ready;
4. activate gates intentionally, beginning with Master + Route while Indexation remains OFF;
5. perform live QA;
6. only then consider separate SEO gates.

## Acceptance evidence required
- all prior v0.6.5/v0.7/v0.7.1/v0.8/v0.9 gates remain PASS;
- v1.0 static contract PASS;
- disposable WordPress + MariaDB proves default OFF state;
- exact route match and non-allowlisted rejection PASS;
- Master-only exposes nothing;
- Route-only remains noindex with other SEO gates OFF;
- independent SEO gates PASS;
- sitemap contains at most the one allowlisted URL;
- Master OFF fast rollback PASS;
- gate audit evidence PASS;
- Tour/audit/options/Stable-ID runtime state restored after test;
- no production deployment;
- Merge requires explicit owner approval.

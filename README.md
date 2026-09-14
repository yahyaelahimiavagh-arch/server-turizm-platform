# Server Turizm Platform

Private canonical source repository for Server Turizm custom WordPress modules,
Google Sheets integrations, schemas, tests, runbooks, and accepted runtime
evidence.

## Repository status

The `main` branch is reserved for accepted runtime baselines. The initial
runtime source is being reconciled on
`migration/2026-09-14-authoritative-baseline` before review and merge.

## Safety locks

- `/umre-1/` remains the live dynamic Umrah Hub.
- Program detail routes remain `PUBLIC_NOINDEX`.
- Program indexation, sitemap inclusion, and bulk submission remain OFF.
- Tour public routes, indexation, sitemap, schema, canonical, and homepage
  exposure remain OFF.
- Stable IDs are identity; rows, slugs, and titles are not.
- Removed Programs are archived, never automatically deleted.
- Production-changing flows must validate first and fail closed.

See `docs/MASTER-PLAN.md` and `docs/CURRENT-RUNTIME-INVENTORY.md` before making
changes.

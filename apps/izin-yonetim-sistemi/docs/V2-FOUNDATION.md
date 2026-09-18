# Leave Management V2 Foundation

Status: IMPLEMENTED ON FEATURE BRANCH — NOT DEPLOYED TO PRODUCTION

Branch: feature/elahimiavagh-ops-foundation  
Draft PR: #32  
Production baseline remains main at eb75cc51c178f06b6e8b3ca1c005bace015ef646.

## Goal

Turn the first Server Turizm deployment into a reusable, policy-driven leave-management product while establishing the shared operations-calendar contract needed by Tours, Umrah, HR and future modules.

Server Turizm is deployment configuration. It is not the reusable product identity.

## Implemented on this branch

- company name and application name are database settings
- persistent Powered by elahimiavagh.com attribution in the shared leave shell
- working weekdays are administrator-configurable
- leave calculation exposes calendar span, non-working days, holidays and effective deducted days
- employee gets live server-side calculation preview before submission
- large month calendar with holidays, configured non-working days and approved leave
- leave types can require an attachment
- medical leave is seeded with attachment required
- PDF/JPEG/PNG documents are stored outside public_html with randomized filenames and SHA-256 integrity
- employee can download only own leave document; administrator can access authorized request documents
- attachment size is policy-driven
- optional Cloudflare Turnstile with server-side verification on login
- immutable audit-log foundation records leave creation, approve/reject decisions and company-policy changes
- preflight checks were expanded for V2 schema, fileinfo, policy settings and audit table
- reusable Operations Calendar Core projection plugin v0.1.0
- versioned shared calendar event schema v1.0
- dedicated CI for PHP lint, calculator regression, V1→V2 database upgrade, staffing DB regression and disposable WordPress runtime smoke
- configurable concurrent-leave staffing threshold with privacy-safe aggregate team availability preview
- shared Operations Calendar adapters for Program Intelligence Umrah and Tour Intelligence projections
- public monthly travel-calendar shortcode with reusable styling
- central Platform Core module/plugin health registry
- MySQL-backed background job queue with duplicate-safe enqueue, retry/backoff and stale-lock recovery
- token-authenticated internal operations feed limited to Tour/Umrah sources
- optional leave-system client for Tour/Umrah operations workload during leave planning
- token-protected approved-leave machine export with minimal internal-only event payload
- Leave Management → Operations Calendar adapter with fail-closed full-window reconciliation
- public ICS subscription feed for Google Calendar / Apple Calendar / Outlook-compatible subscribers

## Database migrations for an existing V1 installation

Run in order only after backup and staging verification:

1. database/migrations/002-policy-branding-foundation.sql
2. database/migrations/003-leave-attachments.sql
3. database/migrations/004-audit-log.sql
4. database/migrations/005-staffing-policy.sql

Fresh installations use the updated schema.sql and seed.sql instead.

## Production safety

- This branch has not been merged.
- Production /izin/ has not been changed by this work.
- Turnstile is disabled unless keys are explicitly placed in private config.
- Uploaded leave/medical files never receive a direct public URL.
- Shared calendar public output accepts only events explicitly projected with visibility=public and status=published.
- The calendar is a projection, never the source of truth.

## Gates before production

- final branch CI green at the exact PR head
- database backup
- migrations 002/003/004/005 pass on staging/private copy
- private attachment directory writable outside public_html
- preflight PASS
- employee leave request with and without document tested
- required medical document enforcement tested
- employee attachment IDOR test
- admin attachment access test
- calculation preview vs persisted ledger parity test
- large calendar mobile/desktop smoke
- settings policy change audit test
- Turnstile test only after private keys are configured

## Not yet production-accepted

- year-end carryover policy engine
- effective-dated policy versioning for every future rule
- effective enforcement mode for staffing/overlap risk (current implementation is warning-only)
- direct Google Calendar write/API adapter beyond the implemented subscription feed
- WhatsApp notification/deep-link adapter
- cPanel cron production wiring for the implemented queue
- per-source adapter runtime acceptance against production-like Program/Tour fixtures
- PWA shell
- optional administrator 2FA

## Calendar publication invariant

Program Intelligence and Tour Intelligence retain their existing lifecycle/publication locks. No source adapter may infer public eligibility merely from the existence of a record. Internal projections may be broader, but public calendar visibility must be explicitly allowed by the authoritative source module.

## Reuse invariant

New business rules belong in policy/configuration data, not hard-coded customer logic. Historical approved records must remain reproducible by snapshot/ledger semantics when future policies change.


## Machine calendar connector contracts

### WordPress → Leave Management
Leave planning can consume the token-authenticated WordPress operations feed containing only Tour/Umrah operational projections.

### Leave Management → WordPress
Approved leave is exported through `calendar-export.php` only when `calendar_export.enabled=true` in the private config.
The feed is token protected, internal-only, bounded to 93 days per request, and deliberately excludes e-mail, comments, medical content and attachments.

The WordPress Operations Calendar adapter fetches the complete configured horizon before replacing the Leave projection. Any window failure aborts reconciliation so a transient connector error cannot erase the last accepted projection.

### Public calendar subscription
The public `/operations-calendar.ics` feed includes only public Tour/Umrah events. Internal leave and internal operations are excluded.

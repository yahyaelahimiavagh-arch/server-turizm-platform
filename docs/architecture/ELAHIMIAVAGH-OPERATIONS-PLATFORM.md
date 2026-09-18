# Elahimiavagh Operations Platform — Architecture Foundation

Status: Draft implementation baseline  
Owner: elahimiavagh.com  
First deployment: Server Turizm  
Architecture style: Modular Monolith + versioned module contracts

## 1. Product direction

Server Turizm is the first deployment, not the identity of the reusable product.

Business modules must not depend on a hard-coded company name, workweek, leave rule, brand, domain, external calendar provider, or notification provider. Company-specific values are configuration/policy data.

Developer attribution is part of the shared product shell:

**Powered by elahimiavagh.com**

A future paid white-label policy may be handled commercially, but module logic must not duplicate or hard-code customer branding.

## 2. Source-of-truth rule

Each domain has exactly one authoritative owner.

| Domain | Authoritative owner |
| --- | --- |
| Hotels | Hotel Intelligence |
| Umrah programs | Program Intelligence / accepted program source |
| Tours | Tour Intelligence |
| Employee leave | Leave Management |
| Public holidays | Leave Management policy data |
| Shared calendar | Projection only; never authoritative |
| Google Calendar | External projection only |
| WhatsApp | Notification/action channel only |

The shared calendar stores a rebuildable projection. It must never become the hidden master copy of tour, Umrah, hotel, or leave data.

## 3. Shared Operations Calendar

Contract: schemas/calendar/event-v1.schema.json

Core WordPress projection module: wordpress/plugins/operations-calendar-core/

Every source module publishes the same event contract:

- stable event_uid
- source_module
- stable source_entity_id
- event_type
- title
- start/end
- visibility
- lifecycle status
- optional location/public URL/metadata
- source version

Visibility rules:

- public: may appear on customer-facing website calendar.
- internal: staff/admin planning only.
- private: restricted operational data; never exposed by public API/shortcode.

Examples:

- Umrah departure/return -> public + internal
- Tour departure/return -> public + internal
- Approved employee leave -> internal/private only
- Medical attachment or medical detail -> never enters the calendar projection
- Public holiday -> internal, optionally public if a future product requires it

## 4. Calendar consumers

One calendar core can feed multiple views without duplicating source records:

1. Public website travel calendar.
2. Homepage upcoming-program widget.
3. Internal operations month/week calendar.
4. Leave-request conflict/context preview.
5. Google Calendar synchronization.
6. Future Outlook / Apple / ICS output.
7. Staffing-risk and workload warnings.

Google Calendar is an output adapter, not the source of truth.

## 5. Leave planning integration

When an employee selects leave dates, the leave system should evaluate:

- company working-day policy,
- public holidays,
- existing employee leave,
- configured minimum staffing,
- blackout/high-intensity dates,
- active tour/Umrah operations from the shared calendar projection.

The employee must receive an explicit explanation before submission.

Example:

- Calendar span: 7 days
- Non-working weekdays: 2
- Public holiday: 1
- Effective leave: 4 days
- Active operations in requested period: 2
- Other approved leave: 1 employee
- Policy result: warning / manager approval / blocked

The final policy outcome must be configuration-driven, not hard-coded.

## 6. Module boundaries

Target platform layout:

- Platform Core
- Users/Auth
- Policy Engine
- Operations Calendar
- Leave Management
- Program/Umrah
- Tours
- Hotels
- Media
- SEO
- Notifications
- Integrations
- Release/Health Registry

Modules communicate through documented contracts and hooks. They do not write directly into each other's private tables.

## 7. Scale strategy

The current risk is complexity, not raw MySQL row count.

Rules:

1. User-facing requests must stay short.
2. Expensive derived work runs asynchronously.
3. Public views read projections/cache, not multiple source plugins on each page load.
4. Every source record has a stable ID.
5. Every cross-module contract is versioned.
6. All schema changes use migrations.
7. Production changes fail closed.
8. Historical approved records are immutable snapshots where required.

## 8. Job queue direction

Heavy tasks move to a database-backed queue processed by cPanel cron:

- calendar projection refresh
- Google Calendar sync
- WhatsApp/email notification
- SEO/schema generation
- cache invalidation
- import reconciliation
- report generation

A save action should persist the authoritative record quickly and enqueue derived work.

For current shared-hosting scale, a MySQL queue + cron is preferred over RabbitMQ, Redis queues, Docker, or additional infrastructure.

## 9. Cache direction

Public calendar and homepage widgets should use bounded caches by time window, for example month-based keys.

A source event update invalidates only affected calendar windows.

No source module is allowed to make the homepage query all domain tables on every request.

## 10. Deployment/release direction

Target release package:

- manifest
- application/plugin files
- ordered migrations
- preflight/health checks
- checksums
- rollback metadata
- release notes

Target environments:

- Development
- Staging / private noindex
- Production

Production deploy must not be the first runtime test.

## 11. Platform Registry

A future admin health page should show at minimum:

- module name
- installed version
- DB schema version
- health state
- last successful job/sync
- pending migrations
- last error summary
- integration state

This avoids relying on memory to know what version is deployed.

## 12. Current implementation checkpoint

Implemented on the foundation branch:

- reusable calendar event schema v1.0
- WordPress Operations Calendar Core v0.1.0
- public REST/shortcode boundary plus token-authenticated internal Tour/Umrah feed
- atomic source projection reconciliation
- Umrah and Tour projection adapters
- public monthly travel-calendar view
- Platform Core module/plugin registry
- database-backed background job queue with retry/backoff/stale recovery
- reusable leave-system branding settings
- elahimiavagh.com attribution
- database-driven working weekdays
- transparent leave-calculation breakdown
- live pre-submit calculation preview
- privacy-safe coworker overlap/staffing context
- configurable concurrent-leave warning threshold
- optional Tour/Umrah operations workload context via private machine API
- optional Cloudflare Turnstile server verification
- secure private leave attachments + authorized download
- leave request and policy audit log
- approved-leave export with token authentication and privacy-minimal payload
- Leave → shared calendar internal projection adapter
- public ICS subscription feed for Google/Apple/Outlook-compatible calendars

Not yet production-accepted:

- hard/soft staffing enforcement modes beyond warning-only
- direct Google Calendar API write adapter beyond subscription/ICS
- cPanel cron production wiring
- production-like adapter fixtures/runtime acceptance
- large public calendar navigation/filter UX beyond the first month view
- year-end carryover execution engine
- PWA/offline shell

These must be implemented and runtime-tested incrementally rather than delivered as one untested monolithic package.


## 13. Cross-runtime calendar bridge

The WordPress platform and standalone Leave Management app remain independently deployable. They exchange only versioned, narrow machine feeds:

- WordPress exposes Tour/Umrah operational context through an authenticated internal REST route.
- Leave Management exposes approved leave through an authenticated JSON event feed.
- Each side stores secrets only in private configuration.
- A failed multi-window Leave fetch never clears the previous accepted Leave projection.
- Public calendar views and ICS feeds never query or expose the Leave source.

This keeps the calendar useful across modules without creating direct cross-database writes or shared-table coupling.


## 14. Shared-hosting worker execution

The queue can run from ordinary WP-Cron, but production shared hosting should prefer a real cPanel cron when WP-CLI is available.

Worker command:

`wp elahi jobs run --limit=50 --path=/absolute/path/to/public_html`

Health command:

`wp elahi jobs status --path=/absolute/path/to/public_html`

Recommended cadence is every minute or every five minutes depending on hosting limits. The worker is bounded, recovers stale locks, retries failed jobs with backoff, and does not require Redis/RabbitMQ/Docker.


## Service-year annual leave model

Annual leave entitlement is based on the employee's hire-date anniversary rather than January–December reset.

Initial Server Turizm company policy:

- completed service years 1–5: 15 company days, with a 14-day statutory floor,
- completed service years 6–14: 20 days,
- completed service years 15+: 26 days,
- employees age 18 or below and age 50 or above: statutory floor of 20 days.

Unused entitlement remains available while employment continues. Active-employment cash-out is disabled; unused leave is not silently expired at year-end. Historical entitlement rows store a policy snapshot so later policy changes do not rewrite already-earned rights.

Work schedule and leave deduction are independent policies. Server Turizm's initial Saturday policy is half-day morning work, but a full-day annual leave request deducts 1 full leave day.

## 0.3.5
- Identity Repair preflight now validates the post-repair approved state using a non-persistent preview verification event.
- Commit semantics remain transactional and unchanged.

## 0.3.4
- Stable-ID identity-drift hard gate.
- Controlled transactional identity repair with snapshots and rollback.
- No delete path.

## 0.3.3 - Explicit Removal Intent

- Added a separate `st-tde-removal-manifest/v1` flow for exact `Programı Kaldır` rows.
- Added Program Intelligence → Removal Review with exact source-identity preflight and explicit archive confirmation.
- Explicit source-removal intent can archive an in-progress/departed Program while preserving immutable snapshots and audit history.
- No delete path was added; missing rows in ordinary partial exports still have no lifecycle effect.
- Active imports clear stale removal intent if a Program is reintroduced.
- Google Sheets exporter v0.2.1 blocks `Sayaç Hedef` / `Gidiş` date mismatches and can export removal manifests.
- Protected fixtures `STP-000036/37` remain blocked from removal.

# Changelog

## 0.3.2 - Frontend Runtime Isolation / Performance Hardening

- Added a hard frontend bootstrap gate: ordinary public requests now return before loading STPI implementation classes or registering private Program/Audit post types.
- Preserved the full Program Intelligence runtime for WordPress admin, admin-post/admin-ajax, cron and WP-CLI contexts.
- Kept activation-state initialization self-contained so the frontend does not need to load the plugin class just to register activation behavior.
- Preserved v0.3.1 Temporal vs Effective State semantics unchanged.
- No ST-TDE schema, Stable ID, candidate data, importer behavior, audit history, archive behavior, public route, SEO/schema, sitemap or `/umre-1/` renderer was added or changed.
- Runtime motivation: repeated A/B tests showed the active v0.3.1 plugin could coincide with multi-second homepage requests while deactivation returned the same route to ~0.6 s; v0.3.2 removes all private-system bootstrap work from public requests.

## 0.3.1 - Temporal / Effective State Admin Semantics

- Fixed Program Review so **Temporal state** renders the pure temporal axis (`upcoming / in_progress / completed / undated`) instead of the composite effective state.
- Kept the composite **Effective state** visible as contextual admin information without changing stored canonical payloads.
- Split the Programs table into distinct **Temporal** and **Effective state** columns.
- Preserved availability (`sold_out`, etc.) as a separate workflow axis.
- Added static regression coverage proving sold-out future programs remain temporally `upcoming`.
- No ST-TDE schema, Stable ID, importer, audit, archive, public rendering, SEO/schema, sitemap or `/umre-1/` behavior changed.

## 0.3.0 - Private Candidate Store + Controlled Lifecycle

- Added staged Candidate Import with explicit human confirmation.
- Added Server-owned immutable `STP-*` allocation with a database advisory lock.
- Added source-row idempotency for CREATE / UPDATE / UNCHANGED / CONFLICT planning.
- Added private canonical JSON storage with WordPress revisions and re-export.
- Added individual Review pages for dates, prices, Hotel references and provenance.
- Added individual and confirmed bulk approval without any public publishing effect.
- Added computed temporal states and explicit Archive Candidate review.
- Added archive snapshots containing Program payload and resolved Hotel facts.
- Added restore and an administrator audit event log; no delete action exists.
- Strengthened approval to require verified + published Hotel Intelligence entities.
- Kept public routes, templates, SEO/schema output, sitemap changes and `/umre-1/` mutations disabled.

## 0.2.0 - Hotel Directory + Google Sheets Shadow Export

- Added an administrator-only, read-only Hotel Directory generated from Hotel Intelligence.
- Added exact/ambiguous hotel-name matching to Stable `STH-*` references in Google Sheets.
- Added a parallel Apps Script menu without replacing the legacy HTML/reservation generator.
- Added active-row validation, selected/all JSON exports and hotel mapping report.
- Preserved explicit G/H/I/J date roles and excluded `Programı Kaldır` rows without archive mutations.
- Removed copied hotel image/map fields from program JSON; Hotel Intelligence remains their source.
- Kept every public route, import, write, SEO output and archive action locked.

## 0.1.0 - ST-TDE Foundation Shadow

- Added versioned Program Batch JSON Schema 1.0.0.
- Added shared Umrah/cultural-tour enums and lifecycle axes.
- Added administrator-only JSON paste/upload Dry Run.
- Added fail-closed business validation and Publish Gate.
- Added completeness scoring and CREATE/UPDATE/UNCHANGED/CONFLICT planning labels.
- Added read-only Stable Hotel ID resolver for Hotel Intelligence v0.9.11.
- Locked all public output, writes, imports and archive mutations.
- Added representative Program 219 and two partial tour brochure payloads.

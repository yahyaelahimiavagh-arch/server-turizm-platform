# V2 Staging / cPanel Deployment Runbook

Status: candidate runbook — production deployment remains manual and gated.

## 1. Preconditions

Do not deploy the V2 candidate until all of these are true:

- Draft PR exact-head CI is green.
- V2 release-candidate packaging workflow is green.
- Database backup has been created.
- Current `/izin/` runtime folder has been backed up.
- Current WordPress plugin files/database have a recoverable backup.
- Private config is outside `public_html`.
- No secret/token is committed to GitHub.

## 2. Candidate packages

The V2 package workflow creates one artifact containing:

- `server-turizm-izin-v2-runtime.zip`
- `server-turizm-izin-v2-migrations.zip`
- `elahimiavagh-platform-core.zip`
- `operations-calendar-core.zip`
- `operations-calendar-adapters.zip`
- `RELEASE-MANIFEST.txt`
- `SHA256SUMS.txt`

Verify checksums before upload.

## 3. Existing Leave V1 database upgrade

Back up the Leave database first.

Run exactly in order:

1. `002-policy-branding-foundation.sql`
2. `003-leave-attachments.sql`
3. `004-audit-log.sql`
4. `005-staffing-policy.sql`

Do not import fresh `schema.sql` over an existing V1 database.

After migration, run the Leave preflight before accepting the deployment.

## 4. Leave runtime replacement

1. Back up the current `public_html/izin/` directory.
2. Do not overwrite the WordPress root `.htaccess`.
3. Extract `server-turizm-izin-v2-runtime.zip` into the existing `/izin/` application directory.
4. Keep the private config outside `public_html`.
5. Keep uploaded leave documents outside `public_html`.
6. Remove uploaded deployment ZIP files from the public directory after verification.

## 5. Private Leave config additions

Keep existing app/database values unchanged and add only the required V2 blocks.

Example structure:

```php
'turnstile' => [
    'enabled' => false,
    'site_key' => '',
    'secret_key' => '',
],

'storage' => [
    'attachments_dir' => '',
],

'operations_calendar' => [
    'enabled' => false,
    'endpoint' => '',
    'token' => '',
],

'calendar_export' => [
    'enabled' => false,
    'token' => '',
],
```

Rules:

- Turnstile secrets stay private.
- Operations Calendar endpoint must be HTTPS.
- Machine tokens must be random and at least 32 characters.
- Use separate tokens for WordPress→Leave and Leave→WordPress.
- Do not paste production tokens into chat, tickets or GitHub.

## 6. WordPress plugins

Upload/install the candidate ZIPs:

1. `elahimiavagh-platform-core.zip`
2. `operations-calendar-core.zip`
3. `operations-calendar-adapters.zip`

Activate Platform Core before Calendar Core/Adapters.

Existing Program Intelligence, Program Publishing Integration and Tour Intelligence remain authoritative and are not replaced by these packages.

The new adapters do not bypass any existing public master, route, Hub or lifecycle gate.

## 7. Private WordPress constants

Only after both applications are healthy, configure machine connectors in a private WordPress config location.

Conceptual values:

```php
define('ELAHI_OPS_CALENDAR_INTERNAL_TOKEN', 'LONG_RANDOM_PRIVATE_TOKEN');
define('ELAHI_LEAVE_CALENDAR_ENDPOINT', 'https://example.com/izin/calendar-export.php');
define('ELAHI_LEAVE_CALENDAR_TOKEN', 'ANOTHER_LONG_RANDOM_PRIVATE_TOKEN');
```

The Leave app must use the matching counterpart tokens in its private config.

Do not enable the Leave connector until the Leave endpoint returns a valid authenticated response.

## 8. Background worker

WP-Cron is supported as a fallback.

When cPanel WP-CLI is available, prefer a real cron worker:

```text
wp elahi jobs run --limit=50 --path=/absolute/path/to/public_html
```

Useful health command:

```text
wp elahi jobs status --path=/absolute/path/to/public_html
```

Choose the actual WP-CLI/PHP executable path provided by the hosting account. Do not guess it in production.

## 9. Acceptance sequence

### Leave application

Verify:

- login
- admin dashboard
- employee creation/edit
- configurable working weekdays
- configurable staffing warning threshold
- full-day leave calculation
- half-day leave calculation
- holiday exclusion
- Friday→Monday behavior under current workweek
- overlap protection
- medical leave requires document
- non-medical optional document behavior
- private document download: owner allowed
- private document download: another employee denied
- admin document access allowed
- audit timeline
- monthly calendar
- mobile layout
- Turnstile only after keys are configured

### Cross-module calendar

Verify:

- Platform Core health page loads.
- Operations Calendar Core table exists.
- Tour projection sync succeeds.
- Umrah projection sync succeeds.
- Leave connector disabled state is healthy/skipped before configuration.
- Leave connector succeeds after token/endpoint configuration.
- Public month calendar contains only public Tour/Umrah events.
- Internal operations feed contains Tour/Umrah but not Leave.
- Public ICS contains only public Tour/Umrah.
- Leave events appear only as internal shared-calendar projections.
- A simulated Leave connector failure does not clear the last accepted Leave projection.

### Background jobs

Verify:

- queue accepts sync job
- worker completes it
- failed job retries/backoffs
- Platform health page shows queue state
- no permanently running stale lock remains

## 10. Rollback

If Leave runtime fails:

1. Restore previous `/izin/` runtime directory.
2. Keep database backup available.
3. Do not attempt ad-hoc reverse SQL on production unless a tested rollback migration exists.

If WordPress platform plugin fails:

1. Disable the new adapter/plugin causing the issue.
2. Existing Program/Tour/Hotel source modules remain authoritative.
3. Shared calendar projection may be rebuilt later; it is not the source of truth.

If a machine connector fails:

1. Set the relevant integration `enabled=false` or remove private WordPress connector constants.
2. Do not delete source records.
3. Do not clear projection tables manually unless following a tested recovery procedure.

## 11. Production acceptance

Only after the staging/private acceptance sequence passes:

- merge the approved PR,
- create/tag the accepted release checkpoint,
- package from the accepted commit,
- deploy the exact accepted artifact,
- record deployed module/database versions in the Platform Registry/release notes.

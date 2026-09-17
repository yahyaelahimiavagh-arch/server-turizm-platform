# Status

Project: Server Turizm İzin Yönetim Sistemi

Branch: `feat/izin-v1-deployment-prep`

## Implemented

- [x] Requirements reviewed
- [x] V1 architecture defined
- [x] Database schema defined
- [x] Seed data defined
- [x] Folder structure defined
- [x] Foundation runtime files
- [x] Authentication / logout / first-admin setup
- [x] Employee dashboard
- [x] Employee leave request workflow
- [x] Weekend / public holiday / half-day calculation
- [x] Employee request history
- [x] Admin dashboard
- [x] Pending request approve / reject workflow
- [x] Processed request history
- [x] Employee create / edit / activate / deactivate
- [x] Employee + year allowance management
- [x] Leave type management
- [x] Public holiday management
- [x] Default annual allowance setting
- [x] Monthly / yearly reports
- [x] Lightweight approved-leave calendar
- [x] cPanel deployment documentation

## V1 hardening implemented

- [x] Strict calendar-date validation
- [x] Explicit pending/approved overlap protection
- [x] Opposite Half Day periods on the same date remain allowed
- [x] Employee row lock serializes concurrent request creation
- [x] Used Leave Type allowance behavior cannot be changed retroactively
- [x] Database-backed login throttling (`8 / 15 min` per email+IP)
- [x] Failed-login values stored as HMAC hashes rather than raw email/IP
- [x] Security response headers (`CSP`, `HSTS` on HTTPS, `no-store`)
- [x] Direct HTTP access to `tests/` blocked
- [x] Dependency-free calculator regression test added
- [x] Acceptance test plan documented

## Deployment prep implemented

- [x] CLI-only `tools/preflight.php` added
- [x] Preflight checks PHP version and required extensions
- [x] Preflight validates private config without printing credentials
- [x] Preflight checks MySQL connection, utf8mb4, required tables and seed data
- [x] Direct HTTP access to `tools/` blocked
- [x] cPanel production runbook added
- [x] Preflight script syntax linted successfully on PHP 8.4

## Security baseline implemented

- [x] PDO prepared statements
- [x] `password_hash()` / `password_verify()`
- [x] CSRF on POST forms
- [x] strict PHP sessions
- [x] session ID regeneration after login
- [x] server-side role authorization
- [x] employee data scoped by session user ID
- [x] escaped HTML output
- [x] DB credentials outside public web root
- [x] production error display disabled
- [x] direct HTTP access blocked for internal folders
- [x] one-time first-admin setup protection
- [x] login brute-force throttling
- [x] sensitive PHP responses marked `no-store`

## Test evidence available now

Calculator regression suite was executed against PHP 8.4 using the committed calculator/test logic:

- [x] Friday → Monday = 2 workdays
- [x] Full-day public holiday exclusion
- [x] Half-day public holiday calculation
- [x] Morning leave + afternoon holiday = 0.5
- [x] Leave during holiday half = 0
- [x] Cross-year ledger dates
- [x] Invalid date rejection
- [x] Multi-date Half Day rejection

Result: **8 passed / 0 failed**.

Deployment preflight syntax check:

- [x] `tools/preflight.php` — no syntax errors on PHP 8.4

## Remaining before production acceptance

- [ ] Run PHP syntax lint across all project PHP files
- [ ] Import schema + seed into real cPanel MySQL/MariaDB
- [ ] Run deployment preflight against the real database
- [ ] Runtime smoke test login / logout / employee / admin flows
- [ ] Runtime overlap scenarios from `docs/ACCEPTANCE-TESTS.md`
- [ ] Test cross-month and cross-year reports against MySQL
- [ ] Test allowance exhaustion and concurrent pending requests against MySQL
- [ ] Login rate-limit runtime test
- [ ] IDOR / CSRF negative tests
- [ ] Mobile responsive QA
- [ ] Add verified Türkiye public holidays in production
- [ ] cPanel production deployment / go-live acceptance

## Balance definition

For allowance-deducting leave types:

- `Yıllık Hak` = employee/year entitlement
- `Onaylanan` = approved ledger days
- `Bekleyen` = pending ledger days
- `Kalan` = entitlement - approved
- `Bekleyen dahil kullanılabilir` = entitlement - approved - pending

Pending is displayed separately and is not double-counted in `Kalan`.

## Deployment target

`https://serverturizm.com.tr/izin/`

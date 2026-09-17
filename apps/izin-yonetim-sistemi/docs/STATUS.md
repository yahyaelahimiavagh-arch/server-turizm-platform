# Status

Project: Server Turizm İzin Yönetim Sistemi

Branch: `feat/izin-v1-foundation`

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

## Remaining before production acceptance

- [ ] Run PHP syntax lint across all project PHP files
- [ ] Import schema + seed into a real MySQL/MariaDB test database
- [ ] Runtime smoke test login / logout / employee / admin flows
- [ ] Add explicit overlapping-request validation for the same employee/date
- [ ] Test Friday-to-Monday calculation
- [ ] Test full-day public holiday exclusion
- [ ] Test half-day public holiday combinations
- [ ] Test cross-month and cross-year reports
- [ ] Test allowance exhaustion and concurrent pending requests
- [ ] IDOR / CSRF negative tests
- [ ] Mobile responsive QA
- [ ] Add verified Türkiye public holidays in production
- [ ] cPanel production deployment

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

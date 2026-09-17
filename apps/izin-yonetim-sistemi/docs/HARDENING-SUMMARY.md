# V1 Hardening Summary

Branch: `feat/izin-v1-hardening`

Base: `main` at `c5ac90188b2525816372eac20d63f424d30517fc`

## Added / changed

- strict ISO calendar-date validation
- pending/approved overlap protection
- opposite half-day periods remain allowed on one date
- per-employee request creation row lock for concurrency safety
- login throttling: 8 failed attempts / 15 minutes per email+IP
- HMAC-hashed login failure identity storage
- CSP / HSTS / no-store response headers
- used Leave Type allowance semantics protected from retroactive change
- calculator regression suite
- cPanel acceptance test checklist
- login failure schema + migration
- protected `tests/` web path

## Test evidence

Dependency-free calculator tests executed with PHP 8.4:

`8 passed / 0 failed`

## Not yet production accepted

Still requires target-environment evidence:

- full project PHP lint
- MySQL/MariaDB schema + seed import
- runtime login/logout/admin/employee smoke test
- overlap + allowance concurrency runtime tests
- CSRF / IDOR negative tests
- report runtime QA
- mobile responsive QA
- verified Türkiye public holidays
- cPanel HTTPS deployment

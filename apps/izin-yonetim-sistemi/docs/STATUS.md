# Status

Project: Server Turizm İzin Yönetim Sistemi

Branch: `feat/izin-v1-foundation`

## Current

- [x] Requirements reviewed
- [x] V1 architecture defined
- [x] Database schema defined
- [x] Seed data defined
- [x] Folder structure defined
- [ ] Foundation runtime files
- [ ] Authentication
- [ ] Employee core
- [ ] Admin core
- [ ] Settings
- [ ] Reports
- [ ] Calendar
- [ ] Production hardening

## Security baseline

- PDO prepared statements
- `password_hash()` / `password_verify()`
- CSRF on POST
- strict PHP sessions
- server-side authorization
- escaped output
- external production DB config
- production error display disabled

## Deployment target

`https://serverturizm.com.tr/izin/`

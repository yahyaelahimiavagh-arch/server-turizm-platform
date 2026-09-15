# Unified Google Sheets Direct Sync — SUPERSEDED

This runbook described the retired shared Google Apps Script client/menu architecture.

As of 2026-09-15, the active Google Sheets operator architecture is separated:

- Umrah has its own exporter/client/menu/state stack;
- Tour has its own generator/client/menu/technical-state stack;
- only the WordPress REST gateway/contract is shared.

Use:

`docs/runbooks/direct-sync/SEPARATED-GOOGLE-SHEETS-DIRECT-SYNC-RUNBOOK.md`

Do not copy `integrations/google-sheets/shared/*` into Production Sheets.

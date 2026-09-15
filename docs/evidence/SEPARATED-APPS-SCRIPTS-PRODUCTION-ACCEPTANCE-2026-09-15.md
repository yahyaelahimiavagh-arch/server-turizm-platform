# Separated Apps Scripts — Production Acceptance Evidence — 2026-09-15

## Scope

Evidence is limited to Google Sheets operator-stack separation and selected-row no-change behavior.

It does not claim a new WordPress plugin version was deployed and does not claim a live controlled archive was executed.

## Umrah

Workbook:

`Server Turizm Umre Programlari 2026 Control Panel`

One-time state migration:

```text
Umrah State Migration
Completed
Migrated Umrah state: 36
Invalid/skipped rows: 0
WordPress WRITE: none
```

Fixture:

`STP-000005 / Program No 223`

Final observed sequence:

```text
Umrah Ön Kontrol — WordPress yazma yok
SYNC OK
STP-000005 — UNCHANGED

Umrah Direct Sync
SYNC OK
STP-000005 — UNCHANGED
```

Conclusion: separated Umrah Apps Script preserved accepted identity/canonical checksum on the tested no-change path.

## Tour

Workbook:

`Server Turizm Kultur Turlari Control Panel`

Fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

A first clean-generator attempt incorrectly assumed fixed Z/AA technical columns while `Vize` occupied Z. That candidate was rejected before write. The corrected generator uses header-driven/dynamic technical columns and preserves producer version `0.6.2.1` to avoid payload drift.

Final observed sequence:

```text
Tour Ön Kontrol — WordPress yazma yok
SYNC OK
STT-000001 — UNCHANGED

Tour Direct Sync
SYNC OK
STT-000001 — UNCHANGED
```

Conclusion: separated Tour Apps Script preserved exact existing targeting and accepted canonical checksum on the tested no-change path.

## Architecture conclusion

Production Google Sheets operator code is now logically separated:

- no Tour menu/functions in Umrah stack;
- no Umrah menu/functions in Tour stack;
- transport signing/retry is implemented independently in both clients;
- WordPress REST contract remains shared.

# STTI v0.9.0 — First Real Full Tour Runbook

## Scope

Private/disposable acceptance of real operator record `STT-000001` only. No live-site deployment and no public/SEO unlock.

## Source authority

- File: `examples/operator-sheet-snapshots/culture-tours-2026-09-14.csv`
- Row: 3
- Git blob SHA-1: `161f1df200c5e9222b35a7b4471d03eb8e6f88ca`
- Row SHA-256: `800255742ca2fbe07825519e484e8374f82c6245f848c8eb8932f9889ee92f1d`
- Stable ID: `STT-000001`
- Program No: `IRN-2026-01`

## Expected private record

Current dates: `2027-01-26 → 2027-02-12`, 18 days / 17 nights.

Route: `Tahran → Kaşan → İsfahan → Yezd → Şiraz`.

Price evidence: 899 EUR in the operator sheet `2 Kişilik` column; unsupported basis semantics remain `unknown`.

No Hotel, Transport, Airline or day-by-day business facts may be invented when the source row is blank.

## Verification

Run baseline verification, then disposable WordPress + MariaDB. The v0.9 gate must create the private candidate, write the dedicated audit event, read it through the accepted v0.8 renderer, and clean up Tour/audit/Stable-ID sequence exactly.

## Stop conditions

Stop and fail if source binding drifts, old 2026 dates reappear, a missing Hotel/Transport fact is synthesized, any public/SEO lock turns on, cleanup is incomplete, or any accepted earlier regression fails.

## Merge policy

One PR. Inspect CI once. No auto-merge. Merge only after explicit owner approval.

# STTI v0.5.0 — Private Customer Experience Runtime Gate

## PCE-A — Installation isolation
Install with **Replace current version**. Before opening Customer Preview, export Full JSON Evidence.

Expected:
- plugin_version 0.5.0
- mode private_customer_experience_pilot
- candidate_count 1
- STT-000001 checksum unchanged: `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`
- updated_at unchanged
- no new audit event
- frontend_writes 0
- all public release locks OFF
- private_renderer.status runtime_accepted
- customer_experience.status pilot_active

## PCE-B — Customer UI runtime
Tours → STT-000001 → Customer Preview.

Expected visual/data parity:
- Standalone page, no WordPress admin chrome
- Büyük İran Turu
- Iran / 5 cities / 5 route stops
- 16–24 Ekim 2026
- 9 Gün / 8 Gece
- 899 EUR equivalent display
- 9 itinerary days
- Hotel/transport/visa missing facts are not invented
- no canonical media means brand fallback, not fabricated photography
- responsive mobile layout

## PCE-C — Read-only evidence
After opening/refreshing Customer Preview, export Full JSON Evidence.
Expected same candidate checksum, updated_at and latest audit id as before.

## PCE-D — Access isolation
Open the exact Customer Preview URL in Incognito while logged out. It must fail closed / redirect / 404 / login. It must never render the tour.

## Stop conditions
STOP if candidate checksum changes, audit event is added merely by previewing, a public route appears, frontend_writes > 0, or a source-missing business fact is rendered as if verified.

After acceptance: freeze v0.5 customer experience baseline, then separately plan controlled public route parity. Do not enable public release automatically.

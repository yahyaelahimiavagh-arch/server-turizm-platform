# H10.0 — Full Hotel Dossier Data Contract

Version: **0.9.8 candidate**

Purpose: close the schema/import gap before Pilot Hotel population.

Key changes:
- Separates `long_caption_tr` from `editorial_long_tr`; `long_caption` no longer aliases the full article.
- Adds Brand separately from Hotel Chain.
- Adds display names TR/EN/AR, license/registration number, floor count, semantic topics, source checked date and Source Ledger JSON.
- Data Bridge imports/exports all new fields with Dry Run, audit snapshot and rollback coverage.
- Room JSON accepts `occupancy` as a compatibility alias for canonical `capacity`.
- Reference Point JSON can retain coordinates and an explicit distance method; `distance_km_straight_line` is accepted and normalized to meters + `straight_line`.
- Existing H9E publication lock stays untouched and locked.

Acceptance rule: this candidate is NOT the accepted baseline until owner runtime PASS. Rollback target: v0.9.7.

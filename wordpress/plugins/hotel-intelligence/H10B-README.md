# H10.0 — Provenance Consistency Hotfix

Version: **0.9.9 candidate**

Purpose: close the two runtime blockers found after the first full Nusk dossier import.

1. Sacred distance source of truth
   - VERIFIED structured reference-point coordinates override city fallback anchors.
   - The city constants remain fallback for Hotels without dossier references.
   - Nusk `STH-000010` should therefore render approximately **430 m**, matching its imported Masjid an-Nabawi reference, instead of the legacy-anchor **410 m**.

2. Star-rating provenance gate
   - Public Hotel, overview, Hub and schema star output requires a star source URL + verification date.
   - Existing raw star values remain in admin for review but are not publicly asserted without provenance.
   - This is intentional fail-closed behavior under the no-hallucination policy.

Acceptance test:
- `STH-000010` remains the same entity.
- Publication Lock stays LOCKED / NOINDEX.
- Harem distance displays ~430 m consistently.
- Current unproven 3-star value is NOT rendered publicly until independently resolved.
- Media remains 10 and dossier content remains intact.
- Data Health may drop by 4 points because unverified star data no longer earns completeness credit.

Rollback target: **v0.9.8**.
Accepted baseline remains **v0.9.7** until owner runtime PASS.

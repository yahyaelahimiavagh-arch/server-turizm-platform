# R5: WordPress approved, missing on public Umrah page — resolved

**Date:** 2026-11-17  
**Program code:** R5  
**Stable ID:** `STP-000054`  
**Source:** User screenshots and follow-up confirmation.

## Observed
- Google Sheets Home contained the new R5 departure 2027-03-01, return 2027-03-12.
- Auto Sync admin showed `programs: 65`, `hotels: 16`, `errors: 0`, `complete: true`, and empty queue.
- Publishing Integration admin listed R5 `approved / scheduled / open`, temporal `upcoming`, and `public_noindex` with canonical path under `/umre-programlari/`.
- R5 was not visible in the public /umre-1/ cards on initial inspection.
- User confirmed **"حل شد"** after troubleshooting; the exact change that produced success was not observed independently.

## Non-destructive diagnostic order
1. Search `PROGRAM R5` in the public page and check date sorting (not necessarily last card).
2. Confirm canonical R5 detail route responds instead of 404.
3. Confirm Publishing Integration registry `mode=public_noindex`, master/Hub flags, and `STPPI_Renderer::model` validation including hotel/payload hashes.
4. Purge LiteSpeed page cache and reload `/umre-1/` with a hard refresh. It is a plausible stale-output cause, not a proven root cause in this case.
5. Only if missing, inspect `public_noindex_registry()` → `program_items_from_registry()` → `hub_shortcode()` and fast update invalidation.
6. Preserve original `[stppi_umre_programs]` renderer and current design; no need to reinstall plugins solely for cache.

## Future design improvement
Invalidate public Umrah page cache on successful program create/update/archive and on publishing registry changes. Add diagnostic counters for: imported, approved, registry eligible, model validated and rendered. Avoid hardcoded hotel-specific patches.

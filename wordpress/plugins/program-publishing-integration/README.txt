Server Turizm Program Publishing Integration v0.4.2

Accepted UI baseline: v0.2.8 FINAL UI FREEZE.
This release does not redesign the Hub card.

v0.3.1 adds FULL ELIGIBLE MIGRATION:
- automatically selects every real Turkish scheduled + upcoming Umrah Program whose approval gate is READY or APPROVED;
- permanently excludes STP-000036 and STP-000037;
- removes the previous 8-record wave cap;
- Step 1 performs controlled APPROVE + PREPARE for the selected eligible set;
- approval preflight runs before canonical writes;
- registry preparation is committed only if all selected route preparations succeed;
- Step 2 performs an all-record runtime-model preflight before switching the selected set to PUBLIC/NOINDEX;
- one stale/invalid Program blocks the complete NOINDEX switch;
- Hub stays OFF until separate post-QA re-arm;
- indexation, sitemap exposure and Hotel public links remain unavailable from this screen.

Cutover contract remains unchanged:
RAW1 + RAW2 stay.
The single legacy Program RAW3 is replaced by [stppi_umre_programs].
The three SEO RAW blocks after RAW3 stay.
Page Custom CSS stays during first cutover.

v0.3.2 — P0 MOBILE INFORMATION-ARCHITECTURE HOTFIX
- fixes the critical mobile card sequence introduced by the v0.2.7 conversion-order rule;
- mobile flow is now: card header → hero image → journey → Hotel stays in canonical travel order → trust logos → prices → child fees / important notes → Tour Details → Reservation;
- desktop layout is unchanged;
- Program / Hotel canonical data, routes, noindex state, sitemap state and lifecycle logic are unchanged;
- the exact v0.3.1 → v0.3.2 upgrade is treated as a safe UI-only upgrade and preserves the already accepted live Hub/public-noindex state; all other version transitions remain fail-closed;
- no DOM duplication and no JavaScript reordering are introduced: the patch restores the existing semantic source order with one centralized mobile CSS contract.


v0.4.1 — Hotel→Program Controlled Relation Canary
===================================================
- Exact Stable Hotel ID canary. Default OFF.
- Safe upgrade from v0.3.2 preserves accepted public/noindex Program + live Hub state but forcibly closes Hotel links and clears canary.
- Reverse relation is derived at runtime from Program stays[].hotel_id.
- Never writes Program lists or relation metadata into Hotel records.
- Requires canonical public Hotel route context; does not render in Hotel preview.
- Only APPROVED + SCHEDULED + UPCOMING Programs with a live public_noindex/indexable Final Route qualify.
- First public pilot is one exact Hotel only; no mass Hotel activation.
- Public-noindex Program links receive rel=nofollow during the canary stage.
- Dedicated Hotel relation CSS is loaded only when the exact public canary renders.
- Rollback: Hotel public canary OFF; full STPPI close remains available.


v0.4.1 — EMERGENCY ADMIN RECOVERY HOTFIX
-----------------------------------------
- Fixes an accidental self-recursion in stppi_close_hotel_links() that could exhaust PHP workers/memory on wp-admin immediately after the 0.3.2 -> 0.4.0 replacement.
- Preserves the accepted Program public/noindex registry and live Hub when upgrading from 0.3.2 or the broken 0.4.0 build.
- Forces Hotel public relations OFF and clears Hotel canary on upgrade.
- No Program facts, Hotel facts, post IDs, routes, sitemap locks, canonical policy, or indexation policy are intentionally changed.


v0.4.2 — CONTROLLED MULTI-HOTEL STABLE-ID ALLOWLIST
====================================================
- Replaces the single exact Hotel canary runtime gate with a validated Stable Hotel ID allowlist.
- Exact safe upgrade contract: v0.4.1 -> v0.4.2 preserves accepted Program public/noindex routes + live Hub, but forces Hotel relations OFF and starts with an EMPTY allowlist.
- Admin allowlist is built only from read-only derived Hotel relations that currently have eligible Programs.
- Saving/changing the allowlist always closes public Hotel relations first.
- Opening rollout preflights every selected Hotel: derived relation present, canonical Hotel public route present, and at least one eligible public Program.
- Runtime renders Hotel→Program only when the current canonical Hotel Stable ID is in the allowlist.
- Allowlist outside Hotels remain untouched.
- One-click Hotel→Program emergency close preserves the allowlist for rollback inspection.
- Separate clear action closes rollout and empties the allowlist.
- Evidence adds hotel_relation_mode, hotel_allowlist and hotel_allowlist_count; legacy hotel_link_canary remains only for backward-compatible evidence shape and is cleared on the safe upgrade.
- No Program list is ever written into Hotel records; relation remains derived from Program stays[].hotel_id.
- Program indexation/sitemap locks are unchanged; public_noindex links remain rel=nofollow.


v0.4.3 — UMRE PREMIUM SOFT SKIN CANDIDATE
==========================================
- Presentation-only visual candidate for the exact /umre-1/ hub.
- Exact safe upgrade contract 0.4.2 -> 0.4.3 preserves accepted Program public/noindex routes, live Hub, Hotel allowlist rollout and all entity data.
- New skin starts PUBLIC OFF and must be explicitly enabled after an admin-only signed preview.
- Ivory/warm off-white page field replaces the hard white sheet; Program cards remain bright for readability and conversion.
- Uses CSS-only navy/gold accents and subtle geometry; no extra background image request.
- Does not modify H1/content, Program/Hotel facts, links, canonical, robots, sitemap, schema, URLs or lifecycle/indexation policy.
- One-click Legacy rollback is available in Program Publishing Integration admin.


v0.4.6 — UMRE PREMIUM SOFT NEUTRAL PALETTE HOTFIX
- CSS-only correction to remove excessive warm/yellow cast from v0.4.3.
- Preserves all accepted Program/Hub/Hotel runtime state and current visual-skin gate state.
- Pearl/stone page field, white cards, navy-neutral shadows; gold remains accent-only.


v0.4.7 — UMRE PERFORMANCE-LIGHT PEARL BACKGROUND HOTFIX
- Keeps the accepted v0.4.6 pearl visual direction while removing expensive full-page radial gradients, large pseudo-element geometry and shadow rings.
- Uses one simple vertical gradient plus a tiny divider only.
- No Program card/CTA selectors are owned by the skin.
- Preserves all existing public/preview gates, Program routes, Hotel relations, SEO ownership and skin enabled state on upgrade from v0.4.6.


v0.4.8 — Transactional Incremental Recovery Hotfix
- Batch Step 1 no longer shuts Public Master/Hub before validation.
- Batch prepare refreshes accepted live routes in place and writes only after full selected-set validation.
- Batch Step 2 is additive and never demotes unselected accepted routes.
- Existing Hub/Hotel runtime state is preserved during incremental route opening.
- Emergency Live Baseline Recovery validates every existing registry route against current canonical Program/Hotel data before atomically refreshing hashes and restoring PUBLIC/NOINDEX + Hub.
- Recovery keeps Hotel→Program public rollout OFF for a separate explicit re-enable gate.


v0.4.9 — TEMPORAL-AWARE LIVE BASELINE RECOVERY HOTFIX
========================================================
- Fixes v0.4.8 emergency recovery incorrectly blocking when an older registry Program naturally moved from UPCOMING to ongoing/completed.
- Recovery now treats non-UPCOMING historical routes as expected temporal drift, not a fatal production error.
- Historical routes remain PREPARED for later archive-policy handling; they are not surfaced in the live Hub.
- Current UPCOMING routes still receive full canonical Program/Hotel validation and hash refresh before any write.
- Any real validation error on an UPCOMING route still blocks the entire recovery atomically.
- Exact 0.4.8 -> 0.4.9 replacement preserves the current runtime gate state; nothing opens automatically.
- Hotel→Program public rollout remains OFF after recovery and must be re-enabled separately.

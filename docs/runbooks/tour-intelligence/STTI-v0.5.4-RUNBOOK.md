# STTI v0.5.4 — Header Seam Fix + Animated Global Route Runtime Gate

## Purpose
Close the remaining white seam below the live header without replacing the Server Turizm theme header, and animate the worldwide route progressively from origin to destination.

## Locked invariants
- Public Master OFF
- Public routes OFF
- Sitemap OFF
- Indexation OFF
- STTI schema output OFF
- Canonical/robots writes OFF
- Frontend canonical writes = 0
- STT-000001 checksum must remain unchanged

## Installation gate
1. Replace the current STTI plugin with v0.5.4.
2. Before opening Customer Preview, export `Tüm JSON Evidence İndir`.
3. Confirm candidate_count=1, stable_id=STT-000001, schema=1.1.0, checksum unchanged and latest audit still ID 8.

## Visual gate
Open Customer Preview and verify:
- no white spacer is visible between the live theme header and the hero,
- hero image begins directly beneath/behind the glass navigation surface,
- header remains the live Server Turizm theme header,
- summary cards remain large and color-coded.

## Animated route gate
Scroll to the Route section and verify:
- the first marker appears at the origin,
- each route segment draws progressively in canonical stop order,
- the next marker activates only after its segment arrives,
- the horizontal timeline reveals in sync,
- after animation the complete route remains visible,
- reduced-motion users receive the final state without animation,
- unresolved coordinates are never invented.

## Fail closed
Stop if a new audit event appears, the candidate checksum changes, a public route becomes reachable, or the theme header is replaced rather than reused.

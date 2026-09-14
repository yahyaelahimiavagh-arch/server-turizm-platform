# Server Turizm Program Foundation v0.2.0 — H6B Candidate

H6A is runtime accepted. v0.2.0 preserves the accepted Stable Program ID/Data Bridge behavior and adds the smallest safe H6B relationship layer.

## Relationship source of truth

`Program Entity → hotel_refs[] → Stable Hotel IDs`

Reverse usage is derived at read time:

`Hotel → Programs whose hotel_refs[] contain this Hotel ID`

No independent Program list is stored on Hotel records.

## New in H6B
- **Program Foundation → Relations** relationship graph.
- Program edit screen shows its linked Hotels by Stable Hotel ID.
- Hotel edit screen shows Programs using that Hotel, derived live from Program `hotel_refs[]`.
- Registers the Hotel-content resolver hook `sthi_resolve_program_canonical_url` so Hotel editorial `program:STP-xxxxxx` intents can resolve through the Program Entity.
- Public Program target safety gate:
  - Program ID must exist.
  - Program lifecycle must be `active`.
  - `public_url` must be an internal Server Turizm URL.
  - The owning WordPress target must be published, public/viewable and not carry supported noindex signals.
  - Draft/inactive/noindex/non-public targets resolve to **no link**.
- Existing H6A CREATE / UPDATE / UNCHANGED / CONFLICT / INVALID, rollback, source keys and Hotel-ID validation remain unchanged.

## Important current behavior
Relationship existence does **not** automatically make a Program public. If the Program does not yet have an approved stable public target, its Hotel relation remains valid but Program-specific caption links stay inactive. This intentionally preserves the current `/umre-1/` workflow until a stable Program public URL/anchor contract is approved.

## H6B runtime acceptance sequence
1. Upgrade Program Foundation v0.1.0 → v0.2.0. Do not deactivate Hotel Intelligence v0.8.0 or Semantic Adapter v0.33.0.
2. Confirm existing `STP-000002` and `STH-000007` remain unchanged.
3. Open **Program Foundation → Relations**.
4. Confirm `STP-000002 → STH-000007` appears.
5. Confirm reverse proof for `STH-000007` says **derived ✓**.
6. Edit Hotel `STH-000007`; confirm the read-only **Umre / Program Usage (Derived)** box lists `STP-000002`.
7. Edit Program `STP-000002`; confirm **Hotel Relations / H6B** lists `STH-000007` and its Hotel preview/edit controls.
8. Because the test Program is still lifecycle `draft` with no approved public URL, confirm its public target says **Not link-eligible** rather than inventing a URL.
9. Re-test Hotel Preview and `/umre-1/` for visual/functional regression.
10. Data Bridge re-import of the same row must still return UNCHANGED.

Do not close all of H6B until the relation graph passes and the owner decides the stable public Program URL/anchor contract needed for a positive Program-specific link test.

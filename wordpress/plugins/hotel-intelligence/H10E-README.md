# H10E — Public Slug Lifecycle Hotfix

## Change ID
H10E-ROUTE-SLUG-001

## Version
0.9.10

## Root cause
Data Bridge creates/updates the WordPress post first, which triggers `save_post_sthi_hotel`. At that moment the imported Hotel may not yet have final `workflow_status=published`, `verification_status=verified`, city, or scanned gallery media. The H9B slug-freeze hook therefore correctly refuses to freeze `_sthi_public_slug`. Data Bridge then applies the remaining metadata/media but did not ask the route layer to freeze again. This produced Hotel Grid `Publish Ready = READY` while `/otel/{slug}/` returned 404.

## Fix
- One-time v0.9.10 backfill of missing public slugs for all currently public-ready Hotels.
- Explicit `ensure_public_slug()` after Data Bridge completes an import row.
- Explicit `ensure_public_slug()` after Hotel Grid inline changes.
- Existing frozen public slugs are preserved.
- Collisions still fail closed.

## Regression test
1. Keep Publication Lock LOCKED.
2. Activate v0.9.10.
3. Load any wp-admin page once (runs the one-time backfill).
4. Open **Hotel Intelligence → Launch Control** and confirm `Routable QA Hotels = 9`.
5. Purge LiteSpeed cache.
6. For each of the 9 Pilot Hotels, obtain the system public URL (do not assume WP `post_name` when Official Name differs).
7. Confirm HTTP 200, `noindex, follow`, no self-canonical while locked, correct Hotel identity, and Media > 0.
8. Confirm Preview URLs remain HTTP 200 + noindex.
9. Confirm `/hotel-preview/{slug}/` remains 404.

## Rollback
v0.9.9

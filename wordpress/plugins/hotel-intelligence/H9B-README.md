# H9B — Canonical / Indexation / Sitemap (v0.9.4)

## Public URL policy
- `/oteller/` — indexable only when at least one public-ready Hotel exists.
- `/mekke-otelleri/` — indexable only when at least one eligible Makkah Hotel exists.
- `/medine-otelleri/` — remains noindex while inventory is empty.
- `/otel/{frozen-public-slug}/` — indexable only for one exact public-ready Hotel.

## Frozen public slug
The first H9B activation freezes `_sthi_public_slug` for each current public-ready Hotel. Future title/official-name edits do not silently change its public URL. Slug collisions fail closed and are not indexed.

## Preview separation
- `/?sthi_hotel_preview=POST_ID` stays noindex and canonical-absent.
- `/?sthi_hub_preview=...` stays editor-only/noindex.
- `/hotel-preview/{slug}/` remains 404 to prevent duplicate Hotel URLs.

## Sitemap
A native WordPress custom provider exposes only H9B canonical/indexable Hotel Intelligence URLs. Empty/noindex hubs are excluded. `lastmod` uses Hotel post modified timestamps; hub `lastmod` is the newest eligible Hotel timestamp in that hub.

## Shell ownership
Hotel Intelligence continues to call standard `get_header()` / `get_footer()`. The independent Server Turizm Header & Footer plugin remains the shell owner.

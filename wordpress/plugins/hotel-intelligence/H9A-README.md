# H9A — Controlled Public Route Foundation (v0.9.3)

## Candidate routes (NOINDEX)
- `/oteller/` — All Hotels, data-driven
- `/mekke-otelleri/` — Makkah Hub
- `/medine-otelleri/` — Madinah Hub
- `/otel/{post-slug}/` — public-route candidate for a Hotel Entity

## Safety
- All H9A candidate routes remain `noindex, follow`.
- Canonical remains absent.
- `sthi_hotel` remains excluded from native WordPress sitemap.
- Stable Preview `/?sthi_hotel_preview=POST_ID` remains available and noindex.
- Historical `/hotel-preview/{slug}/` native CPT URLs are forced to 404 to avoid duplicates.
- The plugin does not own or duplicate Header/Footer markup; it uses normal WordPress header/footer calls.

## Expected pilot
For `STH-000007` Makarem Umm Al Qura (public-ready):
- `/otel/makarem-umm-al-qura/` => 200 + noindex + one H1
- `/oteller/` => 200 + one hotel card
- `/mekke-otelleri/` => 200 + one hotel card
- `/medine-otelleri/` => 200 + zero-state
- Preview remains healthy
- `/umre-1/` remains healthy

## Not activated in v0.9.2
- Self-canonical
- index/follow public activation
- Hotel/hub sitemap inclusion
- final lastmod
- Search Console/Bing/IndexNow submission

## v0.9.3 route resolver fix
- Candidate Hotel URL slug is derived exactly from `_sthi_official_name` (fallback post title), not blindly from internal WordPress `post_name`.
- Resolver is exact-match only and fails closed on duplicate candidate slugs.
- No persistent public slug is written yet; H9A remains noindex/canonical-locked.

## 0.9.11 — Gate 4.6 Hotel Knowledge Graph + Factual Summary

- Extends the existing single Hotel JSON-LD `@graph`; no parallel schema block is introduced.
- Connects every Hotel `WebPage` to the canonical `#website`, and that `WebSite` to the canonical Server Turizm `#organization` publisher entity.
- Types the shared publisher entity as both `Organization` and `TravelAgency`, preserving the established Server Turizm identity without claiming ownership or operation of any hotel.
- Adds a deterministic short factual Hotel summary generated only from structured Hotel Entity data: name, location, provenance-gated star class, accepted sacred-site distance and explicitly positive amenities.
- Uses the generated summary as the visible Overview fallback and as `Hotel.description`; an existing human-authored WordPress excerpt remains authoritative for the visible Overview.
- Removes the public placeholder sentence from empty Hotel overviews without writing generated text back into Hotel records.
- Keeps Stable Hotel IDs, frozen public slugs, canonical/indexation policy, Hotel/Program relations, Data Bridge, media, maps, long editorial content and owner-verified source records unchanged.

## 0.9.10 — H10E Public Slug Lifecycle Hotfix

- Fixes published + verified + media-ready Hotel Entities that showed `Publish Ready = READY` but returned 404 on `/otel/{public-slug}/`.
- Root cause: Data Bridge completed verification/workflow/media metadata after the WordPress `save_post` slug-freeze hook had already run, leaving `_sthi_public_slug` empty for later Pilot imports.
- Performs a one-time safe backfill for existing public-ready Hotels; already frozen public slugs are never changed.
- Data Bridge now freezes the public slug after the full Entity row and media scan have been applied.
- Hotel Grid inline updates also re-check slug eligibility so future out-of-band readiness changes cannot strand a Hotel without a route.
- Slug collision behavior remains fail-closed. Stable `STH-*` IDs, Publication Lock, noindex state, canonical rules, sitemap rules, media, and Header/Footer ownership are unchanged.
- Rollback target: v0.9.9.

## 0.9.9 — H10.0 Provenance Consistency Hotfix (candidate)

- Sacred-site distance now prefers the Hotel dossier's VERIFIED `reference_points_json` primary holy-site coordinates; hard-coded Makkah/Madinah anchors are fallback only.
- Grid, Hero, map card and sacred-distance panels therefore consume one reference-coordinate source of truth.
- Public star rating now fails closed unless `_sthi_star_rating_source_url` and `_sthi_star_rating_verified_at` are both present.
- Hub cards and Hotel overview follow the same verified-star rule; raw admin star value remains editable for operator review.
- Data Health awards star-rating completeness only when star provenance is present.
- No Publication Lock, Stable ID, Data Bridge, route, sitemap, media or Header/Footer behavior is weakened.
- Acceptance status: candidate only. Rollback target: v0.9.8 (and accepted technical baseline remains v0.9.7 until owner PASS).

## 0.9.8 — H10.0 Full Hotel Dossier Data Contract (candidate)

- Fixes the Data Bridge contract so `long_caption` is stored independently from the 900–1800 word `editorial_long_tr` article.
- Removes the unsafe `brand` alias from Hotel Chain and adds a dedicated Brand field.
- Adds Display Name TR/EN/AR, License / Registration Number, Floor Count, Primary/Secondary Semantic Topics, Source Checked At and Source Ledger JSON.
- New dossier fields participate in Data Bridge mapping, Dry Run, export, snapshot and rollback.
- Room JSON accepts `occupancy` as an import compatibility alias for canonical `capacity`.
- Reference Point JSON now retains latitude/longitude and explicit `distance_method`; legacy `distance_km_straight_line` input normalizes safely.
- Front-end verified details can show Brand, Floor Count and License/Registration Number; Hotel schema may emit verified Entity Brand.
- H9E publication/indexation lock, Stable Hotel IDs, routes, sitemap and Header/Footer ownership are unchanged.
- Acceptance status: candidate only. Rollback target: v0.9.7.

## 0.9.7 — H9G Publication Readiness Visual Gate

- Required publication cells in Hotel Grid remain red until complete.
- Exact visual checks: Hotel Name, WP Status=Published, Official Name, Country, City, Verification=Verified, Last Verified At, Workflow=Published, Destinations, and at least one Media item.
- Added per-row Publish Ready badge with missing-field count and tooltip.
- Readiness colors update immediately after Hotel Grid autosave.
- Does not weaken or change the existing frontend public-ready gate or H9E Publication Lock.

## 0.9.6 — H9F Admin Recovery + Canonical Geography + Taxonomy Filtering

- Restores access to native Hotel Trash and adds a visible Trash button/count in Hotel Grid.
- Preserves Destination/Collection taxonomy filters when native WordPress admin links redirect into Hotel Grid.
- Adds Destination and Collection filter dropdowns to Hotel Grid.
- Adds canonical Country/City registry and autocomplete suggestions.
- Recognizes TR/EN/FA/AR aliases for Makkah/Madinah and other pilot cities.
- Known City can infer Country and automatically append canonical hierarchical Destination terms.
- Data Bridge canonicalizes City/Country before Dry Run/import and shows inferred Country in the plan.
- Hub and Harem-distance detection consume canonical city identity.
- Publication Lock and public SEO behavior are unchanged.

# Changelog

## 0.9.5 — H9E Publication Master Lock
- Adds an explicit default-LOCKED Hotel public indexation master switch.
- Final public routes remain 200/routable for owner QA while locked.
- Locked state forces public Hotel/Hub surfaces to `noindex, follow` and suppresses self-canonical.
- Hotel Intelligence sitemap provider emits zero pages while locked, removing Hotel URLs from the native sitemap index.
- Separates routable public eligibility from search-engine indexability.
- Adds **Hotel Intelligence → Launch Control** admin UI with an explicit enable checkbox.
- Preview surfaces remain permanently noindex and legacy routes remain retired.

## 0.9.4 — H9B Canonical / Indexation / Sitemap
- Freezes a dedicated `_sthi_public_slug` for public-ready Hotel Entities; Stable Hotel IDs remain the identity source.
- Public Hotel route uses only the frozen slug and fails closed on collisions.
- Activates self-canonical + `index, follow` only for exact public-ready Hotel URLs.
- Activates self-canonical + `index, follow` only for non-empty data-driven Hotel hubs. Empty Medine remains noindex and outside sitemap.
- Adds native WordPress custom Hotel Intelligence sitemap provider containing only canonical indexable hub/hotel URLs.
- Sitemap `lastmod` and HTTP `Last-Modified` derive from Hotel post modified timestamps.
- Query-string Hotel/Hub previews remain permanently noindex and canonical-absent.
- Native `/hotel-preview/` duplicate route remains blocked.
- Header/Footer ownership remains external and untouched.


## 0.9.3 — H9A Exact Public Hotel Route Resolver Fix
- Fixes `/otel/{slug}/` when the internal WordPress `post_name` differs from the current verified Hotel name/title.
- Candidate Hotel URLs now use exact normalized `_sthi_official_name` (fallback: post title) instead of blindly exposing the internal WP slug.
- Resolver uses exact equality only; duplicate exact candidate slugs fail closed to 404 rather than guessing a Hotel entity.
- Keeps native `post_name` resolution as a safe compatibility path when it already matches a public-ready Hotel.
- H9A remains `noindex, follow` and canonical-absent; no Stable Hotel ID, Hotel data or Program relation is mutated.
- Strengthens unresolved public-route 404 handling.


## 0.9.2 — H9A Controlled Public Route Foundation
- Claims the retired legacy archive path `/oteller/` as a new data-driven All Hotels candidate hub.
- Adds noindex runtime candidate routes `/mekke-otelleri/`, `/medine-otelleri/` and `/otel/{slug}/`.
- Public candidate Hotel routes resolve only published Hotel Entities that pass the existing `is_public_ready()` gate.
- Preserves the accepted stable query-string Hotel Preview route and keeps it noindex.
- Forces historical native `/hotel-preview/{slug}/` CPT URLs to 404 to prevent duplicate Hotel entity URLs.
- Keeps canonical tags absent and Hotel sitemap exclusion intact; H9B/H9C indexation remains locked.
- Does not modify or render the separately accepted Server Turizm Header & Footer plugin; normal `get_header()` / `get_footer()` remain the shell contract.
- Does not change Hotel IDs, Program IDs, H6B relations, Data Bridge, `/umre-1/`, gallery, map, sacred-distance or editorial content architecture.


## 0.8.0 — Hotel SEO / AI Quality Layer candidate
- Normalizes Hotel pilot `<head>` output so Porto/Core metadata cannot leave duplicate Hotel meta/OG/Twitter tags.
- Adds Hotel-specific title, meta description, OG and Twitter output with primary image semantics.
- Removes the incorrect homepage canonical from temporary noindex Hotel preview routes; final Hotel canonical remains intentionally unassigned until URL/migration policy is approved.
- Adds optional human-editable `seo_title_tr` and `meta_description_tr` fields to the Hotel Entity and Data Bridge.
- Adds intrinsic width/height to same-site hosting-folder images where server dimensions are available.
- Rebuilds Hotel JSON-LD around a stable Hotel entity `@id`, WebPage, BreadcrumbList, verified geo/address, positive verified amenities and primary ImageObject.
- `starRating` is emitted only when an explicit rating source URL and verification date exist.
- `sameAs`/contact schema values are emitted only from structured contacts carrying a verification date.
- Preserves v0.7.2 Preview/Post-ID routing, Header/Hero integration, Data Bridge safety, gallery/map/distance and Long Content contracts.

# 0.7.2
- H3 resolver hotfix: approved registered routes (current Umre hub and primary Contact) can resolve centrally without storing fragile URLs in Hotel captions.
- Known WordPress objects still must pass publish/password/noindex/public-viewability checks; no bypass for blocked targets.
- Future Mekke/Medine hub route fallback remains disabled until those hubs are actually public/approved.
- Preview/Post-ID/Header-Hero contracts unchanged.

# 0.7.1
- Fixed F-HOTEL-CONTENT-001 stable hub/contact link resolution discovered during runtime acceptance.
- Hub registry values now resolve via centralized URL/path → WordPress object → canonical URL logic instead of assuming a built-in `page` post type.
- Public/noindex/self-link safety gates remain unchanged.
- Preview/Post-ID routing, Header/Hero integration, Hotel IDs, media, map and gallery contracts are untouched.

# 0.7.0
- Added F-HOTEL-CONTENT-001 long editorial content engine.
- Added human-editable Turkish editorial summary/long content + source/verification metadata.
- Added Data Bridge import/export support for editorial fields and stable link intents.
- Added server-rendered editorial section with semantic H2/H3 content and clean empty-state behavior.
- Added stable internal-link resolver/tokens; Hotel/Program/Destination links remain inactive until valid public canonical targets exist.
- Preserved v0.6.10 Post-ID Preview lifecycle and Header/Hero integration.

# Changelog

## 0.6.10 — Header Contract Integration (built directly from v0.6.8)
- Built directly from the accepted v0.6.8 technical baseline; rejected v0.6.9 code is not used.
- Fixes the Hotel Preview header/Hero overlap at its source: the root Post-ID preview URL was being classified by Header/Footer v1.1.10 as `st-shell-front`, which intentionally gives `#main` zero header clearance.
- Normalizes Hotel Intelligence contexts to the existing `st-shell-internal` contract at late `body_class` priority, allowing Header/Footer v1.1.10 to own its responsive `--st-shell-height` and WordPress admin-bar behavior.
- Replaces broad Porto/DOM probing with direct integration against the accepted visible shell (`.st-site-header`, `.st-topbar`, `.st-brand-logo`).
- Removes JavaScript Hero-offset guessing; JS now measures only sticky Hotel navigation and the intentional hanging-logo safe area.
- Preview routing, Hotel IDs/data model, Data Bridge/import/rollback, map/distance engine, gallery architecture, conditional rendering and accepted luxury editorial design remain unchanged.

## 0.6.8 — Preview Layout & Conditional Content Fix
- Keeps the stable Post-ID preview architecture from v0.6.7.
- Adds dynamic hero clearance so Porto's fixed/absolute header cannot cover the hotel gallery.
- Replaces the rough WhatsApp glyph with a cleaner custom line icon.
- Filters empty room/reference/contact/360 records before public rendering.
- Hides the entire contact/360 section when neither has usable public data.
- Uses dynamic section/navigation labels: Contact only, 360 only, or both.
- Hides the Details section when no verified detail fields exist.

## 0.6.7 — Preview Recovery / Rewrite-Independent Pilot URL
- REJECTS the v0.6.6 routing experiment and rebuilds from the v0.6.5 visual/runtime baseline.
- Preview/View links for Hotel Intelligence now use a stable direct Post-ID pilot URL (`?sthi_hotel_preview=ID`) instead of depending on CPT rewrite rules or slug state.
- Draft, pending, private and published hotels remain previewable for authorized editors after any number of updates, media changes or status transitions.
- Published pilot records can still be viewed on the same noindex route; draft/private records remain protected from anonymous visitors.
- No slug locking and no rewrite-version bump are used in this recovery build.
- Existing `/hotel-preview/slug/` URLs are left untouched for compatibility, but WordPress Preview/View actions now prefer the stable route.

## 0.6.5
- Fixed mobile cinematic gallery so each slide occupies the full hero width; the next image no longer appears as an accidental clipped strip.
- Added operator-selected Primary Image support for WordPress Media and hosting-folder images. The selected image becomes first hero/gallery/schema image.
- Fixed mobile ScrollSpy auto-centering: active hotel nav links now scroll horizontally inside the nav only and can no longer move the document vertically back toward the top.
- Preserved v0.6.4 map/header stacking fix and all existing backend/Data Bridge behavior.


## 0.6.4
- Fixed Leaflet map layers/controls painting above the sticky hotel sub-navigation while scrolling.
- Added an isolated stacking context to the map surface and normalized Leaflet pane z-index values inside the hotel map only.
- Raised the hotel journey navigation modestly above the map while preserving Porto header precedence.
- Added a small scroll-margin adjustment for the location section.
- No changes to routing, sacred-distance calculation, data model, Data Bridge, gallery, backend or schema behavior.

## 0.6.3
- Final polish on the accepted v0.6.2 frontend architecture.
- Added dynamic horizontal safe-area protection for the sticky hotel journey navigation when Porto's hanging logo overlaps the left side.
- Protection is calculated from the actual runtime logo and navigation geometry and is only active while the hotel navigation is sticky.
- Tightened desktop section rhythm, story-column gap and surface padding to reduce unnecessary white space.
- Preserved hero/gallery, map/sacred-distance engine, backend, Data Bridge, preview routing, noindex policy and structured-data behavior.


## 0.6.2
- Rebuilt the top fold as a cinematic image-first hero: hotel identity, stars, location, sacred-distance context and CTAs now overlay the gallery instead of consuming a large white block above it.
- Added a controlled navy/gold gradient system for reliable text contrast over hotel photography.
- Moved breadcrumbs into the hero as a lightweight glass element on desktop and visually hides them on mobile while retaining semantic markup.
- Improved sticky header measurement to account for Porto sticky layers, WordPress Admin Bar and the hanging Server Turizm logo.
- Added a separate dynamic story-label safe offset so numbered editorial headings remain below the real header/logo footprint while scrolling.
- Preserved v0.6.1 backend, data bridge, map/distance engine, schema and gallery behavior.

## 0.6.1
- Visual polish pass on the accepted v0.6.0 frontend architecture.
- Fixed final CTA heading contrast against Porto/theme heading color rules.
- Reworked final CTA to a Server Turizm navy + gold treatment with explicit accessible text contrast.
- Reduced excessive vertical gaps between editorial hotel sections.
- Refined information surfaces with a softer ivory gradient and lighter shadow rhythm.
- No routing, data, map, gallery, distance-engine, import, schema, or backend architecture changes.

## 0.4.6
- Runtime layout guard for Porto's hanging header logo: breadcrumb/title gain a measured left safe-area instead of being covered.
- Hotel shell widened substantially on desktop to reduce unused white margins.
- Runtime detection of third-party fixed widgets on the right reserves only the space actually needed, preventing the information deck from sitting underneath the assistant/WhatsApp UI.
- Mobile bottom actions can reserve a small right-side safe area when a floating assistant bubble is present.
- Preserves the accepted v0.4.5 one-screen workspace, sacred-distance engine, map and data behavior.

# Changelog

## 0.4.4
- Keeps the owner-accepted v0.4 public front-end baseline unchanged.
- Keeps the v0.4.2/v0.4.3 unified spreadsheet, Dry Run, rollback and manual Media Folder URL workflow.
- Expands Hotel Data Bridge so a single TSV/CSV row can import structured hotel Details generated by ChatGPT.
- Adds import/export fields: `amenities_json`, `rooms_json`, `reference_points_json`, `contacts_json`, `scenes360_json`.
- Blank structured cells are no-op; `__CLEAR__` explicitly clears a structured group.
- Structured JSON is sanitized with the same rules used by the manual Details editor.
- Structured groups participate in Dry Run diff, conflict detection, snapshots and rollback.
- CSV export serializes complex groups as valid JSON cells for round-trip reuse.
- Contacts and 360 imports keep legacy v0.4 renderer fields synchronized so the accepted public page remains compatible.


## 0.4.3
- Keeps the owner-accepted v0.4 public hotel front-end baseline unchanged.
- Keeps v0.4.2 Hotel Data Bridge and manual Media Folder URL workflow intact.
- Adds a **Details** drawer directly inside the unified Hotels spreadsheet; no separate Add Hotel workflow is reintroduced.
- Adds standardized amenity statuses using `Yes / No / Unknown` so unverified facts are never treated as `No`.
- Adds structured repeaters for:
  - Room Types
  - verified Reference Points / Distances / walking time
  - multiple hotel Contacts
  - multiple 360° Scenes
- Adds a computed **Data Health** percentage to every hotel row.
- Maintains backwards compatibility with v0.4 flat Wi-Fi / Restaurant / Elevator / primary contact / 360° fields so the accepted front-end does not break.
- No takeover of legacy `/oteller/`; preview routing/indexation behavior remains unchanged.

## 0.4.2
- Unified spreadsheet + Hotel Data Bridge + manual Media Folder URL workflow.

## 0.4.5
- Rebuilt the accepted v0.4 front-end into a single-screen tabbed hotel workspace; hotel photos are rendered once only.
- Added mobile swipe navigation between data panels and swipeable/keyboard-friendly gallery controls.
- Added automatic Makkah/Madinah detection from city text with coordinate fallback.
- Added automatic straight-line distance to Masjid al-Haram or Masjid an-Nabawi.
- Added a Harem Distance column to the hotel spreadsheet.
- Replaced the non-working Google embed dependency with an on-demand interactive Leaflet/OpenStreetMap view.
- Interactive map draws hotel + sacred-site markers, a geographic connecting line and distance label.
- Google Maps route link remains available; automatic distance is explicitly labelled as straight-line, not walking distance.
- Leaflet loads only after the Map panel is opened; primary hotel content remains server-rendered.

## 0.6.0 — Full Front-end Rebuild from v0.4.6 baseline
- Rejected v0.4.8/v0.4.9 branches are not included.
- Rebuilt the public hotel UI from scratch while preserving v0.4.6 data/import/map logic.
- New luxury editorial layout with cinematic 5-image mosaic on desktop and native scroll-snap gallery on mobile.
- Hotel photos render in one visual gallery only; full collection remains available in the lightbox without a second gallery section.
- Replaced hidden tab panels with server-rendered, crawlable natural-scroll sections.
- Added sticky glass journey navigation, scrollspy, progress line and smooth anchor navigation.
- Added progressive reveal micro-interactions and subtle pointer parallax; reduced-motion users receive a static experience.
- Map now lazy-loads near the viewport and preserves the automatic Makkah/Madinah sacred-distance line.
- Removed the plugin mobile fixed action bar to avoid collisions with the site's existing assistant/WhatsApp widgets.
- Preserved noindex pilot behavior until the controlled /oteller/ migration.

## 0.9.1 — H7 Mobile Overflow Patch
- Keeps all v0.9.0 H7 hub/data/publication-lock behavior unchanged.
- Fixes the measured 10px mobile horizontal overflow on H7 routes caused by the accepted Header/Footer off-canvas menu closed transform using `translateX(103%)`.
- Applies an H7-route-only CSS override to normalize the closed menu transform to `translateX(100%)`.
- Does not edit or replace the Header/Footer plugin and does not change the menu open state.
- No Hotel data, IDs, H6B relations, `/umre-1/`, legacy `/oteller/`, canonical or indexation policy changes.

## 0.9.0 — H7 Candidate
- Adds data-driven Mekke / Medine Hotel Hubs.
- Adds editor-only noindex H7 Preview surfaces.
- Hub membership derives from structured Hotel city data with explicit city alias registry; no Hotel-name guessing.
- Candidate Hotels must pass the existing `STHI_Frontend::is_public_ready()` gate.
- Public Hotel links remain locked behind `sthi_hotel_link_target_is_indexable` + approved `sthi_hotel_canonical_url` (H9).
- Adds `Hubs — H7` admin diagnostics and candidate/public-eligible counts.
- Adds dynamic `[sthi_hotel_hub id="mekke"]` / `[sthi_hotel_hub id="medine"]` renderer for later approved canonical page integration.
- Does not touch legacy `/oteller/`, `/umre-1/`, Hotel IDs, Program IDs, or H6B relationships.

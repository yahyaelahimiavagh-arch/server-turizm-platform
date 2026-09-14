# Server Turizm Hotel Intelligence — v0.9.11

Technical baseline: **accepted Golden v0.9.10**. v0.9.11 is the limited Gate 4.6 Hotel knowledge-graph and factual-summary candidate documented in `CHANGELOG.md`.

## v0.7.0 — F-HOTEL-CONTENT-001
- Adds `editorial_summary_tr` and `editorial_long_tr` to the Hotel Entity/Data Bridge workflow.
- Adds content provenance fields: source mode, verified date/by and source notes.
- Adds stable `link_intents_json` plus inline stable link tokens.
- Internal URLs are resolved at render time through a centralized canonical resolver.
- Draft/noindex/non-public Hotel targets do not receive automatic links. Program and Destination targets stay inactive until their stable relationship resolvers exist.
- Long content is server-rendered, H1 inside editorial content is downgraded to H2, and the whole section disappears when empty.
- Editors can preview unverified content; anonymous/public rendering requires content verification.
- Data Bridge preserves blank-cell=no-op, `__CLEAR__`, Dry Run, CREATE/UPDATE/UNCHANGED/CONFLICT, audit and rollback semantics.
- Accepted v0.6.10 Preview/Post-ID lifecycle, Header/Hero integration, gallery, map/distance and conditional rendering are intentionally untouched.

## Stable link examples
Inline editorial token (no URL stored in the caption):

```text
{{sthi-link:hub:umre|güncel Umre programları}}
{{sthi-link:page:1663|Server Turizm iletişim}}
{{sthi-link:hotel:STH-000123|ilgili otel}}
```

`hotel:` remains inactive during the noindex pilot. `program:` and `destination:` are future-ready resolver hooks and do not fabricate URLs.

## v0.7.0 acceptance checks
1. Existing v0.6.10 Hotel Preview lifecycle still resolves the same Post ID / Hotel ID.
2. Empty editorial fields render no editorial section.
3. Saved/verified editorial content appears in server HTML.
4. H2/H3 and paragraphs remain semantic; no second H1 is created.
5. Data Bridge Dry Run can CREATE/UPDATE/UNCHANGED editorial fields without duplicate Hotels.
6. `link_intents_json` imports/exports as JSON and rollback restores previous content.
7. `hub:umre` / `contact:primary` resolve only when the target is a valid published public page.
8. Hotel/Program/Destination automatic links stay suppressed until a valid public canonical resolver exists.

## Front-end direction
- Luxury travel editorial system tailored to Server Turizm navy/gold identity.
- Cinematic image-first hero gallery with no duplicated lower gallery.
- Natural page scrolling; no artificial viewport-height panels or internal content scrollbars.
- All primary hotel content remains server-rendered and crawlable.
- Sticky section navigation is progressive enhancement; anchors work without JavaScript.
- Interactive map and Leaflet runtime load only when the map approaches the viewport.
- Automatic Makkah/Madinah sacred-site detection and distance line from the v0.4.6 engine are preserved.
- Mobile uses native horizontal scroll-snap for the cinematic gallery and a compact sticky section rail.

## Safety
The temporary `/hotel-preview/` hotel URLs remain `noindex` and excluded from WordPress sitemaps until the controlled `/oteller/` migration is approved.


## v0.6.3 final frontend polish
- Preserves the accepted v0.6.2 cinematic/editorial architecture.
- Adds a runtime horizontal safe-area to the sticky hotel navigation only while Porto's hanging logo overlaps it.
- Keeps the rail visually centered when it is not sticky.
- Tightens desktop editorial spacing and surface padding without changing content, map, gallery, import, routing or schema behavior.


## v0.6.4 map/header stacking patch
- Preserves the accepted v0.6.3 visual architecture.
- Constrains Leaflet panes and controls to an isolated map stacking context so the map cannot paint over the sticky hotel navigation.
- Keeps the hotel journey navigation above the map without raising it over the main Porto header.
- Adds a small location anchor offset for cleaner navigation into the map section.
- No routing, data, sacred-distance, gallery, backend, import or schema changes.


## v0.6.5
- Set any gallery item as the Primary Image in Hotel Photos & Gallery.
- Mobile hero uses full-width snap slides without exposing a partial neighboring image.
- ScrollSpy keeps the active tab visible using horizontal-only scrolling, preventing document jump-back on mobile.

## v0.8.0 SEO / AI Quality Layer
- Hotel pilot previews stay `noindex,follow` and outside the sitemap.
- Temporary preview URLs intentionally have no canonical until the final Hotel URL/migration contract is approved; they must never canonicalize to the homepage.
- Hotel-specific search/social metadata is normalized to one title, one description, one OG/Twitter set.
- Optional SEO title/meta fields are operator-editable and Data Bridge compatible.
- Hotel JSON-LD uses stable Hotel Entity IDs and conservative verified facts only.
- Same-site folder images receive intrinsic dimensions when resolvable from the server filesystem.


## v0.9.0 H7 Data-driven Hotel Hubs
- Mekke / Medine hub candidates derive from verified/public-ready Hotel Entities.
- Editor-only noindex previews are available under Hotel Intelligence → Hubs — H7.
- H9 public canonical/indexation remains locked.
- Legacy `/oteller/` remains untouched.

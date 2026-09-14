# STTI v0.5.3 — Visual Polish + Global Route Map Runtime Gate

## Scope

This build extends the private Customer Preview only.

Changes:
- removes/collapses empty theme header spacer bands where safely detectable
- keeps the live WordPress header/footer
- enlarges and color-codes Destination / Date / Duration / Price summary cards
- adds a worldwide interactive Leaflet route map
- uses OpenStreetMap tiles
- resolves canonical route stops via a rate-limited Nominatim client pilot
- caches resolved coordinates in browser localStorage
- auto-fits map bounds for single-country, multi-country and worldwide routes
- draws the route in canonical stop order
- keeps the horizontal route timeline below the map
- never invents coordinates; unresolved stops remain unresolved and are reported in the private preview
- no canonical Tour write is introduced

## Public locks

Remain HARD OFF:
- Public Renderer
- Public Routes
- Sitemap
- Indexation
- Homepage Adapter
- STTI Schema output
- Canonical/robots writes

`frontend_writes = 0`

## Gate A — Installation Isolation

After Replace Current Version, do NOT open Customer Preview yet.

Download:
Tour Intelligence → Import / Export → Tüm JSON Evidence İndir

Expected:
- plugin_version = 0.5.3
- mode = private_customer_experience_global_route_map_pilot
- candidate_count = 1
- STT-000001 checksum remains exactly:
  d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348
- updated_at remains 2026-09-11 09:47:41
- latest audit remains ID 8
- no ID 9
- frontend_writes = 0
- all public release locks remain false

Customer Experience evidence should include:
- route_map_engine = leaflet_global_route
- route_map_tiles = openstreetmap
- route_geocoder = nominatim_client_pilot
- route_coordinate_cache = browser_localstorage_pilot
- route_unresolved_policy = never_invent_admin_warning
- route_auto_fit_bounds = true

## Gate B — Visual / Map Runtime

Open Customer Preview.

Expected:
- no empty white spacer above the active header/navigation stack
- summary information is larger and color-coded
- route section shows an interactive world map
- Tehran → Kashan → Isfahan → Yazd → Shiraz resolve automatically for STT-000001
- numbered markers follow canonical order
- gold route polyline connects resolved stops
- map auto-fits the route
- horizontal timeline remains below map
- Customer Preview remains admin-only/noindex

If a geocoder result cannot be verified:
- do not guess a coordinate
- show a private-preview warning
- keep the textual/timeline route

## Pilot note

The browser-localStorage coordinate cache is intentionally a no-server-write pilot.
A later controlled gate may promote verified coordinates into canonical Tour Intelligence only after explicit review.
